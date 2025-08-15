
    // Profile picture preview
    document.getElementById('user_image').addEventListener('change', function(e) {
    if (e.target.files && e.target.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
    document.getElementById('profilePreview').src = e.target.result;
}
    reader.readAsDataURL(e.target.files[0]);
}
});

    // Password toggle functionality
    document.querySelectorAll('.toggle-password').forEach(button => {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const targetInput = document.getElementById(targetId);
        const icon = this.querySelector('i');

        if (targetInput.type === 'password') {
            targetInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            targetInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
});

    // Password strength checker
    document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthDiv = document.getElementById('passwordStrength');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');

    if (password.length === 0) {
    strengthDiv.style.display = 'none';
    return;
}

    strengthDiv.style.display = 'block';

    let strength = 0;
    let feedback = '';

    if (password.length >= 8) strength++;
    else feedback = 'At least 8 characters required';

    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;

    strengthFill.className = 'strength-fill';

    if (strength <= 2) {
    strengthFill.classList.add('strength-weak');
    strengthText.textContent = feedback || 'Weak password';
    strengthText.style.color = 'var(--danger-color)';
} else if (strength === 3) {
    strengthFill.classList.add('strength-fair');
    strengthText.textContent = 'Fair password';
    strengthText.style.color = 'var(--warning-color)';
} else if (strength === 4) {
    strengthFill.classList.add('strength-good');
    strengthText.textContent = 'Good password';
    strengthText.style.color = 'var(--info-color)';
} else {
    strengthFill.classList.add('strength-strong');
    strengthText.textContent = 'Strong password';
    strengthText.style.color = 'var(--success-color)';
}
});

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const currentPassword = document.getElementById('current_password').value;

    // Password validation
    if (newPassword || confirmPassword || currentPassword) {
    if (!currentPassword) {
    e.preventDefault();
    alert('Please enter your current password to change it.');
    document.getElementById('current_password').focus();
    return;
}

    if (newPassword !== confirmPassword) {
    e.preventDefault();
    alert('New passwords do not match!');
    document.getElementById('confirm_password').focus();
    return;
}

    if (newPassword.length < 6) {
    e.preventDefault();
    alert('New password must be at least 6 characters long!');
    document.getElementById('new_password').focus();
    return;
}
}

    // Phone number validation
    const phoneNo = document.getElementById('phone_no').value;
    if (phoneNo && !/^\d{10}$/.test(phoneNo)) {
    e.preventDefault();
    alert('Phone number must be exactly 10 digits!');
    document.getElementById('phone_no').focus();
    return;
}

    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;

    // Re-enable button after a delay in case of validation errors
    setTimeout(() => {
    submitBtn.innerHTML = originalText;
    submitBtn.disabled = false;
}, 5000);
});

    // File size validation
    document.getElementById('user_image').addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
    alert('File size must be less than 5MB');
    this.value = '';
    return;
}

    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
    alert('Only JPG, PNG, and GIF files are allowed');
    this.value = '';
    return;
}
}
});