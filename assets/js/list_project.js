document.addEventListener('DOMContentLoaded', function() {
    // Lazy Loading Implementation
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const projectsGrid = document.getElementById('projectsGrid');

    // Load more projects function
    function loadMoreProjects(page) {
        if (loadMoreBtn) {
            loadMoreBtn.style.display = 'none';
        }
        loadingSpinner.style.display = 'flex';

        // Get current filter parameters
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('page', page);
        urlParams.set('ajax', '1');

        // Use the current page URL for the request
        const baseUrl = window.location.pathname;

        fetch(baseUrl + '?' + urlParams.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.html) {
                    // Create temporary container
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;

                    // Add new cards to grid with animation
                    const newCards = tempDiv.querySelectorAll('.project-card');
                    newCards.forEach((card, index) => {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        projectsGrid.appendChild(card);

                        // Animate in
                        setTimeout(() => {
                            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, index * 100);
                    });

                    // Set up event listeners for new view details buttons
                    setupViewDetailsButtons();

                    // Update load more button
                    if (data.hasMore) {
                        loadMoreBtn.setAttribute('data-page', data.nextPage);
                        loadMoreBtn.style.display = 'block';
                    } else {
                        if (loadMoreBtn) {
                            loadMoreBtn.style.display = 'none';
                        }
                    }

                    // Update pagination info
                    const paginationInfo = document.querySelector('.text-center.mt-3.text-muted small');
                    if (paginationInfo && data.paginationInfo) {
                        paginationInfo.innerHTML = data.paginationInfo;
                    }
                } else {
                    throw new Error(data.message || 'Failed to load projects');
                }
            })
            .catch(error => {
                console.error('Error loading projects:', error);
                showAlert('Failed to load more projects: ' + error.message, 'danger');
                if (loadMoreBtn) {
                    loadMoreBtn.style.display = 'block';
                }
            })
            .finally(() => {
                loadingSpinner.style.display = 'none';
            });
    }

    // Function to show project details in modal
    function showProjectDetails(projectId) {
        const modal = new bootstrap.Modal(document.getElementById('projectDetailModal'));
        const modalContent = document.getElementById('projectModalContent');
        const modalTitle = document.getElementById('modalProjectTitle');
        const editBtn = document.getElementById('editProjectBtn');

        // Show modal and reset content
        modal.show();
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
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.project) {
                    const project = data.project;
                    modalTitle.textContent = project.project_name;

                    // Generate detailed project HTML
                    modalContent.innerHTML = `
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-purple fw-bold mb-3">
                                <i class="fas fa-info-circle me-2"></i>Project Details
                            </h6>
                            <div class="detail-item">
                                <strong><i class="fas fa-hashtag me-2 text-muted"></i>ID:</strong> 
                                <span class="ms-2">${project.er_number}</span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="fas fa-cog me-2 text-muted"></i>Type:</strong> 
                                <span class="ms-2">${project.project_type}</span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="fas fa-tag me-2 text-muted"></i>Classification:</strong> 
                                <span class="ms-2">${project.classification}</span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="fas fa-flag me-2 text-muted"></i>Priority:</strong>
                                <span class="badge ${project.priority_class} ms-2">
                                    ${project.priority1}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-purple fw-bold mb-3">
                                <i class="fas fa-clock me-2"></i>Status & Timeline
                            </h6>
                            <div class="detail-item">
                                <strong><i class="fas fa-circle me-2 text-muted" style="font-size: 0.5rem;"></i>Status:</strong>
                                <span class="badge ${project.status_class} ms-2">
                                    ${project.status}
                                </span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="fas fa-calendar-plus me-2 text-muted"></i>Submitted:</strong> 
                                <span class="ms-2">${project.submission_datetime}</span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="fas fa-user me-2 text-muted"></i>Assigned To:</strong>
                                <span class="ms-2">${project.assigned_to}</span>
                            </div>
                            <div class="detail-item">
                                <strong><i class="fas fa-calendar-check me-2 text-muted"></i>Completion Date:</strong>
                                <span class="ms-2">${project.completion_date}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="text-purple fw-bold mb-3">
                            <i class="fas fa-file-alt me-2"></i>Project Description
                        </h6>
                        <div class="description-container p-3 rounded">
                            <div class="description-text">${project.description}</div>
                        </div>
                    </div>

                    ${project.status === 'In Progress' ? `
                    <div class="mb-4">
                        <h6 class="text-purple fw-bold mb-3">
                            <i class="fas fa-chart-line me-2"></i>Progress
                        </h6>
                        <div class="progress mb-2" style="height: 12px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: 65%; background: var(--purple-gradient);"
                                 aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Estimated 65% Complete
                        </small>
                    </div>
                    ` : ''}

                    <div class="project-actions-detail mt-4 pt-3 border-top">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Last updated: ${new Date().toLocaleDateString()}
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">
                                    <i class="fas fa-eye me-1"></i>
                                    Project ID: ${project.id}
                                </small>
                            </div>
                        </div>
                        ${!project.can_edit ? `
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-warning">
                                    <i class="fas fa-info-circle me-1"></i>
                                    You can only edit your own projects
                                </small>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;

                    // Show edit button only if user can edit this project
                    if (project.can_edit) {
                        editBtn.style.display = 'inline-block';
                        editBtn.onclick = function() {
                            window.location.href = 'edit.php?id=' + project.id;
                        };
                    } else {
                        editBtn.style.display = 'none';
                    }

                    // Add CSS styles for the modal content
                    const style = document.createElement('style');
                    style.textContent = `
                    .detail-item {
                        margin-bottom: 12px;
                        padding: 8px 0;
                        border-bottom: 1px solid #f1f5f9;
                    }
                    .detail-item:last-child {
                        border-bottom: none;
                    }
                    .description-container {
                        background: linear-gradient(135deg, #f8fafc 0%, var(--light-purple) 100%);
                        border: 1px solid rgba(139, 92, 246, 0.1);
                        min-height: 100px;
                    }
                    .description-text {
                        line-height: 1.6;
                        color: #374151;
                    }
                    .project-actions-detail {
                        background: rgba(248, 250, 252, 0.5);
                        margin: -1.5rem -1.5rem 0 -1.5rem;
                        padding: 1rem 1.5rem 0 1.5rem;
                    }
                `;

                    // Remove any existing style elements and add the new one
                    const existingStyle = document.querySelector('#modal-detail-styles');
                    if (existingStyle) {
                        existingStyle.remove();
                    }
                    style.id = 'modal-detail-styles';
                    document.head.appendChild(style);

                } else {
                    throw new Error(data.message || 'Failed to load project details');
                }
            })
            .catch(error => {
                console.error('Error loading project details:', error);
                modalContent.innerHTML = `
                <div class="text-center p-4">
                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">Error Loading Project</h5>
                    <p class="text-muted">
                        ${error.message || 'Unable to load project details. Please try again.'}
                    </p>
                    <button class="btn btn-outline-purple" onclick="showProjectDetails(${projectId})">
                        <i class="fas fa-refresh me-2"></i>Retry
                    </button>
                </div>
            `;
            });
    }

    // Setup view details buttons for dynamically loaded content
    function setupViewDetailsButtons() {
        const viewBtns = document.querySelectorAll('.view-details-btn');
        viewBtns.forEach(btn => {
            if (!btn.hasAttribute('data-listener-added')) {
                btn.setAttribute('data-listener-added', 'true');
                btn.addEventListener('click', function() {
                    const projectId = this.getAttribute('data-project-id');
                    showProjectDetails(projectId);
                });
            }
        });
    }

    // Load more button click handler
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            const nextPage = parseInt(this.getAttribute('data-page'));
            loadMoreProjects(nextPage);
        });
    }

    // Setup initial view details buttons
    setupViewDetailsButtons();

    // Helper function to show alerts
    function showAlert(message, type = 'info') {
        const alertContainer = document.querySelector('.main-content');
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        // Insert after page header
        const pageHeader = document.querySelector('.page-header');
        if (pageHeader) {
            pageHeader.after(alert);
        } else {
            alertContainer.prepend(alert);
        }

        // Auto-hide after 5 seconds
        setTimeout(() => {
            const alertInstance = new bootstrap.Alert(alert);
            alertInstance.close();
        }, 5000);
    }

    // Initialize tooltips for disabled edit buttons
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"], [title]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Filter form handling
    const filterForm = document.getElementById('filterForm');
    const filterButton = filterForm ? filterForm.querySelector('button[type="submit"]') : null;

    if (filterForm && filterButton) {
        filterForm.addEventListener('submit', function() {
            filterButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
            filterButton.disabled = true;
        });
    }

    // Project card animations on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe initial project cards
    const projectCards = document.querySelectorAll('.project-card');
    projectCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
        observer.observe(card);
    });

    // Search input live feedback
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            if (searchTerm.length > 0) {
                this.style.borderColor = 'var(--primary-purple)';
            } else {
                this.style.borderColor = '#e2e8f0';
            }
        });
    }

    // Project ID click to copy functionality
    const projectIds = document.querySelectorAll('.project-id');
    projectIds.forEach(id => {
        id.style.cursor = 'pointer';
        id.title = 'Click to copy ID';

        id.addEventListener('click', function() {
            const idText = this.textContent.replace('ID: ', '');
            navigator.clipboard.writeText(idText).then(() => {
                const originalText = this.textContent;
                this.textContent = 'ID: Copied!';
                this.style.color = 'var(--success-color)';

                setTimeout(() => {
                    this.textContent = originalText;
                    this.style.color = '';
                }, 2000);
            }).catch(() => {
                // Fallback for browsers that don't support clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = idText;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);

                const originalText = this.textContent;
                this.textContent = 'ID: Copied!';
                this.style.color = 'var(--success-color)';

                setTimeout(() => {
                    this.textContent = originalText;
                    this.style.color = '';
                }, 2000);
            });
        });
    });

    // Keyboard navigation for modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                const modal = bootstrap.Modal.getInstance(openModal);
                if (modal) modal.hide();
            }
        }
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.classList.contains('show')) {
                const alertInstance = new bootstrap.Alert(alert);
                alertInstance.close();
            }
        }, 5000);
    });

    // Add hover effects for disabled edit buttons
    document.addEventListener('mouseover', function(e) {
        if (e.target.matches('button[disabled]') && e.target.title) {
            e.target.style.cursor = 'not-allowed';
        }
    });
});