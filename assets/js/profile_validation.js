// Profile Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.querySelector('button[name="update_profile"]').closest('form');
    
    profileForm.addEventListener('submit', function(e) {
        const nameField = this.querySelector('input[name="name"]');
        const domainField = this.querySelector('input[name="domain"]');
        let isValid = true;

        // Reset validation states
        this.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });

        // Validate name field
        if (!nameField.value.trim()) {
            nameField.classList.add('is-invalid');
            isValid = false;
        }

        // Validate domain field
        if (!domainField.value.trim()) {
            domainField.classList.add('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            showNotification('Name and department are required fields.', 'danger');
            
            const firstInvalid = this.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.focus();
            }
        }
    });
});

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border: none;
        border-radius: 12px;
    `;
    
    notification.innerHTML = `
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}