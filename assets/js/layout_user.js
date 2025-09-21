// Fixed Sidebar JavaScript for All Projects
document.addEventListener('DOMContentLoaded', function () {
    // Get elements
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const body = document.body;

    // Function to open sidebar
    function openSidebar()
    {
        if (sidebar) {
            sidebar.classList.add('open');
        }
        if (overlay) {
            overlay.classList.add('active');
        }
        body.classList.add('sidebar-open');

        // Change hamburger to X
        if (mobileMenuToggle) {
            const icon = mobileMenuToggle.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-times';
            }
        }
    }

    // Function to close sidebar
    function closeSidebar()
    {
        if (sidebar) {
            sidebar.classList.remove('open');
        }
        if (overlay) {
            overlay.classList.remove('active');
        }
        body.classList.remove('sidebar-open');

        // Change X back to hamburger
        if (mobileMenuToggle) {
            const icon = mobileMenuToggle.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-bars';
            }
        }
    }

    // Function to toggle sidebar
    function toggleSidebar()
    {
        if (sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    // Mobile menu toggle click event
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Overlay click event to close sidebar
    if (overlay) {
        overlay.addEventListener('click', function () {
            closeSidebar();
        });
    }

    // Close sidebar when clicking outside
    document.addEventListener('click', function (event) {
        // Only apply on mobile/tablet
        if (window.innerWidth <= 1024) {
            if (sidebar && sidebar.classList.contains('open')) {
                // Check if click is outside sidebar and toggle button
                if (!sidebar.contains(event.target) &&
                    !mobileMenuToggle.contains(event.target)) {
                    closeSidebar();
                }
            }
        }
    });

    // Prevent sidebar content clicks from closing sidebar
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    // Close sidebar when clicking nav links on mobile
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            // Only close on mobile/tablet
            if (window.innerWidth <= 1024) {
                // Small delay to allow navigation to start
                setTimeout(() => {
                    closeSidebar();
                }, 150);
            }
        });
    });

    // Handle window resize
    function handleResize()
    {
        if (window.innerWidth > 1024) {
            // Desktop view - ensure sidebar is visible and overlay is hidden
            if (sidebar) {
                sidebar.classList.remove('open');
            }
            if (overlay) {
                overlay.classList.remove('active');
            }
            body.classList.remove('sidebar-open');

            // Reset toggle button icon
            if (mobileMenuToggle) {
                const icon = mobileMenuToggle.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-bars';
                }
            }
        }
    }

    window.addEventListener('resize', handleResize);

    // Handle escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && window.innerWidth <= 1024) {
            closeSidebar();
        }
    });

    // Ensure proper initial state
    handleResize();

    // Debug function to test sidebar
    window.testSidebar = function () {
        toggleSidebar();
    };

});