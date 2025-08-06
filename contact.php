<?php 
$PAGE_TITLE = "Contact Us";
include_once(__DIR__ . '/header.php'); 
// Generate a CSRF token if one does not exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<div class="container mt-5">
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-6">
            <h2>Contact</h2>
            <p>Feel free to reach out to us through any of the methods below:</p>
            <p>Email: <a href="mailto:contact@orientaloutsourcing.com">contact@orientaloutsourcing.com</a></p>
            <p>Phone: <a href="tel:+11234567890">(123) 456-7890</a></p>
            <p>Address: SCO 64-b, City Heart, Kharar, Punjab, India, 140301</p>
            <img src="https://orientaloutsourcing.com/images/contact.png" class="img-fluid" alt="Contact Image">
        </div>
        <!-- Right Column -->
        <div class="col-md-6">
            <h2>Send Us a Message</h2>
            <p><small class="text-muted">* Fields are mandatory</small></p>
            <form id="contactForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <div class="form-group mb-3">
                    <label for="name">Name: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name">
                    <small class="text-danger" id="nameErr"></small>
                </div>
                <div class="form-group mb-3">
                    <label for="email">Email: <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email">
                    <small class="text-danger" id="emailErr"></small>
                </div>
                <div class="form-group mb-3">
                    <label for="message">Message: <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="message" name="message" rows="5"></textarea>
                    <small class="text-danger" id="messageErr"></small>
                </div>
                <div class="form-group mb-3">
                    <label for="file">Upload File:</label>
                    <input type="file" class="form-control" id="file" name="file">
                    <small class="text-danger" id="fileErr"></small>
                </div>
                <button type="submit" class="btn btn-primary w-100">Submit</button>
            </form>
            <div id="responseMessage" class="mt-3"></div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#contactForm').on('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting via the browser
            var formData = new FormData(this);
            var isValid = true;

            $('#nameErr').text('');
            $('#emailErr').text('');
            $('#messageErr').text('');
            $('#fileErr').text('');

            var name = $('#name').val().trim();
            var email = $('#email').val().trim();
            var message = $('#message').val().trim();
            var file = $('#file')[0].files[0];

            if (name === '') {
                $('#nameErr').text('Name is required');
                isValid = false;
            }
            if (email === '') {
                $('#emailErr').text('Email is required');
                isValid = false;
            } else if (!validateEmail(email)) {
                $('#emailErr').text('Invalid email format');
                isValid = false;
            }
            if (message === '') {
                $('#messageErr').text('Message is required');
                isValid = false;
            }
            if (file) {
                var allowedExtensions = /(\.docx|\.pdf|\.xlsx)$/i;
                if (!allowedExtensions.exec(file.name)) {
                    $('#fileErr').text('Invalid file type. Only DOCX, PDF, and XLSX files are allowed.');
                    isValid = false;
                } else if (file.size > 5 * 1024 * 1024) { // 5MB max file size
                    $('#fileErr').text('File size must be less than 5MB.');
                    isValid = false;
                }
            }

            if (isValid) {
                $.ajax({
                    url: 'save_contact_ajax.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        $('#responseMessage').html('<div class="alert alert-success">Your message has been successfully sent. We will get back to you shortly.</div>');
                        $('#contactForm')[0].reset();
                    },
                    error: function() {
                        $('#responseMessage').html('<div class="alert alert-danger">There was an error sending your message. Please try again later.</div>');
                    }
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