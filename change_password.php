<?php
$PAGE_TITLE = "Change Password";
include_once(__DIR__ ."/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token 
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


?>


<div class="container rounded bg-white mt-5 change-password-container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="p-3 py-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Change Password</h3>
                    <a href="profile.php" class="btn btn-secondary">Back to Profile</a>
                </div>
                
                <div id="responseMessage"></div>
                
                <form id="changePasswordForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="currentPassword" class="form-label">Current Password<span class="text-danger">*</span></label>
                            <input type="password" class="form-control" placeholder="Enter current password" 
                                   id="currentPassword" name="currentPassword" >
                            <small class="text-danger" id="currentPasswordErr"></small>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="newPassword" class="form-label">New Password<span class="text-danger">*</span></label>
                            <input type="password" class="form-control" placeholder="Enter new password" 
                                   id="newPassword" name="newPassword" >
                            <!-- <small class="text-muted" id="passwordHint">Password must be at least 8 characters with uppercase, lowercase, number and special character</small> -->
                            <small class="text-danger" id="newPasswordErr"></small>
                        </div>
                        <div class="col-md-6">
                            <label for="confirmNewPassword" class="form-label">Confirm New Password<span class="text-danger">*</span></label>
                            <input type="password" class="form-control" placeholder="Confirm new password" 
                                   id="confirmNewPassword" name="confirmNewPassword" >
                            <small class="text-danger" id="confirmNewPasswordErr"></small>
                        </div>
                    </div>
                    
                    <div class="mt-5 text-right">
                        <button class="btn btn-primary w-100 profile-button" type="submit" id="saveBtn">Save New Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Move checkPasswordStrength function to global scope
function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = [];

    if (password.length >= 8) strength++;
    else feedback.push("at least 8 characters");

    if (/[a-z]/.test(password)) strength++;
    else feedback.push("lowercase letter");

    if (/[A-Z]/.test(password)) strength++;
    else feedback.push("uppercase letter");

    if (/[0-9]/.test(password)) strength++;
    else feedback.push("number");

    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    else feedback.push("special character");

    return { strength: strength, feedback: feedback };
}

// Validation functions
function validateCurrentPassword(password) {
    if (!password.trim()) {
        return 'Current password is required';
    }
    return '';
}

function validateNewPassword(password) {
    if (!password.trim()) {
        return 'New password is required';
    }
    
    const result = checkPasswordStrength(password);
    if (result.strength < 5) {
        return 'Missing: ' + result.feedback.join(', ');
    }
    
    return '';
}

function validateConfirmPassword(password, confirmPassword) {
    if (!confirmPassword.trim()) {
        return 'Please confirm your new password';
    }
    
    if (password !== confirmPassword) {
        return 'Passwords do not match';
    }
    
    return '';
}

function clearErrors() {
    $('.form-control').removeClass('error');
    $('.text-danger').text('');
}

function showFieldError(fieldId, message) {
    $('#' + fieldId).addClass('error');
    $('#' + fieldId + 'Err').text(message);
}



// Form submission
$('#changePasswordForm').on('submit', function(e) {
    e.preventDefault();
    
    clearErrors();
    let isValid = true;

    // Validate all fields
    const currentPasswordError = validateCurrentPassword($('#currentPassword').val());
    if (currentPasswordError) {
        showFieldError('currentPassword', currentPasswordError);
        isValid = false;
    }

    const newPasswordError = validateNewPassword($('#newPassword').val());
    if (newPasswordError) {
        showFieldError('newPassword', newPasswordError);
        isValid = false;
    }

    const confirmPasswordError = validateConfirmPassword($('#newPassword').val(), $('#confirmNewPassword').val());
    if (confirmPasswordError) {
        showFieldError('confirmNewPassword', confirmPasswordError);
        isValid = false;
    }

    // Check if new password is same as current password (only if both fields have values)
    if ($('#currentPassword').val() && $('#newPassword').val() && 
        $('#currentPassword').val() === $('#newPassword').val()) {
        showFieldError('newPassword', 'New password must be different from current password');
        isValid = false;
    }

    if (!isValid) {
        $('#responseMessage').html('<div class="alert alert-danger">Please fix the errors below.</div>');
        return;
    }

    // Show loading state
    $('#saveBtn').prop('disabled', true).text('Changing Password...');
    $('.container').addClass('loading');

    // Submit form
    const formData = new FormData(this);
    
    $.ajax({
        url: 'change_password_ajax.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            if (response.success) {
                $('#responseMessage').html('<div class="alert alert-success">' + response.message + '</div>');
                $('#changePasswordForm')[0].reset();
                // $('#passwordHint').text('Password must be at least 8 characters with uppercase, lowercase, number and special character');
                
                // Redirect after 2 seconds
                setTimeout(function() {
                    window.location.href = 'profile.php';
                }, 2000);
            } else {
                $('#responseMessage').html('<div class="alert alert-danger">' + response.message + '</div>');
            }
        },
       
        complete: function() {
            $('#saveBtn').prop('disabled', false).text('Save New Password');
            $('.container').removeClass('loading');
            
            // Scroll to top to show message
            $('html, body').animate({scrollTop: 0}, 300);
        }
    });
});
</script>

<?php
include_once(__DIR__ ."/footer.php");
?>