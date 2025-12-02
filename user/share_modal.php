<!-- Share Content Modal -->
<div class="modal fade" id="shareContentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-share-alt"></i> Share <span id="shareContentType"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Content Preview -->
                <div class="content-preview mb-3" id="contentPreview">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title" id="previewTitle"></h6>
                            <p class="card-text text-muted small" id="previewDescription"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Select User -->
                <div class="form-group">
                    <label for="selectUser">Select User to Share With:</label>
                    <select class="form-control" id="selectUser" required>
                        <option value="">-- Select a user --</option>
                    </select>
                </div>
                
                <!-- Optional Message -->
                <div class="form-group">
                    <label for="shareMessage">Add a message (optional):</label>
                    <textarea class="form-control" id="shareMessage" rows="3" 
                              placeholder="Add a personal message..."></textarea>
                </div>
                
                <!-- Share Button -->
                <button type="button" class="btn btn-primary btn-block" id="btnShare">
                    <i class="fas fa-paper-plane"></i> Share
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.content-preview .card {
    border-left: 4px solid #007bff;
    background-color: #f8f9fa;
}

.content-preview .card-title {
    color: #007bff;
    font-weight: 600;
}

#selectUser {
    border: 2px solid #e9ecef;
    transition: border-color 0.3s;
}

#selectUser:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

#btnShare {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 12px;
    font-weight: 600;
    transition: transform 0.2s;
}

#btnShare:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
</style>

<script>
let shareContentData = {
    type: null,
    id: null,
    title: null,
    description: null
};

// Initialize share modal
function initShareModal(contentType, contentId, title, description) {
    shareContentData = {
        type: contentType,
        id: contentId,
        title: title,
        description: description
    };
    
    $('#shareContentType').text(contentType.charAt(0).toUpperCase() + contentType.slice(1));
    $('#previewTitle').text(title);
    $('#previewDescription').text(description.substring(0, 150) + '...');
    
    // Load users who can receive messages
    loadAvailableUsers();
    
    // Show modal using Bootstrap 5
    const modalElement = document.getElementById('shareContentModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

// Get the correct API path based on current location
function getApiPath(endpoint) {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/Blog/')) {
        return '../api/' + endpoint;
    }
    return 'api/' + endpoint;
}

// Load users with active conversations
function loadAvailableUsers() {
    $.ajax({
        url: getApiPath('get_message_contacts.php'),
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">-- Select a user --</option>';
                response.users.forEach(function(user) {
                    options += `<option value="${user.id}">${user.name} (${user.email})</option>`;
                });
                $('#selectUser').html(options);
            } else {
                showNotification(response.message || 'Error loading users', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading users:', error);
            showNotification('Error loading users. Please try again.', 'error');
        }
    });
}

// Share content
$('#btnShare').click(function() {
    const receiverId = $('#selectUser').val();
    const message = $('#shareMessage').val();
    
    if (!receiverId) {
        showNotification('Please select a user', 'warning');
        return;
    }
    
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sharing...');
    
    $.ajax({
        url: getApiPath('share_content.php'),
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            receiver_id: receiverId,
            content_type: shareContentData.type,
            content_id: shareContentData.id,
            message: message
        }),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showNotification(response.message, 'success');
                // Hide modal using Bootstrap 5
                const modalElement = document.getElementById('shareContentModal');
                const modal = bootstrap.Modal.getInstance(modalElement);
                if (modal) {
                    modal.hide();
                }
                $('#shareMessage').val('');
            } else {
                showNotification(response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error sharing content:', error);
            showNotification('Error sharing content. Please try again.', 'error');
        },
        complete: function() {
            btn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Share');
        }
    });
});

// Notification helper
function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-warning';
    
    const notification = $(`
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert" 
             style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `);
    
    $('body').append(notification);
    setTimeout(() => {
        const alert = bootstrap.Alert.getInstance(notification[0]);
        if (alert) {
            alert.close();
        } else {
            notification.remove();
        }
    }, 3000);
}
</script>
