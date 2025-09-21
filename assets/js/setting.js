
    // Sidebar toggle for mobile
    document.getElementById('sidebarToggle').addEventListener('click', function () {
        document.querySelector('.sidebar').classList.toggle('show');
        document.querySelector('.main-content').classList.toggle('pushed');
    });

    // Auto-save form data to localStorage
    document.querySelectorAll('input, select, textarea').forEach(function (element) {
        element.addEventListener('change', function () {
            const formData = new FormData(document.querySelector('form'));
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            localStorage.setItem('settingsFormData', JSON.stringify(data));
        });
    });

    // Load saved form data on page load
    window.addEventListener('load', function () {
        const savedData = localStorage.getItem('settingsFormData');
        if (savedData) {
            const data = JSON.parse(savedData);
            for (let key in data) {
                const element = document.querySelector(`[name = "${key}"]`);
                if (element) {
                    element.value = data[key];
                }
            }
        }
    });