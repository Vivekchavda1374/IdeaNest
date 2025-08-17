// Fixed Sidebar JavaScript for All Projects
document.addEventListener('DOMContentLoaded', function() {
    console.log('Sidebar script loaded');

    // Get elements
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    const body = document.body;

    console.log('Elements found:', {
        mobileMenuToggle: !!mobileMenuToggle,
        sidebar: !!sidebar,
        overlay: !!overlay
    });

    // Function to open sidebar
    function openSidebar() {
        if (sidebar) {
            sidebar.classList.add('open');
            console.log('Sidebar opened');
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
    function closeSidebar() {
        if (sidebar) {
            sidebar.classList.remove('open');
            console.log('Sidebar closed');
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
    function toggleSidebar() {
        if (sidebar && sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    // Mobile menu toggle click event
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Mobile menu toggle clicked');
            toggleSidebar();
        });
    }

    // Overlay click event to close sidebar
    if (overlay) {
        overlay.addEventListener('click', function() {
            console.log('Overlay clicked');
            closeSidebar();
        });
    }

    // Close sidebar when clicking outside
    document.addEventListener('click', function(event) {
        // Only apply on mobile/tablet
        if (window.innerWidth <= 1024) {
            if (sidebar && sidebar.classList.contains('open')) {
                // Check if click is outside sidebar and toggle button
                if (!sidebar.contains(event.target) &&
                    !mobileMenuToggle.contains(event.target)) {
                    console.log('Clicked outside sidebar');
                    closeSidebar();
                }
            }
        }
    });

    // Prevent sidebar content clicks from closing sidebar
    if (sidebar) {
        sidebar.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // Close sidebar when clicking nav links on mobile
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Only close on mobile/tablet
            if (window.innerWidth <= 1024) {
                console.log('Nav item clicked on mobile');
                // Small delay to allow navigation to start
                setTimeout(() => {
                    closeSidebar();
                }, 150);
            }
        });
    });

    // Handle window resize
    function handleResize() {
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
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && window.innerWidth <= 1024) {
            closeSidebar();
        }
    });

    // Ensure proper initial state
    handleResize();

    // Debug function to test sidebar
    window.testSidebar = function() {
        console.log('Testing sidebar...');
        toggleSidebar();
    };

    console.log('Sidebar JavaScript initialized successfully');
});