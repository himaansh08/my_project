// Global AJAX Loading and Error Handler
let hoverEnabled = false; // Add this if you need to control notifications

jQuery(document).bind("ajaxStart", function () {
    jQuery(".ajax_loading").show();
}).bind("ajaxStop", function () {
    jQuery(".ajax_loading").hide();
});

$(document).ajaxError(function (event, jqXHR, ajaxSettings, thrownError) {
    if (!hoverEnabled) {
        let errorMessage = '';
        
        if (jqXHR.status === 0) {
            errorMessage = "Not connected. Verify Network.";
        }
        else if (jqXHR.status == 401) {
            errorMessage = "Session Expired! Please login";
            showErrorMessage(errorMessage);
            // Auto redirect to login after session expiry
            setTimeout(() => window.location.href = 'login.php', 2000);
            return;
        }
        else if (jqXHR.status == 404) {
            errorMessage = "Requested page not found. [404]";
        } else if (jqXHR.status == 500) {
            errorMessage = "Internal Server Error [500].";
        } else if (thrownError === "parsererror") {
            errorMessage = "Requested JSON parse failed.";
        } else if (thrownError === "timeout") {
            errorMessage = "Time out error.";
        } else if (thrownError === "abort") {
            errorMessage = "Ajax request aborted.";
        } else {
            // Try to parse JSON error response for custom messages
            try {
                const errorResponse = JSON.parse(jqXHR.responseText);
                if (errorResponse.error) {
                    errorMessage = errorResponse.error;
                } else {
                    errorMessage = "Uncaught Error. " + jqXHR.responseText;
                }
            } catch (e) {
                errorMessage = "Uncaught Error. " + jqXHR.responseText;
            }
        }
        
        showErrorMessage(errorMessage);
    }
    $(".ajax_loading").hide();
});

// Function to show error messages
function showErrorMessage(message, container = '#responseMessage') {
    if ($(container).length) {
        $(container).html(`
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
    } else {
        // Fallback to alert if no container found
        alert(message);
    }
}

// Function to show success messages
function showSuccessMessage(message, container = '#responseMessage') {
    if ($(container).length) {
        $(container).html(`
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
    }
}

// Utility function for email validation
function validateEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}