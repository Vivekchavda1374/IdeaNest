<!-- Report Idea Modal -->
<div class="modal fade" id="reportIdeaModal" tabindex="-1" aria-labelledby="reportIdeaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="reportIdeaModalLabel">
                    <i class="fas fa-flag me-2"></i>Report Idea
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reportIdeaForm">
                    <input type="hidden" id="reportIdeaId" name="idea_id">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Help us maintain a safe community.</strong> Please report ideas that violate our community guidelines.
                    </div>
                    
                    <div class="mb-3">
                        <label for="reportReason" class="form-label">Reason for reporting <span class="text-danger">*</span></label>
                        <select class="form-select" id="reportReason" name="report_reason" required>
                            <option value="">Select a reason...</option>
                            <option value="spam">Spam or repetitive content</option>
                            <option value="inappropriate">Inappropriate content</option>
                            <option value="offensive">Offensive or harmful language</option>
                            <option value="copyright">Copyright violation</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reportDetails" class="form-label">Additional details (optional)</label>
                        <textarea class="form-control" id="reportDetails" name="report_details" rows="3" 
                                  placeholder="Please provide more details about why you're reporting this idea..."></textarea>
                        <div class="form-text">Help us understand the issue better by providing specific details.</div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <small><strong>Note:</strong> False reports may result in restrictions on your account. Please only report content that genuinely violates our guidelines.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-warning" id="submitReportBtn">
                    <i class="fas fa-flag me-1"></i>Submit Report
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Setup report functionality
    const reportModal = document.getElementById('reportIdeaModal');
    const reportForm = document.getElementById('reportIdeaForm');
    const submitReportBtn = document.getElementById('submitReportBtn');
    
    // Function to show report modal
    window.showReportModal = function(ideaId, ideaTitle) {
        document.getElementById('reportIdeaId').value = ideaId;
        document.getElementById('reportIdeaModalLabel').innerHTML = 
            `<i class="fas fa-flag me-2"></i>Report Idea: ${ideaTitle}`;
        
        // Reset form
        reportForm.reset();
        document.getElementById('reportIdeaId').value = ideaId;
        
        const modal = new bootstrap.Modal(reportModal);
        modal.show();
    };
    
    // Handle report submission
    if (submitReportBtn) {
        submitReportBtn.addEventListener('click', function() {
            const formData = new FormData(reportForm);
            
            // Validate form
            const reason = formData.get('report_reason');
            if (!reason) {
                showToast('Please select a reason for reporting', 'warning');
                return;
            }
            
            // Disable button during submission
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Submitting...';
            
            fetch('report_idea.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    bootstrap.Modal.getInstance(reportModal).hide();
                } else {
                    showToast(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Report error:', error);
                showToast('Network error. Please try again.', 'danger');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-flag me-1"></i>Submit Report';
            });
        });
    }
    
    // Toast notification function (if not already defined)
    if (typeof showToast === 'undefined') {
        window.showToast = function(message, type = 'info') {
            // Remove existing toasts
            const existingToasts = document.querySelectorAll('.toast-notification');
            existingToasts.forEach(toast => toast.remove());

            const toast = document.createElement('div');
            toast.className = `toast-notification alert alert-${type} alert-dismissible fade show`;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                min-width: 300px;
                animation: slideInRight 0.3s ease;
            `;

            const icons = {
                success: 'fas fa-check-circle',
                danger: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };

            toast.innerHTML = `
                <i class="${icons[type] || icons.info} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.remove();
                }
            }, 5000);
        };
    }
});
</script>

<style>
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.toast-notification {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    border: none;
    border-radius: 8px;
}
</style>