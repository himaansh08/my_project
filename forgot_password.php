<?php
$PAGE_TITLE = "Forgot Password";
include_once(__DIR__ . "/header.php");


// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}
?>

<div class="containerr mt-4">
    <div class="mt-5 justify-content-center">
        <div class="cardd">
            <div class="text-center">
                <img src="resources/images/7070629_3293465.jpg" alt="Forgot Password Image" class="img-fluid mb-2" style="max-width: 200px;">
            </div>
            <h2 class="text-center">Forgot Password</h2>
            <p class="mt-3">Enter the email address associated with your account and we'll send you a link to reset your password.</p>

            <form id="forgotPasswordForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="email">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email">
                    <small class="text-danger" id="emailErr"></small>
                </div>
                <button type="submit" class="btn btn-primary w-100">Continue</button>
                <div id="responseMessage" class="mt-3"></div>
            </form>

            <div class="text-center my-5">
                <a href="register.php" class="text-primary">Don't have an account? Sign up</a>
            </div>
        </div>
    </div>
</div>



<script>
$(document).ready(function() {
    $('#forgotPasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        $('#emailErr').text('');
        $('#email').removeClass('is-invalid');
        
        const email = $('#email').val().trim();
        let isValid = true;

        // Client-side validation
        if (!email) {
            $('#emailErr').text('Email is required');
            $('#email').addClass('is-invalid');
            isValid = false;
        } else if (!validateEmail(email)) {
            $('#emailErr').text('Invalid email format');
            $('#email').addClass('is-invalid');
            isValid = false;
        }

        if (isValid) {
            $.ajax({
                url: 'send_otp.php',
                type: 'POST',
                data: new FormData(this),
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        showSuccessMessage(response.message);
                        setTimeout(function() {
                            window.location.href = 'verify_otp.php';
                        },1500 );
                    }
                    // Errors are handled by global error handler
                }
                // No need for error handler - global handler takes care of it
            });
        }
    });
});
</script>

<?php include_once(__DIR__ . "/footer.php"); ?>