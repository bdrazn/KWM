// Booking form handling
document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.getElementById('bookingForm');
    const submitBtn = document.getElementById('submitBtn');
    const buttonText = submitBtn.querySelector('.button-text');
    const spinner = submitBtn.querySelector('.spinner-border');

    // Form submission handler
    bookingForm.addEventListener('submit', function(e) {
        console.log('Form submission started');
        
        // Log form data
        const formData = new FormData(this);
        formData.forEach((value, key) => {
            console.log(`${key}: ${value}`);
        });
        
        // Disable button and show spinner
        submitBtn.disabled = true;
        buttonText.textContent = 'Processing...';
        spinner.classList.remove('d-none');
    });

    // Error handling from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        handleBookingError(urlParams.get('error'));
    }
});

// Error handling function
function handleBookingError(errorType) {
    console.log('Error parameter found:', errorType);
    let errorMessage = '';
    
    switch(errorType) {
        case 'missing_fields':
            errorMessage = 'Please fill in all required fields.';
            break;
        case 'invalid_date':
            errorMessage = 'Please select a future date and time.';
            break;
        case 'time_taken':
            errorMessage = 'This time slot is already booked. Please select another time.';
            break;
        case 'system':
            errorMessage = 'A system error occurred. Please try again later.';
            break;
        case 'service_unavailable':
            errorMessage = 'This service is currently unavailable.';
            break;
        case 'booking_limit':
            errorMessage = 'You have reached the maximum number of bookings for this service.';
            break;
        default:
            errorMessage = 'An error occurred. Please contact support if the problem persists.';
    }
    
    if (typeof toastr !== 'undefined') {
        toastr.error(errorMessage);
    } else {
        alert(errorMessage);
    }
}

// Global error handler
window.onerror = function(msg, url, lineNo, columnNo, error) {
    console.error('Error:', msg);
    console.error('URL:', url);
    console.error('Line:', lineNo);
    return false;
};