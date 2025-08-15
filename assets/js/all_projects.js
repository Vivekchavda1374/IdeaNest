
    // Enhanced JavaScript functionality
    document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle functionality
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', function() {
    sidebar.classList.toggle('open');
    overlay.classList.toggle('active');
});
}

    // Close sidebar when clicking overlay
    if (overlay) {
    overlay.addEventListener('click', function() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
});
}

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
    if (window.innerWidth <= 1024) {
    if (sidebar && !sidebar.contains(event.target) &&
    mobileMenuToggle && !mobileMenuToggle.contains(event.target)) {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
}
}
});

    // Navigation item click handlers - only for mobile sidebar closing
    document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
    // Close sidebar on mobile after clicking (but allow navigation to proceed)
    if (window.innerWidth <= 1024) {
    setTimeout(() => {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
}, 100); // Small delay to allow navigation to start
}
});
});

    // Responsive sidebar handling
    function handleResize() {
    if (window.innerWidth > 1024) {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
}
}

    window.addEventListener('resize', handleResize);

    // Auto-hide alerts with enhanced animation
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
    // Add initial animation
    alert.style.animation = 'fadeInUp 0.5s ease-out';

    setTimeout(function() {
    // Fade out with slide up effect
    alert.style.transition = "all 0.5s cubic-bezier(0.4, 0, 0.2, 1)";
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-30px) scale(0.95)';
    alert.style.maxHeight = '0';
    alert.style.padding = '0';
    alert.style.margin = '0';

    // Remove from DOM after animation
    setTimeout(function() {
    if (alert.parentNode) {
    alert.remove();
}
}, 500);
}, 4000); // Show for 4 seconds
});

    // Enhanced loading states for bookmark buttons
    document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn && submitBtn.name === 'toggle_bookmark') {
    // Add loading state
    const originalHTML = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    submitBtn.style.opacity = '0.7';
    submitBtn.style.pointerEvents = 'none';

    // If form submission fails, restore button
    setTimeout(() => {
    if (submitBtn) {
    submitBtn.innerHTML = originalHTML;
    submitBtn.style.opacity = '1';
    submitBtn.style.pointerEvents = 'auto';
}
}, 3000);
}
});
});

    // Smooth scroll for pagination
    document.querySelectorAll('.pagination .page-link, .pagination-nav-btn').forEach(link => {
    link.addEventListener('click', function(e) {
    if (this.getAttribute('href') !== '#' && !this.closest('.disabled')) {
    // Add loading effect
    this.style.opacity = '0.6';
    this.style.pointerEvents = 'none';

    // Smooth scroll to top
    window.scrollTo({
    top: 0,
    behavior: 'smooth'
});
}
});
});

    // Enhanced intersection observer for staggered animations
    const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -100px 0px'
};

    const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
    if (entry.isIntersecting) {
    entry.target.style.opacity = '1';
    entry.target.style.transform = 'translateY(0)';
    observer.unobserve(entry.target);
}
});
}, observerOptions);

    // Apply staggered animation to project cards
    const cards = document.querySelectorAll('.project-card');
    cards.forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(50px)';
    card.style.transition = `all 0.6s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.1}s`;
    observer.observe(card);
});

    // Add purple glow effect on card hover
    cards.forEach(card => {
    card.addEventListener('mouseenter', function() {
    this.classList.add('purple-glow');
});

    card.addEventListener('mouseleave', function() {
    this.classList.remove('purple-glow');
});
});

    // Enhanced modal animations
    document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('show.bs.modal', function() {
    this.querySelector('.modal-content').style.animation = 'fadeInUp 0.4s ease-out';
});
});

    // Add ripple effect to buttons
    document.querySelectorAll('.btn, .page-link').forEach(button => {
    button.addEventListener('click', function(e) {
    if (this.classList.contains('disabled') || this.getAttribute('href') === '#') {
    return;
}

    const ripple = document.createElement('span');
    const rect = this.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = e.clientX - rect.left - size / 2;
    const y = e.clientY - rect.top - size / 2;

    ripple.style.cssText = `
                        position: absolute;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                        background: rgba(255, 255, 255, 0.3);
                        border-radius: 50%;
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        pointer-events: none;
                    `;

    this.style.position = 'relative';
    this.style.overflow = 'hidden';
    this.appendChild(ripple);

    setTimeout(() => {
    ripple.remove();
}, 600);
});
});

    // Add CSS for ripple animation
    const style = document.createElement('style');
    style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
    document.head.appendChild(style);

    // Enhanced form validation and UX
    const searchInput = document.getElementById('search');
    if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
    // Add visual feedback for search
    if (this.value.length > 0) {
    this.style.borderColor = 'var(--primary-purple)';
    this.style.boxShadow = '0 0 0 3px rgba(139, 92, 246, 0.1)';
} else {
    this.style.borderColor = '';
    this.style.boxShadow = '';
}
}, 300);
});
}
});