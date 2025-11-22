/**
 * IdeaNest - AJAX Handler for Ideas
 * Handles all AJAX interactions for idea pages
 */

const IdeaAjax = {
    baseUrl: 'ajax_idea_handler.php',
    
    /**
     * Toggle like on an idea
     */
    toggleLike: function(ideaId, callback) {
        this.sendRequest({
            action: 'toggle_like',
            idea_id: ideaId
        }, (response) => {
            if (response.success) {
                // Update UI
                const likeBtn = document.querySelector(`[data-idea-id="${ideaId}"] .like-btn`);
                if (likeBtn) {
                    likeBtn.classList.toggle('liked', response.liked);
                    const countSpan = likeBtn.querySelector('.count');
                    if (countSpan) countSpan.textContent = response.count;
                }
                
                // Update stats row
                const statsLike = document.querySelector(`[data-idea-id="${ideaId}"] .stats-row .stat-item:first-child span`);
                if (statsLike) statsLike.textContent = response.count;
            }
            if (callback) callback(response);
        });
    },
    
    /**
     * Toggle bookmark on an idea
     */
    toggleBookmark: function(ideaId, callback) {
        this.sendRequest({
            action: 'toggle_bookmark',
            idea_id: ideaId
        }, (response) => {
            if (response.success) {
                const bookmarkBtn = document.querySelector(`[data-idea-id="${ideaId}"] .bookmark-btn`);
                if (bookmarkBtn) {
                    bookmarkBtn.classList.toggle('bookmarked', response.bookmarked);
                }
            }
            if (callback) callback(response);
        });
    },
    
    /**
     * Toggle follow on an idea
     */
    toggleFollow: function(ideaId, callback) {
        this.sendRequest({
            action: 'toggle_follow',
            idea_id: ideaId
        }, (response) => {
            if (response.success) {
                const followBtn = document.querySelector(`[data-idea-id="${ideaId}"] .follow-btn`);
                if (followBtn) {
                    followBtn.classList.toggle('following', response.following);
                }
                
                // Update follower count in stats
                const statsFollow = document.querySelector(`[data-idea-id="${ideaId}"] .stats-row .stat-item:nth-child(3) span`);
                if (statsFollow) statsFollow.textContent = response.count;
            }
            if (callback) callback(response);
        });
    },
    
    /**
     * Submit rating for an idea
     */
    submitRating: function(ideaId, rating, callback) {
        this.sendRequest({
            action: 'submit_rating',
            idea_id: ideaId,
            rating: rating
        }, (response) => {
            if (response.success) {
                // Update rating display
                const ratingBadge = document.querySelector(`[data-idea-id="${ideaId}"] .rating-badge`);
                if (ratingBadge) {
                    ratingBadge.textContent = `★ ${response.avg_rating}`;
                }
                
                this.showNotification('Rating submitted successfully!', 'success');
            }
            if (callback) callback(response);
        });
    },
    
    /**
     * Add a comment to an idea
     */
    addComment: function(ideaId, comment, parentId, callback) {
        this.sendRequest({
            action: 'add_comment',
            idea_id: ideaId,
            comment: comment,
            parent_id: parentId || ''
        }, (response) => {
            if (response.success) {
                this.showNotification('Comment added successfully!', 'success');
                
                // Update comment count
                const commentBtn = document.querySelector(`[data-idea-id="${ideaId}"] .comment-btn .count`);
                if (commentBtn) commentBtn.textContent = response.count;
            } else {
                this.showNotification(response.message || 'Failed to add comment', 'error');
            }
            if (callback) callback(response);
        });
    },
    
    /**
     * Delete a comment
     */
    deleteComment: function(commentId, callback) {
        if (!confirm('Are you sure you want to delete this comment?')) return;
        
        this.sendRequest({
            action: 'delete_comment',
            comment_id: commentId
        }, (response) => {
            if (response.success) {
                // Remove comment from DOM
                const commentEl = document.querySelector(`[data-comment-id="${commentId}"]`);
                if (commentEl) commentEl.remove();
                
                this.showNotification('Comment deleted', 'success');
            }
            if (callback) callback(response);
        });
    },
    
    /**
     * Track view for an idea
     */
    trackView: function(ideaId) {
        this.sendRequest({
            action: 'track_view',
            idea_id: ideaId
        });
    },
    
    /**
     * Track share for an idea
     */
    trackShare: function(ideaId, platform, callback) {
        this.sendRequest({
            action: 'track_share',
            idea_id: ideaId,
            platform: platform
        }, callback);
    },
    
    /**
     * Submit a report for an idea
     */
    submitReport: function(ideaId, reason, description, callback) {
        this.sendRequest({
            action: 'submit_report',
            idea_id: ideaId,
            reason: reason,
            description: description
        }, (response) => {
            if (response.success) {
                this.showNotification('Report submitted successfully', 'success');
            } else {
                this.showNotification(response.message || 'Failed to submit report', 'error');
            }
            if (callback) callback(response);
        });
    },
    
    /**
     * Generic AJAX request sender
     */
    sendRequest: function(data, callback) {
        const formData = new URLSearchParams();
        for (const key in data) {
            formData.append(key, data[key]);
        }
        
        fetch(this.baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (callback) callback(data);
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            this.showNotification('An error occurred. Please try again.', 'error');
        });
    },
    
    /**
     * Show notification to user
     */
    showNotification: function(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `ajax-notification ajax-notification-${type}`;
        notification.textContent = message;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
};

// Auto-initialize view tracking
document.addEventListener('DOMContentLoaded', function() {
    // Track views when idea cards are visible
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const ideaId = entry.target.dataset.ideaId;
                if (ideaId) {
                    IdeaAjax.trackView(ideaId);
                    observer.unobserve(entry.target);
                }
            }
        });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('.idea-card').forEach(card => {
        observer.observe(card);
    });
});

// Comment form handlers
function toggleCommentForm() {
    const form = document.getElementById('commentForm');
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        if (form.style.display === 'block') {
            form.querySelector('textarea')?.focus();
        }
    }
}

function toggleReplyForm(commentId) {
    const form = document.getElementById('replyForm' + commentId);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        if (form.style.display === 'block') {
            form.querySelector('textarea')?.focus();
        }
    }
}

function submitComment(event, ideaId) {
    event.preventDefault();
    const form = event.target;
    const comment = form.querySelector('[name="comment"]').value.trim();
    
    if (!comment) {
        IdeaAjax.showNotification('Please enter a comment', 'error');
        return;
    }
    
    IdeaAjax.addComment(ideaId, comment, null, (response) => {
        if (response.success) {
            // Add comment to DOM
            addCommentToDOM(response, null);
            form.reset();
            toggleCommentForm();
        }
    });
}

function submitReply(event, ideaId, parentId) {
    event.preventDefault();
    const form = event.target;
    const comment = form.querySelector('[name="comment"]').value.trim();
    
    if (!comment) {
        IdeaAjax.showNotification('Please enter a reply', 'error');
        return;
    }
    
    IdeaAjax.addComment(ideaId, comment, parentId, (response) => {
        if (response.success) {
            // Add reply to DOM
            addCommentToDOM(response, parentId);
            form.reset();
            toggleReplyForm(parentId);
        }
    });
}

function addCommentToDOM(commentData, parentId) {
    const commentHTML = `
        <div class="comment-item mb-3 p-3 border rounded" data-comment-id="${commentData.comment_id}">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <strong>${commentData.user_name}</strong>
                    <small class="text-muted ms-2">Just now</small>
                </div>
                <button class="btn btn-sm btn-outline-danger" onclick="IdeaAjax.deleteComment(${commentData.comment_id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <p class="mb-0">${commentData.comment}</p>
        </div>
    `;
    
    if (parentId) {
        // Add as reply
        const parentComment = document.querySelector(`[data-comment-id="${parentId}"]`);
        let repliesContainer = parentComment.querySelector('.replies');
        if (!repliesContainer) {
            repliesContainer = document.createElement('div');
            repliesContainer.className = 'replies ms-4 mt-3';
            parentComment.appendChild(repliesContainer);
        }
        repliesContainer.insertAdjacentHTML('beforeend', commentHTML);
    } else {
        // Add as main comment
        const commentsList = document.querySelector('.comments-list');
        if (commentsList) {
            commentsList.insertAdjacentHTML('afterbegin', commentHTML);
        }
    }
}

// Star rating handler
function initStarRating() {
    const stars = document.querySelectorAll('.star-rating i');
    stars.forEach((star) => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            const ideaId = document.getElementById('ideaDetailModal')?.dataset.ideaId;
            
            if (ideaId) {
                IdeaAjax.submitRating(ideaId, rating, (response) => {
                    if (response.success) {
                        // Update visual state
                        stars.forEach((s, i) => {
                            s.classList.toggle('active', i < rating);
                        });
                    }
                });
            }
        });
        
        // Hover effect
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            stars.forEach((s, i) => {
                s.style.color = i < rating ? '#fbbf24' : '#d1d5db';
            });
        });
    });
    
    const ratingContainer = document.querySelector('.star-rating');
    if (ratingContainer) {
        ratingContainer.addEventListener('mouseleave', function() {
            const activeRating = document.querySelectorAll('.star-rating i.active').length;
            stars.forEach((s, i) => {
                s.style.color = i < activeRating ? '#fbbf24' : '#d1d5db';
            });
        });
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initStarRating);
