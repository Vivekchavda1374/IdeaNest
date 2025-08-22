
    // Open project modal function
    function openProjectModal(projectId) {
    const modal = new bootstrap.Modal(document.getElementById('projectModal' + projectId));
    modal.show();
}

    document.addEventListener('DOMContentLoaded', function() {
    // Add click animation to buttons
    const buttons = document.querySelectorAll('.btn, .filter-btn');
    buttons.forEach(button => {
    button.addEventListener('click', function(e) {
    this.classList.add('btn-clicked');
    setTimeout(() => {
    this.classList.remove('btn-clicked');
}, 150);

    // Add loading animation for view filter buttons
    if (this.classList.contains('filter-btn') && !this.classList.contains('active')) {
    const icon = this.querySelector('i');
    const originalIcon = icon.className;
    icon.className = 'fas fa-spinner fa-spin';

    // Reset after navigation starts
    setTimeout(() => {
    icon.className = originalIcon;
}, 1000);
}
});
});

    // Add search focus animation
    const searchInput = document.querySelector('#search');
    if (searchInput) {
    searchInput.addEventListener('focus', function() {
    this.classList.add('search-active');
});
    searchInput.addEventListener('blur', function() {
    this.classList.remove('search-active');
});
}

    // Animate project cards on scroll
    const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

    const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
    if (entry.isIntersecting) {
    entry.target.classList.remove('fade-in-hidden');
    entry.target.classList.add('fade-in-visible');
}
});
}, observerOptions);

    // Observe all project cards for animation
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach((card, index) => {
    card.classList.add('fade-in-hidden');
    // Add slight delay for staggered animation
    setTimeout(() => {
    observer.observe(card);
}, index * 50);
});

    // Smooth scroll for pagination links
    const paginationLinks = document.querySelectorAll('.pagination a, .pagination-nav-btn');
    paginationLinks.forEach(link => {
    link.addEventListener('click', function(e) {
    if (this.getAttribute('href') !== '#') {
    setTimeout(() => {
    window.scrollTo({
    top: 0,
    behavior: 'smooth'
});
}, 100);
}
});
});

    // Add hover effect to stat items
    const statItems = document.querySelectorAll('.stat-item');
    statItems.forEach(item => {
    item.addEventListener('mouseenter', function() {
    this.style.transform = 'translateY(-8px) scale(1.02)';
});
    item.addEventListener('mouseleave', function() {
    this.style.transform = 'translateY(-4px) scale(1)';
});
});

    // Enhanced modal animations
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
    modal.addEventListener('show.bs.modal', function() {
    this.querySelector('.modal-dialog').style.transform = 'scale(0.8)';
    this.querySelector('.modal-dialog').style.opacity = '0';
});

    modal.addEventListener('shown.bs.modal', function() {
    this.querySelector('.modal-dialog').style.transition = 'all 0.3s ease';
    this.querySelector('.modal-dialog').style.transform = 'scale(1)';
    this.querySelector('.modal-dialog').style.opacity = '1';
});
});

    // Add loading state to bookmark buttons with proper form handling
    const bookmarkForms = document.querySelectorAll('form[method="post"]');
    bookmarkForms.forEach(form => {
    form.addEventListener('submit', function(e) {
    const button = this.querySelector('button[name="toggle_bookmark"]');
    if (button) {
    const icon = button.querySelector('i');
    if (icon) {
    const originalIcon = icon.className;
    icon.className = 'fas fa-spinner fa-spin';
    button.disabled = true;

    // Show temporary feedback
    const originalText = button.querySelector('span');
    if (originalText) {
    const originalTextContent = originalText.textContent;
    originalText.textContent = 'Processing...';

    // Reset after form submission
    setTimeout(() => {
    icon.className = originalIcon;
    originalText.textContent = originalTextContent;
    button.disabled = false;
}, 3000);
}
}
}
});
});

    // Add confirmation for edit actions on mobile
    const editLinks = document.querySelectorAll('a[href*="edit_project.php"]');
    editLinks.forEach(link => {
    if (window.innerWidth <= 768) {
    link.addEventListener('click', function(e) {
    if (!confirm('Are you sure you want to edit this project?')) {
    e.preventDefault();
}
});
}
});

    // Enhanced tooltips for better mobile experience
    const tooltips = document.querySelectorAll('.tooltip');
    tooltips.forEach(tooltip => {
    if ('ontouchstart' in window) {
    tooltip.addEventListener('touchstart', function() {
    const tooltipText = this.querySelector('.tooltip-text');
    if (tooltipText) {
    tooltipText.style.visibility = 'visible';
    tooltipText.style.opacity = '1';
    setTimeout(() => {
    tooltipText.style.visibility = 'hidden';
    tooltipText.style.opacity = '0';
}, 2000);
}
});
}
});

    // Add filter button animations
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(button => {
    button.addEventListener('mouseenter', function() {
    if (!this.classList.contains('active')) {
    this.style.transform = 'translateY(-3px) scale(1.02)';
}
});

    button.addEventListener('mouseleave', function() {
    if (!this.classList.contains('active')) {
    this.style.transform = 'translateY(0) scale(1)';
}
});
});

    // Update URL without reload when filter changes
    const urlParams = new URLSearchParams(window.location.search);
    const currentView = urlParams.get('view') || 'all';

    // Highlight active filter button
    filterButtons.forEach(button => {
    const buttonView = new URLSearchParams(button.search).get('view') || 'all';
    if (buttonView === currentView) {
    button.classList.add('active');
}
});

    // Prevent modal opening when clicking on action buttons
    const actionElements = document.querySelectorAll('.bookmark-float, .edit-actions, .card-footer-actions');
    actionElements.forEach(element => {
    element.addEventListener('click', function(e) {
    e.stopPropagation();
});
});
});

    function showEditSuccess() {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success fade-in-up';
    alert.innerHTML = '<i class="fas fa-check-circle me-2"></i>Project updated successfully!';
    document.querySelector('.main-content').insertBefore(alert, document.querySelector('.projects-header').nextSibling);

    setTimeout(() => {
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 300);
}, 3000);
}

    function showBookmarkSuccess(action) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + (action === 'added' ? 'success' : 'info') + ' fade-in-up';
    let message = action === 'added' ?
    '<i class="fas fa-bookmark me-2"></i>Project bookmarked successfully!' :
    '<i class="fas fa-bookmark-o me-2"></i>Bookmark removed successfully!';

    alert.innerHTML = message;
    document.querySelector('.main-content').insertBefore(alert, document.querySelector('.view-filter-buttons').nextSibling);

    setTimeout(() => {
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 300);
}, 2000);
}

    function showFilterChangeSuccess(filterType) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-info fade-in-up';
    let message = '';

    switch(filterType) {
    case 'owned':
    message = '<i class="fas fa-user me-2"></i>Now showing your projects only';
    break;
    case 'bookmarked':
    message = '<i class="fas fa-bookmark me-2"></i>Now showing bookmarked projects only';
    break;
    default:
    message = '<i class="fas fa-th-large me-2"></i>Now showing all projects';
}

    alert.innerHTML = message;
    document.querySelector('.main-content').insertBefore(alert, document.querySelector('.view-filter-buttons').nextSibling);

    setTimeout(() => {
    alert.style.opacity = '0';
    setTimeout(() => alert.remove(), 300);
}, 2000);
}

    // Check for edit success parameter
    if (window.location.search.includes('edit_success=1')) {
    showEditSuccess();
    // Clean URL
    const url = new URL(window.location);
    url.searchParams.delete('edit_success');
    window.history.replaceState({}, document.title, url);
}

    // Check for bookmark success parameter
    if (window.location.search.includes('bookmark_success=1')) {
    showBookmarkSuccess('added');
    const url = new URL(window.location);
    url.searchParams.delete('bookmark_success');
    window.history.replaceState({}, document.title, url);
}

    if (window.location.search.includes('bookmark_removed=1')) {
    showBookmarkSuccess('removed');
    const url = new URL(window.location);
    url.searchParams.delete('bookmark_removed');
    window.history.replaceState({}, document.title, url);
}

    // Check for filter change
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get('view');
    if (document.referrer && document.referrer.includes(window.location.origin)) {
    const referrerParams = new URLSearchParams(new URL(document.referrer).search);
    const previousView = referrerParams.get('view') || 'all';
    const currentView = viewParam || 'all';

    if (previousView !== currentView && !window.location.search.includes('page=')) {
    showFilterChangeSuccess(currentView);
}
}