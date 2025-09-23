// Optimized GitHub content loader for better LCP
document.addEventListener('DOMContentLoaded', function () {
    const githubSection = document.querySelector('.github-section');
    if (!githubSection) {
        return;
    }

    // Defer non-critical GitHub content loading
    const repoCards = document.querySelectorAll('.repo-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('loaded');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '50px' });

    repoCards.forEach(card => observer.observe(card));
});