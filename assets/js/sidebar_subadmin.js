
    // Sidebar toggle functionality
    document.getElementById('sidebarToggle').addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');
});

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');

    if (window.innerWidth <= 768 &&
    !sidebar.contains(e.target) &&
    !toggle.contains(e.target) &&
    sidebar.classList.contains('show')) {
    sidebar.classList.remove('show');
}
});

    // Handle window resize
    window.addEventListener('resize', function() {
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth > 768) {
    sidebar.classList.remove('show');
}
});
