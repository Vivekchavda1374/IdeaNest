// Project type toggle function
function toggleProjectType() {
    const projectType = document.getElementById("projectType").value;
    const softwareOptions = document.getElementById("softwareOptions");
    const hardwareOptions = document.getElementById("hardwareOptions");

    // Hide both options first
    softwareOptions.classList.add("hidden");
    hardwareOptions.classList.add("hidden");

    // Show relevant option based on selection with animation
    if (projectType === "software") {
        setTimeout(() => {
            softwareOptions.classList.remove("hidden");
            softwareOptions.style.opacity = "0";
            softwareOptions.style.transform = "translateY(20px)";
            setTimeout(() => {
                softwareOptions.style.transition = "all 0.3s ease";
                softwareOptions.style.opacity = "1";
                softwareOptions.style.transform = "translateY(0)";
            }, 50);
        }, 100);
    } else if (projectType === "hardware") {
        setTimeout(() => {
            hardwareOptions.classList.remove("hidden");
            hardwareOptions.style.opacity = "0";
            hardwareOptions.style.transform = "translateY(20px)";
            setTimeout(() => {
                hardwareOptions.style.transition = "all 0.3s ease";
                hardwareOptions.style.opacity = "1";
                hardwareOptions.style.transform = "translateY(0)";
            }, 50);
        }, 100);
    }
}

// Form validation and enhancement
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const inputs = form.querySelectorAll('input, select, textarea');
    const fileInputs = form.querySelectorAll('input[type="file"]');

    // Character counter for textareas
    const textareas = form.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        const counter = document.createElement('div');
        counter.className = 'character-counter';
        counter.style.cssText = `
            font-size: 0.875rem;
            color: #64748b;
            text-align: right;
            margin-top: 0.25rem;
        `;

        const updateCounter = () => {
            const remaining = maxLength - textarea.value.length;
            counter.textContent = `${textarea.value.length}/${maxLength} characters`;
            counter.style.color = remaining < 50 ? '#ef4444' : '#64748b';
        };

        textarea.addEventListener('input', updateCounter);
        textarea.parentNode.appendChild(counter);
        updateCounter();
    });

    // Basic field validation (without required field checks)
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });

        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });

    function validateField(field) {
        const value = field.value.trim();
        const fieldType = field.type;
        let isValid = true;
        let errorMessage = '';

        // Only validate format, not required status
        // Email validation
        if (fieldType === 'email' && value !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }

        // URL validation
        else if (fieldType === 'url' && value !== '') {
            try {
                new URL(value);
            } catch {
                isValid = false;
                errorMessage = 'Please enter a valid URL';
            }
        }

        // Apply validation styling
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            removeErrorMessage(field);
        } else {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            showErrorMessage(field, errorMessage);
        }
    }

    function showErrorMessage(field, message) {
        removeErrorMessage(field);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.cssText = `
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
        `;
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i>${message}`;
        field.parentNode.appendChild(errorDiv);
    }

    function removeErrorMessage(field) {
        const existingError = field.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
    }

    // File upload enhancements
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const container = this.closest('.file-upload-container');
            const info = container.querySelector('.file-upload-info');

            if (file) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                const fileName = file.name;

                // Create file info display
                let fileInfo = container.querySelector('.file-selected-info');
                if (!fileInfo) {
                    fileInfo = document.createElement('div');
                    fileInfo.className = 'file-selected-info';
                    fileInfo.style.cssText = `
                        background: #f0f9ff;
                        border: 1px solid #0ea5e9;
                        border-radius: 8px;
                        padding: 0.75rem;
                        margin-top: 0.5rem;
                        color: #0369a1;
                        font-size: 0.875rem;
                    `;
                    container.appendChild(fileInfo);
                }

                fileInfo.innerHTML = `
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fas fa-file me-2"></i>
                            <span class="fw-medium">${fileName}</span>
                        </div>
                        <div class="text-muted">${fileSize} MB</div>
                    </div>
                `;

                // Validate file size (basic client-side check)
                const maxSizes = {
                    'images': 2,
                    'videos': 10,
                    'presentation_file': 15,
                    'additional_files': 20
                };

                const fieldName = this.name;
                const maxSize = maxSizes[fieldName] || 5;

                if (fileSize > maxSize) {
                    this.classList.add('is-invalid');
                    showErrorMessage(this, `File size exceeds ${maxSize}MB limit`);
                } else {
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                    removeErrorMessage(this);
                }
            }
        });
    });

    // Form submission without required field validation
    form.addEventListener('submit', function(e) {
        const submitButton = this.querySelector('button[type="submit"]');

        // Show loading state
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        submitButton.disabled = true;

        // Re-enable button after 10 seconds as fallback
        setTimeout(() => {
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }, 10000);
    });

    // Auto-save draft functionality (using sessionStorage)
    const draftKey = 'project_form_draft';

    function saveDraft() {
        const formData = {};

        // Save input values
        inputs.forEach(input => {
            if (input.type !== 'file') {
                formData[input.name] = input.value;
            }
        });

        // Save textareas
        textareas.forEach(textarea => {
            formData[textarea.name] = textarea.value;
        });

        sessionStorage.setItem(draftKey, JSON.stringify(formData));
    }

    function loadDraft() {
        const savedDraft = sessionStorage.getItem(draftKey);
        if (savedDraft) {
            try {
                const formData = JSON.parse(savedDraft);

                Object.keys(formData).forEach(key => {
                    const field = form.querySelector(`[name="${key}"]`);
                    if (field) {
                        field.value = formData[key];
                        if (key === 'project_type') {
                            toggleProjectType();
                        }
                    }
                });

                showNotification('Draft loaded successfully', 'info');
            } catch (error) {
                console.error('Error loading draft:', error);
            }
        }
    }

    // Auto-save every 30 seconds
    setInterval(saveDraft, 30000);

    // Save on form field changes
    form.addEventListener('input', saveDraft);
    form.addEventListener('change', saveDraft);

    // Load draft on page load
    loadDraft();

    // Clear draft on successful submission
    form.addEventListener('submit', function(e) {
        if (!e.defaultPrevented) {
            sessionStorage.removeItem(draftKey);
        }
    });

    // Notification system (kept for informational messages only)
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        `;

        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Smooth scroll to form sections
    const sectionHeaders = document.querySelectorAll('.form-section h3');
    sectionHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            this.parentElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    });
});

// Shake animation for invalid fields
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);

// Form progress tracking (optional enhancement) - removed required field dependency
function updateFormProgress() {
    const allFields = document.querySelectorAll('input, select, textarea');
    let filledFields = 0;

    allFields.forEach(field => {
        if (field.type !== 'file' && field.value.trim() !== '') {
            filledFields++;
        } else if (field.type === 'file' && field.files.length > 0) {
            filledFields++;
        }
    });

    const progress = Math.round((filledFields / allFields.length) * 100);

    // Create or update progress bar
    let progressBar = document.querySelector('.form-progress-bar');
    if (!progressBar) {
        progressBar = document.createElement('div');
        progressBar.className = 'form-progress-bar';
        progressBar.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: rgba(99, 102, 241, 0.2);
            z-index: 9999;
            transition: all 0.3s ease;
        `;

        const progressFill = document.createElement('div');
        progressFill.className = 'progress-fill';
        progressFill.style.cssText = `
            height: 100%;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            width: 0%;
            transition: width 0.5s ease;
        `;

        progressBar.appendChild(progressFill);
        document.body.appendChild(progressBar);
    }

    const progressFill = progressBar.querySelector('.progress-fill');
    progressFill.style.width = `${progress}%`;
}

// URL validation helper
function isValidURL(string) {
    try {
        const url = new URL(string);
        return url.protocol === "http:" || url.protocol === "https:";
    } catch (_) {
        return false;
    }
}

// GitHub repository validation
function validateGitHubRepo(url) {
    const githubPattern = /^https:\/\/github\.com\/[\w\-\.]+\/[\w\-\.]+\/?$/;
    return githubPattern.test(url);
}

// Enhanced form field interactions
document.addEventListener('DOMContentLoaded', function() {
    // Add enhanced interactions after DOM is loaded
    const form = document.querySelector('form');

    // GitHub repo field validation (format only, not required)
    const githubField = form.querySelector('input[name="github_repo"]');
    if (githubField) {
        githubField.addEventListener('blur', function() {
            const url = this.value.trim();
            if (url && !validateGitHubRepo(url)) {
                this.classList.add('is-invalid');
                showErrorMessage(this, 'Please enter a valid GitHub repository URL');
            }
        });
    }

    // Technology stack suggestions
    const languageField = form.querySelector('input[name="language"]');
    if (languageField) {
        const suggestions = [
            'JavaScript, React, Node.js',
            'Python, Django, PostgreSQL',
            'Java, Spring Boot, MySQL',
            'C++, Qt, SQLite',
            'Python, TensorFlow, Keras',
            'Arduino, C++, IoT',
            'React Native, Firebase',
            'Vue.js, Express.js, MongoDB'
        ];

        languageField.addEventListener('focus', function() {
            if (!this.value) {
                const randomSuggestion = suggestions[Math.floor(Math.random() * suggestions.length)];
                this.placeholder = `e.g., ${randomSuggestion}`;
            }
        });
    }

    // Auto-capitalize project name
    const projectNameField = form.querySelector('input[name="project_name"]');
    if (projectNameField) {
        projectNameField.addEventListener('input', function() {
            // Capitalize first letter of each word
            this.value = this.value.replace(/\b\w+/g, function(word) {
                return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
            });
        });
    }

    // Team size and development time correlation (informational only)
    const teamSizeField = form.querySelector('select[name="team_size"]');
    const devTimeField = form.querySelector('select[name="development_time"]');

    if (teamSizeField && devTimeField) {
        teamSizeField.addEventListener('change', function() {
            const teamSize = parseInt(this.value);
            if (teamSize >= 5 && devTimeField.value === '1-2 weeks') {
                showNotification('Consider if 1-2 weeks is realistic for a team of ' + teamSize + ' people', 'info');
            }
        });
    }

    // Keywords auto-formatting
    const keywordsField = form.querySelector('input[name="keywords"]');
    if (keywordsField) {
        keywordsField.addEventListener('blur', function() {
            // Format keywords: lowercase, remove extra spaces, ensure comma separation
            let keywords = this.value.toLowerCase()
                .split(',')
                .map(keyword => keyword.trim())
                .filter(keyword => keyword.length > 0)
                .join(', ');
            this.value = keywords;
        });
    }

    // Social links validation (format only)
    const socialLinksField = form.querySelector('input[name="social_links"]');
    if (socialLinksField) {
        socialLinksField.addEventListener('blur', function() {
            const links = this.value.split(',').map(link => link.trim());
            const validLinks = links.filter(link => {
                return link === '' || isValidURL(link) ||
                    link.includes('linkedin.com') ||
                    link.includes('twitter.com') ||
                    link.includes('portfolio') ||
                    link.startsWith('www.');
            });

            if (links.length !== validLinks.length && this.value.trim() !== '') {
                showNotification('Some social links may not be valid URLs', 'info');
            }
        });
    }

    // Dynamic form sections collapse/expand
    const sectionHeaders = document.querySelectorAll('.form-section h3');
    sectionHeaders.forEach(header => {
        header.innerHTML += ' <i class="fas fa-chevron-down float-end" style="font-size: 0.8em; margin-top: 0.2em;"></i>';

        header.addEventListener('click', function() {
            const section = this.parentElement;
            const content = section.querySelector('.form-section > *:not(h3)') ||
                Array.from(section.children).filter(child => child.tagName !== 'H3');
            const icon = this.querySelector('.fa-chevron-down, .fa-chevron-up');

            if (section.classList.contains('collapsed')) {
                section.classList.remove('collapsed');
                if (Array.isArray(content)) {
                    content.forEach(el => el.style.display = '');
                } else if (content) {
                    content.style.display = '';
                }
                icon.className = icon.className.replace('fa-chevron-up', 'fa-chevron-down');
            } else {
                section.classList.add('collapsed');
                if (Array.isArray(content)) {
                    content.forEach(el => el.style.display = 'none');
                } else if (content) {
                    content.style.display = 'none';
                }
                icon.className = icon.className.replace('fa-chevron-down', 'fa-chevron-up');
            }
        });
    });

    // Update form progress on any change
    form.addEventListener('input', updateFormProgress);
    form.addEventListener('change', updateFormProgress);

    // Initial progress calculation
    setTimeout(updateFormProgress, 500);

    // Form reset with confirmation
    const resetButton = document.createElement('button');
    resetButton.type = 'button';
    resetButton.className = 'btn btn-outline-secondary me-3';
    resetButton.innerHTML = '<i class="fas fa-undo me-2"></i>Reset Form';

    resetButton.addEventListener('click', function() {
        if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
            form.reset();
            // Clear all validation classes
            form.querySelectorAll('.is-valid, .is-invalid').forEach(el => {
                el.classList.remove('is-valid', 'is-invalid');
            });
            // Clear error messages
            form.querySelectorAll('.error-message').forEach(el => el.remove());
            // Clear file info displays
            form.querySelectorAll('.file-selected-info').forEach(el => el.remove());
            // Clear draft
            sessionStorage.removeItem('project_form_draft');
            // Reset progress
            updateFormProgress();
            // Hide classification sections
            toggleProjectType();

            showNotification('Form has been reset', 'info');
        }
    });

    // Add reset button before submit button
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.parentNode.insertBefore(resetButton, submitButton);

    // Make submit button container flex
    const buttonContainer = submitButton.parentNode;
    buttonContainer.style.display = 'flex';
    buttonContainer.style.justifyContent = 'flex-end';
    buttonContainer.style.alignItems = 'center';
});

// Utility function for error messages (defined globally to avoid duplication)
function showErrorMessage(field, message) {
    removeErrorMessage(field);
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.cssText = `
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
    `;
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i>${message}`;
    field.parentNode.appendChild(errorDiv);
}

function removeErrorMessage(field) {
    const existingError = field.parentNode.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
}

// Notification system (defined globally) - simplified for informational messages only
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        border-radius: 12px;
    `;

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    notification.innerHTML = `
        <i class="fas ${icons[type] || icons.info} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}