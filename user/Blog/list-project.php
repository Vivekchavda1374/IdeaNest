<?php
require_once __DIR__ . '/../../includes/security_init.php';
session_start();
require_once '../../Login/Login/db.php';

$user_name = $_SESSION['user_name'] ?? "Guest";
$user_id = $_SESSION['user_id'] ?? 0;

// Check if user is logged in
if ($user_id == 0) {
    // Redirect to login if not authenticated
    // header("Location: ../../Login/Login/login.php");
    // exit;
}

// Handle like toggle
if (isset($_POST['toggle_like']) && $user_id > 0) {
    $idea_id = (int)$_POST['idea_id'];
    
    // Check if like already exists
    $stmt = $conn->prepare("SELECT id FROM idea_likes WHERE idea_id=? AND user_id=?");
    $stmt->bind_param("ii", $idea_id, $user_id);
    $stmt->execute();
    $check = $stmt->get_result();
    
    if ($check && $check->num_rows > 0) {
        // Unlike - remove the like
        $stmt = $conn->prepare("DELETE FROM idea_likes WHERE idea_id=? AND user_id=?");
        $stmt->bind_param("ii", $idea_id, $user_id);
        $stmt->execute();
    } else {
        // Like - add the like (use INSERT IGNORE to prevent duplicates)
        $stmt = $conn->prepare("INSERT IGNORE INTO idea_likes (idea_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $idea_id, $user_id);
        $stmt->execute();
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit;
}

// Handle bookmark toggle
if (isset($_POST['toggle_bookmark']) && $user_id > 0) {
    $idea_id = (int)$_POST['idea_id'];
    
    $stmt = $conn->prepare("SELECT id FROM idea_bookmarks WHERE idea_id=? AND user_id=?");
    $stmt->bind_param("ii", $idea_id, $user_id);
    $stmt->execute();
    $check = $stmt->get_result();
    
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM idea_bookmarks WHERE idea_id=? AND user_id=?");
        $stmt->bind_param("ii", $idea_id, $user_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO idea_bookmarks (idea_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $idea_id, $user_id);
        $stmt->execute();
    }
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit;
}

// Handle share tracking
if (isset($_POST['track_share']) && $user_id > 0) {
    $idea_id = (int)$_POST['idea_id'];
    $platform = $_POST['platform'] ?? 'other';
    
    $stmt = $conn->prepare("INSERT INTO idea_shares (idea_id, user_id, platform) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $idea_id, $user_id, $platform);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
    exit;
}



// Handle view tracking
if (isset($_POST['track_view']) && $user_id > 0) {
    $idea_id = (int)$_POST['idea_id'];
    
    $stmt = $conn->prepare("INSERT INTO idea_views (idea_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $idea_id, $user_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
    exit;
}

// Handle follow toggle
if (isset($_POST['toggle_follow']) && $user_id > 0) {
    $idea_id = (int)$_POST['idea_id'];
    
    $stmt = $conn->prepare("SELECT id FROM idea_followers WHERE idea_id=? AND user_id=?");
    $stmt->bind_param("ii", $idea_id, $user_id);
    $stmt->execute();
    $check = $stmt->get_result();
    
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM idea_followers WHERE idea_id=? AND user_id=?");
        $stmt->bind_param("ii", $idea_id, $user_id);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO idea_followers (idea_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $idea_id, $user_id);
        $stmt->execute();
    }
    $stmt->close();
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit;
}

// Handle rating submission
if (isset($_POST['submit_rating']) && $user_id > 0) {
    $idea_id = (int)$_POST['idea_id'];
    $rating = (int)$_POST['rating'];
    
    if ($idea_id > 0 && $rating >= 1 && $rating <= 5) {
        $stmt = $conn->prepare("INSERT INTO idea_ratings (idea_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating=?");
        $stmt->bind_param("iiii", $idea_id, $user_id, $rating, $rating);
        $stmt->execute();
        $stmt->close();
    }
    
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit;
}

// Handle comment submission
if (isset($_POST['submit_comment']) && $user_id > 0) {
    $idea_id = (int)$_POST['idea_id'];
    $parent_id = isset($_POST['parent_id']) && !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $comment = trim($_POST['comment'] ?? '');
    
    if ($idea_id > 0 && !empty($comment)) {
        // Use prepared statement for security
        $stmt = $conn->prepare("INSERT INTO idea_comments (idea_id, user_id, parent_id, comment) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $idea_id, $user_id, $parent_id, $comment);
        
        if ($stmt->execute()) {
            $_SESSION['comment_success'] = 'Comment posted successfully!';
        } else {
            $_SESSION['comment_error'] = 'Failed to post comment: ' . $stmt->error;
            error_log("Idea comment insert failed: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $_SESSION['comment_error'] = 'Comment cannot be empty.';
    }
    
    // Redirect back to the same page with filters
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
    exit;
}

// Handle report submission
if (isset($_POST['submit_report']) && $user_id > 0) {
    echo json_encode(['success' => true]);
    exit;
}

// Filters
$search = $_GET['search'] ?? '';
$filter_classification = $_GET['classification'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_priority = $_GET['priority'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';
$view_mode = $_GET['view'] ?? 'my_ideas'; // my_ideas, all_ideas, bookmarked
$current_page = max(1, (int)($_GET['page'] ?? 1));
$ideas_per_page = 12;
$offset = ($current_page - 1) * $ideas_per_page;

// Build WHERE clause
$where = "1=1";

// Filter by view mode
if ($view_mode === 'my_ideas' && $user_id > 0) {
    $where .= " AND b.user_id=$user_id";
} elseif ($view_mode === 'bookmarked' && $user_id > 0) {
    $where .= " AND EXISTS (SELECT 1 FROM idea_bookmarks WHERE idea_id=b.id AND user_id=$user_id)";
} elseif ($view_mode === 'following' && $user_id > 0) {
    // Show ideas from users that the current user follows
    $where .= " AND b.user_id IN (SELECT following_id FROM user_follows WHERE follower_id=$user_id)";
} elseif ($view_mode === 'shared' && $user_id > 0) {
    $where .= " AND EXISTS (SELECT 1 FROM idea_shares WHERE idea_id=b.id AND user_id=$user_id)";
}

if ($search) {
    $s = $conn->real_escape_string($search);
    $where .= " AND (b.project_name LIKE '%$s%' OR b.description LIKE '%$s%')";
}
if ($filter_classification) {
    $c = $conn->real_escape_string($filter_classification);
    $where .= " AND b.classification='$c'";
}
if ($filter_type) {
    $t = $conn->real_escape_string($filter_type);
    $where .= " AND b.project_type='$t'";
}
if ($filter_status) {
    $st = $conn->real_escape_string($filter_status);
    $where .= " AND b.status='$st'";
}
if ($filter_priority) {
    $p = $conn->real_escape_string($filter_priority);
    $where .= " AND b.priority1='$p'";
}

// Sorting
$order_by = "b.submission_datetime DESC";
switch ($sort_by) {
    case 'popular':
        $order_by = "(SELECT COUNT(*) FROM idea_likes WHERE idea_id=b.id) DESC, b.submission_datetime DESC";
        break;

    case 'most_viewed':
        $order_by = "(SELECT COUNT(*) FROM idea_views WHERE idea_id=b.id) DESC, b.submission_datetime DESC";
        break;
    case 'oldest':
        $order_by = "b.submission_datetime ASC";
        break;
}

// Get total count
$total_result = $conn->query("SELECT COUNT(*) as total FROM blog b WHERE $where");
$total_ideas = $total_result ? $total_result->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_ideas / $ideas_per_page);

// Get ideas with stats - simplified query
$sql = "SELECT b.*, 
        COALESCE(r.name, 'Unknown') as author_name,
        0 as total_likes,
        0 as total_comments,
        0 as total_views,
        0 as total_shares,
        0 as is_liked,
        0 as is_bookmarked,
        IF(b.user_id=$user_id, 1, 0) as is_owner
        FROM blog b
        LEFT JOIN register r ON b.user_id=r.id
        WHERE $where
        ORDER BY $order_by
        LIMIT $ideas_per_page OFFSET $offset";

// Get stats separately for each idea
if ($result = $conn->query($sql)) {
    $ideas = $result->fetch_all(MYSQLI_ASSOC);
    
    // Add stats for each idea
    foreach ($ideas as &$idea) {
        $id = $idea['id'];
        
        // Get likes
        $likes_result = $conn->query("SELECT COUNT(*) as count FROM idea_likes WHERE idea_id=$id");
        $idea['total_likes'] = $likes_result ? $likes_result->fetch_assoc()['count'] : 0;
        
        // Get comments count
        $comments_result = $conn->query("SELECT COUNT(*) as count FROM idea_comments WHERE idea_id=$id");
        $idea['total_comments'] = $comments_result ? $comments_result->fetch_assoc()['count'] : 0;
        
        // Get user-specific data
        if ($user_id > 0) {
            $user_like = $conn->query("SELECT COUNT(*) as count FROM idea_likes WHERE idea_id=$id AND user_id=$user_id");
            $idea['is_liked'] = $user_like ? $user_like->fetch_assoc()['count'] : 0;
            
            $user_bookmark = $conn->query("SELECT COUNT(*) as count FROM idea_bookmarks WHERE idea_id=$id AND user_id=$user_id");
            $idea['is_bookmarked'] = $user_bookmark ? $user_bookmark->fetch_assoc()['count'] : 0;
        }
        
        // Get all stats from new tables
        $views_result = $conn->query("SELECT COUNT(*) as count FROM idea_views WHERE idea_id=$id");
        $idea['total_views'] = $views_result ? $views_result->fetch_assoc()['count'] : 0;
        
        $shares_result = $conn->query("SELECT COUNT(*) as count FROM idea_shares WHERE idea_id=$id");
        $idea['total_shares'] = $shares_result ? $shares_result->fetch_assoc()['count'] : 0;
        
        $followers_result = $conn->query("SELECT COUNT(*) as count FROM idea_followers WHERE idea_id=$id");
        $idea['total_followers'] = $followers_result ? $followers_result->fetch_assoc()['count'] : 0;
        
        $ratings_result = $conn->query("SELECT AVG(rating) as avg, COUNT(*) as count FROM idea_ratings WHERE idea_id=$id");
        $rating_data = $ratings_result ? $ratings_result->fetch_assoc() : ['avg' => 0, 'count' => 0];
        $idea['avg_rating'] = round($rating_data['avg'], 1);
        $idea['total_ratings'] = $rating_data['count'];
        
        $reports_result = $conn->query("SELECT COUNT(*) as count FROM idea_reports WHERE idea_id=$id");
        $idea['total_reports'] = $reports_result ? $reports_result->fetch_assoc()['count'] : 0;
        
        // Check user-specific data for new tables
        if ($user_id > 0) {
            $user_follow = $conn->query("SELECT COUNT(*) as count FROM idea_followers WHERE idea_id=$id AND user_id=$user_id");
            $idea['is_following'] = $user_follow ? $user_follow->fetch_assoc()['count'] : 0;
            
            $user_rating = $conn->query("SELECT rating FROM idea_ratings WHERE idea_id=$id AND user_id=$user_id");
            $idea['user_rating'] = $user_rating && $user_rating->num_rows > 0 ? $user_rating->fetch_assoc()['rating'] : 0;
        } else {
            $idea['is_following'] = 0;
            $idea['user_rating'] = 0;
        }
    }
} else {
    $ideas = [];
}

// Ideas are now populated above

// Get classifications for filter
$class_result = $conn->query("SELECT DISTINCT classification FROM blog WHERE classification IS NOT NULL AND classification!='' ORDER BY classification");
$classifications = $class_result ? $class_result->fetch_all(MYSQLI_ASSOC) : [];

// Get user statistics
$user_stats = ['ideas' => 0, 'likes' => 0, 'comments' => 0, 'bookmarks' => 0];
if ($user_id > 0) {
    $stats_result = $conn->query("
        SELECT 
            (SELECT COUNT(*) FROM blog WHERE user_id=$user_id) as ideas,
            (SELECT COUNT(*) FROM idea_likes WHERE user_id=$user_id) as likes,
            0 as comments,
            (SELECT COUNT(*) FROM idea_bookmarks WHERE user_id=$user_id) as bookmarks
    ");
    if ($stats_result) {
        $user_stats = $stats_result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Ideas - IdeaNest</title>
    <link rel="icon" type="image/png" href="../assets/image/fevicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/ajax_notifications.css">
    <style>
        :root{--primary:#6366f1;--secondary:#8b5cf6;--success:#10b981;--warning:#f59e0b;--danger:#ef4444}
        body{background:#f8fafc;font-family:'Inter',sans-serif}
        .main-content{margin-left:280px;padding:2rem;min-height:100vh}
        .page-header{background:linear-gradient(135deg,var(--primary),var(--secondary));color:white;padding:2.5rem;border-radius:1.5rem;margin-bottom:2rem;box-shadow:0 10px 25px rgba(99,102,241,0.15)}
        .page-header h2{margin:0;font-weight:700;font-size:2rem}
        
        /* Stats Cards */
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem}
        .stat-card{background:white;padding:1.5rem;border-radius:1rem;box-shadow:0 4px 6px rgba(0,0,0,0.05);border-left:4px solid var(--primary);transition:transform 0.3s}
        .stat-card:hover{transform:translateY(-5px)}
        .stat-card .stat-icon{font-size:2rem;margin-bottom:0.5rem;color:var(--primary)}
        .stat-card .stat-value{font-size:2rem;font-weight:700;color:#1e293b}
        .stat-card .stat-label{color:#64748b;font-size:0.9rem}
        
        /* View Mode Tabs */
        .view-tabs{background:white;padding:1rem;border-radius:1rem;box-shadow:0 4px 6px rgba(0,0,0,0.05);margin-bottom:2rem}
        .view-tabs .nav-link{border:none;color:#64748b;font-weight:600;padding:0.75rem 1.5rem;border-radius:0.5rem;transition:all 0.3s}
        .view-tabs .nav-link.active{background:var(--primary);color:white}
        .view-tabs .nav-link:hover:not(.active){background:#f1f5f9}
        
        .filter-section{background:white;padding:2rem;border-radius:1rem;box-shadow:0 4px 6px rgba(0,0,0,0.05);margin-bottom:2rem}
        .ideas-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:2rem;margin-bottom:2rem}
        .idea-card{background:white;border-radius:1rem;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.05);transition:all 0.3s ease;border:2px solid #e5e7eb;position:relative}
        .idea-card::before{content:'';position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(135deg,rgba(99,102,241,0.03),rgba(139,92,246,0.03));opacity:0;transition:opacity 0.3s ease;pointer-events:none;z-index:1}
        .idea-card:hover{transform:translateY(-8px);box-shadow:0 16px 32px rgba(99,102,241,0.2);border-color:var(--primary)}
        .idea-card:hover::before{opacity:1}
        .idea-card:active{transform:translateY(-4px) scale(0.98);transition:all 0.1s ease}
        .idea-card > *{position:relative;z-index:2}
        .lock-badge{position:absolute;top:1rem;right:1rem;background:#fbbf24;color:white;padding:0.25rem 0.75rem;border-radius:1rem;font-size:0.75rem;z-index:10}
        .trending-badge{position:absolute;top:1rem;left:1rem;background:linear-gradient(135deg,#ef4444,#dc2626);color:white;padding:0.25rem 0.75rem;border-radius:1rem;font-size:0.75rem;z-index:10;animation:pulse 2s infinite}
        @keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.05)}}
        
        .idea-card-header{padding:1.5rem;border-bottom:1px solid #f1f5f9;transition:background 0.3s ease}
        .idea-card:hover .idea-card-header{background:linear-gradient(135deg,rgba(99,102,241,0.05),rgba(139,92,246,0.05))}
        .idea-title{font-size:1.25rem;font-weight:700;color:#1e293b;margin-bottom:0.5rem;transition:color 0.3s ease}
        .idea-card:hover .idea-title{color:var(--primary)}
        .idea-meta{display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem}
        .badge-custom{padding:0.5rem 1rem;border-radius:2rem;font-size:0.85rem;font-weight:600}
        .badge-classification{background:linear-gradient(135deg,rgba(6,182,212,0.1),rgba(14,165,233,0.1));color:#0891b2;border:1px solid rgba(6,182,212,0.2)}
        .badge-type{background:linear-gradient(135deg,rgba(139,92,246,0.1),rgba(168,85,247,0.1));color:var(--secondary);border:1px solid rgba(139,92,246,0.2)}
        
        .idea-card-body{padding:1.5rem;position:relative}
        .idea-card-body::after{content:'\f061';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;right:1.5rem;bottom:1rem;color:var(--primary);opacity:0;transform:translateX(-10px);transition:all 0.3s ease}
        .idea-card:hover .idea-card-body::after{opacity:1;transform:translateX(0)}
        .idea-description{color:#64748b;line-height:1.6;margin-bottom:1rem;transition:color 0.3s ease}
        .idea-card:hover .idea-description{color:#475569}
        
        /* Stats Row */
        .stats-row{display:flex;gap:1.5rem;padding:1rem 1.5rem;background:#f8fafc;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;transition:background 0.3s ease}
        .idea-card:hover .stats-row{background:#f1f5f9}
        .stat-item{display:flex;align-items:center;gap:0.5rem;color:#64748b;font-size:0.9rem;font-weight:600;transition:all 0.3s ease}
        .stat-item i{color:var(--primary);transition:transform 0.3s ease}
        .idea-card:hover .stat-item i{transform:scale(1.1)}
        
        .idea-actions{display:grid;grid-template-columns:repeat(6,1fr);gap:0.5rem;padding:1rem 1.5rem;background:#f8fafc}
        .action-btn{padding:0.5rem;border:1px solid #e5e7eb;background:white;border-radius:0.5rem;cursor:pointer;transition:all 0.3s;font-size:0.9rem;display:flex;align-items:center;justify-content:center;gap:0.25rem}
        .action-btn:hover:not(:disabled){background:#f8fafc;border-color:var(--primary);color:var(--primary)}
        .action-btn.liked{background:#fee2e2;border-color:#ef4444;color:#ef4444}
        .action-btn.bookmarked{background:#fef3c7;border-color:#f59e0b;color:#f59e0b}
        .action-btn:disabled{opacity:0.5;cursor:not-allowed}
        .btn-edit{background:#10b981;color:white;border:none;text-decoration:none}
        .btn-edit:hover{background:#059669;color:white}
        
        /* Share Modal */
        .share-options{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:1rem;margin-top:1rem}
        .share-btn{padding:1rem;border:1px solid #e5e7eb;border-radius:0.5rem;text-align:center;cursor:pointer;transition:all 0.3s;background:white}
        .share-btn:hover{background:#f8fafc;border-color:var(--primary);transform:translateY(-2px)}
        .share-btn i{font-size:1.5rem;margin-bottom:0.5rem;display:block}
        .share-btn.twitter i{color:#1DA1F2}
        .share-btn.facebook i{color:#4267B2}
        .share-btn.linkedin i{color:#0077b5}
        .share-btn.whatsapp i{color:#25D366}
        .share-btn.copy i{color:#64748b}
        
        .empty-state{text-align:center;padding:4rem 2rem;background:white;border-radius:1rem;box-shadow:0 4px 6px rgba(0,0,0,0.05)}
        .empty-state i{font-size:4rem;color:#cbd5e1;margin-bottom:1rem}
        
        /* New badges */
        .rating-badge{position:absolute;top:1rem;right:4rem;background:#fbbf24;color:white;padding:0.25rem 0.75rem;border-radius:1rem;font-size:0.75rem;z-index:10}
        .action-btn.following{background:#dcfce7;border-color:#16a34a;color:#16a34a}
        
        /* Star rating */
        .star-rating{display:inline-flex;gap:0.25rem;margin-left:0.5rem}
        .star-rating i{cursor:pointer;color:#d1d5db;transition:color 0.2s}
        .star-rating i:hover,.star-rating i.active{color:#fbbf24}
        
        /* Tooltips */
        .tooltip-icon{cursor:help;color:#94a3b8}
        
        @media(max-width:1024px){.main-content{margin-left:0;padding:1rem}.ideas-grid{grid-template-columns:1fr}.stats-grid{grid-template-columns:repeat(2,1fr)}}
    </style>
    <link rel="stylesheet" href="../../assets/css/loader.css">
    <link rel="stylesheet" href="../../assets/css/loading.css">
</head>
<body>
<?php 
$basePath = '../';
include '../layout.php'; 
?>

<main class="main-content">
    <div class="page-header">
        <h2><i class="fas fa-lightbulb me-3"></i>Ideas Hub</h2>
        <p class="mb-0 mt-2">Explore, collaborate, and innovate with our community</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['comment_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['comment_success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['comment_success']); endif; ?>

    <?php if (isset($_SESSION['comment_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($_SESSION['comment_error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['comment_error']); endif; ?>

    <?php if($user_id > 0): ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-lightbulb"></i></div>
            <div class="stat-value"><?= $user_stats['ideas'] ?></div>
            <div class="stat-label">My Ideas</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-heart"></i></div>
            <div class="stat-value"><?= $user_stats['likes'] ?></div>
            <div class="stat-label">Likes Given</div>
        </div>
      
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-bookmark"></i></div>
            <div class="stat-value"><?= $user_stats['bookmarks'] ?></div>
            <div class="stat-label">Bookmarked</div>
        </div>
    </div>
    <?php endif; ?>

    <div class="view-tabs">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link <?= $view_mode==='all_ideas'?'active':'' ?>" href="?view=all_ideas">
                    <i class="fas fa-globe me-2"></i>All Ideas
                </a>
            </li>
            <?php if($user_id > 0): ?>
            <li class="nav-item">
                <a class="nav-link <?= $view_mode==='my_ideas'?'active':'' ?>" href="?view=my_ideas">
                    <i class="fas fa-user me-2"></i>My Ideas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $view_mode==='bookmarked'?'active':'' ?>" href="?view=bookmarked">
                    <i class="fas fa-bookmark me-2"></i>Bookmarked
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $view_mode==='following'?'active':'' ?>" href="?view=following">
                    <i class="fas fa-user-plus me-2"></i>Following Ideas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $view_mode==='shared'?'active':'' ?>" href="?view=shared">
                    <i class="fas fa-share me-2"></i>Shared Ideas
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="filter-section">
        <form method="get" class="row g-3">
            <input type="hidden" name="view" value="<?= htmlspecialchars($view_mode) ?>">
            
            <!-- Search -->
            <div class="col-md-4 col-lg-3">
                <label class="form-label"><i class="fas fa-search me-1"></i>Search Ideas</label>
                <input type="text" class="form-control" name="search" placeholder="Search by name, description..." value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <!-- Classification -->
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label"><i class="fas fa-tag me-1"></i>Classification</label>
                <select class="form-select" name="classification">
                    <option value="">All</option>
                    <?php foreach($classifications as $c): ?>
                        <option value="<?= htmlspecialchars($c['classification']) ?>" <?= $filter_classification===$c['classification']?'selected':'' ?>>
                            <?= htmlspecialchars($c['classification']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Project Type -->
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label"><i class="fas fa-cube me-1"></i>Type</label>
                <select class="form-select" name="type">
                    <option value="">All</option>
                    <option value="software" <?= $filter_type==='software'?'selected':'' ?>>Software</option>
                    <option value="hardware" <?= $filter_type==='hardware'?'selected':'' ?>>Hardware</option>
                    <option value="research" <?= $filter_type==='research'?'selected':'' ?>>Research</option>
                </select>
            </div>
            
            <!-- Status -->
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label"><i class="fas fa-info-circle me-1"></i>Status</label>
                <select class="form-select" name="status">
                    <option value="">All</option>
                    <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
                    <option value="in_progress" <?= $filter_status==='in_progress'?'selected':'' ?>>In Progress</option>
                    <option value="completed" <?= $filter_status==='completed'?'selected':'' ?>>Completed</option>
                    <option value="rejected" <?= $filter_status==='rejected'?'selected':'' ?>>Rejected</option>
                </select>
            </div>
            
            <!-- Priority -->
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label"><i class="fas fa-flag me-1"></i>Priority</label>
                <select class="form-select" name="priority">
                    <option value="">All</option>
                    <option value="low" <?= $filter_priority==='low'?'selected':'' ?>>Low</option>
                    <option value="medium" <?= $filter_priority==='medium'?'selected':'' ?>>Medium</option>
                    <option value="high" <?= $filter_priority==='high'?'selected':'' ?>>High</option>
                </select>
            </div>
            
            <!-- Sort By -->
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label"><i class="fas fa-sort me-1"></i>Sort By</label>
                <select class="form-select" name="sort">
                    <option value="newest" <?= $sort_by==='newest'?'selected':'' ?>>Newest First</option>
                    <option value="oldest" <?= $sort_by==='oldest'?'selected':'' ?>>Oldest First</option>
                    <option value="popular" <?= $sort_by==='popular'?'selected':'' ?>>Most Popular</option>
                    <option value="most_viewed" <?= $sort_by==='most_viewed'?'selected':'' ?>>Most Viewed</option>
                </select>
            </div>
            
            <!-- Buttons -->
            <div class="col-6 col-md-3 col-lg-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-2"></i>Filter</button>
                <a href="?view=<?= htmlspecialchars($view_mode) ?>" class="btn btn-outline-secondary"><i class="fas fa-redo"></i></a>
            </div>
        </form>
        
        <!-- Active Filters Display -->
        <?php
        $active_filters = [];
        if ($search) $active_filters[] = "Search: " . htmlspecialchars($search);
        if ($filter_classification) $active_filters[] = "Classification: " . htmlspecialchars($filter_classification);
        if ($filter_type) $active_filters[] = "Type: " . htmlspecialchars($filter_type);
        if ($filter_status) $active_filters[] = "Status: " . htmlspecialchars($filter_status);
        if ($filter_priority) $active_filters[] = "Priority: " . htmlspecialchars($filter_priority);
        
        if (!empty($active_filters)): ?>
        <div class="mt-3">
            <small class="text-muted"><i class="fas fa-filter me-1"></i>Active Filters:</small>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <?php foreach ($active_filters as $filter): ?>
                    <span class="badge bg-primary"><?= $filter ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="ideas-grid">
        <?php if(count($ideas)>0): ?>
            <?php foreach($ideas as $idea): 
                $is_trending = $idea['total_likes'] > 5 || $idea['total_comments'] > 3;
            ?>
                <div class="idea-card" data-idea-id="<?= $idea['id'] ?>" onclick="window.location.href='idea_details.php?id=<?= $idea['id'] ?>'" style="cursor:pointer">
                    <?php if($is_trending): ?>
                        <span class="trending-badge"><i class="fas fa-fire me-1"></i>Trending</span>
                    <?php endif; ?>
                    <?php if(!$idea['is_owner']): ?>
                        <span class="lock-badge"><i class="fas fa-lock me-1"></i>View Only</span>
                    <?php endif; ?>
                    <?php if($idea['avg_rating'] > 0): ?>
                        <span class="rating-badge">★ <?= $idea['avg_rating'] ?></span>
                    <?php endif; ?>
                    
                    <div class="idea-card-header">
                        <h5 class="idea-title"><?= htmlspecialchars($idea['project_name']) ?></h5>
                        <div class="idea-meta">
                            <?php if(!empty($idea['classification'])): ?>
                                <span class="badge-custom badge-classification">
                                    <i class="fas fa-tag me-1"></i><?= htmlspecialchars($idea['classification']) ?>
                                </span>
                            <?php endif; ?>
                            <?php if(!empty($idea['project_type'])): ?>
                                <span class="badge-custom badge-type">
                                    <i class="fas fa-cogs me-1"></i><?= htmlspecialchars(ucfirst($idea['project_type'])) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="idea-card-body">
                        <p class="idea-description"><?= htmlspecialchars(mb_strimwidth($idea['description'],0,150,'...')) ?></p>
                        <div class="text-muted small">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($idea['author_name']) ?>
                            <span class="mx-2">•</span>
                            <i class="fas fa-calendar me-1"></i><?= date('M j, Y',strtotime($idea['submission_datetime'])) ?>
                        </div>
                    </div>
                    
                    <div class="stats-row">
                        <div class="stat-item">
                            <i class="fas fa-heart"></i>
                            <span><?= $idea['total_likes'] ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-eye"></i>
                            <span><?= $idea['total_views'] ?></span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <span><?= $idea['total_followers'] ?></span>
                        </div>
                        <?php if($idea['avg_rating'] > 0): ?>
                        <div class="stat-item">
                            <i class="fas fa-star"></i>
                            <span><?= $idea['avg_rating'] ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="idea-actions">
                        <?php if($idea['is_owner']): ?>
                            <a href="edit.php?id=<?= $idea['id'] ?>" class="action-btn btn-edit" title="Edit" onclick="event.stopPropagation()">
                                <i class="fas fa-edit"></i>
                            </a>
                        <?php else: ?>
                            <button class="action-btn like-btn <?= $idea['is_liked']?'liked':'' ?>" 
                                    onclick="event.stopPropagation(); IdeaAjax.toggleLike(<?= $idea['id'] ?>)" 
                                    <?= !$user_id?'disabled':'' ?> 
                                    title="Like">
                                <i class="fas fa-heart"></i>
                            </button>
                        <?php endif; ?>
                        
                        <button class="action-btn bookmark-btn <?= $idea['is_bookmarked']?'bookmarked':'' ?>" 
                                onclick="event.stopPropagation(); IdeaAjax.toggleBookmark(<?= $idea['id'] ?>)" 
                                <?= !$user_id?'disabled':'' ?> 
                                title="Bookmark">
                            <i class="fas fa-bookmark"></i>
                        </button>
                        
                        <button class="action-btn" 
                                onclick="event.stopPropagation(); openShareModal(<?= $idea['id'] ?>, '<?= htmlspecialchars($idea['project_name']) ?>')" 
                                <?= !$user_id?'disabled':'' ?> 
                                title="Share">
                            <i class="fas fa-share"></i>
                        </button>
                        
                        <?php if(!$idea['is_owner']): ?>
                        <button class="action-btn follow-btn <?= $idea['is_following']?'following':'' ?>" 
                                onclick="event.stopPropagation(); IdeaAjax.toggleFollow(<?= $idea['id'] ?>)" 
                                <?= !$user_id?'disabled':'' ?> 
                                title="Follow">
                            <i class="fas fa-user-plus"></i>
                        </button>
                        <?php endif; ?>
                        
                        <?php if(!$idea['is_owner']): ?>
                        <button class="action-btn" 
                                onclick="event.stopPropagation(); openReportModal(<?= $idea['id'] ?>, '<?= htmlspecialchars($idea['project_name']) ?>')" 
                                <?= !$user_id?'disabled':'' ?> 
                                title="Report">
                            <i class="fas fa-flag"></i>
                        </button>
                        <?php endif; ?>
                        
                        <button class="action-btn comment-btn" 
                                onclick="event.stopPropagation(); window.location.href='idea_details.php?id=<?= $idea['id'] ?>'" 
                                title="View Details">
                            <i class="fas fa-eye"></i>
                            <span class="ms-1 count"><?= $idea['total_comments'] ?></span>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column:1/-1">
                <i class="fas fa-search"></i>
                <h4>No Ideas Found</h4>
                <p class="text-muted">
                    <?php if($view_mode==='my_ideas'): ?>
                        You haven't created any ideas yet.
                    <?php elseif($view_mode==='bookmarked'): ?>
                        You haven't bookmarked any ideas yet.
                    <?php elseif($view_mode==='following'): ?>
                        You aren't following any ideas yet.
                    <?php elseif($view_mode==='shared'): ?>
                        You haven't shared any ideas yet.
                    <?php else: ?>
                        No ideas match your current filters.
                    <?php endif; ?>
                </p>
                <a href="?view=all_ideas" class="btn btn-primary mt-3"><i class="fas fa-refresh me-2"></i>View All Ideas</a>
            </div>
        <?php endif; ?>
    </div>



    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Idea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" id="shareIdeaName"></p>
                    <div class="share-options">
                        <div class="share-btn twitter" onclick="shareOn('twitter')">
                            <i class="fab fa-twitter"></i>
                            <div>Twitter</div>
                        </div>
                        <div class="share-btn facebook" onclick="shareOn('facebook')">
                            <i class="fab fa-facebook"></i>
                            <div>Facebook</div>
                        </div>
                        <div class="share-btn linkedin" onclick="shareOn('linkedin')">
                            <i class="fab fa-linkedin"></i>
                            <div>LinkedIn</div>
                        </div>
                        <div class="share-btn whatsapp" onclick="shareOn('whatsapp')">
                            <i class="fab fa-whatsapp"></i>
                            <div>WhatsApp</div>
                        </div>
                        <div class="share-btn copy" onclick="shareOn('copy')">
                            <i class="fas fa-link"></i>
                            <div>Copy Link</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Report Idea</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" id="reportIdeaName"></p>
                    <form id="reportForm">
                        <input type="hidden" id="reportIdeaId" name="idea_id">
                        <div class="mb-3">
                            <label class="form-label">Reason for reporting:</label>
                            <select class="form-select" name="reason" required>
                                <option value="inappropriate">Inappropriate content</option>
                                <option value="spam">Spam</option>
                                <option value="copyright">Copyright violation</option>
                                <option value="harassment">Harassment</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Additional details (optional):</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Please provide more details..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="submitReport()">Submit Report</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Idea Modal -->
    <div class="modal fade" id="ideaDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ideaDetailTitle">Idea Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="ideaDetailBody">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="rating-section" id="ratingSection">
                        <span>Rate this idea:</span>
                        <div class="star-rating" id="starRating">
                            <i class="fas fa-star" data-rating="1"></i>
                            <i class="fas fa-star" data-rating="2"></i>
                            <i class="fas fa-star" data-rating="3"></i>
                            <i class="fas fa-star" data-rating="4"></i>
                            <i class="fas fa-star" data-rating="5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if($total_pages>1): ?>
        <nav class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted">
                    Showing <?= (($current_page-1)*$ideas_per_page)+1 ?> to <?= min($current_page*$ideas_per_page,$total_ideas) ?> of <?= $total_ideas ?> ideas
                </div>
            </div>
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $current_page<=1?'disabled':'' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$current_page-1])) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php for($i=max(1,$current_page-2);$i<=min($total_pages,$current_page+2);$i++): ?>
                    <li class="page-item <?= $i==$current_page?'active':'' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $current_page>=$total_pages?'disabled':'' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$current_page+1])) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/idea_ajax.js"></script>
<script>
// Override base URL for AJAX handler
if (typeof IdeaAjax !== 'undefined') {
    IdeaAjax.baseUrl = 'ajax_idea_handler.php';
}

// Global functions for comment/reply forms (needed for modal content)
window.toggleCommentForm = function() {
    const form = document.getElementById('commentForm');
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
};

window.toggleReplyForm = function(commentId) {
    const form = document.getElementById('replyForm' + commentId);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }
};

window.submitComment = function(event) {
    event.preventDefault();
    const form = event.target;
    const commentTextarea = form.querySelector('[name="comment"]');
    const comment = commentTextarea.value.trim();
    const ideaId = window.currentIdeaId || document.getElementById('ideaDetailModal').dataset.ideaId;
    
    if (!comment) {
        alert('Please enter a comment');
        return;
    }
    
    if (!ideaId) {
        alert('Error: Idea ID not found');
        return;
    }
    
    // Disable form to prevent double submission
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    // Submit via AJAX
    const formData = new FormData();
    formData.append('action', 'add_comment');
    formData.append('idea_id', ideaId);
    formData.append('comment', comment);
    
    fetch('ajax_idea_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(response => {
        if (response.success) {
            // Clear the form
            commentTextarea.value = '';
            
            // Reload the modal content to show new comment
            fetch(`idea_details.php?id=${ideaId}`)
                .then(r => r.text())
                .then(html => {
                    const modalBody = document.getElementById('ideaDetailBody');
                    if (modalBody) {
                        modalBody.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Error reloading:', error);
                    submitBtn.disabled = false;
                });
        } else {
            alert(response.message || 'Failed to post comment');
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to post comment. Please try again.');
        submitBtn.disabled = false;
    });
};

window.submitReply = function(event, parentId) {
    event.preventDefault();
    const form = event.target;
    const commentTextarea = form.querySelector('[name="comment"]');
    const comment = commentTextarea.value.trim();
    const ideaId = window.currentIdeaId || document.getElementById('ideaDetailModal').dataset.ideaId;
    
    if (!comment) {
        alert('Please enter a reply');
        return;
    }
    
    if (!ideaId) {
        alert('Error: Idea ID not found');
        return;
    }
    
    // Disable form to prevent double submission
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    
    // Submit via AJAX
    const formData = new FormData();
    formData.append('action', 'add_comment');
    formData.append('idea_id', ideaId);
    formData.append('parent_id', parentId);
    formData.append('comment', comment);
    
    fetch('ajax_idea_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(response => {
        if (response.success) {
            // Clear the form
            commentTextarea.value = '';
            
            // Reload the modal content to show new reply
            fetch(`idea_details.php?id=${ideaId}`)
                .then(r => r.text())
                .then(html => {
                    const modalBody = document.getElementById('ideaDetailBody');
                    if (modalBody) {
                        modalBody.innerHTML = html;
                    }
                })
                .catch(error => {
                    console.error('Error reloading:', error);
                    submitBtn.disabled = false;
                });
        } else {
            alert(response.message || 'Failed to post reply');
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to post reply. Please try again.');
        submitBtn.disabled = false;
    });
};

// Star rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const starRating = document.getElementById('starRating');
    if (starRating) {
        const stars = starRating.querySelectorAll('.fa-star');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                // Try multiple ways to get the idea ID
                const modalElement = document.getElementById('ideaDetailModal');
                const ideaId = modalElement.dataset.ideaId || window.currentIdeaId;
                
                if (!ideaId) {
                    alert('Please select an idea first');
                    console.error('No idea ID found. Modal dataset:', modalElement.dataset, 'Window:', window.currentIdeaId);
                    return;
                }
                
                console.log('Submitting rating:', rating, 'for idea:', ideaId);
                
                // Submit rating
                const formData = new FormData();
                formData.append('action', 'submit_rating');
                formData.append('idea_id', ideaId);
                formData.append('rating', rating);
                
                fetch('ajax_idea_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(response => {
                    console.log('Rating response:', response);
                    if (response.success) {
                        // Update star display
                        updateStars(rating);
                        alert(`Thank you! You rated this idea ${rating} stars.`);
                        
                        // Reload modal content to show updated rating
                        fetch(`idea_details.php?id=${ideaId}`)
                            .then(r => r.text())
                            .then(html => {
                                const modalBody = document.getElementById('ideaDetailBody');
                                if (modalBody) {
                                    modalBody.innerHTML = html;
                                }
                            });
                    } else {
                        alert(response.message || 'Failed to submit rating');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to submit rating. Check console for details.');
                });
            });
            
            // Hover effect
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                updateStars(rating);
            });
        });
        
        starRating.addEventListener('mouseleave', function() {
            updateStars(0);
        });
    }
    
    function updateStars(rating) {
        const stars = document.querySelectorAll('#starRating .fa-star');
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.add('text-warning');
                star.classList.remove('text-muted');
            } else {
                star.classList.add('text-muted');
                star.classList.remove('text-warning');
            }
        });
    }
});

let currentShareIdeaId = null;

// View tracking is handled by idea_ajax.js automatically

function openShareModal(ideaId, ideaName) {
    currentShareIdeaId = ideaId;
    document.getElementById('shareIdeaName').textContent = ideaName;
    new bootstrap.Modal(document.getElementById('shareModal')).show();
}

function shareOn(platform) {
    const url = window.location.origin + window.location.pathname + '?idea_id=' + currentShareIdeaId;
    const text = document.getElementById('shareIdeaName').textContent;
    
    let shareUrl = '';
    switch(platform) {
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`;
            break;
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            break;
        case 'linkedin':
            shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(url)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`;
            break;
        case 'copy':
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copied to clipboard!');
            });
            trackShare(platform);
            return;
    }
    
    if(shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
        trackShare(platform);
    }
}

function trackShare(platform) {
    IdeaAjax.trackShare(currentShareIdeaId, platform);
}

function openIdeaModal(ideaId) {
    const modalElement = document.getElementById('ideaDetailModal');
    const modal = new bootstrap.Modal(modalElement);
    
    // IMPORTANT: Set the idea ID on the modal element
    modalElement.dataset.ideaId = ideaId;
    
    modal.show();
    
    // Load idea details
    fetch(`idea_details.php?id=${ideaId}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('ideaDetailBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('ideaDetailBody').innerHTML = '<p class="text-danger">Error loading details</p>';
        });
}

// Star rating is handled by idea_ajax.js

function openReportModal(ideaId, ideaName) {
    document.getElementById('reportIdeaId').value = ideaId;
    document.getElementById('reportIdeaName').textContent = ideaName;
    new bootstrap.Modal(document.getElementById('reportModal')).show();
}

function submitReport() {
    const form = document.getElementById('reportForm');
    const formData = new FormData(form);
    const ideaId = document.getElementById('reportIdeaId').value;
    const reason = formData.get('reason');
    const description = formData.get('description');
    
    IdeaAjax.submitReport(ideaId, reason, description, (response) => {
        if (response.success) {
            bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
            form.reset();
        }
    });
}

</script>

<!-- Universal Loader -->
<div id="universalLoader" class="loader-overlay">
    <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text" id="loaderText">Loading...</div>
    </div>
</div>

<script src="../../assets/js/loader.js"></script>
<script src="../../assets/js/loading.js"></script>
</body>
</html>