document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 991.98 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
        
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991.98) {
                sidebar.classList.remove('show');
            }
        });
    }
});