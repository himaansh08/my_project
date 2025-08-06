<?php
// DEBUG SCRIPT - Add this temporarily to your profile.php or create a separate debug file

require_once 'config.php';

echo "<h2>Skills Debug Information</h2>";

// 1. Check if skills table exists and what it contains
echo "<h3>1. Skills Table Structure:</h3>";
$result = $conn->query("DESCRIBE skills");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "ERROR: Skills table doesn't exist or can't be accessed: " . $conn->error;
}

// 2. Check skills table data
echo "<h3>2. Skills Table Data:</h3>";
$result = $conn->query("SELECT * FROM skills LIMIT 10");
if ($result) {
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Skill Name</th><th>Description</th><th>Category</th><th>Is Active</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['skill_name'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars(substr($row['description'] ?? '', 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($row['category'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['is_active'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Skills table is empty!";
    }
} else {
    echo "ERROR querying skills: " . $conn->error;
}

// 3. Check user_skills table structure
echo "<h3>3. User Skills Table Structure:</h3>";
$result = $conn->query("DESCRIBE user_skills");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "ERROR: User_skills table doesn't exist: " . $conn->error;
}

// 4. Check what data gets posted when form is submitted
echo "<h3>4. Form Data Simulation:</h3>";
echo "Let's see what your form would send if you checked PHP, HTML, CSS:<br>";

// Simulate the skills array that would be posted
$test_skills = ['PHP', 'HTML', 'CSS']; // This is what your current form sends
echo "Current form sends skill NAMES: ";
print_r($test_skills);
echo "<br><br>";

echo "But your PHP code expects skill IDs (integers)<br>";
foreach ($test_skills as $skill) {
    $intval_result = intval($skill);
    echo "intval('$skill') = $intval_result<br>";
}

// 5. Check if there are any existing user_skills records
echo "<h3>5. Existing User Skills Records:</h3>";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "For user ID: $user_id<br>";
    
    $stmt = $conn->prepare("SELECT us.*, s.skill_name FROM user_skills us LEFT JOIN skills s ON us.skill_id = s.id WHERE us.user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Skill ID</th><th>Skill Name</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['user_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['skill_id'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['skill_name'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No existing user skills found.";
    }
    $stmt->close();
} else {
    echo "No user logged in to check.";
}

// 6. Solution recommendations
echo "<h3>6. Possible Solutions:</h3>";
echo "<ol>";
echo "<li><strong>If skills table is empty:</strong> Run the INSERT statements from my previous response to populate it.</li>";
echo "<li><strong>If skills table doesn't exist:</strong> Run the complete CREATE TABLE statements.</li>";
echo "<li><strong>If user_skills table structure is wrong:</strong> It might still be expecting skill names instead of IDs.</li>";
echo "<li><strong>If your HTML form still sends skill names:</strong> Update the profile.php form to use skill IDs as values.</li>";
echo "</ol>";

// 7. Quick fix query generator
echo "<h3>7. Quick Fix - Update Existing Data:</h3>";
echo "If you have existing user_skills with skill names instead of IDs, run these queries:<br>";
echo "<pre>";
echo "-- First, let's see what's in user_skills:\n";
echo "SELECT * FROM user_skills;\n\n";
echo "-- If the skill_id column contains text instead of numbers, we need to fix it\n";
echo "-- But first, let's check the column type:\n";
echo "DESCRIBE user_skills;\n";
echo "</pre>";

?>

<script>
// Also let's check what the form actually sends
console.log("=== FORM DEBUG INFO ===");
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profileForm');
    if (form) {
        console.log("Form found");
        const skillInputs = form.querySelectorAll('input[name="skills[]"]');
        console.log("Skill inputs found:", skillInputs.length);
        
        skillInputs.forEach(function(input, index) {
            console.log(`Skill ${index + 1}: name="${input.name}", value="${input.value}", checked=${input.checked}`);
        });
    } else {
        console.log("Profile form not found on this page");
    }
});
</script>