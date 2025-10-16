// Support Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeCategorySelection();
    initializePrioritySelection();
    initializeFormValidation();
    initializeAnimations();
});

// Category Selection
function initializeCategorySelection() {
    const categoryCards = document.querySelectorAll('.category-card');
    const categoryInput = document.getElementById('selectedCategory');
    const categoryError = document.getElementById('categoryError');

    categoryCards.forEach(card => {
        card.addEventListener('click', function() {
            // Remove selected class from all cards
            categoryCards.forEach(c => c.classList.remove('selected'));
            
            // Add selected class to clicked card
            this.classList.add('selected');
            
            // Set the hidden input value
            const category = this.getAttribute('data-category');
            categoryInput.value = category;
            
            // Hide error message
            categoryError.style.display = 'none';
            
            // Add animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
}

// Priority Selection
function initializePrioritySelection() {
    const priorityOptions = document.querySelectorAll('.priority-option');
    const priorityInput = document.getElementById('selectedPriority');
    const priorityError = document.getElementById('priorityError');

    priorityOptions.forEach(option => {
        option.addEventListener('click', function() {
            // Remove selected class from all options
            priorityOptions.forEach(o => o.classList.remove('selected'));
            
            // Add selected class to clicked option
            this.classList.add('selected');
            
            // Set the hidden input value
            const priority = this.getAttribute('data-priority');
            priorityInput.value = priority;
            
            // Hide error message
            priorityError.style.display = 'none';
            
            // Add animation
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
}

// Form Validation
function initializeFormValidation() {
    const form = document.getElementById('supportForm');
    const categoryInput = document.getElementById('selectedCategory');
    const priorityInput = document.getElementById('selectedPriority');
    const categoryError = document.getElementById('categoryError');
    const priorityError = document.getElementById('priorityError');

    form.addEventListener('submit', function(e) {
        let isValid = true;

        // Validate category selection
        if (!categoryInput.value) {
            categoryError.style.display = 'block';
            categoryError.textContent = 'Please select a category';
            isValid = false;
        } else {
            categoryError.style.display = 'none';
        }

        // Validate priority selection
        if (!priorityInput.value) {
            priorityError.style.display = 'block';
            priorityError.textContent = 'Please select a priority level';
            isValid = false;
        } else {
            priorityError.style.display = 'none';
        }

        if (!isValid) {
            e.preventDefault();
            
            // Scroll to first error
            const firstError = document.querySelector('.text-danger[style*="block"]');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            return false;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        setButtonLoading(submitBtn, true, 'Submitting...');
    });
}

// Initialize Animations
function initializeAnimations() {
    // Animate elements on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);

    // Observe elements
    document.querySelectorAll('.glass-card, .quick-action').forEach(el => {
        observer.observe(el);
    });
}

// Utility Functions
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }
}

function resetForm() {
    // Reset category selection
    document.querySelectorAll('.category-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Reset priority selection
    document.querySelectorAll('.priority-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Clear hidden inputs
    document.getElementById('selectedCategory').value = '';
    document.getElementById('selectedPriority').value = '';
    
    // Hide error messages
    document.getElementById('categoryError').style.display = 'none';
    document.getElementById('priorityError').style.display = 'none';
    
    // Show success message
    showNotification('Form has been reset', 'info');
}

function setButtonLoading(button, loading, text = 'Loading...') {
    if (loading) {
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            ${text}
        `;
        button.disabled = true;
        button.classList.add('btn-loading');
    } else {
        button.innerHTML = button.dataset.originalText || button.innerHTML;
        button.disabled = false;
        button.classList.remove('btn-loading');
        delete button.dataset.originalText;
    }
}

// Enhanced form interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add floating label effect
    const formControls = document.querySelectorAll('.form-floating-custom .form-control');
    
    formControls.forEach(control => {
        // Check if field has value on load
        if (control.value) {
            control.classList.add('has-value');
        }
        
        control.addEventListener('input', function() {
            if (this.value) {
                this.classList.add('has-value');
            } else {
                this.classList.remove('has-value');
            }
        });
        
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        control.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });
    
    // Character counter for textarea
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        const maxLength = 1000;
        const counter = document.createElement('div');
        counter.className = 'character-counter text-muted small mt-1';
        counter.textContent = `0 / ${maxLength} characters`;
        messageTextarea.parentElement.appendChild(counter);
        
        messageTextarea.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = `${length} / ${maxLength} characters`;
            
            if (length > maxLength * 0.9) {
                counter.classList.add('text-warning');
            } else {
                counter.classList.remove('text-warning');
            }
            
            if (length >= maxLength) {
                counter.classList.add('text-danger');
                counter.classList.remove('text-warning');
            } else {
                counter.classList.remove('text-danger');
            }
        });
    }
});

// Notification function
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
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Export functions for global use
window.scrollToElement = scrollToElement;
window.resetForm = resetForm;
window.setButtonLoading = setButtonLoading;
window.showNotification = showNotification;