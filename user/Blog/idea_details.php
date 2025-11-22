<?php
session_start();
require_once '../../Login/Login/db.php';

$idea_id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;

if ($idea_id <= 0) {
    echo '<p class="text-danger">Invalid idea ID</p>';
    exit;
}

// Get idea details with all stats
$sql = "SELECT b.*, 
        COALESCE(r.name, 'Unknown') as author_name,
        (SELECT COUNT(*) FROM idea_likes WHERE idea_id=b.id) as total_likes,
        (SELECT COUNT(*) FROM idea_views WHERE idea_id=b.id) as total_views,
        (SELECT COUNT(*) FROM idea_shares WHERE idea_id=b.id) as total_shares,
        (SELECT COUNT(*) FROM idea_followers WHERE idea_id=b.id) as total_followers,
        (SELECT AVG(rating) FROM idea_ratings WHERE idea_id=b.id) as avg_rating,
        (SELECT COUNT(*) FROM idea_ratings WHERE idea_id=b.id) as total_ratings,
        (SELECT COUNT(*) FROM idea_reports WHERE idea_id=b.id) as total_reports
        FROM blog b
        LEFT JOIN register r ON b.user_id=r.id
        WHERE b.id=$idea_id";

$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    echo '<p class="text-danger">Idea not found</p>';
    exit;
}

$idea = $result->fetch_assoc();

// Get tags
$stmt = $conn->prepare("SELECT t.tag_name, t.tag_color FROM idea_tag_relations tr JOIN idea_tags t ON tr.tag_id=t.id WHERE tr.idea_id=?");
$stmt->bind_param("i", $idea_id);
$stmt->execute();
$tags_result = $stmt->get_result();
$tags = $tags_result ? $tags_result->fetch_all(MYSQLI_ASSOC) : [];

// Get recent activity
$activity_result = $conn->query("SELECT al.*, r.name as user_name FROM idea_activity_log al LEFT JOIN register r ON al.user_id=r.id WHERE al.idea_id=$idea_id ORDER BY al.created_at DESC LIMIT 10");
$activities = $activity_result ? $activity_result->fetch_all(MYSQLI_ASSOC) : [];

// Get collaborations
$collab_result = $conn->query("SELECT ic.*, r1.name as requester_name, r2.name as owner_name FROM idea_collaborations ic LEFT JOIN register r1 ON ic.requester_id=r1.id LEFT JOIN register r2 ON ic.owner_id=r2.id WHERE ic.idea_id=$idea_id ORDER BY ic.created_at DESC");
$collaborations = $collab_result ? $collab_result->fetch_all(MYSQLI_ASSOC) : [];

// Get comments with replies
$comments_result = $conn->query("SELECT c.*, r.name as user_name FROM idea_comments c LEFT JOIN register r ON c.user_id=r.id WHERE c.idea_id=$idea_id ORDER BY c.parent_id ASC, c.created_at ASC");
$all_comments = $comments_result ? $comments_result->fetch_all(MYSQLI_ASSOC) : [];

// Organize comments into parent-child structure
$comments = [];
$replies = [];
foreach ($all_comments as $comment) {
    if ($comment['parent_id'] === null) {
        $comments[] = $comment;
    } else {
        $replies[$comment['parent_id']][] = $comment;
    }
}
?>

<div class="idea-detail-content">
    <div class="row">
        <div class="col-md-8">
            <h4><?= htmlspecialchars($idea['project_name']) ?></h4>
            <p class="text-muted mb-3">
                By <?= htmlspecialchars($idea['author_name']) ?> • 
                <?= date('M j, Y', strtotime($idea['submission_datetime'])) ?>
            </p>
            
            <div class="mb-3">
                <strong>Classification:</strong> <?= htmlspecialchars($idea['classification']) ?><br>
                <strong>Type:</strong> <?= htmlspecialchars(ucfirst($idea['project_type'])) ?><br>
                <strong>Status:</strong> <?= htmlspecialchars(ucfirst($idea['status'])) ?>
            </div>
            
            <div class="mb-4">
                <h6>Description</h6>
                <p><?= nl2br(htmlspecialchars($idea['description'])) ?></p>
            </div>
            
            <?php if(count($tags) > 0): ?>
            <div class="mb-3">
                <h6>Tags</h6>
                <?php foreach($tags as $tag): ?>
                    <span class="badge me-1" style="background-color:<?= $tag['tag_color'] ?>">
                        <?= htmlspecialchars($tag['tag_name']) ?>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-heart text-danger"></i> Likes</span>
                        <strong><?= $idea['total_likes'] ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-eye text-primary"></i> Views</span>
                        <strong><?= $idea['total_views'] ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-users text-success"></i> Followers</span>
                        <strong><?= $idea['total_followers'] ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-share text-info"></i> Shares</span>
                        <strong><?= $idea['total_shares'] ?></strong>
                    </div>
                    <?php if($idea['avg_rating'] > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="fas fa-star text-warning"></i> Rating</span>
                        <strong><?= round($idea['avg_rating'], 1) ?>/5 (<?= $idea['total_ratings'] ?>)</strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if(count($collaborations) > 0): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Collaboration Requests</h6>
                </div>
                <div class="card-body">
                    <?php foreach(array_slice($collaborations, 0, 3) as $collab): ?>
                        <div class="mb-2 pb-2 border-bottom">
                            <small class="text-muted"><?= htmlspecialchars($collab['requester_name']) ?></small>
                            <span class="badge badge-<?= $collab['status'] === 'pending' ? 'warning' : ($collab['status'] === 'accepted' ? 'success' : 'danger') ?> ms-2">
                                <?= ucfirst($collab['status']) ?>
                            </span>
                            <?php if($collab['message']): ?>
                                <div class="small mt-1"><?= htmlspecialchars($collab['message']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if(count($activities) > 0): ?>
    <div class="mt-4">
        <h6>Recent Activity</h6>
        <div class="activity-timeline">
            <?php foreach(array_slice($activities, 0, 5) as $activity): ?>
                <div class="activity-item mb-2 p-2 bg-light rounded">
                    <small class="text-muted"><?= date('M j, g:i A', strtotime($activity['created_at'])) ?></small>
                    <div><?= htmlspecialchars($activity['user_name'] ?? 'Someone') ?> <?= $activity['activity_type'] ?> this idea</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Comments Section -->
    <div class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6>Comments (<?= count($comments) ?>)</h6>
            <?php if($user_id > 0): ?>
                <button class="btn btn-sm btn-primary" onclick="toggleCommentForm()">Add Comment</button>
            <?php endif; ?>
        </div>
        
        <!-- Comment Form -->
        <?php if($user_id > 0): ?>
        <div id="commentForm" class="mb-4" style="display:none">
            <form onsubmit="submitComment(event)">
                <input type="hidden" name="idea_id" value="<?= $idea_id ?>">
                <textarea class="form-control mb-2" name="comment" rows="3" placeholder="Write your comment..." required></textarea>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Post Comment</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleCommentForm()">Cancel</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Comments List -->
        <div class="comments-list">
            <?php foreach($comments as $comment): ?>
                <div class="comment-item mb-3 p-3 border rounded">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong><?= htmlspecialchars($comment['user_name']) ?></strong>
                            <small class="text-muted ms-2"><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></small>
                        </div>
                        <?php if($user_id > 0): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleReplyForm(<?= $comment['id'] ?>)">Reply</button>
                        <?php endif; ?>
                    </div>
                    <p class="mb-2"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                    
                    <!-- Reply Form -->
                    <?php if($user_id > 0): ?>
                    <div id="replyForm<?= $comment['id'] ?>" class="mt-3" style="display:none">
                        <form onsubmit="submitReply(event, <?= $comment['id'] ?>)">
                            <input type="hidden" name="idea_id" value="<?= $idea_id ?>">
                            <input type="hidden" name="parent_id" value="<?= $comment['id'] ?>">
                            <textarea class="form-control mb-2" name="comment" rows="2" placeholder="Write your reply..." required></textarea>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">Reply</button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleReplyForm(<?= $comment['id'] ?>)">Cancel</button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Replies -->
                    <?php if(isset($replies[$comment['id']])): ?>
                        <div class="replies ms-4 mt-3">
                            <?php foreach($replies[$comment['id']] as $reply): ?>
                                <div class="reply-item mb-2 p-2 bg-light rounded">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong class="small"><?= htmlspecialchars($reply['user_name']) ?></strong>
                                        <small class="text-muted"><?= date('M j, g:i A', strtotime($reply['created_at'])) ?></small>
                                    </div>
                                    <p class="mb-0 small"><?= nl2br(htmlspecialchars($reply['comment'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <?php if(count($comments) === 0): ?>
                <p class="text-muted text-center py-4">No comments yet. Be the first to comment!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('ideaDetailModal').dataset.ideaId = '<?= $idea_id ?>';

function submitComment(event) {
    event.preventDefault();
    const form = event.target;
    const comment = form.querySelector('[name="comment"]').value.trim();
    const ideaId = <?= $idea_id ?>;
    
    if (!comment) {
        alert('Please enter a comment');
        return;
    }
    
    IdeaAjax.addComment(ideaId, comment, null, (response) => {
        if (response.success) {
            // Reload the modal content to show new comment
            location.reload();
        }
    });
}

function submitReply(event, parentId) {
    event.preventDefault();
    const form = event.target;
    const comment = form.querySelector('[name="comment"]').value.trim();
    const ideaId = <?= $idea_id ?>;
    
    if (!comment) {
        alert('Please enter a reply');
        return;
    }
    
    IdeaAjax.addComment(ideaId, comment, parentId, (response) => {
        if (response.success) {
            // Reload the modal content to show new reply
            location.reload();
        }
    });
}
</script>