
    document.addEventListener('DOMContentLoaded', function() {
    // Lazy Loading Implementation
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const projectsGrid = document.getElementById('projectsGrid');

    // Load more projects function
    function loadMoreProjects(page) {
    if (loadMoreBtn) {
    loadMoreBtn.style.display = 'none';
}
    loadingSpinner.style.display = 'flex';

    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('page', page);
    urlParams.set('ajax', '1');

    // Use the current page URL for the request
    const baseUrl = window.location.pathname;

    fetch(baseUrl + '?' + urlParams.toString(), {
    method: 'GET',
    headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json'
}
})
    .then(response => {
    if (!response.ok) {
    throw new Error('Network response was not ok: ' + response.status);
}
    return response.json();
})
    .then(data => {
    if (data.success && data.html) {
    // Create temporary container
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = data.html;

    // Add new cards to grid with animation
    const newCards = tempDiv.querySelectorAll('.project-card');
    newCards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    projectsGrid.appendChild(card);

    // Animate in
    setTimeout(() => {
    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    card.style.opacity = '1';
    card.style.transform = 'translateY(0)';
}, index * 100);
});

    // Set up event listeners for new view details buttons
    setupViewDetailsButtons();

    // Update load more button
    if (data.hasMore) {
    loadMoreBtn.setAttribute('data-page', data.nextPage);
    loadMoreBtn.style.display = 'block';
} else {
    if (loadMoreBtn) {
    loadMoreBtn.style.display = 'none';
}
}

    // Update pagination info
    const paginationInfo = document.querySelector('.text-center.mt-3.text-muted small');
    if (paginationInfo && data.paginationInfo) {
    paginationInfo.innerHTML = data.paginationInfo;
}
} else {
    throw new Error(data.message || 'Failed to load projects');
}
})
    .catch(error => {
    console.error('Error loading projects:', error);
    alert('Failed to load more projects: ' + error.message);
    if (loadMoreBtn) {
    loadMoreBtn.style.display = 'block';
}
})
    .finally(() => {
    loadingSpinner.style.display = 'none';
});
}


    // Setup view details buttons for dynamically loaded content
    function setupViewDetailsButtons() {
    const viewBtns = document.querySelectorAll('.view-details-btn');
    viewBtns.forEach(btn => {
    if (!btn.hasAttribute('data-listener-added')) {
    btn.setAttribute('data-listener-added', 'true');
    btn.addEventListener('click', function() {
    const projectId = this.getAttribute('data-project-id');
    // For now, just show an alert with project ID
    // You can implement modal functionality here
    alert('View details for project ID: ' + projectId);
    // Or redirect to a details page
    // window.location.href = 'project_details.php?id=' + projectId;
});
}
});
}

    // Load more button click handler
    if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function() {
    const nextPage = parseInt(this.getAttribute('data-page'));
    loadMoreProjects(nextPage);
});
}

    // Setup initial view details buttons
    setupViewDetailsButtons();

    // Infinite scroll (optional - uncomment to enable)
    /*
    let isLoading = false;
    window.addEventListener('scroll', function() {
        if (isLoading) return;

        const scrollTop = document.documentElement.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight;
        const clientHeight = document.documentElement.clientHeight;

        if (scrollTop + clientHeight >= scrollHeight - 1000) {
            if (loadMoreBtn && loadMoreBtn.style.display !== 'none') {
                isLoading = true;
                const nextPage = parseInt(loadMoreBtn.getAttribute('data-page'));
                loadMoreProjects(nextPage);
                setTimeout(() => { isLoading = false; }, 1000);
            }
        }
    });
    */

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

    // Filter form handling
    const filterForm = document.getElementById('filterForm');
    const filterButton = filterForm.querySelector('button[type="submit"]');

    if (filterForm && filterButton) {
    filterForm.addEventListener('submit', function() {
    filterButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
    filterButton.disabled = true;
});
}

    // Project card animations on scroll
    const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

    const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
    if (entry.isIntersecting) {
    entry.target.style.opacity = '1';
    entry.target.style.transform = 'translateY(0)';
}
});
}, observerOptions);

    // Observe initial project cards
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
    observer.observe(card);
});

    // Search input live feedback
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
    searchInput.addEventListener('input', function() {
    const searchTerm = this.value.trim();
    if (searchTerm.length > 0) {
    this.style.borderColor = 'var(--primary-purple)';
} else {
    this.style.borderColor = '#e2e8f0';
}
});
}

    // Project ID click to copy functionality
    const projectIds = document.querySelectorAll('.project-id');
    projectIds.forEach(id => {
    id.style.cursor = 'pointer';
    id.title = 'Click to copy ID';

    id.addEventListener('click', function() {
    const idText = this.textContent.replace('ID: ', '');
    navigator.clipboard.writeText(idText).then(() => {
    const originalText = this.textContent;
    this.textContent = 'ID: Copied!';
    this.style.color = 'var(--success-color)';

    setTimeout(() => {
    this.textContent = originalText;
    this.style.color = '';
}, 2000);
}).catch(() => {
    // Fallback for browsers that don't support clipboard API
    const textArea = document.createElement('textarea');
    textArea.value = idText;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);

    const originalText = this.textContent;
    this.textContent = 'ID: Copied!';
    this.style.color = 'var(--success-color)';

    setTimeout(() => {
    this.textContent = originalText;
    this.style.color = '';
}, 2000);
});
});
});

    // Keyboard navigation for modals
    document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
    const openModal = document.querySelector('.modal.show');
    if (openModal) {
    const modal = bootstrap.Modal.getInstance(openModal);
    if (modal) modal.hide();
}
}
});

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
    setTimeout(() => {
    const alertInstance = new bootstrap.Alert(alert);
    alertInstance.close();
}, 5000);
});
});