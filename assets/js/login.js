// Add some interactive effects
document.querySelectorAll('.input-group input').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
    });

    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
    });
});

// Form submission with loading state
document.querySelector('form').addEventListener('submit', function(e) {
    const btn = document.querySelector('.login-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Signing In...';
    btn.disabled = true;

    // Re-enable after 3 seconds (for demo purposes)
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 3000);
});