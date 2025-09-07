document.querySelectorAll('.input-group input, .input-group textarea').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
    });

    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
    });
});

// Form submission with loading state
document.querySelector('form').addEventListener('submit', function(e) {
    const btn = document.querySelector('.register-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>Creating Account...';
    btn.disabled = true;

    // Re-enable after 3 seconds (for demo purposes)
    setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 3000);
});

// Password confirmation validation
document.getElementById('confirm').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirm = this.value;

    if (confirm && password !== confirm) {
        this.style.borderColor = 'var(--error)';
    } else {
        this.style.borderColor = 'var(--gray-300)';
    }
});