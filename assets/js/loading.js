// Global Loading System
class LoadingManager {
    constructor() {
        this.createLoadingOverlay();
        this.setupFormHandlers();
    }

    createLoadingOverlay() {
        if (document.getElementById('globalLoading')) return;
        
        const overlay = document.createElement('div');
        overlay.id = 'globalLoading';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <p class="loading-text" id="loadingText">Loading...</p>
                <div class="progress-loading">
                    <div class="progress-loading-bar"></div>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    show(message = 'Loading...', type = 'default') {
        const overlay = document.getElementById('globalLoading');
        const text = document.getElementById('loadingText');
        const content = overlay.querySelector('.loading-content');
        
        if (text) text.textContent = message;
        
        // Apply type-specific styling
        content.className = 'loading-content';
        if (type === 'email') {
            content.classList.add('email-loading');
        }
        
        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    hide() {
        const overlay = document.getElementById('globalLoading');
        if (overlay) {
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    setupFormHandlers() {
        // Auto-handle form submissions
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (form.tagName === 'FORM' && !form.hasAttribute('data-no-loading')) {
                this.handleFormSubmission(form);
            }
        });

        // Auto-handle AJAX requests
        this.interceptFetch();
    }

    handleFormSubmission(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            this.setButtonLoading(submitBtn, true);
        }
        
        // Determine loading message based on form action
        let message = 'Processing...';
        if (form.action.includes('email') || form.id.includes('email')) {
            message = 'Sending email...';
            this.show(message, 'email');
        } else if (form.action.includes('upload') || form.enctype === 'multipart/form-data') {
            message = 'Uploading files...';
            this.show(message);
        } else {
            this.show(message);
        }
    }

    setButtonLoading(button, loading) {
        if (loading) {
            button.dataset.originalText = button.textContent;
            button.textContent = 'Loading...';
            button.classList.add('btn-loading');
            button.disabled = true;
        } else {
            button.textContent = button.dataset.originalText || button.textContent;
            button.classList.remove('btn-loading');
            button.disabled = false;
        }
    }

    interceptFetch() {
        const originalFetch = window.fetch;
        const self = this;
        
        window.fetch = function(...args) {
            const url = args[0];
            const options = args[1] || {};
            
            // Don't show loading for certain requests
            if (options.noLoading || url.includes('api/')) {
                return originalFetch.apply(this, args);
            }
            
            // Determine loading message
            let message = 'Loading...';
            if (url.includes('email') || url.includes('send')) {
                message = 'Sending...';
                self.show(message, 'email');
            } else {
                self.show(message);
            }
            
            return originalFetch.apply(this, args)
                .then(response => {
                    self.hide();
                    return response;
                })
                .catch(error => {
                    self.hide();
                    throw error;
                });
        };
    }
}

// Email-specific loading functions
function showEmailLoading(message = 'Sending email...') {
    window.loadingManager.show(message, 'email');
}

function hideEmailLoading() {
    window.loadingManager.hide();
}

// Form-specific loading
function showFormLoading(form, message = 'Processing...') {
    form.classList.add('form-loading');
    window.loadingManager.show(message);
}

function hideFormLoading(form) {
    form.classList.remove('form-loading');
    window.loadingManager.hide();
}

// Button-specific loading
function setButtonLoading(button, loading, loadingText = 'Loading...') {
    if (loading) {
        button.dataset.originalText = button.textContent;
        button.textContent = loadingText;
        button.classList.add('btn-loading');
        button.disabled = true;
    } else {
        button.textContent = button.dataset.originalText || button.textContent;
        button.classList.remove('btn-loading');
        button.disabled = false;
    }
}

// Initialize loading manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.loadingManager = new LoadingManager();
});

// Page loading for navigation
function showPageLoading() {
    const pageLoading = document.createElement('div');
    pageLoading.id = 'pageLoading';
    pageLoading.className = 'page-loading';
    pageLoading.innerHTML = `
        <div class="page-loading-content">
            <div class="page-loading-spinner"></div>
            <p class="page-loading-text">Loading page...</p>
        </div>
    `;
    document.body.appendChild(pageLoading);
}

function hidePageLoading() {
    const pageLoading = document.getElementById('pageLoading');
    if (pageLoading) {
        pageLoading.remove();
    }
}

// Auto-hide page loading when page loads
window.addEventListener('load', hidePageLoading);