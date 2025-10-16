// Project Interactions JavaScript
// Handles bookmark, like, and comment functionality

class ProjectInteractions {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        console.log('Project interactions initialized');
    }

    bindEvents() {
        // Bookmark buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.bookmark-btn-ajax')) {
                e.preventDefault();
                this.handleBookmark(e.target.closest('.bookmark-btn-ajax'));
            }
        });

        // Like buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.like-btn-ajax')) {
                e.preventDefault();
                this.handleLike(e.target.closest('.like-btn-ajax'));
            }
        });

        // Comment like buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.comment-like-btn-ajax')) {
                e.preventDefault();
                this.handleCommentLike(e.target.closest('.comment-like-btn-ajax'));
            }
        });

        // Comment forms
        document.addEventListener('submit', (e) => {
            if (e.target.closest('.comment-form-ajax')) {
                e.preventDefault();
                this.handleCommentSubmit(e.target.closest('.comment-form-ajax'));
            }
        });
    }

    async handleBookmark(button) {
        const projectId = button.dataset.projectId;
        
        if (!projectId) {
            this.showMessage('Error: Project ID not found', 'error');
            return;
        }

        // Show loading state
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        button.disabled = true;

        try {
            const response = await fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_bookmark&project_id=${projectId}`
            });

            const data = await response.json();

            if (data.success) {
                // Update button state
                if (data.bookmarked) {
                    button.classList.add('bookmarked');
                    button.innerHTML = '<i class="fas fa-bookmark"></i> Saved';
                } else {
                    button.classList.remove('bookmarked');
                    button.innerHTML = '<i class="fas fa-bookmark"></i> Save';
                }
                
                this.showMessage(data.message, 'success');
            } else {
                this.showMessage(data.message || 'Failed to update bookmark', 'error');
                button.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Bookmark error:', error);
            this.showMessage('Network error occurred', 'error');
            button.innerHTML = originalText;
        } finally {
            button.disabled = false;
        }
    }

    async handleLike(button) {
        const projectId = button.dataset.projectId;
        
        if (!projectId) {
            this.showMessage('Error: Project ID not found', 'error');
            return;
        }

        // Show loading state
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        try {
            const response = await fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_like&project_id=${projectId}`
            });

            const data = await response.json();

            if (data.success) {
                // Update button state
                if (data.liked) {
                    button.classList.add('liked');
                } else {
                    button.classList.remove('liked');
                }
                
                // Update like count
                button.innerHTML = `<i class="fas fa-heart"></i> <span>${data.count}</span>`;
                
                this.showMessage(data.liked ? 'Project liked!' : 'Like removed', 'success');
            } else {
                this.showMessage(data.message || 'Failed to update like', 'error');
                button.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Like error:', error);
            this.showMessage('Network error occurred', 'error');
            button.innerHTML = originalText;
        } finally {
            button.disabled = false;
        }
    }

    async handleCommentLike(button) {
        const commentId = button.dataset.commentId;
        
        if (!commentId) {
            this.showMessage('Error: Comment ID not found', 'error');
            return;
        }

        // Show loading state
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;

        try {
            const response = await fetch('ajax_handlers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_comment_like&comment_id=${commentId}`
            });

            const data = await response.json();

            if (data.success) {
                // Update button state
                if (data.liked) {
                    button.classList.add('liked');
                } else {
                    button.classList.remove('liked');
                }
                
                // Update like count
                button.innerHTML = `<i class="fas fa-heart"></i> <span>${data.count}</span>`;
            } else {
                this.showMessage(data.message || 'Failed to update comment like', 'error');
                button.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Comment like error:', error);
            this.showMessage('Network error occurred', 'error');
            button.innerHTML = originalText;
        } finally {
            button.disabled = false;
        }
    }

    async handleCommentSubmit(form) {
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const textarea = form.querySelector('textarea[name="comment_text"]');
        
        if (!textarea.value.trim()) {
            this.showMessage('Please enter a comment', 'error');
            return;
        }

        // Show loading state
        const originalText = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
        submitButton.disabled = true;

        try {
            const response = await fetch('ajax_handlers.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showMessage(data.message, 'success');
                
                // Clear the form
                textarea.value = '';
                
                // Optionally reload comments or update comment count
                this.updateCommentCount(formData.get('project_id'), data.count);
                
                // You might want to reload the page or dynamically add the comment
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                this.showMessage(data.message || 'Failed to add comment', 'error');
            }
        } catch (error) {
            console.error('Comment submit error:', error);
            this.showMessage('Network error occurred', 'error');
        } finally {
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }
    }

    updateCommentCount(projectId, count) {
        // Update comment count displays
        const commentCountElements = document.querySelectorAll(`[data-project-id="${projectId}"] .comment-count`);
        commentCountElements.forEach(element => {
            element.textContent = count;
        });
    }

    showMessage(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(toast);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 5000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ProjectInteractions();
});

// Export for use in other scripts
window.ProjectInteractions = ProjectInteractions;