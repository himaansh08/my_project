<?php
// Remove this line since config.php will handle session start
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

$PAGE_TITLE = "Email Verification";
include_once(__DIR__ . '/header.php'); // This likely includes config.php which starts the session

// Check if there's pending registration data
if (!isset($_SESSION['pending_registration'])) {
    header('Location: register.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$email = $_SESSION['pending_registration']['email'];
$maskedEmail = substr($email, 0, 3) . '****' . substr($email, strpos($email, '@'));
?>

<div class="containerr mt-4">
    <div class="mt-5 justify-content-center">
        <div class="cardd">
            <div class="text-center">
                <img src="resources/images/13246824_5191079.jpg" alt="Email Verification Image" class="img-fluid mb-2" style="max-width: 200px;">
            </div>
            <h2 class="text-center">Verify Your Email</h2>
            <!-- <p class="mt-3">Enter the OTP sent to <strong><?php echo htmlspecialchars($maskedEmail); ?></strong></p> -->

            <div id="alert-container"></div>

            <form id="otpForm" class="mb-5" novalidate>
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-group">
                    <label for="otp">OTP <span class="text-danger">*</span></label>
                    <input type="text" 
                           class="form-control" 
                           id="otp" 
                           name="otp" 
                           maxlength="6" 
                          
                           required>
                    <small class="text-danger" id="otp-error"></small>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="verifyBtn">
                    Verify OTP
                </button>

                <div class="text-center mt-3">
                    <!-- <p class="text-muted mb-2">Didn't receive the code?</p> -->
                    <button type="button" class="btn btn-link p-0" id="resendBtn">
                        Resend Code
                    </button>
                    <div class="mt-2">
                        <small class="text-muted" id="timer"></small>
                    </div>
                </div>

                <!-- <div class="text-center mt-4">
                    <a href="register.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Registration
                    </a>
                </div> -->

                <div id="responseMessage" class="mt-3"></div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let resendCooldown = 60; // 60 seconds cooldown
    let timerInterval;

    // Start timer
    function startTimer() {
        $('#resendBtn').prop('disabled', true);
        timerInterval = setInterval(function() {
            if (resendCooldown > 0) {
                $('#timer').text(`Resend available in ${resendCooldown} seconds`);
                resendCooldown--;
            } else {
                clearInterval(timerInterval);
                $('#timer').text('');
                $('#resendBtn').prop('disabled', false);
                resendCooldown = 60;
            }
        }, 1000);
    }

    // Start initial timer
    startTimer();

    // Clear errors
    function clearErrors() {
        $('#otp-error').text('');
        $('#otp').removeClass('is-invalid is-valid');
        $('#alert-container').empty();
        $('#responseMessage').html('');
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
            setTimeout(() => $('.alert').alert('close'), 3000);
        }
    }

    // OTP input formatting
    $('#otp').on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remove non-digits
        $(this).val(value);
        clearErrors();
    });

    // Handle form submission
    $('#otpForm').on('submit', function(e) {
        e.preventDefault();
        
        const otp = $('#otp').val().trim();
        
        if (!otp) {
            $('#otp-error').text('OTP is required.');
            $('#otp').addClass('is-invalid');
            return;
        }

        if (otp.length !== 6) {
            $('#otp-error').text('Invalid OTP format. It should be a 6-digit number.');
            $('#otp').addClass('is-invalid');
            return;
        }

        // Show loading state
        const $submitBtn = $('#verifyBtn');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Verifying...');

        const formData = new FormData();
        formData.append('action', 'verify_otp');
        formData.append('otp', otp);
        formData.append('csrf_token', $('#csrf_token').val());

        $.ajax({
            url: 'register-otp-verfication-ajax.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                console.log('OTP Verification Response:', response);
                if (response.success) {
                    $('#responseMessage').html('<div class="alert alert-success">' + response.message + '</div>');
                    $('#otpForm')[0].reset();
                    if (response.redirectUrl) {
                        setTimeout(() => window.location.href = response.redirectUrl, 2000);
                    }
                } else {
                    $('#responseMessage').html('<div class="alert alert-danger">' + (response.error || 'Verification failed') + '</div>');
                    $('#otp').addClass('is-invalid');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr, status, error);
                console.log('Response Text:', xhr.responseText);
                let errorMessage = 'An unexpected error occurred. Please try again.';
                
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                
                $('#responseMessage').html('<div class="alert alert-danger">' + errorMessage + '</div>');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Handle resend OTP
    $('#resendBtn').on('click', function() {
        const $this = $(this);
        const originalText = $this.text();
        $this.prop('disabled', true).text('Sending...');

        const formData = new FormData();
        formData.append('action', 'resend_otp');
        formData.append('csrf_token', $('#csrf_token').val());

        $.ajax({
            url: 'register-otp-verfication-ajax.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                console.log('Resend OTP Response:', response);
                if (response.success) {
                    $('#responseMessage').html('<div class="alert alert-success">' + response.message + '</div>');
                    startTimer(); // Start cooldown timer
                } else {
                    $('#responseMessage').html('<div class="alert alert-danger">' + (response.error || 'Failed to resend code') + '</div>');
                    $this.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.log('Resend AJAX Error:', xhr, status, error);
                $('#responseMessage').html('<div class="alert alert-danger">Failed to resend code. Please try again.</div>');
                $this.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>

<?php include_once(__DIR__ . '/footer.php'); ?>