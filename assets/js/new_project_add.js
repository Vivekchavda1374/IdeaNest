
    function toggleProjectType() {
    const projectType = document.getElementById("projectType").value;
    const softwareOptions = document.getElementById("softwareOptions");
    const hardwareOptions = document.getElementById("hardwareOptions");

    // Hide both options first
    softwareOptions.classList.add("hidden");
    hardwareOptions.classList.add("hidden");

    // Show relevant option based on selection
    if (projectType === "software") {
    softwareOptions.classList.remove("hidden");
} else if (projectType === "hardware") {
    hardwareOptions.classList.remove("hidden");
}
}

    // Form validation and enhancement
    document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

    // Add real-time validation
    inputs.forEach(input => {
    input.addEventListener('blur', function() {
    if (this.value.trim() === '') {
    this.classList.add('is-invalid');
    this.classList.remove('is-valid');
} else {
    this.classList.add('is-valid');
    this.classList.remove('is-invalid');
}
});
});

    // Form submission enhancement
    form.addEventListener('submit', function(e) {
    let isValid = true;

    inputs.forEach(input => {
    if (input.value.trim() === '') {
    input.classList.add('is-invalid');
    isValid = false;
}
});

    if (!isValid) {
    e.preventDefault();
    // Scroll to first invalid field
    const firstInvalid = form.querySelector('.is-invalid');
    if (firstInvalid) {
    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    firstInvalid.focus();
}
}
});
});