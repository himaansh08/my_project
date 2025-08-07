<?php
include_once (__DIR__ . '/header.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch admin data from the database
$userId = $_SESSION['admin_id'];  // Assuming you store the admin ID in session
$adminData = [];

// Fetch admin details from the database
$query = 'SELECT first_name, last_name, email, phone, homeaddress, profile_image FROM admin_info WHERE admin_id = ?';
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $adminData = $result->fetch_assoc();
}
$stmt->close();
?>
<style>
    .profile-image {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        /* margin-bottom: 15px; */
    }
</style>
<div class="container my-3">
    <div class="row">
        <div class="d-flex p-2 bd-highlight gap-3">
            <div class="w-50 align-middle p-3">
                <div class="d-flex gap-4 justify-content-between align-items-center ">
                    <!-- Replace your image section with this -->
<div class="text-center">
    <?php
    // Determine the image source
    $imageSrc = 'uploads/default.png';  // Default fallback

    if (!empty($adminData['profile_image'])) {
        // Check if the custom image file exists
        if (file_exists($adminData['profile_image'])) {
            $imageSrc = $adminData['profile_image'];
        }
    }
    ?>
    
    <img class="profile-image" 
         src="<?php echo htmlspecialchars($imageSrc); ?>" 
         alt="Profile Photo"
         onerror="this.src='https://via.placeholder.com/120x120/6c757d/ffffff?text=<?php echo strtoupper(substr($adminData['first_name'] ?? 'A', 0, 1)); ?>';">
</div>
                    <h4 class="text-center">Admin Profile</h4>
                </div>
                <form class="p-3" id="profileForm" enctype="multipart/form-data">

                    <form class="p-3" id="profileForm" enctype="multipart/form-data">
                   
                    <div class="mb-1">
                        <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="firstName" name="first_name" value="<?php echo htmlspecialchars($adminData['first_name'] ?? ''); ?>">
                        <small class="text-danger" id="firstNameErr"></small>
                    </div>
                    <div class="mb-1">
                        <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="lastName" name="last_name" value="<?php echo htmlspecialchars($adminData['last_name'] ?? ''); ?>">
                        <small class="text-danger" id="lastNameErr"></small>
                    </div>
                    <div class="mb-1">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($adminData['email'] ?? ''); ?>">
                        <small class="text-danger" id="emailErr"></small>
                    </div>
                    <div class="mb-1">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($adminData['phone'] ?? ''); ?>">
                        <small class="text-danger" id="phoneErr"></small>
                    </div>
                    <div class="mb-1">
                        <label for="homeaddress" class="form-label">Home Address</label>
                        <textarea class="form-control" id="homeaddress" name="homeaddress"><?php echo htmlspecialchars($adminData['homeaddress'] ?? ''); ?></textarea>
                        <small class="text-danger" id="homeaddressErr"></small>
                    </div>
                    <div class="mb-1">
                        <label for="profileImage" class="form-label">Upload Profile Photo</label>
                        <input class="form-control" type="file" id="profileImage" name="profile_image">
                        <small class="text-danger" id="profileImageErr"></small>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="update_profile">
                    <button type="submit" class="btn btn-primary my-3">Save Profile</button>
                    <div id="profileResponseMessage" class="mt-3"></div>
                </form>
                   
            </div>
            <div class="w-50 align-middle p-3">
                <div style="height: 120px;" class="d-flex justify-content-end align-items-center">
                    <h4 class="text-center">Change Password</h4>
                </div>
                <form id="passwordForm" class="p-3">
                    <div class="mb-1">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" name="current_password">
                        <small class="text-danger" id="currentPasswordErr"></small>
                    </div>
                    <div class="mb-1">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword" name="new_password">
                        <small class="text-danger" id="newPasswordErr"></small>
                    </div>
                    <div class="mb-1">
                        <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmNewPassword" name="confirm_new_password">
                        <small class="text-danger" id="confirmNewPasswordErr"></small>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="update_password">
                    <button type="submit" class="btn btn-primary my-3">Change Password</button>
                    <div id="passwordResponseMessage" class="mt-3"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>

$(document).ready(function() {
    // Profile form submission handler
    $('#profileForm').on('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting via the browser
        var formData = new FormData(this);
        var isValid = true;

        // Clear previous error messages
        $('#firstNameErr').text('');
        $('#lastNameErr').text('');
        $('#emailErr').text('');
        $('#phoneErr').text('');
        $('#homeaddressErr').text('');
        $('#profileImageErr').text('');
        $('#profileResponseMessage').html('');

        var firstName = $('#firstName').val().trim();
        var lastName = $('#lastName').val().trim();
        var email = $('#email').val().trim();
        var phone = $('#phone').val().trim();
        var profileImageInput = $('#profileImage')[0];
        var profileImage = profileImageInput && profileImageInput.files ? profileImageInput.files[0] : null;

        // Client-side validation
        if (firstName === '') {
            $('#firstNameErr').text('First Name is required');
            isValid = false;
        }
        if (lastName === '') {
            $('#lastNameErr').text('Last Name is required');
            isValid = false;
        }
        if (email === '') {
            $('#emailErr').text('Email is required');
            isValid = false;
        } else if (!validateEmail(email)) {
            $('#emailErr').text('Invalid email format');
            isValid = false;
        }
        
        if (phone !== '' && !validatePhone(phone)) {
            $('#phoneErr').text('Invalid phone number format');
            isValid = false;
        }
        if (profileImage) {
            var validImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validImageTypes.includes(profileImage.type)) {
                $('#profileImageErr').text('Invalid image type. Only JPG, PNG, and GIF are allowed.');
                isValid = false;
            }
            if (profileImage.size > 2 * 1024 * 1024) { // 2MB
                $('#profileImageErr').text('Image size exceeds 2MB.');
                isValid = false;
            }
        }

        if (isValid) {
            // Show loading message
            $('#profileResponseMessage').html('<div class="alert alert-info">Updating profile...</div>');
            
            $.ajax({
                url: './admin_profile_ajax.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json', // Expect JSON response
                success: function(response) {
                    console.log('Response:', response); // Debug log
                    if (response.success) {
                        $('#profileResponseMessage').html('<div class="alert alert-success">' + response.message + ' Redirecting...</div>');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#profileResponseMessage').html('<div class="alert alert-danger">' + (response.error || 'An error occurred') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr.responseText); // Debug log
                    $('#profileResponseMessage').html('<div class="alert alert-danger">There was an error processing your request. Please try again later.</div>');
                }
            });
        }
    });

    // Password form submission handler (moved outside of profile form handler)
    $('#passwordForm').on('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting via the browser
        var formData = $(this).serialize();
        var isValid = true;

        // Clear previous error messages
        $('#currentPasswordErr').text('');
        $('#newPasswordErr').text('');
        $('#confirmNewPasswordErr').text('');
        $('#passwordResponseMessage').html('');

        var currentPassword = $('#currentPassword').val().trim();
        var newPassword = $('#newPassword').val().trim();
        var confirmNewPassword = $('#confirmNewPassword').val().trim();

        if (newPassword !== '' || confirmNewPassword !== '' || currentPassword !== '') {
            if (currentPassword === '') {
                $('#currentPasswordErr').text('Current Password is required');
                isValid = false;
            }
            if (newPassword === '') {
                $('#newPasswordErr').text('New Password is required');
                isValid = false;
            }
            if (confirmNewPassword === '') {
                $('#confirmNewPasswordErr').text('Confirm New Password is required');
                isValid = false;
            } else if (newPassword !== confirmNewPassword) {
                $('#confirmNewPasswordErr').text('Passwords do not match');
                isValid = false;
            }
        }

        if (isValid) {
            // Show loading message
            $('#passwordResponseMessage').html('<div class="alert alert-info">Updating password...</div>');

            $.ajax({
                url: 'admin_profile_ajax.php',
                type: 'POST',
                data: formData,
                dataType: 'json', // Expect JSON response
                success: function(response) {
                    console.log('Password Response:', response); // Debug log
                    if (response.success) {
                        $('#passwordResponseMessage').html('<div class="alert alert-success">' + response.message + ' Redirecting...</div>');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#passwordResponseMessage').html('<div class="alert alert-danger">' + (response.error || 'An error occurred') + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', xhr.responseText); // Debug log
                    $('#passwordResponseMessage').html('<div class="alert alert-danger">There was an error processing your request. Please try again later.</div>');
                }
            });
        }
    });

    // Utility functions
    function validateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function validatePhone(phone) {
        var re = /^[0-9]{10}$/; // Assuming a 10-digit phone number format. Adjust the regex according to your requirements.
        return re.test(phone);
    }
});
</script>

<?php
include_once (__DIR__ . '/footer.php');
?>