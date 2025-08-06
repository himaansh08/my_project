<?php
$PAGE_TITLE = "Register";
// $EXTRA_STYLES = '<link rel="stylesheet" href="./styles/register.css">';
include_once(__DIR__ . '/header.php');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mb-4">
            <img src="https://orientaloutsourcing.com/images/contact.png" class="img-fluid mb-3" alt="Register">
            <h2 class="mb-4">Create Account</h2>
            <p class="text-muted mb-4">Join us today! Please fill in the details below to create your account.</p>
        </div>
        <div class="col-md-6">
            <div id="alert-container"></div>

            <form id="registrationForm" novalidate>
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="mb-3">
                    <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                    <div class="text-danger" id="firstName-error"></div>
                </div>

                <div class="mb-3">
                    <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                    <div class="text-danger" id="lastName-error"></div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="text-danger" id="email-error"></div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="text-danger" id="password-error"></div>
                </div>

                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                    <div class="text-danger" id="confirmPassword-error"></div>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="registerBtn">Create Account</button>

                <div class="text-center mt-3">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
   $(document).ready(function() {
    console.log('Registration form initialized');

    function isValidName(name) {
        return name && name.length >= 2 && /^[a-zA-Z\s]+$/.test(name);
    }

    // Validation functions
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) && email.length <= 255;
    }

    function isValidPassword(password) {
        return password.length >= 8 &&
            /[A-Z]/.test(password) &&
            /[a-z]/.test(password) &&
            /[0-9]/.test(password) &&
            /[\W_]/.test(password);
    }

    
    // Clear all errors
    function clearErrors() {
        $('.text-danger').not('span').text('');
        $('.form-control').removeClass('is-invalid is-valid');
        $('#alert-container').empty();
    }

    // Show field error
    function showFieldError(fieldId, message) {
        $('#' + fieldId + '-error').text(message);
        $('#' + fieldId).addClass('is-invalid');
    }

    // Show alert
    function showAlert(message, type = 'danger') {
        $('#alert-container').html(`
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `);
        if (type === 'success') {
            setTimeout(() => $('.alert').alert('close'), 5000);
        }
    }

    // Client-side validation
    function validateForm() {
        clearErrors();
        let isValid = true;

        const firstName = $('#firstName').val().trim();
        const lastName = $('#lastName').val().trim();
        const email = $('#email').val().trim();
        const password = $('#password').val();
        const confirmPassword = $('#confirmPassword').val();

        if (!firstName) {
            showFieldError('firstName', 'First name is required');
            isValid = false;
        } else if (!isValidName(firstName)) {
            showFieldError('firstName', 'Must be 2+ characters, letters only');
            isValid = false;
        }

        if (!lastName) {
            showFieldError('lastName', 'Last name is required');
            isValid = false;
        } else if (!isValidName(lastName)) {
            showFieldError('lastName', 'Must be 2+ characters, letters only');
            isValid = false;
        }

        if (!email) {
            showFieldError('email', 'Email is required');
            isValid = false;
        } else if (!isValidEmail(email)) {
            showFieldError('email', 'Invalid email format');
            isValid = false;
        }

        if (!password) {
            showFieldError('password', 'Password is required');
            isValid = false;
        } else if (password.length < 8) {
            showFieldError('password', 'Password must be at least 8 characters');
            isValid = false;
        } else if (!/[A-Z]/.test(password)) {
            showFieldError('password', 'Password must contain at least one uppercase letter');
            isValid = false;
        } else if (!/[a-z]/.test(password)) {
            showFieldError('password', 'Password must contain at least one lowercase letter');
            isValid = false;
        } else if (!/[0-9]/.test(password)) {
            showFieldError('password', 'Password must contain at least one number');
            isValid = false;
        } else if (!/[\W_]/.test(password)) {
            showFieldError('password', 'Password must contain at least one special character');
            isValid = false;
        }

        if (!confirmPassword) {
            showFieldError('confirmPassword', 'Confirm password is required');
            isValid = false;
        } else if (password !== confirmPassword) {
            showFieldError('confirmPassword', 'Passwords do not match');
            isValid = false;
        }

        return isValid;
    }

   
    $('#registrationForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Form submitted');

        if (!validateForm()) {
            console.log('Client validation failed');
            return;
        }

        // Show loading state on button
        const $submitBtn = $('#registerBtn');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Creating Account...');

        const formData = new FormData();
        formData.append('action', 'register_user');
        formData.append('firstName', $('#firstName').val().trim());
        formData.append('lastName', $('#lastName').val().trim());
        formData.append('email', $('#email').val().trim());
        formData.append('password', $('#password').val());
        formData.append('confirmPassword', $('#confirmPassword').val());
        formData.append('csrf_token', $('#csrf_token').val());

        console.log('Sending AJAX request to register-ajax.php');
        console.log('Form data:', {
            action: 'register_user',
            firstName: $('#firstName').val().trim(),
            lastName: $('#lastName').val().trim(),
            email: $('#email').val().trim(),
            csrf_token: $('#csrf_token').val()
        });

        $.ajax({
            url: 'register-ajax.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            timeout: 30000, // Increased timeout
            success: function(response) {
                console.log('AJAX Success Response:', response);
                
                if (response.success) {
                    console.log('Registration successful, showing success message');
                    showAlert(response.message, 'success');
                    $('#registrationForm')[0].reset();
                    
                    if (response.redirectUrl) {
                        console.log('Redirecting to:', response.redirectUrl);
                        // Show success message briefly then redirect
                        setTimeout(function() {
                            console.log('Executing redirect now...');
                            window.location.href = response.redirectUrl;
                        }, 2000);
                    } else {
                        console.log('No redirect URL provided in response');
                    }
                } else {
                    console.log('Registration failed:', response);
                    // Handle server-side field errors
                    if (response.errors) {
                        console.log('Field errors:', response.errors);
                        Object.keys(response.errors).forEach(field => {
                            showFieldError(field, response.errors[field]);
                        });
                    } else {
                        showAlert(response.error || response.message || 'Registration failed', 'danger');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error Details:');
                console.log('Status:', status);
                console.log('Error:', error);
                console.log('XHR:', xhr);
                console.log('Response Text:', xhr.responseText);
                console.log('Status Code:', xhr.status);
                
                let errorMessage = 'An unexpected error occurred. Please try again.';
                
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please check the server logs.';
                } else if (xhr.status === 404) {
                    errorMessage = 'Registration handler not found. Please check the file path.';
                } else if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                } else if (xhr.responseText) {
                    // Try to parse the response text to see if there's a PHP error
                    try {
                        const parsedResponse = JSON.parse(xhr.responseText);
                        if (parsedResponse.error) {
                            errorMessage = parsedResponse.error;
                        }
                    } catch (parseError) {
                        console.log('Could not parse error response as JSON:', parseError);
                        // Show first 200 chars of response for debugging
                        if (xhr.responseText.length > 0) {
                            errorMessage = 'Server error. Check console for details.';
                            console.log('Full server response:', xhr.responseText);
                        }
                    }
                }
                
                showAlert(errorMessage, 'danger');
            },
            complete: function() {
                console.log('AJAX request completed');
                // Reset button state
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Clear errors when user starts typing (better UX)
    $('.form-control').on('input', function() {
        $(this).removeClass('is-invalid');
        const errorId = $(this).attr('id') + '-error';
        $('#' + errorId).text('');
    });
});
</script>

<?php include_once(__DIR__ . '/footer.php'); ?>