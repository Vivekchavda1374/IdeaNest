// Loading Component JavaScript
if (typeof LoadingManager === 'undefined') {
class LoadingManager {
    constructor() {
        this.activeLoaders = new Set();
    }

    // Show page loading overlay
    showPageLoading(message = 'Loading...') {
        const loaderId = 'page-loader';
        if (this.activeLoaders.has(loaderId)) return;

        const overlay = document.createElement('div');
        overlay.id = loaderId;
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-content">
                <div class="loading-spinner"></div>
                <p style="margin-top: 1rem; color: #64748b; font-weight: 500;">${message}</p>
            </div>
        `;
        
        document.body.appendChild(overlay);
        this.activeLoaders.add(loaderId);
        
        // Show with animation
        requestAnimationFrame(() => {
            overlay.classList.add('show');
        });
        
        return loaderId;
    }

    // Hide page loading overlay
    hidePageLoading(loaderId = 'page-loader') {
        const overlay = document.getElementById(loaderId);
        if (overlay) {
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.remove();
                this.activeLoaders.delete(loaderId);
            }, 300);
        }
    }

    // Show button loading state
    showButtonLoading(button, text = 'Loading...') {
        if (button.classList.contains('btn-loading')) return;
        
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `
            <div class="loading-dots">
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
                <div class="loading-dot"></div>
            </div>
            <span style="margin-left: 8px;">${text}</span>
        `;
        button.classList.add('btn-loading');
        button.disabled = true;
    }

    // Hide button loading state
    hideButtonLoading(button) {
        if (!button.classList.contains('btn-loading')) return;
        
        button.innerHTML = button.dataset.originalText || button.innerHTML;
        button.classList.remove('btn-loading');
        button.disabled = false;
        delete button.dataset.originalText;
    }

    // Show inline loading for specific elements
    showInlineLoading(element, size = 'small') {
        const spinner = document.createElement('div');
        spinner.className = `loading-spinner loading-${size}`;
        spinner.style.cssText = `
            width: ${size === 'small' ? '20px' : '40px'};
            height: ${size === 'small' ? '20px' : '40px'};
            border-width: ${size === 'small' ? '2px' : '4px'};
            margin: 10px auto;
        `;
        
        element.dataset.originalContent = element.innerHTML;
        element.innerHTML = '';
        element.appendChild(spinner);
    }

    // Hide inline loading
    hideInlineLoading(element) {
        if (element.dataset.originalContent) {
            element.innerHTML = element.dataset.originalContent;
            delete element.dataset.originalContent;
        }
    }
}

// Create global instance
const loadingManager = new LoadingManager();

// Global functions for backward compatibility
function showPageLoading(message) {
    return loadingManager.showPageLoading(message);
}

function hidePageLoading(loaderId) {
    loadingManager.hidePageLoading(loaderId);
}

// Auto-hide loading on page navigation
window.addEventListener('beforeunload', function() {
    loadingManager.activeLoaders.forEach(loaderId => {
        loadingManager.hidePageLoading(loaderId);
    });
});

// Form submission loading
document.addEventListener('submit', function(e) {
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
    
    if (submitBtn && !submitBtn.classList.contains('no-loading')) {
        loadingManager.showButtonLoading(submitBtn, 'Processing...');
    }
});

// AJAX loading for links with data-loading attribute
document.addEventListener('click', function(e) {
    const link = e.target.closest('a[data-loading]');
    if (link) {
        const message = link.dataset.loading || 'Loading...';
        showPageLoading(message);
    }
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LoadingManager;
}

// Make available globally
window.LoadingManager = LoadingManager;
window.loadingManager = loadingManager;
}