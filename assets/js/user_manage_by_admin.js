
    // JavaScript to maintain the active tab and search parameters after form submission
    document.addEventListener('DOMContentLoaded', function () {
    // Get the stored tab from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');

        if (tabParam) {
        // If tab parameter exists in URL, activate that tab
            const tabToActivate = document.querySelector('#userTabs button[data-bs-target="#' + tabParam + '-users"]');
            if (tabToActivate) {
                const tab = new bootstrap.Tab(tabToActivate);
                tab.show();
            }
        }

    // Add event listeners to tabs to store the active tab
        const tabs = document.querySelectorAll('#userTabs button');
        tabs.forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function (event) {
                const targetId = event.target.getAttribute('data-bs-target').replace('#', '').replace('-users', '');
            // Update URL without refreshing page, preserving search term
                const searchParam = urlParams.get('search') ? '&search=' + urlParams.get('search') : '';
                history.replaceState(null, null, '?tab=' + targetId + searchParam);
            });
        });

    // Sidebar toggle functionality for mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function () {
                sidebar.classList.toggle('show');
                mainContent.classList.toggle('pushed');
            });
        }
    });