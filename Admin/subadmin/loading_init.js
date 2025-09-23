// Initialize loading for subadmin pages
document.addEventListener('DOMContentLoaded', function() {
    // Handle form submissions with custom loading messages
    document.querySelectorAll('form[data-loading-message]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const message = this.getAttribute('data-loading-message');
            if (window.loadingManager) {
                window.loadingManager.show(message);
            }
        });
    });

    // Handle navigation links
    document.querySelectorAll('a[href]:not([href^="#"]):not([href^="mailto:"]):not([target="_blank"])').forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!this.hasAttribute('data-no-loading')) {
                showPageLoading();
            }
        });
    });

    // Handle approve/reject buttons specifically
    document.querySelectorAll('button[name="action"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            const action = this.value;
            let message = 'Processing...';
            
            if (action === 'approve') {
                message = 'Approving project...';
            } else if (action === 'reject') {
                message = 'Rejecting project...';
            }
            
            if (window.loadingManager) {
                window.loadingManager.show(message);
            }
        });
    });
});