
    // Sidebar functionality
    document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle functionality
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();

    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');

    // Change icon based on state
    const icon = this.querySelector('i');
    if (sidebar.classList.contains('open')) {
    icon.className = 'fas fa-times';
} else {
    icon.className = 'fas fa-bars';
}
});
}

    // Close sidebar when clicking overlay
    if (overlay) {
    overlay.addEventListener('click', function() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');

    // Reset icon
    if (mobileMenuToggle) {
    const icon = mobileMenuToggle.querySelector('i');
    icon.className = 'fas fa-bars';
}
});
}

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
    if (window.innerWidth <= 1024) {
    if (sidebar && !sidebar.contains(event.target) &&
    mobileMenuToggle && !mobileMenuToggle.contains(event.target)) {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
}
}
});

    // Navigation item click handlers - only for mobile sidebar closing
    document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
    // Close sidebar on mobile after clicking (but allow navigation to proceed)
    if (window.innerWidth <= 1024) {
    setTimeout(() => {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');

    // Reset icon
    if (mobileMenuToggle) {
    const icon = mobileMenuToggle.querySelector('i');
    icon.className = 'fas fa-bars';
}
}, 100); // Small delay to allow navigation to start
}
});
});

    // Responsive sidebar handling
    function handleResize() {
    if (window.innerWidth > 1024) {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');

    // Reset icon
    if (mobileMenuToggle) {
    const icon = mobileMenuToggle.querySelector('i');
    icon.className = 'fas fa-bars';
}
}
}

    window.addEventListener('resize', handleResize);
});