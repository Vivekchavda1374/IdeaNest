
    function updateClassifications() {
    const projectType = document.getElementById('projectType').value;
    const classificationSelect = document.getElementById('classification');
    const currentClassification = '<?php echo $classification; ?>';

    // Reset classification options
    classificationSelect.innerHTML = '';

    if (projectType === 'software') {
    const softwareOptions = [{
    value: '',
    text: 'Select Classification',
    icon: ''
},
{
    value: 'webapp',
    text: 'Web Application',
    icon: 'fa-globe'
},
{
    value: 'mobileapp',
    text: 'Mobile Application',
    icon: 'fa-mobile-alt'
},
{
    value: 'desktopapp',
    text: 'Desktop Application',
    icon: 'fa-desktop'
},
{
    value: 'embeddedsystem',
    text: 'Embedded System',
    icon: 'fa-microchip'
}
    ];

    softwareOptions.forEach(option => {
    const optionElement = document.createElement('option');
    optionElement.value = option.value;
    optionElement.textContent = option.text;
    if (option.value === currentClassification) {
    optionElement.selected = true;
}
    classificationSelect.appendChild(optionElement);
});

} else if (projectType === 'hardware') {
    const hardwareOptions = [{
    value: '',
    text: 'Select Classification',
    icon: ''
},
{
    value: 'iotdevice',
    text: 'IoT Device',
    icon: 'fa-wifi'
},
{
    value: 'robotics',
    text: 'Robotics',
    icon: 'fa-robot'
},
{
    value: 'electroniccircuit',
    text: 'Electronic Circuit',
    icon: 'fa-microchip'
}
    ];

    hardwareOptions.forEach(option => {
    const optionElement = document.createElement('option');
    optionElement.value = option.value;
    optionElement.textContent = option.text;
    if (option.value === currentClassification) {
    optionElement.selected = true;
}
    classificationSelect.appendChild(optionElement);
});

} else {
    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Select Project Type First';
    classificationSelect.appendChild(defaultOption);
}
}

    // Run this on page load to ensure correct classification options are displayed
    document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('projectType').value) {
    updateClassifications();
}

    // Add animation to form sections
    const formSections = document.querySelectorAll('.form-section');
    formSections.forEach((section, index) => {
    section.style.opacity = '0';
    section.style.transform = 'translateY(20px)';
    section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

    setTimeout(() => {
    section.style.opacity = '1';
    section.style.transform = 'translateY(0)';
}, 300 + (index * 200));
});
});