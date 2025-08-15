
    // Sidebar toggle for mobile
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.querySelector('.sidebar').classList.toggle('show');
    document.querySelector('.main-content').classList.toggle('pushed');
});

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
    var alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
    var bsAlert = new bootstrap.Alert(alert);
    bsAlert.close();
});
}, 5000);

    // Confirm removal action
    function confirmRemoval() {
    return confirm('Are you sure you want to remove this subadmin? This action cannot be undone.');
}

    // Tab persistence
    document.addEventListener('DOMContentLoaded', function() {
    // Get active tab from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');

    if (activeTab) {
    const tabButton = document.getElementById(activeTab + '-tab');
    if (tabButton) {
    const tab = new bootstrap.Tab(tabButton);
    tab.show();
}
}

    // Update URL when tab changes
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(function(button) {
    button.addEventListener('shown.bs.tab', function(e) {
    const tabId = e.target.getAttribute('data-bs-target').substring(1);
    const url = new URL(window.location);
    url.searchParams.set('tab', tabId);
    window.history.replaceState({}, '', url);
});
});
});

    // Auto-refresh ticket stats every 30 seconds
    setInterval(function() {
    if (document.querySelector('#tickets.active')) {
    location.reload();
}
}, 30000);

    // Form validation for ticket responses
    document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        const textarea = form.querySelector('textarea[name="admin_response"]');
        if (textarea && textarea.value.trim().length < 10) {
            e.preventDefault();
            alert('Please provide a more detailed response (at least 10 characters).');
            textarea.focus();
        }
    });
});
