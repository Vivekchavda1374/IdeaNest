/**
 * IdeaNest AJAX Core Library
 * Centralized AJAX functionality for seamless user experience
 */

class AjaxCore {
    constructor() {
        this.baseUrl = window.location.origin;
        this.init();
    }

    init() {
        this.setupGlobalHandlers();
        this.setupFormInterceptors();
        this.setupLinkInterceptors();
    }

    // Show loading indicator
    showLoading(element = null) {
        if (element) {
            const spinner = document.createElement('div');
            spinner.className = 'ajax-spinner';
            spinner.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            element.appendChild(spinner);
        }
    }

    // Hide loading indicator
    hideLoading(element = null) {
        if (element) {
            const spinner = element.querySelector('.ajax-spinner');
            if (spinner) spinner.remove();
        }
    }

    // Generic AJAX request
    async request(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = { ...defaults, ...options };

        try {
            const response = await fetch(url, config);
            const contentType = response.headers.get('content-type');
            
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            return await response.text();
        } catch (error) {
            console.error('AJAX Error:', error);
            this.showToast('Network error occurred', 'danger');
            throw error;
        }
    }

    // POST request helper
    async post(url, data) {
        const formData = new FormData();
        for (const key in data) {
            formData.append(key, data[key]);
        }

        return this.request(url, {
            method: 'POST',
            body: formData
        });
    }

    // GET request helper
    async get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        return this.request(fullUrl);
    }

    // Setup global AJAX handlers
    setupGlobalHandlers() {
        // Handle AJAX forms
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('ajax-form')) {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            }
        });

        // Handle AJAX links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('.ajax-link');
            if (link) {
                e.preventDefault();
                this.handleLinkClick(link);
            }
        });
    }

    // Handle form submission via AJAX
    async handleFormSubmit(form) {
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn ? submitBtn.innerHTML : '';
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }

        try {
            const formData = new FormData(form);
            const response = await this.request(form.action || window.location.href, {
                method: form.method || 'POST',
                body: formData
            });

            if (response.success) {
                this.showToast(response.message || 'Success!', 'success');
                
                // Trigger custom event
                form.dispatchEvent(new CustomEvent('ajax:success', { detail: response }));
                
                // Reset form if specified
                if (form.dataset.resetOnSuccess !== 'false') {
                    form.reset();
                }
            } else {
                this.showToast(response.message || 'Operation failed', 'danger');
                form.dispatchEvent(new CustomEvent('ajax:error', { detail: response }));
            }
        } catch (error) {
            this.showToast('An error occurred', 'danger');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    }

    // Handle link click via AJAX
    async handleLinkClick(link) {
        const url = link.href;
        const target = link.dataset.target;

        try {
            const response = await this.get(url);
            
            if (target) {
                const targetElement = document.querySelector(target);
                if (targetElement) {
                    targetElement.innerHTML = response;
                }
            }

            link.dispatchEvent(new CustomEvent('ajax:complete', { detail: response }));
        } catch (error) {
            console.error('Link AJAX error:', error);
        }
    }

    // Setup form interceptors
    setupFormInterceptors() {
        // Auto-convert forms with data-ajax attribute
        document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
            form.classList.add('ajax-form');
        });
    }

    // Setup link interceptors
    setupLinkInterceptors() {
        // Auto-convert links with data-ajax attribute
        document.querySelectorAll('a[data-ajax="true"]').forEach(link => {
            link.classList.add('ajax-link');
        });
    }

    // Show toast notification
    showToast(message, type = 'info') {
        const existingToasts = document.querySelectorAll('.ajax-toast');
        existingToasts.forEach(toast => toast.remove());

        const toast = document.createElement('div');
        toast.className = `ajax-toast alert alert-${type} alert-dismissible fade show`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        `;

        const icons = {
            success: 'check-circle',
            danger: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };

        toast.innerHTML = `
            <i class="fas fa-${icons[type] || icons.info} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }

    // Load content into element
    async loadContent(url, targetSelector, showLoader = true) {
        const target = document.querySelector(targetSelector);
        if (!target) return;

        if (showLoader) {
            target.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
        }

        try {
            const content = await this.get(url);
            target.innerHTML = content;
            target.dispatchEvent(new CustomEvent('content:loaded'));
        } catch (error) {
            target.innerHTML = '<div class="alert alert-danger">Failed to load content</div>';
        }
    }

    // Pagination handler
    setupPagination(containerSelector) {
        const container = document.querySelector(containerSelector);
        if (!container) return;

        container.addEventListener('click', async (e) => {
            const pageLink = e.target.closest('.page-link');
            if (!pageLink) return;

            e.preventDefault();
            const url = pageLink.href;
            await this.loadContent(url, containerSelector);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Filter handler
    setupFilters(formSelector, resultSelector) {
        const form = document.querySelector(formSelector);
        if (!form) return;

        form.addEventListener('change', async () => {
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            const url = `${form.action}?${params.toString()}`;
            await this.loadContent(url, resultSelector);
        });
    }
}

// Initialize AJAX Core
const ajaxCore = new AjaxCore();

// Export for use in other scripts
window.AjaxCore = AjaxCore;
window.ajaxCore = ajaxCore;

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .ajax-spinner {
        display: inline-block;
        margin-left: 10px;
    }

    .ajax-toast {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .ajax-loading {
        opacity: 0.6;
        pointer-events: none;
    }
`;
document.head.appendChild(style);
