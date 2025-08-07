<?php
$PAGE_TITLE = "Customers";
include_once(__DIR__ . '/header.php');

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); // Ensure page is at least 1

// Sorting parameters 
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'first_name';
$order = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Validate sorting parameters to prevent SQL injection
$allowed_columns = ['first_name', 'last_name', 'email'];
$allowed_orders = ['ASC', 'DESC'];

if (!in_array($sort_by, $allowed_columns)) {
    $sort_by = 'first_name';
}
if (!in_array($order, $allowed_orders)) {
    $order = 'ASC';
}

// Calculate offset
$offset = ($current_page - 1) * $records_per_page;

// Get total number of records
$count_query = "SELECT COUNT(*) as total FROM users_info";
$count_result = $conn->query($count_query);
if ($count_result === false) {
    die("Error fetching count: " . $conn->error);
}
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Fetch records with pagination and sorting
$query = "SELECT * FROM users_info ORDER BY $sort_by $order LIMIT $records_per_page OFFSET $offset";
$result = $conn->query($query);

// Check if there are results
if ($result === false) {
    die("Error fetching data: " . $conn->error);
}

// Function to generate sort URL
function getSortUrl($column, $current_sort, $current_order, $current_page): string {
    $new_order = ($current_sort === $column && $current_order === 'ASC') ? 'DESC' : 'ASC';
    return '?' . http_build_query([
        'sort' => $column,
        'order' => $new_order,
        'page' => $current_page
    ]);
}

// Function to get sort icon
function getSortIcon($column, $current_sort, $current_order) {
    if ($current_sort === $column) {
        return $current_order === 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>';
    }
    return '<i class="fas fa-sort"></i>';
}
?> 

    <div class="container mt-4">
        <h1 class="mb-4">Customers</h1>
        
        <!-- Display pagination info -->
        <div class="mb-3">
            <p class="text-muted">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> 
                of <?php echo $total_records; ?> customers
            </p>
        </div>
        
        <div class="table-container">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>
                        
                            <input type="checkbox" id="select-all">
                        </th>
                        <th>
                            <a href="<?php echo getSortUrl('first_name', $sort_by, $order, $current_page); ?>" class="text-decoration-none text-dark">
                                First Name <?php echo getSortIcon('first_name', $sort_by, $order); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo getSortUrl('last_name', $sort_by, $order, $current_page); ?>" class="text-decoration-none text-dark">
                                Last Name <?php echo getSortIcon('last_name', $sort_by, $order); ?>
                            </a>
                        </th>
                        <th>
                            <a href="<?php echo getSortUrl('email', $sort_by, $order, $current_page); ?>" class="text-decoration-none text-dark">
                                Email <?php echo getSortIcon('email', $sort_by, $order); ?>
                            </a>
                        </th>
                        <th>
                        
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                    <input type="checkbox" class="row-checkbox" name="selected_users[]" value="<?php echo $row['id']; ?>">
                </td>
                            <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                             <td>
                                    <a href="delete_customer.php?id=<?php echo $row['id']; ?>" 
                                       onclick="return confirm('Delete this user: <?php echo addslashes($row['first_name'] . ' ' . $row['last_name']); ?>?')">

                                        <i class="fa fa-trash text-danger" aria-hidden="true"></i>
                                    </a>
                               

                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center">No customers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="d-flex justify-content-center mt-4">
            <nav aria-label="Customer pagination">
                <ul class="pagination">
                    <!-- Show all page numbers -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(['page' => $i, 'sort' => $sort_by, 'order' => $order]); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <!-- Next button -->
                    <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(['page' => $current_page + 1, 'sort' => $sort_by, 'order' => $order]); ?>">
                            Next
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="page-item disabled">
                        <span class="page-link">Next</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
        
        <!-- Page info -->
        
    </div>
    <script>
        document.getElementById('select-all').addEventListener('change', function () {
            let checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        
    </script>


<?php
include_once(__DIR__ . '/footer.php');
?>