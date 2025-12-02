/**
 * Add this code to user/chat/index.php to handle shared idea/project messages
 * Insert this code in the loadMessages function, right after the decryption attempt
 */

// Add this after line ~990 where messages are being processed
// Replace the message rendering section with this enhanced version:

/*
for (const msg of messages) {
    let decrypted = 'Message';
    let isSharedContent = false;
    let sharedContentHTML = '';
    
    // Check if this is a shared content message
    if (msg.message_type === 'idea_share' || msg.message_type === 'project_share') {
        isSharedContent = true;
        const contentType = msg.message_type === 'idea_share' ? 'Idea' : 'Project';
        const contentId = msg.message_type === 'idea_share' ? msg.shared_idea_id : msg.shared_project_id;
        const viewLink = msg.message_type === 'idea_share' 
            ? `../Blog/idea_details.php?id=${contentId}`
            : `../view_idea.php?id=${contentId}`;
        
        // Try to decrypt any accompanying message
        try {
            if (msg.encrypted_content && msg.encrypted_content.trim() !== '') {
                decrypted = await chatEncryption.decryptMessage(
                    msg.encrypted_content,
                    msg.iv,
                    currentEncryptionKey
                );
            } else {
                decrypted = `Shared a ${contentType.toLowerCase()} with you`;
            }
        } catch (e) {
            decrypted = `Shared a ${contentType.toLowerCase()} with you`;
        }
        
        // Create shared content card
        sharedContentHTML = `
            <div class="shared-content-card" style="
                background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
                border: 2px solid rgba(59, 130, 246, 0.3);
                border-radius: 12px;
                padding: 15px;
                margin-top: 10px;
            ">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <i class="fas fa-${msg.message_type === 'idea_share' ? 'lightbulb' : 'project-diagram'}" 
                       style="color: #3b82f6; font-size: 1.2rem;"></i>
                    <span style="font-weight: 600; color: #3b82f6;">Shared ${contentType}</span>
                </div>
                <a href="${viewLink}" target="_blank" style="
                    display: inline-block;
                    background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                    color: white;
                    padding: 8px 16px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-size: 0.9rem;
                    transition: transform 0.2s;
                " onmouseover="this.style.transform='translateY(-2px)'" 
                   onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-external-link-alt"></i> View ${contentType}
                </a>
            </div>
        `;
    } else {
        // Regular message - decrypt normally
        try {
            decrypted = await chatEncryption.decryptMessage(
                msg.encrypted_content,
                msg.iv,
                currentEncryptionKey
            );
        } catch (e) {
            console.error('Error decrypting message:', e);
            decrypted = 'Message';
        }
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${msg.sender_id == currentUserId ? 'sent' : 'received'}`;
    messageDiv.dataset.messageId = msg.id;
    
    const escapedText = decrypted.replace(/'/g, "&#39;").replace(/"/g, "&quot;").replace(/\\/g, "&#92;");
    const displayText = decrypted.replace(/</g, "&lt;").replace(/>/g, "&gt;");
    const actions = msg.sender_id == currentUserId ? `
        <div class="message-actions">
            <button class="message-menu-btn" onclick="toggleMessageMenu(event, ${msg.id})">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="message-dropdown" id="dropdown-${msg.id}">
                ${!isSharedContent ? `
                <div class="message-dropdown-item" onclick="editMessage(event, ${msg.id}, '${escapedText}')">
                    <i class="fas fa-edit"></i> Edit
                </div>
                ` : ''}
                <div class="message-dropdown-item delete" onclick="deleteMessage(event, ${msg.id})">
                    <i class="fas fa-trash"></i> Delete
                </div>
            </div>
        </div>
    ` : '';
    
    messageDiv.innerHTML = `
        <div class="message-content">
            ${actions}
            <div class="message-text">${displayText}</div>
            ${sharedContentHTML}
            <div class="message-time">${formatTime(msg.created_at)}</div>
        </div>
    `;
    messagesContainer.appendChild(messageDiv);
}
*/
