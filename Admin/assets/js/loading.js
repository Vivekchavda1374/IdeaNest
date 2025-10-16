// Loading JavaScript
function showPageLoading() {
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.style.display = 'flex';
    loadingOverlay.innerHTML = `
        <div class="text-center">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading...</div>
        </div>
    `;
    document.body.appendChild(loadingOverlay);
}

function hidePageLoading() {
    const loadingOverlay = document.querySelector('.loading-overlay');
    if (loadingOverlay) {
        loadingOverlay.remove();
    }
}

// Auto-hide loading on page load
window.addEventListener('load', function() {
    hidePageLoading();
});