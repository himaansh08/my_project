<?php
include_once(__DIR__ . '/../config.php');

$response = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'delete_user') {
        header('Content-Type: application/json');
        $user_id = $_POST['user_id'];
        $user_id = $conn->real_escape_string($user_id);
        $sql = "DELETE FROM users_info WHERE id = '$user_id'";
        if ($conn->query($sql)) {
            $response['success'] = true;
            $response['message'] = 'User deleted successfully';
        } else {
            $response['error'] = "Couldn't delete user";
        }
        echo json_encode($response);
    }

    if (isset($_POST['action']) && $_POST['action'] == 'delete_users') {
        header('Content-Type: application/json');
        $users = $_POST['users'];
        foreach ($users as $user) {
            $user_id = $conn->real_escape_string($user['user_id']);
            $sql = "DELETE FROM users_info WHERE id = '$user_id'";
            if ($conn->query($sql)) {
                $response['success'] = true;
                $response['message'] = 'Users deleted successfully';
            } else {
                $response['error'][] = "Couldn't delete user " . $user['first_name'];
            }
        }
        if (!empty($response['error'])) {
            $response['success'] = false;
        }
        echo json_encode($response);
    }

    if (isset($_POST['action']) && $_POST['action'] == 'search_users') {
        header('Content-Type: text/html');
        
        $search = trim($_POST['query']);
        $skill_id = isset($_POST['skill_id']) ? trim($_POST['skill_id']) : '';
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
        $limit = 5; // Same as main page
        $offset = ($page - 1) * $limit;
        
        // Build the WHERE clause
        $where_conditions = [];
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
            $params = array_merge($params, [$search_param, $search_param, $search_param]);
            $types .= 'sss';
        }
        
        if (!empty($skill_id) && $skill_id !== 'all') {
            $where_conditions[] = "us.skill_id = ?";
            $params[] = $skill_id;
            $types .= 'i';
        }
        
        if (empty($where_conditions)) {
            echo "Please enter a search term or select a skill.";
            exit;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Count total results for pagination
        $count_query = "SELECT COUNT(DISTINCT u.id) as total FROM users_info u";
        if (!empty($skill_id) && $skill_id !== 'all') {
            $count_query .= " INNER JOIN user_skills us ON u.id = us.user_id";
        }
        $count_query .= " WHERE " . $where_clause;
        
        $count_stmt = $conn->prepare($count_query);
        if (!empty($params)) {
            $count_stmt->bind_param($types, ...$params);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_rows = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_rows / $limit);
        
        // Main search query with pagination
        $query = "SELECT DISTINCT u.*, GROUP_CONCAT(s.skill_name) as skills FROM users_info u";
        if (!empty($skill_id) && $skill_id !== 'all') {
            $query .= " INNER JOIN user_skills us ON u.id = us.user_id";
        }
        $query .= " LEFT JOIN user_skills us2 ON u.id = us2.user_id";
        $query .= " LEFT JOIN skills s ON us2.skill_id = s.id";
        $query .= " WHERE " . $where_clause;
        $query .= " GROUP BY u.id ORDER BY u.first_name ASC LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($query);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        if ($stmt->bind_param($types, ...$params) && $stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $output = "<div class='search-results-container'>";
                $output .= "<div class='d-flex justify-content-between align-items-center mb-3'>";
                $output .= "<h5>Search Results ($total_rows found)</h5>";
                $output .= "<button class='btn btn-secondary btn-sm' onclick='clearSearch()'>Show All Users</button>";
                $output .= "</div>";
                
                $output .= "<table class='table table-striped table-bordered table-hover'>";
                $output .= "<thead><tr><th><input type='checkbox' id='search_select_all'></th><th>First Name</th><th>Last Name</th><th>Email</th><th>Skills</th><th>Action</th></tr></thead>";
                $output .= "<tbody>";
                
                while ($row = $result->fetch_assoc()) {
                    $skills = $row['skills'] ? $row['skills'] : 'No skills';
                    $output .= "<tr>
                        <td><input type='checkbox' class='user-checkbox' data-user-id='" . $row['id'] . "' data-user-first-name='" . htmlspecialchars($row['first_name']) . "'></td>
                        <td>" . htmlspecialchars($row['first_name']) . "</td>
                        <td>" . htmlspecialchars($row['last_name']) . "</td>
                        <td>" . htmlspecialchars($row['email']) . "</td>
                        <td>" . htmlspecialchars($skills) . "</td>
                        <td><i data-user-id='" . $row['id'] . "' class='delete_customer text-danger fas fa-trash fa-sm' style='cursor: pointer;'></i></td>
                      </tr>";
                }
                $output .= "</tbody></table>";
                
                // Pagination for search results
                if ($total_pages > 1) {
                    $output .= "<nav aria-label='Search results pagination'>";
                    $output .= "<ul class='pagination justify-content-start'>";
                    
                    // Previous button
                    if ($page > 1) {
                        $output .= "<li class='page-item'>";
                        $output .= "<button class='page-link' onclick='searchWithPagination(" . ($page - 1) . ")' aria-label='Previous'>";
                        $output .= "<span aria-hidden='true'>&laquo;</span></button></li>";
                    }
                    
                    // Page numbers
                    for ($i = 1; $i <= $total_pages; $i++) {
                        $active_class = ($i == $page) ? 'active' : '';
                        $output .= "<li class='page-item $active_class'>";
                        $output .= "<button class='page-link' onclick='searchWithPagination($i)'>$i</button></li>";
                    }
                    
                    // Next button
                    if ($page < $total_pages) {
                        $output .= "<li class='page-item'>";
                        $output .= "<button class='page-link' onclick='searchWithPagination(" . ($page + 1) . ")' aria-label='Next'>";
                        $output .= "<span aria-hidden='true'>&raquo;</span></button></li>";
                    }
                    
                    $output .= "</ul></nav>";
                }
                
                $output .= "</div>";
                echo $output;
            } else {
                echo "<div class='alert alert-info'>No results found for your search criteria.</div>";
            }
            $stmt->close();
        } else {
            echo "<div class='alert alert-danger'>Error executing search query</div>";
        }
        exit;
    }
}
?>