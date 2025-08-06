<?php
$PAGE_TITLE = "Verify OTP";
include_once(__DIR__ . "/header.php");

// Check if email exists in session
if (!isset($_SESSION['otp_email']) || empty($_SESSION['otp_email'])) {
    header("Location: forgot_password.php?error=session_expired");
    exit;
}

// Generate a CSRF token if one does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<div class="containerr mt-4">
    <div class="mt-5 justify-content-center">
        <div class="cardd">
            <div class="text-center">
                <img src="resources/images/13246824_5191079.jpg" alt="OTP Verification Image" class="img-fluid mb-2" style="max-width: 200px;">
            </div>
            <h2 class="text-center">Verify OTP</h2>
            <!-- <p class="mt-3">Enter the OTP sent to <strong><?php echo htmlspecialchars($_SESSION['otp_email']); ?></strong></p> -->
            <form id="verifyOtpForm" class="mb-5">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <!-- Removed email input field since it's now handled via session only -->
                <div class="form-group">
                    <label for="otp">OTP <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="otp" name="otp">
                    <small class="text-danger" id="otpErr"></small>
                </div>
                <button type="submit" class="btn btn-primary w-100">Verify OTP</button>
                <div id="responseMessage" class="mt-3"></div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#verifyOtpForm').on('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting via the browser
            var formData = new FormData(this);
            var isValid = true;

            $('#otpErr').text('');
            $('#responseMessage').html('');

            var otp = $('#otp').val().trim();

            if (otp === '') {
                $('#otpErr').text('OTP is required.');
                isValid = false;
            } else if (otp.length !== 6 || isNaN(otp)) {
                $('#otpErr').text('Invalid OTP format. It should be a 6-digit number.');
                isValid = false;
            }

            if (isValid) {
                $.ajax({
                    url: 'check_otp.php', 
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        if (response.success) {
                            $('#responseMessage').html('<div class="alert alert-success">' + response.message + '</div>');
                            setTimeout(function() {
                                window.location.href = 'reset_password.php'; // Removed email parameter from URL
                            }, 2000); // 2000 milliseconds = 2 seconds
                        } else {
                            $('#responseMessage').html('<div class="alert alert-danger">' + response.error + '</div>');
                        }
                    }
                });
            }
        });
    });
</script>

<?php
include_once(__DIR__ . "/footer.php");
?>