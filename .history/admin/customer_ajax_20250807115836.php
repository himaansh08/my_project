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

    if (isset($_POST['action']) && $_POST['action'] == 'search_users)') {
        $search = $_POST['query'];

        if ($search !== ' ') {
             $search = '%' . $search . '%'; // for LIKE query
            $stmt = $conn->prepare("SELECT * FROM users_info WHERE name LIKE ? OR email LIKE ?");
            $stmt->bind_param("ss", $search, $search);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<table border='1' cellpadding='5'><tr><th>Name</th><th>Email</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr><td>" . htmlspecialchars($row['first_name']) . "</td><td>" .htmlspecialchars($row['last_name']) . "</td></tr>". htmlspecialchars($row['email']) . "</td></tr>";
                }
                echo "</table>";
            } else {
                echo "No results found.";
            }

            $stmt->close();
        } else {
            echo "Please enter a search term.";
        }
    }
}
