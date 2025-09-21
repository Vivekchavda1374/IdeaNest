
    document.addEventListener('DOMContentLoaded', function () {
    // Sidebar Toggle Elements
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const mainContent = document.querySelector('.main-content');

    // Toggle Sidebar Function
        function toggleSidebar()
        {
            if (sidebar) {
                sidebar.classList.toggle('show');
            }
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('show');
            }
            if (mainContent) {
                mainContent.classList.toggle('pushed');
            }
        }

    // Close Sidebar Function
        function closeSidebar()
        {
            if (sidebar) {
                sidebar.classList.remove('show');
            }
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('show');
            }
            if (mainContent) {
                mainContent.classList.remove('pushed');
            }
        }

    // Event Listeners
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', closeSidebar);
        }

    // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (event) {
            if (window.innerWidth <= 991.98) {
                const isClickInsideSidebar = sidebar && sidebar.contains(event.target);
                const isToggleButton = sidebarToggle && sidebarToggle.contains(event.target);

                if (!isClickInsideSidebar && !isToggleButton && sidebar && sidebar.classList.contains('show')) {
                    closeSidebar();
                }
            }
        });

    // Handle window resize
        window.addEventListener('resize', function () {
            if (window.innerWidth > 991.98) {
                closeSidebar();
            }
        });
    });