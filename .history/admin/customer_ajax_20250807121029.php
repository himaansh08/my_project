<?php
include_once(__DIR__ . '/../config.php');

header('Content-Type: application/json');
$response = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'delete_user') {
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
        $search = trim($_POST['query']);
        
        // Debug: Check what we received
        error_log("Search query received: " . $search);
        
        if (!empty($search)) {
            $search_param = '%' . $search . '%'; // for LIKE query
            $stmt = $conn->prepare("SELECT * FROM users_info WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?");
            $stmt->bind_param("sss", $search_param, $search_param, $search_param);

            if ($stmt->execute()) {
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $output = "<table class='table table-bordered'>";
                    $output .= "<thead><tr><th>First Name</th><th>Last Name</th><th>Email</th></tr></thead>";
                    $output .= "<tbody>";
                    
                    while ($row = $result->fetch_assoc()) {
                        $output .= "<tr>
                            <td>" . htmlspecialchars($row['first_name']) . "</td>
                            <td>" . htmlspecialchars($row['last_name']) . "</td>
                            <td>" . htmlspecialchars($row['email']) . "</td>
                          </tr>";
                    }
                    $output .= "</tbody></table>";
                    echo $output;
                } else {
                    echo "No results found";
                }
                $stmt->close();
            } else {
                echo "Error executing search query";
            }
        } else {
            echo "Please enter a search term.";
        }
        exit; // Important: exit after handling search to prevent JSON header conflicts
    }
}
?>