// Enhanced Admin Project Management JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initSidebar();
    initAlerts();
    initTableEnhancements();
    initFormValidation();
    initSearchEnhancements();
    initModalEnhancements();
    initTooltips();
    initLoadingStates();
    initKeyboardShortcuts();
    initStatsAnimation();
});

// Sidebar Management
function initSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('pushed');

            // Store sidebar state in localStorage
            const isOpen = sidebar.classList.contains('show');
            localStorage.setItem('sidebarOpen', isOpen);
        });

        // Restore sidebar state
        const sidebarOpen = localStorage.getItem('sidebarOpen') === 'true';
        if (sidebarOpen && window.innerWidth > 991.98) {
            sidebar.classList.add('show');
            mainContent.classList.add('pushed');
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 991.98) {
                sidebar.classList.remove('show');
                mainContent.classList.remove('pushed');
            } else if (localStorage.getItem('sidebarOpen') === 'true') {
                sidebar.classList.add('show');
                mainContent.classList.add('pushed');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 991.98) {
                if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                    sidebar.classList.remove('show');
                    mainContent.classList.remove('pushed');
                }
            }
        });
    }
}

// Enhanced Alert System
function initAlerts() {
    // Auto dismiss alerts after 5 seconds with smooth transition
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        // Add fade-in animation
        alert.classList.add('fade-in');

        // Auto dismiss after 5 seconds
        setTimeout(function() {
            if (alert && alert.parentNode) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100%)';
                alert.style.transition = 'all 0.3s ease';

                setTimeout(function() {
                    if (alert && alert.parentNode) {
                        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                        bsAlert.close();
                    }
                }, 300);
            }
        }, 5000);
    });

    // Add click to dismiss functionality
    alerts.forEach(function(alert) {
        alert.style.cursor = 'pointer';
        alert.addEventListener('click', function() {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        });
    });
}

// Table Enhancements
function initTableEnhancements() {
    const table = document.querySelector('.project-table');
    if (!table) return;

    // Add row selection functionality
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        row.addEventListener('click', function(e) {
            // Don't select if clicking on buttons or links
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.closest('.btn-group')) {
                return;
            }

            // Toggle selection
            row.classList.toggle('table-active');
        });
    });

    // Add hover effects with smooth transitions
    rows.forEach(function(row) {
        row.style.transition = 'all 0.2s ease';
    });

    // Sort functionality for table headers
    const headers = table.querySelectorAll('th[data-sortable]');
    headers.forEach(function(header) {
        header.style.cursor = 'pointer';
        header.style.userSelect = 'none';

        header.addEventListener('click', function() {
            const column = this.dataset.sortable;
            sortTable(table, column, this);
        });
    });
}

// Table sorting function
function sortTable(table, column, header) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = header.classList.contains('sort-asc');

    // Remove all sort classes
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });

    // Add appropriate sort class
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');

    // Sort rows
    rows.sort(function(a, b) {
        const aVal = a.querySelector(`td[data-${column}]`).textContent.trim();
        const bVal = b.querySelector(`td[data-${column}]`).textContent.trim();

        if (isAscending) {
            return bVal.localeCompare(aVal, undefined, { numeric: true });
        } else {
            return aVal.localeCompare(bVal, undefined, { numeric: true });
        }
    });

    // Reorder DOM elements
    rows.forEach(row => tbody.appendChild(row));
}

// Form Validation Enhancement
function initFormValidation() {
    const forms = document.querySelectorAll('form');

    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const isValid = validateForm(form);
            if (!isValid) {
                e.preventDefault();
                e.stopPropagation();
            }

            form.classList.add('was-validated');
        });

        // Real-time validation
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(function(input) {
            input.addEventListener('blur', function() {
                validateField(input);
            });

            input.addEventListener('input', function() {
                // Clear validation state on input
                input.classList.remove('is-valid', 'is-invalid');
            });
        });
    });
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input, textarea, select');

    inputs.forEach(function(input) {
        if (!validateField(input)) {
            isValid = false;
        }
    });

    return isValid;
}

function validateField(field) {
    let isValid = true;

    // Required field validation
    if (field.hasAttribute('required') && !field.value.trim()) {
        isValid = false;
        showFieldError(field, 'This field is required.');
    }

    // Email validation
    if (field.type === 'email' && field.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(field.value)) {
            isValid = false;
            showFieldError(field, 'Please enter a valid email address.');
        }
    }

    // URL validation
    if (field.type === 'url' && field.value) {
        try {
            new URL(field.value);
        } catch {
            isValid = false;
            showFieldError(field, 'Please enter a valid URL.');
        }
    }

    if (isValid) {
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
        hideFieldError(field);
    }

    return isValid;
}

function showFieldError(field, message) {
    field.classList.remove('is-valid');
    field.classList.add('is-invalid');

    let errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        field.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

function hideFieldError(field) {
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Search Enhancements
function initSearchEnhancements() {
    const searchInput = document.querySelector('input[name="search"]');
    if (!searchInput) return;

    let searchTimeout;

    // Add search suggestions
    const searchWrapper = searchInput.parentNode;
    const suggestionsDiv = document.createElement('div');
    suggestionsDiv.className = 'search-suggestions';
    suggestionsDiv.style.cssText = `
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #d1d5db;
        border-top: none;
        border-radius: 0 0 0.5rem 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    `;
    searchWrapper.style.position = 'relative';
    searchWrapper.appendChild(suggestionsDiv);

    // Debounced search
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length >= 2) {
                fetchSearchSuggestions(this.value, suggestionsDiv);
            } else {
                suggestionsDiv.style.display = 'none';
            }
        }, 300);
    });

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchWrapper.contains(e.target)) {
            suggestionsDiv.style.display = 'none';
        }
    });

    // Keyboard navigation for suggestions
    searchInput.addEventListener('keydown', function(e) {
        const suggestions = suggestionsDiv.querySelectorAll('.suggestion-item');
        const currentActive = suggestionsDiv.querySelector('.suggestion-item.active');

        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            let nextActive;

            if (e.key === 'ArrowDown') {
                nextActive = currentActive ? currentActive.nextElementSibling : suggestions[0];
            } else {
                nextActive = currentActive ? currentActive.previousElementSibling : suggestions[suggestions.length - 1];
            }

            suggestions.forEach(s => s.classList.remove('active'));
            if (nextActive) nextActive.classList.add('active');
        } else if (e.key === 'Enter' && currentActive) {
            e.preventDefault();
            currentActive.click();
        }
    });
}

function fetchSearchSuggestions(query, suggestionsDiv) {
    // Simulate API call - replace with actual implementation
    const suggestions = [
        'Web Development Projects',
        'Mobile Applications',
        'Machine Learning',
        'Data Science',
        'Game Development',
        'API Development'
    ].filter(item => item.toLowerCase().includes(query.toLowerCase()));

    if (suggestions.length > 0) {
        suggestionsDiv.innerHTML = suggestions.map(suggestion => `
            <div class="suggestion-item" style="padding: 0.5rem 0.75rem; cursor: pointer; border-bottom: 1px solid #f3f4f6;">
                ${suggestion}
            </div>
        `).join('');

        suggestionsDiv.style.display = 'block';

        // Add click handlers
        suggestionsDiv.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelector('input[name="search"]').value = this.textContent;
                suggestionsDiv.style.display = 'none';
                // Trigger search
                this.closest('form').submit();
            });

            item.addEventListener('mouseenter', function() {
                suggestionsDiv.querySelectorAll('.suggestion-item').forEach(s => s.classList.remove('active'));
                this.classList.add('active');
            });
        });
    } else {
        suggestionsDiv.style.display = 'none';
    }
}

// Modal Enhancements
function initModalEnhancements() {
    const modals = document.querySelectorAll('.modal');

    modals.forEach(function(modal) {
        const bsModal = new bootstrap.Modal(modal);

        // Enhanced modal animations
        modal.addEventListener('show.bs.modal', function() {
            modal.style.display = 'block';
            modal.style.opacity = '0';
            modal.style.transition = 'opacity 0.3s ease';

            setTimeout(() => {
                modal.style.opacity = '1';
            }, 10);
        });

        modal.addEventListener('hide.bs.modal', function() {
            modal.style.opacity = '0';
        });

        // Auto-focus first input in modal
        modal.addEventListener('shown.bs.modal', function() {
            const firstInput = modal.querySelector('input, textarea, select');
            if (firstInput) {
                firstInput.focus();
            }
        });

        // Prevent modal close on backdrop click for forms with data
        modal.addEventListener('hide.bs.modal', function(e) {
            const form = modal.querySelector('form');
            if (form && hasFormData(form)) {
                if (!confirm('You have unsaved changes. Are you sure you want to close?')) {
                    e.preventDefault();
                }
            }
        });

        // Handle form submission in modals
        const form = modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    setLoadingState(submitButton, true);
                }
            });
        }
    });
}

function hasFormData(form) {
    const inputs = form.querySelectorAll('input, textarea, select');
    return Array.from(inputs).some(input => {
        if (input.type === 'checkbox' || input.type === 'radio') {
            return input.checked;
        }
        return input.value.trim() !== '';
    });
}

// Tooltips Initialization
function initTooltips() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl, {
            delay: { show: 500, hide: 100 },
            animation: true,
            placement: 'auto'
        });
    });

    // Add tooltips to action buttons
    const actionButtons = document.querySelectorAll('.btn-group .btn');
    actionButtons.forEach(function(button) {
        if (!button.hasAttribute('title') && !button.hasAttribute('data-bs-original-title')) {
            const icon = button.querySelector('i');
            if (icon) {
                let title = '';
                if (icon.classList.contains('bi-eye')) title = 'View Details';
                else if (icon.classList.contains('bi-check-circle')) title = 'Approve Project';
                else if (icon.classList.contains('bi-x-circle')) title = 'Reject Project';
                else if (icon.classList.contains('bi-pencil')) title = 'Edit Project';
                else if (icon.classList.contains('bi-trash')) title = 'Delete Project';

                if (title) {
                    button.setAttribute('title', title);
                    button.setAttribute('data-bs-toggle', 'tooltip');
                    new bootstrap.Tooltip(button);
                }
            }
        }
    });
}

// Loading States Management
function initLoadingStates() {
    // Add loading states to all forms
    const forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                setLoadingState(submitButton, true);
            }
        });
    });

    // Add loading states to action links
    const actionLinks = document.querySelectorAll('a[href*="action=approve"], a[href*="action=reject"]');
    actionLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            setLoadingState(this, true);
        });
    });
}

function setLoadingState(element, isLoading) {
    if (isLoading) {
        element.classList.add('loading');
        element.disabled = true;

        // Store original content
        element.dataset.originalContent = element.innerHTML;

        // Add loading spinner
        const spinner = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>';
        if (element.tagName === 'BUTTON') {
            element.innerHTML = spinner + 'Processing...';
        }
    } else {
        element.classList.remove('loading');
        element.disabled = false;

        // Restore original content
        if (element.dataset.originalContent) {
            element.innerHTML = element.dataset.originalContent;
        }
    }
}

// Keyboard Shortcuts
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        // Escape to close modals and clear selections
        if (e.key === 'Escape') {
            // Close modals
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) bsModal.hide();
            });

            // Clear table selections
            const selectedRows = document.querySelectorAll('.project-table tbody tr.table-active');
            selectedRows.forEach(row => row.classList.remove('table-active'));

            // Clear search
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput && searchInput === document.activeElement) {
                searchInput.blur();
            }
        }

        // Ctrl/Cmd + A to select all visible table rows
        if ((e.ctrlKey || e.metaKey) && e.key === 'a') {
            const table = document.querySelector('.project-table');
            if (table && !e.target.matches('input, textarea')) {
                e.preventDefault();
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => row.classList.add('table-active'));
            }
        }

        // Delete key to show bulk actions for selected rows
        if (e.key === 'Delete') {
            const selectedRows = document.querySelectorAll('.project-table tbody tr.table-active');
            if (selectedRows.length > 0) {
                e.preventDefault();
                showBulkActions(selectedRows);
            }
        }
    });
}

function showBulkActions(selectedRows) {
    const count = selectedRows.length;
    const message = `${count} project${count > 1 ? 's' : ''} selected. What would you like to do?`;

    // Create bulk actions modal or toolbar
    if (confirm(message + '\n\nPress OK to continue or Cancel to clear selection.')) {
        // Here you would show bulk actions UI
        console.log('Show bulk actions for', count, 'projects');
    } else {
        selectedRows.forEach(row => row.classList.remove('table-active'));
    }
}

// Statistics Animation
function initStatsAnimation() {
    const statsNumbers = document.querySelectorAll('.stats-number');

    const animateNumber = (element) => {
        const finalNumber = parseInt(element.textContent);
        const duration = 1000; // 1 second
        const steps = 20;
        const increment = finalNumber / steps;
        let current = 0;
        let stepCount = 0;

        element.textContent = '0';

        const timer = setInterval(() => {
            current += increment;
            stepCount++;

            if (stepCount >= steps) {
                element.textContent = finalNumber;
                clearInterval(timer);
            } else {
                element.textContent = Math.round(current);
            }
        }, duration / steps);
    };

    // Use Intersection Observer to animate when visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.dataset.animated) {
                entry.target.dataset.animated = 'true';
                animateNumber(entry.target);
            }
        });
    });

    statsNumbers.forEach(number => observer.observe(number));
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    }
}

// Data Export Functionality
function exportTableData(format = 'csv') {
    const table = document.querySelector('.project-table');
    if (!table) return;

    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(row => {
        return Array.from(row.querySelectorAll('td')).map(td => {
            // Clean up cell content (remove action buttons, etc.)
            const cleanText = td.textContent.trim().replace(/\s+/g, ' ');
            return cleanText;
        });
    });

    if (format === 'csv') {
        exportToCSV([headers, ...rows], 'projects_export.csv');
    } else if (format === 'json') {
        exportToJSON(headers, rows, 'projects_export.json');
    }
}

function exportToCSV(data, filename) {
    const csvContent = data.map(row =>
        row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(',')
    ).join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    downloadFile(blob, filename);
}

function exportToJSON(headers, rows, filename) {
    const jsonData = rows.map(row => {
        const obj = {};
        headers.forEach((header, index) => {
            obj[header] = row[index];
        });
        return obj;
    });

    const blob = new Blob([JSON.stringify(jsonData, null, 2)], { type: 'application/json' });
    downloadFile(blob, filename);
}

function downloadFile(blob, filename) {
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

// Filter Management
function clearAllFilters() {
    const form = document.querySelector('.filter-bar form');
    if (form) {
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (input.type === 'text' || input.type === 'search') {
                input.value = '';
            } else if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            }
        });
        form.submit();
    }
}

function saveFilterPreset(name) {
    const form = document.querySelector('.filter-bar form');
    if (form) {
        const formData = new FormData(form);
        const filters = {};
        for (let [key, value] of formData.entries()) {
            if (value && value !== 'all') {
                filters[key] = value;
            }
        }

        let presets = JSON.parse(localStorage.getItem('filterPresets') || '{}');
        presets[name] = filters;
        localStorage.setItem('filterPresets', JSON.stringify(presets));

        updateFilterPresetsUI();
    }
}

function loadFilterPreset(name) {
    const presets = JSON.parse(localStorage.getItem('filterPresets') || '{}');
    const preset = presets[name];

    if (preset) {
        const form = document.querySelector('.filter-bar form');
        if (form) {
            // Clear current filters
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'text' || input.type === 'search') {
                    input.value = preset[input.name] || '';
                } else if (input.tagName === 'SELECT') {
                    const option = input.querySelector(`option[value="${preset[input.name] || 'all'}"]`);
                    if (option) {
                        input.value = preset[input.name] || 'all';
                    }
                }
            });
            form.submit();
        }
    }
}

function updateFilterPresetsUI() {
    // Implementation would depend on UI design for filter presets
    console.log('Filter presets updated');
}

// Performance Monitoring
function initPerformanceMonitoring() {
    // Monitor page load time
    window.addEventListener('load', function() {
        const loadTime = performance.now();
        console.log(`Page loaded in ${loadTime.toFixed(2)}ms`);

        // Send to analytics if needed
        if (loadTime > 3000) {
            console.warn('Page load time is slow:', loadTime);
        }
    });

    // Monitor table rendering performance
    const table = document.querySelector('.project-table');
    if (table) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    console.log('Table updated, rows added:', mutation.addedNodes.length);
                }
            });
        });

        observer.observe(table.querySelector('tbody'), { childList: true });
    }
}

// Initialize performance monitoring
initPerformanceMonitoring();

// Global error handling
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);

    // Show user-friendly error message
    showErrorNotification('An unexpected error occurred. Please refresh the page.');
});

function showErrorNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 1060; max-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(notification);
            bsAlert.close();
        }
    }, 5000);
}

// Add some additional utility functions for enhanced functionality
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

function showSuccessNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 1060; max-width: 300px;';
    notification.innerHTML = `
        <i class="bi bi-check-circle me-2"></i>${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    setTimeout(() => {
        if (notification.parentNode) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(notification);
            bsAlert.close();
        }
    }, 3000);
}

// Enhanced form submission with progress indication
function submitFormWithProgress(form, progressCallback) {
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');

    setLoadingState(submitButton, true);

    // Simulate progress for demo - replace with actual implementation
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += 10;
        if (progressCallback) progressCallback(progress);

        if (progress >= 100) {
            clearInterval(progressInterval);
            setLoadingState(submitButton, false);
        }
    }, 100);
}

// Add event listeners for export functionality
document.addEventListener('click', function(e) {
    if (e.target.matches('[data-export]')) {
        const format = e.target.dataset.export;
        exportTableData(format);
    }

    if (e.target.matches('[data-clear-filters]')) {
        clearAllFilters();
    }
});

console.log('Enhanced Admin Project Management JavaScript loaded successfully');