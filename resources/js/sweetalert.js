// SweetAlert2 Global Configuration and Helpers

// Initialize SweetAlert with default settings
window.Swal = Swal.mixin({
    confirmButtonColor: '#3b82f6',
    cancelButtonColor: '#6b7280',
    denyButtonColor: '#ef4444',
    buttonsStyling: true,
    allowOutsideClick: false,
    allowEscapeKey: true,
});

// Helper function for confirmation dialogs
window.confirmAction = function(options) {
    const defaults = {
        title: 'Are you sure?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, proceed',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
    };

    const config = Object.assign({}, defaults, options);
    
    return Swal.fire(config);
};

// Handle Laravel flash messages
document.addEventListener('DOMContentLoaded', function() {
    // Success messages
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '{{ session('success') }}',
            confirmButtonColor: '#10b981',
            timer: 3000,
            timerProgressBar: true,
        });
    @endif

    // Error messages
    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{{ session('error') }}',
            confirmButtonColor: '#ef4444',
        });
    @endif

    // Warning messages
    @if(session('warning'))
        Swal.fire({
            icon: 'warning',
            title: 'Warning!',
            text: '{{ session('warning') }}',
            confirmButtonColor: '#f59e0b',
        });
    @endif

    // Info messages
    @if(session('info'))
        Swal.fire({
            icon: 'info',
            title: 'Information',
            text: '{{ session('info') }}',
            confirmButtonColor: '#3b82f6',
        });
    @endif

    // Validation errors
    @if($errors->any())
        const errorMessages = @json($errors->all());
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            html: '<ul style="text-align: left; margin-top: 10px;">' + 
                  errorMessages.map(msg => '<li>' + msg + '</li>').join('') + 
                  '</ul>',
            confirmButtonColor: '#ef4444',
        });
    @endif
});

// Form confirmation helper
window.confirmFormSubmit = function(event, options) {
    event.preventDefault();
    const form = event.target;
    
    confirmAction(options).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
};





