<?php
$PAGE_TITLE = "Profile";
include_once(__DIR__ . "/header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $conn->prepare("SELECT first_name, last_name, email, dob, profile_picture FROM users_info WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get all available skills
$stmt = $conn->prepare("SELECT id, skill_name FROM skills WHERE is_active = 1 ORDER BY id");
$stmt->execute();
$result = $stmt->get_result();
$availableSkills = [];
while ($row = $result->fetch_assoc()) {
    $availableSkills[] = $row;
}
$stmt->close();

// Get user skills (IDs)
$stmt = $conn->prepare("SELECT skill_id FROM user_skills WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userSkillIds = [];
while ($row = $result->fetch_assoc()) {
    $userSkillIds[] = (int)$row['skill_id']; // Ensure integer type
}
$stmt->close();


?>



<div class="container rounded bg-white mt-5">
    <div class="row">
        <div class="col-md-4 border-right">
            <div class="d-flex flex-column align-items-center text-center p-3 py-5">
                <img class="rounded-circle mt-5" id="profileImage"
                    src="<?php echo htmlspecialchars($user['profile_picture'] ?: 'resources/images/default.png'); ?>"
                    width="180" height="180" alt="Profile Photo">
                <span class="font-weight-bold" id="displayName">
                    <?php echo isset($user['first_name'], $user['last_name']) ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : ''; ?>
                </span>
                <span class="text-black-50"><?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?></span>
            </div>
        </div>
        <div class="col-md-8">
            <div class="p-3 py-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="text-right">Profile</h3>
                    <a href="change_password.php" class="btn btn-primary profile-button">Change Password</a>
                </div>

                <div id="responseMessage"></div>

                <form id="profileForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label for="firstName" class="form-label">First Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                value="<?php echo isset($user['first_name']) ? htmlspecialchars($user['first_name']) : ''; ?>"
                                id="firstName" name="firstName" required>
                            <small class="text-danger" id="firstNameErr"></small>
                        </div>
                        <div class="col-md-6">
                            <label for="lastName" class="form-label">Last Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control"
                                value="<?php echo isset($user['last_name']) ? htmlspecialchars($user['last_name']) : ''; ?>"

                                id="lastName" name="lastName" required>
                            <small class="text-danger" id="lastNameErr"></small>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control"
                                value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>"
                                id="email" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" placeholder="Date of Birth"
                                value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>"
                                id="dob" name="dob">
                            <small class="text-danger" id="dobErr"></small>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label for="profilePhoto" class="form-label">Profile Photo</label>
                            <input type="file" class="form-control" id="profilePhoto" name="profilePhoto"
                                accept="image/jpeg,image/jpg,image/png,image/gif">
                            <small class="text-muted">Max size: 5MB. Allowed: JPG, PNG, GIF</small>
                            <small class="text-danger" id="profilePhotoErr"></small>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <fieldset class="mb-3">
                                <legend class="col-form-label">Skills</legend>
                                <?php foreach ($availableSkills as $skill): ?>
                                    <div class="form-check form-check-inline">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="skill<?= $skill['id'] ?>"
                                            name="skills[]"
                                            value="<?= $skill['id'] ?>"
                                            <?= in_array($skill['id'], $userSkillIds) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="skill<?= $skill['id'] ?>">
                                            <?= ucfirst($skill['skill_name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </fieldset>
                        </div>
                    </div>


                    <div class="mt-5 text-right">
                        <button class="btn btn-primary profile-button w-100" type="submit" id="saveBtn">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Client-side validation functions
        function validateName(name, fieldName) {
            if (!name.trim()) {
                return fieldName + ' is required';
            }
            if (name.trim().length < 2 || name.trim().length > 50) {
                return fieldName + ' must be between 2 and 50 characters';
            }
            if (!/^[a-zA-Z\s]+$/.test(name.trim())) {
                return fieldName + ' can only contain letters and spaces';
            }
            return '';
        }

        function validateDateOfBirth(dob) {
            if (!dob) return '';

            const dobDate = new Date(dob);
            const now = Date.now();
            const ageInMs = now - dobDate.getTime();

            const ageInYears = ageInMs / (1000 * 60 * 60 * 24 * 365.25); // Approximate year

            if (ageInYears < 13 || ageInYears > 120) {
                return 'Age must be between 13 and 120 years';
            }

            return '';
        }


        function validateFile(file) {
            if (!file) return '';

            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                return 'Only JPG, PNG, and GIF files are allowed';
            }

            if (file.size > 5 * 1024 * 1024) {
                return 'File size must be less than 5MB';
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
        $('#profileForm').on('submit', function(e) {
            e.preventDefault();

            clearErrors();
            let isValid = true;

            // Validate all fields
            const firstNameError = validateName($('#firstName').val(), 'First name');
            if (firstNameError) {
                showFieldError('firstName', firstNameError);
                isValid = false;
            }

            const lastNameError = validateName($('#lastName').val(), 'Last name');
            if (lastNameError) {
                showFieldError('lastName', lastNameError);
                isValid = false;
            }

            const dobError = validateDateOfBirth($('#dob').val());
            if (dobError) {
                showFieldError('dob', dobError);
                isValid = false;
            }

            const file = $('#profilePhoto')[0].files[0];
            const fileError = validateFile(file);
            if (fileError) {
                showFieldError('profilePhoto', fileError);
                isValid = false;
            }

            if (!isValid) {
                $('#responseMessage').html('<div class="alert alert-danger">Please fix the errors below.</div>');
                return;
            }

            // Show loading state
            $('#saveBtn').prop('disabled', true).text('Saving...');
            $('.container').addClass('loading');

            // Submit form
            const formData = new FormData(this);

            $.ajax({
                url: 'profile-ajax.php',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response.success) {
                        $('#responseMessage').html('<div class="alert alert-success">' + response.message + '</div>');

                        // Update profile image and name if changed
                        if (response.profile_picture) {
                            $('#profileImage').attr('src', response.profile_picture);
                        }
                        $('#displayName').text($('#firstName').val() + ' ' + $('#lastName').val());


                        // Clear file input
                        $('#profilePhoto').val('');
                    } else {
                        $('#responseMessage').html('<div class="alert alert-danger">' + response.message + '</div>');
                    }
                },

                complete: function() {
                    $('#saveBtn').prop('disabled', false).text('Save');
                    $('.container').removeClass('loading');

                    // Scroll to top to show message
                    $('html, body').animate({
                        scrollTop: 0
                    }, 300);
                }
            });
        });
    });
</script>

<?php
include_once(__DIR__ . "/footer.php");
?>