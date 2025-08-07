<?php
$PAGE_TITLE = "Customers";
include_once(__DIR__ . '/header.php');

// Pagination settings
$limit = 5; // Number of entries to show per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$_GET['page'] = $page;
$offset = ($page - 1) * $limit;

// Sorting settings 
if (!isset($_GET['sort_by']) || (isset($_GET['sort_by']) && !in_array($_GET['sort_by'], array('first_name', 'last_name', 'email')))) {
    $_GET['sort_by'] = "first_name";
}
$sort_by = $_GET['sort_by'];

if (!isset($_GET['sort_order']) || (isset($_GET['sort_order']) && !in_array($_GET['sort_order'], array('ASC', 'DESC')))) {
    $_GET['sort_order'] = "ASC";
}
$sort_order = $_GET['sort_order'];

// Search and filter parameters
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_skill = isset($_GET['skill']) ? (int)$_GET['skill'] : '';

// Build the WHERE clause for search and filter
$where_conditions = [];
$params = [];
$param_types = '';

// Search functionality
if (!empty($search_term)) {
    $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
    $search_param = '%' . $search_term . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= 'sss';
}

// Skill filter functionality
if (!empty($selected_skill)) {
    $where_conditions[] = "u.id IN (SELECT user_id FROM user_skills WHERE skill_id = ?)";
    $params[] = $selected_skill;
    $param_types .= 'i';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
}

// Get total number of records with filters applied
$total_query = "SELECT COUNT(DISTINCT u.id) FROM users_info u" . $where_clause;
if (!empty($params)) {
    $total_stmt = $conn->prepare($total_query);
    if (!empty($param_types)) {
        $total_stmt->bind_param($param_types, ...$params);
    }
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_rows = $total_result->fetch_row()[0];
    $total_stmt->close();
} else {
    $total_result = $conn->query($total_query);
    $total_rows = $total_result->fetch_row()[0];
}

$total_pages = ceil($total_rows / $limit);

// Fetch paginated results with sorting, search, and filter
$query = "SELECT u.*, GROUP_CONCAT(us.skill_name SEPARATOR ', ') as user_skills 
          FROM users_info u 
          LEFT JOIN user_skills us ON u.id = us.user_id" . $where_clause . "
          GROUP BY u.id";
if ($sort_by && $sort_order) {
    $query .= " ORDER BY u.$sort_by $sort_order";
}
$query .= " LIMIT $limit OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($query);
    if (!empty($param_types)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

// Check if there are results
if ($result === false) {
    die("Error fetching data: " . $conn->error);
}

// Determine sort direction for each column
$first_name_sort_order = ($sort_by === 'first_name' && $sort_order === 'ASC') ? 'DESC' : 'ASC';
$last_name_sort_order = ($sort_by === 'last_name' && $sort_order === 'ASC') ? 'DESC' : 'ASC';
$email_sort_order = ($sort_by === 'email' && $sort_order === 'ASC') ? 'DESC' : 'ASC';

$skillsquery = "SELECT * FROM skills";
$skills = $conn->query($skillsquery);

// Base URL without sorting and pagination parameters
$base_url = strtok($_SERVER["REQUEST_URI"], '?');
?>

<div class="container mt-4">
    <h1 class="mb-4">Customers</h1>
    <div id="responseMessage" class="mt-3"></div>

    <!-- Search and Filter Form -->
    <form method="GET" class="mb-3">
        <!-- Preserve current sorting parameters -->
        <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sort_by); ?>">
        <input type="hidden" name="sort_order" value="<?php echo htmlspecialchars($sort_order); ?>">
        
        <div class="d-flex justify-content-between align-items-center">
            <!-- Left side: search input + dropdown + search button -->
            <div class="d-flex align-items-center gap-2">
                <div style="width: 250px;">
                    <input type="search" name="search" class="form-control" 
                           placeholder="Search by name or email ..." 
                           value="<?php echo htmlspecialchars($search_term); ?>">
                </div>

                <div style="width: 180px;">
                    <select class="form-select" name="skill">
                        <option value="">Select Skill</option>
                        <?php
                        if ($skills && $skills->num_rows > 0) {
                            while ($row = $skills->fetch_assoc()) {
                                $selected = ($selected_skill == $row['id']) ? 'selected' : '';
                                echo '<option value="' . $row['id'] . '" ' . $selected . '>' . htmlspecialchars($row['skill_name']) . '</option>';
                            }
                        } else {
                            echo '<option disabled>No skills found</option>';
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
                
                <?php if (!empty($search_term) || !empty($selected_skill)) { ?>
                <div>
                    <a href="<?php echo $base_url; ?>?sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>" 
                       class="btn btn-secondary">Clear</a>
                </div>
                <?php } ?>
            </div>

            <!-- Right side: delete button -->
            <div>
                <button type="button" id="delete_selected" class="btn btn-danger" disabled>Delete</button>
            </div>
        </div>
    </form>

    <?php if (!empty($search_term) || !empty($selected_skill)) { ?>
        <div class="alert alert-info">
            <strong>Search Results:</strong> 
            <?php 
            echo "Found $total_rows customer(s)";
            if (!empty($search_term)) {
                echo " matching \"" . htmlspecialchars($search_term) . "\"";
            }
            if (!empty($selected_skill)) {
                // Get skill name for display
                $skill_name_query = "SELECT skill_name FROM skills WHERE id = ?";
                $skill_stmt = $conn->prepare($skill_name_query);
                $skill_stmt->bind_param('i', $selected_skill);
                $skill_stmt->execute();
                $skill_result = $skill_stmt->get_result();
                if ($skill_row = $skill_result->fetch_assoc()) {
                    echo (!empty($search_term) ? " and" : "") . " with skill \"" . htmlspecialchars($skill_row['skill_name']) . "\"";
                }
                $skill_stmt->close();
            }
            ?>
        </div>
    <?php } ?>

    <div class="table-container">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>
                        <input type="checkbox" id="select_all">
                    </th>
                    <th>
                        <?php
                        $temp_get = $_GET;
                        $temp_get['sort_by'] = "first_name";
                        $temp_get['sort_order'] = $first_name_sort_order;
                        ?>
                        <a href="?<?php echo http_build_query($temp_get); ?>" class="text-decoration-none">
                            First Name
                            <?php if ($sort_by === 'first_name') { ?>
                                <i class="fa <?php echo $sort_order === 'ASC' ? 'fa-sort-asc' : 'fa-sort-desc'; ?>"></i>
                            <?php } else { ?>
                                <i class="fa fa-sort"></i>
                            <?php } ?>
                        </a>
                    </th>
                    <th>
                        <?php
                        $temp_get = $_GET;
                        $temp_get['sort_by'] = "last_name";
                        $temp_get['sort_order'] = $last_name_sort_order;
                        ?>
                        <a href="?<?php echo http_build_query($temp_get); ?>" class="text-decoration-none">
                            Last Name
                            <?php if ($sort_by === 'last_name') : ?>
                                <i class="fa <?php echo $sort_order === 'ASC' ? 'fa-sort-asc' : 'fa-sort-desc'; ?>"></i>
                            <?php else : ?>
                                <i class="fa fa-sort"></i>
                            <?php endif; ?>
                        </a>
                    </th>
                    <th>
                        <?php
                        $temp_get = $_GET;
                        $temp_get['sort_by'] = "email";
                        $temp_get['sort_order'] = $email_sort_order;
                        ?>
                        <a href="?<?php echo http_build_query($temp_get); ?>" class="text-decoration-none">
                            Email
                            <?php if ($sort_by === 'email') { ?>
                                <i class="fa <?php echo $sort_order === 'ASC' ? 'fa-sort-asc' : 'fa-sort-desc'; ?>"></i>
                            <?php  } else {   ?>
                                <i class="fa fa-sort"></i>
                            <?php } ?>
                        </a>
                    </th>
                    <th>
                        Skills
                    </th>
                    <th>
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="user-checkbox" data-user-id="<?php echo $row['id']; ?>" data-user-first-name="<?php echo htmlspecialchars($row['first_name']); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <?php 
                                if (!empty($row['user_skills'])) {
                                    $skills_array = explode(', ', $row['user_skills']);
                                    foreach ($skills_array as $skill) {
                                        echo '<span class="badge bg-primary me-1">' . htmlspecialchars($skill) . '</span>';
                                    }
                                } else {
                                    echo '<span class="text-muted">No skills</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <i data-user-id="<?php echo $row['id']; ?>" id="delete_customer" class="text-danger fas fa-trash fa-sm" style="cursor: pointer;"></i>
                            </td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="6" class="text-center">
                            <?php 
                            if (!empty($search_term) || !empty($selected_skill)) {
                                echo "No customers found matching your search criteria.";
                            } else {
                                echo "No customers found.";
                            }
                            ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination controls -->
    <?php if ($total_pages > 1) { ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-start">
            <?php if ($page > 1) { ?>
                <li class="page-item">
                    <?php
                    $temp_get = $_GET;
                    $temp_get['page'] = $temp_get['page'] - 1;
                    ?>
                    <a class="page-link" href="?<?php echo http_build_query($temp_get); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                        <span class="sr-only">Previous</span>
                    </a>
                </li>
            <?php } ?>

            <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                <?php
                $temp_get = $_GET;
                $temp_get['page'] = $i;
                ?>
                <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                    <a class="page-link" href="?<?php echo http_build_query($temp_get); ?>"><?php echo $i; ?></a>
                </li>
            <?php } ?>

            <?php if ($page < $total_pages) { ?>
                <?php
                $temp_get = $_GET;
                $temp_get['page'] = $page + 1;
                ?>
                <li class="page-item">
                    <a class="page-link" href="?<?php echo http_build_query($temp_get); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                        <span class="sr-only">Next</span>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </nav>
    <?php } ?>
</div>

<script>
    $(document).ready(function() {
        $(document).on('click', '#delete_customer', function(event) {
            event.preventDefault(); // Prevent the default action of the link                
            if (confirm('Sure you want to delete the customer?')) {
                var user_id = $(this).attr("data-user-id");

                $.ajax({
                    url: 'customer_ajax.php',
                    type: 'POST',
                    data: {
                        action: 'delete_user',
                        user_id: user_id
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#responseMessage').html('<div class="alert alert-success"> ' + response.message + ' Redirecting...</div>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $('#responseMessage').html('<div class="alert alert-danger">' + response.error + '</div>');
                        }
                    },
                    error: function() {
                        $('#responseMessage').html('<div class="alert alert-danger">There was an error processing your request. Please try again later.</div>');
                    }
                });
            }
        });

        $('#select_all').on('click', function() {
            $('.user-checkbox').prop('checked', this.checked);
            toggleDeleteButton();
        });

        // Toggle delete button based on checkbox state
        $('.user-checkbox').on('change', function() {
            if (!this.checked) {
                $('#select_all').prop('checked', false);
            }
            toggleDeleteButton();
        });

        // Function to toggle the Delete button
        function toggleDeleteButton() {
            if ($('.user-checkbox:checked').length > 0) {
                $('#delete_selected').prop('disabled', false);
            } else {
                $('#delete_selected').prop('disabled', true);
            }
        }

        // Handle delete button click
        $('#delete_selected').on('click', function() {
            if (confirm('Are you sure you want to delete the selected customers?')) {
                var users = [];
                $('.user-checkbox:checked').each(function() {
                    users.push({
                        user_id: $(this).data('user-id'),
                        first_name: $(this).data('user-first-name')
                    });
                });
                

                $.ajax({
                    url: 'customer_ajax.php',
                    type: 'POST',
                    data: {
                        action: 'delete_users',
                        users: users
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#responseMessage').html('<div class="alert alert-success"> ' + response.message + ' Redirecting...</div>');
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $('#responseMessage').html('<div class="alert alert-danger">' + response.error + '</div>');
                        }
                    },
                    
                });
            }
        });
    });
</script>
<?php
// Close prepared statements if they were used
if (!empty($params) && isset($stmt)) {
    $stmt->close();
}
include_once(__DIR__ . '/footer.php');
?>