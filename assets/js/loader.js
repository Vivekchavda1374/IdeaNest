// Universal Loader JavaScript
if (typeof Loader === 'undefined') {
class Loader {
    constructor() {
        // Check if auto-loader is disabled
        if (window.DISABLE_AUTO_LOADER) {
            return;
        }
        this.createLoader();
    }

    createLoader() {
        if (document.getElementById('universalLoader')) return;
        
        // Wait for DOM to be ready
        if (document.body) {
            const loader = document.createElement('div');
            loader.id = 'universalLoader';
            loader.className = 'loader-overlay';
            loader.innerHTML = `
                <div class="loader">
                    <div class="loader-spinner"></div>
                    <div class="loader-text" id="loaderText">Loading...</div>
                </div>
            `;
            document.body.appendChild(loader);
        } else {
            // If body not ready, wait for DOMContentLoaded
            document.addEventListener('DOMContentLoaded', () => {
                this.createLoader();
            });
        }
    }

    show(text = 'Loading...') {
        const loader = document.getElementById('universalLoader');
        const loaderText = document.getElementById('loaderText');
        if (loader) {
            if (loaderText) loaderText.textContent = text;
            loader.classList.add('active');
        }
    }

    hide() {
        const loader = document.getElementById('universalLoader');
        if (loader) {
            loader.classList.remove('active');
        }
    }

    showButtonLoader(button, text = '') {
        if (!button) return;
        button.classList.add('btn-loading');
        button.disabled = true;
        if (text) {
            const textSpan = button.querySelector('.btn-text') || button;
            textSpan.setAttribute('data-original', textSpan.textContent);
            textSpan.textContent = text;
        }
    }

    hideButtonLoader(button) {
        if (!button) return;
        button.classList.remove('btn-loading');
        button.disabled = false;
        const textSpan = button.querySelector('.btn-text') || button;
        const original = textSpan.getAttribute('data-original');
        if (original) {
            textSpan.textContent = original;
            textSpan.removeAttribute('data-original');
        }
    }
}

// Global loader instance (only if not disabled)
if (!window.DISABLE_AUTO_LOADER) {
    window.loader = new Loader();

    // Auto-show loader for form submissions
    document.addEventListener('DOMContentLoaded', function() {
        // Handle form submissions
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && window.loader) {
                    window.loader.showButtonLoader(submitBtn);
                }
                if (window.loader) {
                    window.loader.show('Submitting...');
                }
            });
        });

        // Handle navigation links
        document.querySelectorAll('a[href]').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.href && !this.href.includes('#') && !this.target && window.loader) {
                    window.loader.show('Loading page...');
                }
            });
        });

        // Hide loader on page load
        window.addEventListener('load', function() {
            if (window.loader) {
                window.loader.hide();
            }
        });
    });
} else {
    // Create dummy loader object for compatibility
    window.loader = {
        show: function() {},
        hide: function() {},
        showButtonLoader: function() {},
        hideButtonLoader: function() {}
    };
}

// Utility functions
function showLoader(text) {
    window.loader.show(text);
}

function hideLoader() {
    window.loader.hide();
}

function showButtonLoader(button, text) {
    window.loader.showButtonLoader(button, text);
}

function hideButtonLoader(button) {
    window.loader.hideButtonLoader(button);
}
}