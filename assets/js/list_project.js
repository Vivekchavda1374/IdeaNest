document.addEventListener('DOMContentLoaded', function() {
    // Override any alert functions to prevent browser alerts
    window.alert = function(message) {
        // Do nothing - no alerts will show
    };

    // Override confirm to prevent confirmation dialogs
    window.confirm = function(message) {
        return true; // Always return true
    };

    // Function to show project details in modal - COMPLETELY ALERT FREE
    function showProjectDetails(projectId) {
        // Remove any existing alerts on the page
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());

        const modal = new bootstrap.Modal(document.getElementById('projectDetailModal'));
        const modalContent = document.getElementById('projectModalContent');
        const modalTitle = document.getElementById('modalProjectTitle');
        const editBtn = document.getElementById('editProjectBtn');

        // Show modal immediately - no alerts
        modal.show();

        // Set loading content
        modalContent.innerHTML = `
            <div class="text-center p-4">
                <div class="spinner-border text-purple" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading project details...</p>
            </div>
        `;
        modalTitle.textContent = 'Project Details';
        editBtn.style.display = 'none';

        // Fetch project details
        const baseUrl = window.location.pathname;
        const params = new URLSearchParams({
            get_project_details: '1',
            project_id: projectId
        });

        fetch(baseUrl + '?' + params.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load project details');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.project) {
                    const project = data.project;
                    modalTitle.textContent = project.project_name;

                    // Generate detailed project HTML - NO ALERTS ANYWHERE
                    modalContent.innerHTML = `
                    <!-- Project Header -->
                    <div class="project-detail-header mb-4">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="project-name text-purple fw-bold mb-2">
                                    <i class="fas fa-project-diagram me-2"></i>
                                    ${project.project_name}
                                </h4>
                                <p class="project-id-display mb-0">
                                    <i class="fas fa-hashtag me-1"></i>
                                    <strong>Project ID:</strong> 
                                    <span class="badge bg-secondary ms-1">${project.er_number}</span>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="status-priority-badges">
                                    <div class="mb-2">
                                        <span class="badge ${project.priority_class} fs-6 px-3 py-2">
                                            <i class="fas fa-flag me-1"></i>${project.priority1.toUpperCase()} Priority
                                        </span>
                                    </div>
                                    <div>
                                        <span class="badge ${project.status_class} fs-6 px-3 py-2">
                                            <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                                            ${project.status.replace('_', ' ').toUpperCase()}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Details Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="detail-section">
                                <h6 class="section-title">
                                    <i class="fas fa-info-circle me-2"></i>Project Information
                                </h6>
                                
                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-cog me-2"></i>Project Type
                                    </span>
                                    <span class="detail-value">
                                        <i class="fas fa-${project.project_type === 'software' ? 'laptop-code' : 'microchip'} me-1"></i>
                                        ${project.project_type.charAt(0).toUpperCase() + project.project_type.slice(1)}
                                    </span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-tag me-2"></i>Classification
                                    </span>
                                    <span class="detail-value">${project.classification}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-user me-2"></i>Assigned To
                                    </span>
                                    <span class="detail-value">
                                        ${project.assigned_to === 'Not Assigned' ?
                        '<span class="text-muted"><i class="fas fa-user-slash me-1"></i>Unassigned</span>' :
                        project.assigned_to
                    }
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="detail-section">
                                <h6 class="section-title">
                                    <i class="fas fa-clock me-2"></i>Timeline Information
                                </h6>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-calendar-plus me-2"></i>Submitted
                                    </span>
                                    <span class="detail-value">${project.submission_datetime}</span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-calendar-check me-2"></i>Completion
                                    </span>
                                    <span class="detail-value">
                                        ${project.completion_date === 'N/A' ?
                        '<span class="text-muted">Not Set</span>' :
                        project.completion_date
                    }
                                    </span>
                                </div>

                                <div class="detail-row">
                                    <span class="detail-label">
                                        <i class="fas fa-chart-line me-2"></i>Progress
                                    </span>
                                    <span class="detail-value">
                                        ${getProgressInfo(project.status)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Section -->
                    <div class="mb-4">
                        <div class="detail-section">
                            <h6 class="section-title">
                                <i class="fas fa-file-alt me-2"></i>Project Description
                            </h6>
                            <div class="description-box">
                                ${project.description}
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info Section -->
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="info-card text-center">
                                    <i class="fas fa-calendar-day text-primary mb-2"></i>
                                    <div class="info-number">${calculateDaysActive(project.submission_datetime)}</div>
                                    <div class="info-label">Days Active</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card text-center">
                                    <i class="fas fa-tasks text-info mb-2"></i>
                                    <div class="info-number">${getCompletionPercentage(project.status)}%</div>
                                    <div class="info-label">Complete</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="info-card text-center">
                                    <i class="fas fa-database text-secondary mb-2"></i>
                                    <div class="info-number">#${project.id}</div>
                                    <div class="info-label">Internal ID</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Info -->
                    <div class="project-footer-info">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-eye me-1"></i>
                                    Viewed on ${new Date().toLocaleString()}
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                ${project.can_edit ?
                        '<span class="badge bg-success"><i class="fas fa-edit me-1"></i>Editable</span>' :
                        '<span class="badge bg-secondary"><i class="fas fa-lock me-1"></i>View Only</span>'
                    }
                            </div>
                        </div>
                    </div>
                `;

                    // Handle edit button
                    if (project.can_edit) {
                        editBtn.style.display = 'inline-block';
                        editBtn.onclick = function() {
                            modal.hide();
                            window.location.href = 'edit.php?id=' + project.id;
                        };
                    } else {
                        editBtn.style.display = 'none';
                    }

                    // Add styling
                    addDetailModalStyles();

                } else {
                    // Handle error without alerts
                    modalContent.innerHTML = `
                    <div class="text-center p-5">
                        <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">Unable to Load Project Details</h5>
                        <p class="text-muted mb-4">
                            ${data.message || 'There was an issue loading the project information.'}
                        </p>
                        <button class="btn btn-purple me-2" onclick="showProjectDetails(${projectId})">
                            <i class="fas fa-refresh me-2"></i>Try Again
                        </button>
                        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                    </div>
                `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Handle error without alerts
                modalContent.innerHTML = `
                <div class="text-center p-5">
                    <i class="fas fa-wifi text-danger mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted">Connection Error</h5>
                    <p class="text-muted mb-4">
                        Unable to connect to server. Please check your connection and try again.
                    </p>
                    <button class="btn btn-purple me-2" onclick="showProjectDetails(${projectId})">
                        <i class="fas fa-refresh me-2"></i>Retry
                    </button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                </div>
            `;
            });
    }

    // Load More Button Functionality
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const projectsGrid = document.getElementById('projectsGrid');

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            const currentPage = parseInt(this.getAttribute('data-page'));

            // Show loading state
            loadMoreBtn.style.display = 'none';
            loadingSpinner.style.display = 'flex';

            // Get current filter parameters from the URL or form
            const urlParams = new URLSearchParams(window.location.search);
            const filterParams = new URLSearchParams();

            // Add existing filters
            if (urlParams.get('type')) filterParams.set('type', urlParams.get('type'));
            if (urlParams.get('status')) filterParams.set('status', urlParams.get('status'));
            if (urlParams.get('priority')) filterParams.set('priority', urlParams.get('priority'));
            if (urlParams.get('search')) filterParams.set('search', urlParams.get('search'));

            // Add pagination and AJAX parameters
            filterParams.set('ajax', '1');
            filterParams.set('page', currentPage);

            // Make AJAX request
            fetch(window.location.pathname + '?' + filterParams.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.html) {
                        // Append new projects to the grid
                        projectsGrid.insertAdjacentHTML('beforeend', data.html);

                        // Setup view details buttons for new projects
                        setupViewDetailsButtons();

                        // Update pagination info if available
                        const paginationInfo = document.querySelector('.pagination-info');
                        if (paginationInfo && data.paginationInfo) {
                            paginationInfo.textContent = data.paginationInfo;
                        }

                        // Update load more button
                        if (data.hasMore) {
                            loadMoreBtn.setAttribute('data-page', data.nextPage);
                            loadMoreBtn.style.display = 'block';
                        } else {
                            // No more projects to load
                            loadMoreBtn.style.display = 'none';

                            // Show "all loaded" message
                            const allLoadedMsg = document.createElement('div');
                            allLoadedMsg.className = 'text-center mt-3 text-muted';
                            allLoadedMsg.innerHTML = '<small><i class="fas fa-check-circle me-1"></i>All projects loaded</small>';
                            loadingSpinner.parentNode.insertBefore(allLoadedMsg, loadingSpinner);
                        }
                    } else {
                        // Handle error
                        console.error('Error loading more projects:', data.message);
                        showErrorMessage('Failed to load more projects. Please try again.');
                        loadMoreBtn.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Network error:', error);
                    showErrorMessage('Network error. Please check your connection and try again.');
                    loadMoreBtn.style.display = 'block';
                })
                .finally(() => {
                    // Hide loading spinner
                    loadingSpinner.style.display = 'none';
                });
        });
    }

    // Helper function to show error messages
    function showErrorMessage(message) {
        // Remove existing error messages
        const existingErrors = document.querySelectorAll('.load-more-error');
        existingErrors.forEach(error => error.remove());

        // Create and show new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-warning alert-dismissible fade show load-more-error mt-3';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        if (loadMoreBtn) {
            loadMoreBtn.parentNode.insertBefore(errorDiv, loadMoreBtn);
        } else {
            projectsGrid.parentNode.appendChild(errorDiv);
        }

        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }

    // Helper functions
    function getProgressInfo(status) {
        const progressMap = {
            'pending': { percent: 10, color: 'warning', text: 'Pending' },
            'in_progress': { percent: 60, color: 'info', text: 'In Progress' },
            'completed': { percent: 100, color: 'success', text: 'Completed' },
            'rejected': { percent: 0, color: 'danger', text: 'Rejected' }
        };

        const progress = progressMap[status] || progressMap['pending'];

        return `
            <div class="progress mb-1" style="height: 6px;">
                <div class="progress-bar bg-${progress.color}" style="width: ${progress.percent}%"></div>
            </div>
            <small class="text-${progress.color}">${progress.percent}%</small>
        `;
    }

    function calculateDaysActive(startDate) {
        if (!startDate || startDate === 'N/A') return 0;
        const start = new Date(startDate);
        const now = new Date();
        const diffTime = Math.abs(now - start);
        return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    }

    function getCompletionPercentage(status) {
        const percentMap = {
            'pending': 10,
            'in_progress': 60,
            'completed': 100,
            'rejected': 0
        };
        return percentMap[status] || 10;
    }

    function addDetailModalStyles() {
        const existingStyle = document.querySelector('#detail-modal-styles');
        if (existingStyle) existingStyle.remove();

        const style = document.createElement('style');
        style.id = 'detail-modal-styles';
        style.textContent = `
            .project-detail-header {
                background: linear-gradient(135deg, #f8fafc 0%, var(--light-purple) 100%);
                border: 2px solid rgba(139, 92, 246, 0.1);
                border-radius: 12px;
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .project-name {
                margin-bottom: 0.5rem;
                color: var(--primary-purple);
            }

            .status-priority-badges .badge {
                font-size: 0.85rem;
                padding: 0.5rem 1rem;
            }

            .detail-section {
                background: rgba(248, 250, 252, 0.6);
                border: 1px solid rgba(139, 92, 246, 0.1);
                border-radius: 12px;
                padding: 1.5rem;
                height: 100%;
            }

            .section-title {
                color: var(--primary-purple);
                font-weight: 700;
                margin-bottom: 1rem;
                padding-bottom: 0.5rem;
                border-bottom: 2px solid rgba(139, 92, 246, 0.2);
            }

            .detail-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 0;
                border-bottom: 1px solid rgba(139, 92, 246, 0.1);
            }

            .detail-row:last-child {
                border-bottom: none;
            }

            .detail-label {
                font-weight: 600;
                color: #374151;
                min-width: 120px;
            }

            .detail-value {
                text-align: right;
                color: #1f2937;
                font-weight: 500;
            }

            .description-box {
                background: white;
                border: 2px solid rgba(139, 92, 246, 0.1);
                border-radius: 8px;
                padding: 1rem;
                min-height: 100px;
                max-height: 200px;
                overflow-y: auto;
                line-height: 1.6;
                color: #374151;
            }

            .info-card {
                background: white;
                border: 2px solid rgba(139, 92, 246, 0.1);
                border-radius: 12px;
                padding: 1.5rem;
                transition: all 0.3s ease;
            }

            .info-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(139, 92, 246, 0.15);
            }

            .info-card i {
                font-size: 2rem;
            }

            .info-number {
                font-size: 1.5rem;
                font-weight: 700;
                color: var(--dark-purple);
                margin: 0.5rem 0;
            }

            .info-label {
                color: #64748b;
                font-weight: 500;
                font-size: 0.9rem;
            }

            .project-footer-info {
                background: rgba(248, 250, 252, 0.8);
                border-top: 2px solid rgba(139, 92, 246, 0.1);
                margin: 1.5rem -1.5rem -1.5rem -1.5rem;
                padding: 1rem 1.5rem;
                border-radius: 0 0 12px 12px;
            }

            .project-footer-info .badge {
                font-size: 0.85rem;
                padding: 0.5rem 1rem;
            }

            /* Priority and status badge colors */
            .priority-high { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
            .priority-medium { background: rgba(245, 158, 11, 0.1); color: #d97706; }
            .priority-low { background: rgba(16, 185, 129, 0.1); color: #059669; }
            
            .status-pending { background: rgba(245, 158, 11, 0.1); color: #d97706; }
            .status-in_progress { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
            .status-completed { background: rgba(16, 185, 129, 0.1); color: #059669; }
            .status-rejected { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
        `;
        document.head.appendChild(style);
    }

    // Setup view details buttons
    function setupViewDetailsButtons() {
        const viewBtns = document.querySelectorAll('.view-details-btn');
        viewBtns.forEach(btn => {
            // Remove any existing listeners
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            // Add new listener
            newBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const projectId = this.getAttribute('data-project-id');
                if (projectId) {
                    showProjectDetails(projectId);
                }
            });
        });
    }

    // Initialize
    setupViewDetailsButtons();

    // Make functions globally available
    window.showProjectDetails = showProjectDetails;
    window.setupViewDetailsButtons = setupViewDetailsButtons;

    // Remove any existing alerts on page load
    document.querySelectorAll('.alert').forEach(alert => alert.remove());

    // Prevent any future alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
    }, 100);
});