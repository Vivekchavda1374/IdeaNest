function toggleProjectType() {
    const projectType = document.getElementById('projectType').value;
    const softwareOptions = document.getElementById('softwareOptions');
    const hardwareOptions = document.getElementById('hardwareOptions');

    if (projectType === 'software') {
        softwareOptions.classList.remove('hidden');
        hardwareOptions.classList.add('hidden');
    } else if (projectType === 'hardware') {
        hardwareOptions.classList.remove('hidden');
        softwareOptions.classList.add('hidden');
    } else {
        softwareOptions.classList.add('hidden');
        hardwareOptions.classList.add('hidden');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleProjectType();
});
