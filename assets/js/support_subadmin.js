
    // Category and priority selection functionality
    document.addEventListener('DOMContentLoaded', function() {
    const categoryCards = document.querySelectorAll('.category-card');
    const priorityOptions = document.querySelectorAll('.priority-option');
    const supportForm = document.getElementById('supportForm');

    // Category selection
    categoryCards.forEach(card => {
    card.addEventListener('click', function() {
    // Remove selected class from all cards
    categoryCards.forEach(c => c.classList.remove('selected'));
    // Add selected class to clicked card
    this.classList.add('selected');
    // Set hidden input value
    document.getElementById('selectedCategory').value = this.dataset.category;
    // Hide error message
    document.getElementById('categoryError').style.display = 'none';
});
});

    // Priority selection
    priorityOptions.forEach(option => {
    option.addEventListener('click', function() {
    // Remove selected class from all options
    priorityOptions.forEach(o => o.classList.remove('selected'));
    // Add selected class to clicked option
    this.classList.add('selected');
    // Set hidden input value
    document.getElementById('selectedPriority').value = this.dataset.priority;
    // Hide error message
    document.getElementById('priorityError').style.display = 'none';
});
});

    // Form validation
    supportForm.addEventListener('submit', function(e) {
    let isValid = true;
    const category = document.getElementById('selectedCategory').value;
    const priority = document.getElementById('selectedPriority').value;
    const subject = document.getElementById('subject').value.trim();
    const message = document.getElementById('message').value.trim();

    // Reset error messages
    document.getElementById('categoryError').style.display = 'none';
    document.getElementById('priorityError').style.display = 'none';

    // Validate category
    if (!category) {
    document.getElementById('categoryError').style.display = 'block';
    isValid = false;
}

    // Validate priority
    if (!priority) {
    document.getElementById('priorityError').style.display = 'block';
    isValid = false;
}

    // Validate subject
    if (!subject) {
    document.getElementById('subject').classList.add('is-invalid');
    isValid = false;
} else {
    document.getElementById('subject').classList.remove('is-invalid');
}

    // Validate message
    if (!message) {
    document.getElementById('message').classList.add('is-invalid');
    isValid = false;
} else {
    document.getElementById('message').classList.remove('is-invalid');
}

    // Prevent form submission if validation fails
    if (!isValid) {
    e.preventDefault();
    // Scroll to first error
    if (!category) {
    scrollToElement('new-ticket');
}
    return false;
}
});
});

    // Smooth scroll function
    function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
    element.scrollIntoView({
    behavior: 'smooth',
    block: 'start'
});
}
}

    // Reset form function
    function resetForm() {
    // Remove selected classes
    document.querySelectorAll('.category-card.selected').forEach(card => {
        card.classList.remove('selected');
    });
    document.querySelectorAll('.priority-option.selected').forEach(option => {
    option.classList.remove('selected');
});

    // Clear hidden inputs
    document.getElementById('selectedCategory').value = '';
    document.getElementById('selectedPriority').value = '';

    // Hide error messages
    document.getElementById('categoryError').style.display = 'none';
    document.getElementById('priorityError').style.display = 'none';

    // Remove validation classes
    document.getElementById('subject').classList.remove('is-invalid');
    document.getElementById('message').classList.remove('is-invalid');
}

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
    setTimeout(() => {
    if (alert.querySelector('.btn-close')) {
    alert.querySelector('.btn-close').click();
}
}, 5000);
});
});