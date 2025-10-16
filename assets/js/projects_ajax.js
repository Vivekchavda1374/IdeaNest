/**
 * Projects AJAX Handler
 * Handles all project-related AJAX operations
 */

class ProjectsAjax {
    constructor() {
        this.currentPage = 1;
        this.loading = false;
        this.init();
    }

    init() {
        this.setupLikeButtons();
        this.setupBookmarkButtons();
        this.setupCommentForms();
        this.setupLoadMore();
        this.setupFilters();
        this.setupSearch();
    }

    // Setup like buttons
    setupLikeButtons() {
        document.addEventListener('click', async (e) => {
            const likeBtn = e.target.closest('.ajax-like-btn');
            if (!likeBtn) return;

            e.preventDefault();
            e.stopPropagation();

            const projectId = likeBtn.dataset.projectId;
            const type = likeBtn.dataset.type || 'project';
            
            likeBtn.disabled = true;
            const originalHtml = likeBtn.innerHTML;
            likeBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const response = await ajaxCore.post('api/ajax_router.php', {
                    action: 'toggle_like',
                    project_id: projectId,
                    type: type
                });

                if (response.success) {
                    // Update button state
                    likeBtn.classList.toggle('liked', response.liked);
                    
                    // Update count
                    const countElement = likeBtn.querySelector('.like-count');
                    if (countElement) {
                        countElement.textContent = response.count;
                    }
                    
                    // Update icon
                    const icon = response.liked ? 'fas fa-heart' : 'far fa-heart';
                    likeBtn.innerHTML = `<i class="${icon}"></i> <span class="like-count">${response.count}</span>`;
                    
                    ajaxCore.showToast(response.message, 'success');
                } else {
                    likeBtn.innerHTML = originalHtml;
                    ajaxCore.showToast(response.message, 'warning');
                }
            } catch (error) {
                likeBtn.innerHTML = originalHtml;
                ajaxCore.showToast('Failed to update like', 'danger');
            } finally {
                likeBtn.disabled = false;
            }
        });
    }

    // Setup bookmark buttons
    setupBookmarkButtons() {
        document.addEventListener('click', async (e) => {
            const bookmarkBtn = e.target.closest('.ajax-bookmark-btn');
            if (!bookmarkBtn) return;

            e.preventDefault();
            e.stopPropagation();

            const projectId = bookmarkBtn.dataset.projectId;
            
            bookmarkBtn.disabled = true;
            const originalHtml = bookmarkBtn.innerHTML;
            bookmarkBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            try {
                const response = await ajaxCore.post('api/ajax_router.php', {
                    action: 'toggle_bookmark',
                    project_id: projectId
                });

                if (response.success) {
                    bookmarkBtn.classList.toggle('bookmarked', response.bookmarked);
                    
                    const icon = response.bookmarked ? 'fas fa-bookmark' : 'far fa-bookmark';
                    const text = response.bookmarked ? 'Saved' : 'Save';
                    bookmarkBtn.innerHTML = `<i class="${icon}"></i> ${text}`;
                    
                    ajaxCore.showToast(response.message, 'success');
                } else {
                    bookmarkBtn.innerHTML = originalHtml;
                    ajaxCore.showToast(response.message, 'warning');
                }
            } catch (error) {
                bookmarkBtn.innerHTML = originalHtml;
                ajaxCore.showToast('Failed to update bookmark', 'danger');
            } finally {
                bookmarkBtn.disabled = false;
            }
        });
    }

    // Setup comment forms
    setupCommentForms() {
        document.addEventListener('submit', async (e) => {
            const form = e.target.closest('.ajax-comment-form');
            if (!form) return;

            e.preventDefault();

            const submitBtn = form.querySelector('[type="submit"]');
            const textarea = form.querySelector('textarea');
            const projectId = form.dataset.projectId;
            const type = form.dataset.type || 'project';
            
            if (!textarea.value.trim()) {
                ajaxCore.showToast('Please enter a comment', 'warning');
                return;
            }

            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';

            try {
                const response = await ajaxCore.post('api/ajax_router.php', {
                    action: 'add_comment',
                    project_id: projectId,
                    comment_text: textarea.value,
                    type: type
                });

                if (response.success) {
                    textarea.value = '';
                    ajaxCore.showToast(response.message, 'success');
                    
                    // Update comment count
                    const countElements = document.querySelectorAll(`[data-project-id="${projectId}"] .comment-count`);
                    countElements.forEach(el => el.textContent = response.count);
                    
                    // Reload comments if comments section is visible
                    await this.loadComments(projectId, type);
                } else {
                    ajaxCore.showToast(response.message, 'danger');
                }
            } catch (error) {
                ajaxCore.showToast('Failed to add comment', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }

    // Load comments
    async loadComments(projectId, type = 'project') {
        const commentsContainer = document.querySelector(`#comments-${projectId}`);
        if (!commentsContainer) return;

        try {
            const response = await ajaxCore.get('api/ajax_router.php', {
                action: 'load_comments',
                project_id: projectId,
                type: type
            });

            if (response.success) {
                commentsContainer.innerHTML = this.renderComments(response.comments);
            }
        } catch (error) {
            console.error('Failed to load comments:', error);
        }
    }

    // Render comments HTML
    renderComments(comments) {
        if (!comments || comments.length === 0) {
            return '<p class="text-muted text-center">No comments yet. Be the first to comment!</p>';
        }

        return comments.map(comment => `
            <div class="comment-item" data-comment-id="${comment.id}">
                <div class="comment-header">
                    <strong>${this.escapeHtml(comment.user_name || 'Anonymous')}</strong>
                    <small class="text-muted">${this.formatDate(comment.created_at)}</small>
                </div>
                <div class="comment-body">
                    ${this.escapeHtml(comment.comment)}
                </div>
            </div>
        `).join('');
    }

    // Setup load more functionality
    setupLoadMore() {
        const loadMoreBtn = document.querySelector('#ajax-load-more');
        if (!loadMoreBtn) return;

        loadMoreBtn.addEventListener('click', async () => {
            if (this.loading) return;

            this.loading = true;
            this.currentPage++;

            const originalText = loadMoreBtn.innerHTML;
            loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            loadMoreBtn.disabled = true;

            try {
                const response = await ajaxCore.get('api/ajax_router.php', {
                    action: 'load_projects',
                    page: this.currentPage,
                    per_page: 6
                });

                if (response.success && response.projects.length > 0) {
                    const container = document.querySelector('#projects-container');
                    const html = this.renderProjects(response.projects);
                    container.insertAdjacentHTML('beforeend', html);

                    // Re-initialize handlers for new elements
                    this.init();

                    if (!response.has_more) {
                        loadMoreBtn.style.display = 'none';
                    }
                } else {
                    loadMoreBtn.style.display = 'none';
                    ajaxCore.showToast('No more projects to load', 'info');
                }
            } catch (error) {
                ajaxCore.showToast('Failed to load more projects', 'danger');
            } finally {
                this.loading = false;
                loadMoreBtn.innerHTML = originalText;
                loadMoreBtn.disabled = false;
            }
        });
    }

    // Setup filters
    setupFilters() {
        const filterForm = document.querySelector('#ajax-filter-form');
        if (!filterForm) return;

        filterForm.addEventListener('change', async () => {
            const formData = new FormData(filterForm);
            const params = {
                action: 'filter_projects',
                type: formData.get('type'),
                status: formData.get('status'),
                classification: formData.get('classification')
            };

            const container = document.querySelector('#projects-container');
            container.innerHTML = '<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-3x"></i></div>';

            try {
                const response = await ajaxCore.get('api/ajax_router.php', params);

                if (response.success) {
                    container.innerHTML = this.renderProjects(response.projects);
                    this.init(); // Re-initialize handlers
                } else {
                    container.innerHTML = '<div class="alert alert-info">No projects found</div>';
                }
            } catch (error) {
                container.innerHTML = '<div class="alert alert-danger">Failed to load projects</div>';
            }
        });
    }

    // Setup search
    setupSearch() {
        const searchInput = document.querySelector('#ajax-search-input');
        if (!searchInput) return;

        let searchTimeout;
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => this.performSearch(searchInput.value), 500);
        });
    }

    // Perform search
    async performSearch(query) {
        if (query.length < 2) return;

        const resultsContainer = document.querySelector('#search-results');
        if (!resultsContainer) return;

        resultsContainer.innerHTML = '<div class="p-2"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
        resultsContainer.style.display = 'block';

        try {
            const response = await ajaxCore.get('api/ajax_router.php', {
                action: 'search',
                q: query,
                type: 'all'
            });

            if (response.success && response.results.length > 0) {
                resultsContainer.innerHTML = this.renderSearchResults(response.results);
            } else {
                resultsContainer.innerHTML = '<div class="p-2 text-muted">No results found</div>';
            }
        } catch (error) {
            resultsContainer.innerHTML = '<div class="p-2 text-danger">Search failed</div>';
        }
    }

    // Render search results
    renderSearchResults(results) {
        return results.map(result => `
            <a href="${result.type === 'project' ? 'all_projects.php' : 'Blog/list-project.php'}?id=${result.id}" 
               class="search-result-item">
                <div class="search-result-title">${this.escapeHtml(result.title)}</div>
                <div class="search-result-desc">${this.escapeHtml(result.description.substring(0, 100))}...</div>
            </a>
        `).join('');
    }

    // Render projects HTML
    renderProjects(projects) {
        return projects.map(project => `
            <div class="project-card" data-project-id="${project.id}">
                <h3>${this.escapeHtml(project.project_name)}</h3>
                <p>${this.escapeHtml(project.description.substring(0, 150))}...</p>
                <div class="project-actions">
                    <button class="ajax-like-btn" data-project-id="${project.id}">
                        <i class="far fa-heart"></i> <span class="like-count">${project.like_count || 0}</span>
                    </button>
                    <button class="ajax-bookmark-btn" data-project-id="${project.id}">
                        <i class="far fa-bookmark"></i> Save
                    </button>
                    <span class="comment-count">${project.comment_count || 0} comments</span>
                </div>
            </div>
        `).join('');
    }

    // Utility functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.projectsAjax = new ProjectsAjax();
});
