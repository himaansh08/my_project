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

// Get total number of records
$total_query = "SELECT COUNT(*) FROM users_info";
$total_result = $conn->query($total_query);
$total_rows = $total_result->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);

// Fetch paginated results with sorting
$query = "SELECT * FROM users_info";
if ($sort_by && $sort_order) {
    $query .= " ORDER BY $sort_by $sort_order";
}
$query .= " LIMIT $limit OFFSET $offset";
// echo $query;die;

$result = $conn->query($query);

// Check if there are results
if ($result === false) {
    die("Error fetching data: " . $conn->error);
}

// Determine sort direction for each column
$first_name_sort_order = ($sort_by === 'first_name' && $sort_order === 'ASC') ? 'DESC' : 'ASC';
$last_name_sort_order = ($sort_by === 'last_name' && $sort_order === 'ASC') ? 'DESC' : 'ASC';
$email_sort_order = ($sort_by === 'email' && $sort_order === 'ASC') ? 'DESC' : 'ASC';

$skillsquery="SELECT * FROM skills";
$skills=$conn->query($skillsquery);
// if ($skills->num_rows > 0) {
//     while ($row = $skills->fetch_assoc()) {
//         echo "ID: " . $row["id"] . " - Skill: " . $row["skill_name"] . "<br>";
//     }
// } else {
//     echo "No skills found.";
// }

// Base URL without sorting and pagination parameters
$base_url = strtok($_SERVER["REQUEST_URI"], '?');
?>

<div class="container mt-4">
    <h1 class="mb-4">Customers</h1>
    <div id="responseMessage" class="mt-3"></div>

    <!-- Search bar, dropdown, and buttons -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <!-- Left side: search input + dropdown + search button -->
        <div class="d-flex align-items-center gap-2">
            <div style="width: 250px;">
                <input type="search" class="form-control" placeholder="Search by name or email ...">
            </div>

            <div style="width: 180px;">
                <select class="form-select">
                    <option value=""><?php  $skills[]  ?></option>
                    <option value="html">HTML</option>
                    
                </select>
            </div>

            <div>
                <button class="btn btn-primary">Search</button>
            </div>
        </div>

        <!-- Right side: delete button -->
        <div>
            <button id="delete_selected" class="btn btn-danger" disabled>Delete</button>
        </div>

    </div>



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
                        Action
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td>
                            <input type="checkbox" class="user-checkbox" data-user-id="<?php echo $row['id']; ?>" data-user-first-name="<?php echo $row['first_name']; ?>">
                        </td>
                        <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td>
                            <i data-user-id=<?php echo $row['id'] ?> id="delete_customer" class=" text-danger fas fa-trash fa-sm"></i>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination controls -->
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
                // var first_names = [];
                $('.user-checkbox:checked').each(function() {
                    users.push({
                        user_id: $(this).data('user-id'),
                        first_name: $(this).data('user-first-name')
                    });
                });
                // console.log('first_name');

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
                    error: function() {
                        $('#responseMessage').html('<div class="alert alert-danger">There was an error processing your request. Please try again later.</div>');
                    }
                });
            }
        });
    });
</script>
<?php
include_once(__DIR__ . '/footer.php');
?>