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
$query = "SELECT u.*, GROUP_CONCAT(s.skill_name) as skills FROM users_info u 
          LEFT JOIN user_skills us ON u.id = us.user_id 
          LEFT JOIN skills s ON us.skill_id = s.id 
          GROUP BY u.id";
if ($sort_by && $sort_order) {
    $query .= " ORDER BY u.$sort_by $sort_order";
}
$query .= " LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

// Check if there are results
if ($result === false) {
    die("Error fetching data: " . $conn->error);
}

// Determine sort direction for each column
$first_name_sort_order = ($sort_by === 'first_name' && $sort_order === 'ASC') ? 'DESC' : 'ASC';
$last_name_sort_order = ($sort_by === 'last_name' && $sort_order === 'ASC') ? 'DESC' : 'ASC';
$email_sort_order = ($sort_by === 'email' && $sort_order === 'ASC') ? 'DESC' : 'ASC';

$skillsquery = "SELECT * FROM skills ORDER BY skill_name";
$skills = $conn->query($skillsquery);

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
            <div style="width: 280px;">
                <input type="search" class="form-control search-bar" id="search-bar" placeholder="Search by name or email ...">
            </div>

            <div style="width: 180px;">
                <select class="form-select" id="skill-filter" name="skill">
                    <option value="all">All Skills</option>
                    <?php
                    if ($skills && $skills->num_rows > 0) {
                        while ($row = $skills->fetch_assoc()) {
                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['skill_name']) . '</option>';
                        }
                    } else {
                        echo '<option disabled>No skills found</option>';
                    }
                    ?>
                </select>
            </div>

            <div>
                <button class="btn btn-primary" onclick="performSearch()">Search</button>
            </div>
        </div>

        <!-- Right side: delete button -->
        <div>
            <button id="delete_selected" class="btn btn-danger" disabled>Delete Selected</button>
        </div>
    </div>

    <!-- Main table container -->
    <div class="table-container">
        <!-- Original table (shown by default) -->
        <div id="original-table">
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
                        <th>Skills</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="user-checkbox" data-user-id="<?php echo $row['id']; ?>" data-user-first-name="<?php echo htmlspecialchars($row['first_name']); ?>">
                            </td>
                            <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo $row['skills'] ? htmlspecialchars($row['skills']) : 'No skills'; ?></td>
                            <td>
                                <i data-user-id="<?php echo $row['id']; ?>" class="delete_customer text-danger fas fa-trash fa-sm" style="cursor: pointer;"></i>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Search results will appear here -->
        <div id="search-results" class="mt-4" style="display: none;"></div>
    </div>

    <!-- Pagination controls (for original table) -->
    <nav aria-label="Page navigation" id="original-pagination">
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
// Global variables to track current search state
let currentSearchQuery = '';
let currentSkillFilter = 'all';

function performSearch(page = 1) {
    var searchText = $('#search-bar').val().trim();
    var skillId = $('#skill-filter').val();
    
    // Update global state
    currentSearchQuery = searchText;
    currentSkillFilter = skillId;
    
    // Clear previous messages
    $('#responseMessage').html('');
    
    // If both search and skill filter are empty/default, show original table
    if (searchText === '' && skillId === 'all') {
        showOriginalTable();
        return;
    }
    
    // Show loading message
    showInfoMessage('Searching...');
    
    // Perform AJAX search
    $.ajax({
        url: 'customer_ajax.php',
        method: 'POST',
        data: {
            action: 'search_users',
            query: searchText,
            skill_id: skillId,
            page: page
        },
        success: function(response) {
            if (response.includes('No results found') || response.includes('alert alert-info')) {
                showErrorMessage('No matching users found for your search criteria.');
                $('#search-results').html(response).show();
                $('#original-table').hide();
                $('#original-pagination').hide();
            } else if (response.includes('Please enter a search term')) {
                showErrorMessage('Please enter a search term or select a skill.');
                showOriginalTable();
            } else if (response.includes('Error executing search')) {
                showErrorMessage('Error executing search. Please try again.');
                showOriginalTable();
            } else {
                // Show search results and hide original table
                $('#search-results').html(response).show();
                $('#original-table').hide();
                $('#original-pagination').hide();
                $('#responseMessage').html('');
                
                // Reinitialize checkbox functionality for search results
                initializeCheckboxFunctionality();
            }
        },
        error: function() {
            showErrorMessage('An error occurred while searching. Please try again.');
            showOriginalTable();
        }
    });
}

function searchWithPagination(page) {
    performSearch(page);
}

function showOriginalTable() {
    $('#original-table').show();
    $('#search-results').hide();
    $('#original-pagination').show();
    $('#responseMessage').html('');
}

function clearSearch() {
    $('#search-bar').val('');
    $('#skill-filter').val('all');
    currentSearchQuery = '';
    currentSkillFilter = 'all';
    showOriginalTable();
}

function initializeCheckboxFunctionality() {
    // Handle select all for search results
    $(document).off('click', '#search_select_all').on('click', '#search_select_all', function() {
        $('.user-checkbox').prop('checked', this.checked);
        toggleDeleteButton();
    });
    
    // Handle individual checkboxes
    $(document).off('change', '.user-checkbox').on('change', '.user-checkbox', function() {
        var totalCheckboxes = $('.user-checkbox').length;
        var checkedCheckboxes = $('.user-checkbox:checked').length;
        
        $('#select_all, #search_select_all').prop('checked', totalCheckboxes === checkedCheckboxes);
        toggleDeleteButton();
    });
}

function toggleDeleteButton() {
    if ($('.user-checkbox:checked').length > 0) {
        $('#delete_selected').prop('disabled', false);
    } else {
        $('#delete_selected').prop('disabled', true);
    }
}

function showSuccessMessage(message) {
    $('#responseMessage').html('<div class="alert alert-success alert-dismissible fade show" role="alert">' + 
        message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
}

function showErrorMessage(message) {
    $('#responseMessage').html('<div class="alert alert-danger alert-dismissible fade show" role="alert">' + 
        message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
}

function showInfoMessage(message) {
    $('#responseMessage').html('<div class="alert alert-info alert-dismissible fade show" role="alert">' + 
        message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
}

$(document).ready(function() {
    // Search on Enter key
    $('#search-bar').on('keypress', function(e) {
        if (e.which === 13) {
            performSearch();
        }
    });
    
    // Search when skill filter changes
    $('#skill-filter').on('change', function() {
        performSearch();
    });
    
    // Clear search when input is empty and no skill selected
    $('#search-bar').on('input', function() {
        if ($(this).val().trim() === '' && $('#skill-filter').val() === 'all') {
            clearSearch();
        }
    });

    // Initialize checkbox functionality for original table
    $('#select_all').on('click', function() {
        $('.user-checkbox').prop('checked', this.checked);
        toggleDeleteButton();
    });

    $(document).on('change', '.user-checkbox', function() {
        var totalCheckboxes = $('.user-checkbox').length;
        var checkedCheckboxes = $('.user-checkbox:checked').length;
        
        $('#select_all, #search_select_all').prop('checked', totalCheckboxes === checkedCheckboxes);
        toggleDeleteButton();
    });

    // Delete single customer
    $(document).on('click', '.delete_customer', function(event) {
        event.preventDefault();
        if (confirm('Sure you want to delete the customer?')) {
            var user_id = $(this).attr("data-user-id");

            $.ajax({
                url: 'customer_ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'delete_user',
                    user_id: user_id
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessMessage(response.message + ' Refreshing...');
                        setTimeout(function() {
                            // Refresh current view (search or original)
                            if ($('#search-results').is(':visible')) {
                                performSearch();
                            } else {
                                location.reload();
                            }
                        }, 1500);
                    } else {
                        showErrorMessage(response.error);
                    }
                },
                error: function() {
                    showErrorMessage('Error deleting user. Please try again.');
                }
            });
        }
    });

    // Delete selected customers
    $('#delete_selected').on('click', function() {
        var checkedCount = $('.user-checkbox:checked').length;
        if (checkedCount === 0) {
            showErrorMessage('Please select users to delete.');
            return;
        }
        
        if (confirm('Are you sure you want to delete ' + checkedCount + ' selected customer(s)?')) {
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
                dataType: 'json',
                data: {
                    action: 'delete_users',
                    users: users
                },
                success: function(response) {
                    if (response.success) {
                        showSuccessMessage(response.message + ' Refreshing...');
                        setTimeout(function() {
                            // Refresh current view (search or original)
                            if ($('#search-results').is(':visible')) {
                                performSearch();
                            } else {
                                location.reload();
                            }
                        }, 1500);
                    } else {
                        var errorMsg = Array.isArray(response.error) ? response.error.join('<br>') : response.error;
                        showErrorMessage('Some errors occurred:<br>' + errorMsg);
                    }
                },
                error: function() {
                    showErrorMessage('Error deleting users. Please try again.');
                }
            });
        }
    });
});
</script>

<?php
include_once(__DIR__ . '/footer.php');
?>