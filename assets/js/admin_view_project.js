
    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('show');
});

    // Auto dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});