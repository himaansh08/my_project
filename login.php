<?php
$PAGE_TITLE = "Login";
include_once(__DIR__ . '/header.php');
// Generate a CSRF token if one does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 d-flex align-items-center">
            <img src="resources/images/4957136_4957136.jpg" alt="Login Image" class="img-fluid">
        </div>
        <div class="col-md-6 d-flex align-items-center">
            <div class=" p-5 w-100">
                <h2 class="mb-5">Login</h2>
                <form id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="form-group mb-3">
                        <label for="email">Email: <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <small class="text-danger" id="emailErr"></small>
                    </div>
                    <div class="form-group mb-3">
                        <label for="password">Password: <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="text-danger" id="passwordErr"></small>

                    </div>
                    <button type="submit" class="btn btn-primary my-3 w-100">Login</button>
                </form>
                <a href="forgot_password.php" class="d-block mt-2">Forgot Password?</a>
                <div id="responseMessage" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        $('#loginForm').on('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting via the browser
            var formData = new FormData(this);
            var isValid = true;

            $('#emailErr').text('');
            $('#passwordErr').text('');

            var email = $('#email').val().trim();
            var password = $('#password').val().trim();

            if (email === '') {
                $('#emailErr').text('Email is required');
                isValid = false;
            } else if (!validateEmail(email)) {
                $('#emailErr').text('Invalid email');
                isValid = false;
            }
            if (password === '') {
                $('#passwordErr').text('Password is required');
                isValid = false;
            }

            if (isValid) {
                $.ajax({
                    url: 'login_ajax.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        
                        if (response.success) {
                            $('#responseMessage').html('<div class="alert alert-success"> ' + response.message + ' Redirecting...</div>');
                            // Optionally redirect or take another action here
                            setTimeout(function() {
                                window.location.href = response.redirectUrl; // Redirect to the specified URL
                            });
                        } else {
                            $('#responseMessage').html('<div class="alert alert-danger">' + response.error + '</div>');
                        }
                    },
                });
            }
        });

        function validateEmail(email) {
            var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
    });
</script>


<?php include_once(__DIR__ . '/footer.php'); ?>