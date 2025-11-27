<?php
require_once __DIR__ . '/../includes/security_init.php';
// Configure session settings
ini_set('session.cookie_lifetime', 86400);
ini_set('session.cookie_path', '/');
session_start();

if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}

include_once "../../Login/Login/db.php";

$subadmin_id = $_SESSION['subadmin_id'];
$action_message = '';
$message_type = '';

// Initialize variables to prevent undefined variable errors
$subadmin_name = '';
$subadmin_email = '';
$domains = '';

// Fetch subadmin details with error handling
try {
    $stmt = $conn->prepare("SELECT first_name, last_name, email, domains FROM subadmins WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $subadmin_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $first_name = $last_name = '';
    $stmt->bind_result($first_name, $last_name, $subadmin_email, $domains);
    $stmt->fetch();
    $stmt->close();
    $subadmin_name = $first_name . ' ' . $last_name;
} catch (Exception $e) {
    error_log("Error fetching subadmin details: " . $e->getMessage());
    $action_message = "Error loading user data. Please refresh the page.";
    $message_type = 'danger';
}

// Handle support ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = trim($_POST['priority'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validate input
    if (empty($subject) || empty($category) || empty($priority) || empty($message)) {
        $action_message = "Please fill in all required fields.";
        $message_type = 'warning';
    } else {
        try {
            // Generate unique ticket number
            $ticket_number = 'TK-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT) . '-' . date('Y');

            // Check if ticket number already exists and regenerate if needed
            $check_stmt = $conn->prepare("SELECT id FROM support_tickets WHERE ticket_number = ?");
            $check_stmt->bind_param("s", $ticket_number);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            // If ticket number exists, generate a new one with timestamp
            if ($result->num_rows > 0) {
                $ticket_number = 'TK-' . time() . '-' . date('Y');
            }
            $check_stmt->close();

            // Insert support ticket into database
            $stmt = $conn->prepare("INSERT INTO support_tickets (ticket_number, subadmin_id, subadmin_name, subadmin_email, subject, category, priority, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW())");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("sissssss", $ticket_number, $subadmin_id, $subadmin_name, $subadmin_email, $subject, $category, $priority, $message);

            if ($stmt->execute()) {
                $ticket_id = $conn->insert_id;
                $action_message = "Support ticket $ticket_number has been submitted successfully. We'll get back to you within 24-48 hours.";
                $message_type = 'success';

                // Insert initial reply record
                $reply_stmt = $conn->prepare("INSERT INTO support_ticket_replies (ticket_id, sender_type, sender_name, sender_email, message) VALUES (?, 'subadmin', ?, ?, ?)");
                if ($reply_stmt) {
                    $reply_stmt->bind_param("isss", $ticket_id, $subadmin_name, $subadmin_email, $message);
                    $reply_stmt->execute();
                    $reply_stmt->close();
                }

                // Optional: Send email notification to admin
                // You can implement email notification here
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error submitting support ticket: " . $e->getMessage());
            $action_message = "Failed to submit support ticket. Please try again. Error: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Fetch user's support tickets with error handling
$tickets_result = null;
try {
    $stmt = $conn->prepare("SELECT id, ticket_number, subject, category, priority, status, created_at FROM support_tickets WHERE subadmin_id = ? ORDER BY created_at DESC LIMIT 10");
    if ($stmt) {
        $stmt->bind_param("i", $subadmin_id);
        if ($stmt->execute()) {
            $tickets_result = $stmt->get_result();
        }
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Error fetching support tickets: " . $e->getMessage());
}

// Include the sidebar after processing
require_once "sidebar_subadmin.php"; // Include the layout file

// Start output buffering to capture the content
ob_start();
?>
    <link rel="stylesheet" href="../../assets/css/support_subadmin.css">

    <!-- Support Hero Section -->
    <div class="support-hero">
        <div class="support-icon">
            <i class="bi bi-headset"></i>
        </div>
        <h2>How can we help you?</h2>
        <p>Get the support you need to manage your projects effectively. Our team is here to assist you 24/7.</p>
    </div>

    <!-- Action Message Alert -->
<?php if ($action_message) : ?>
    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
        <?php echo htmlspecialchars($action_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="#new-ticket" class="quick-action" onclick="scrollToElement('new-ticket')">
            <div class="quick-action-icon">
                <i class="bi bi-plus-circle-fill"></i>
            </div>
            <h5>Submit New Ticket</h5>
            <p class="text-muted mb-0">Create a new support request</p>
        </a>

        <a href="#my-tickets" class="quick-action" onclick="scrollToElement('my-tickets')">
            <div class="quick-action-icon">
                <i class="bi bi-ticket-perforated-fill"></i>
            </div>
            <h5>My Tickets</h5>
            <p class="text-muted mb-0">View your support history</p>
        </a>

        <a href="#faq" class="quick-action" onclick="scrollToElement('faq')">
            <div class="quick-action-icon">
                <i class="bi bi-question-circle-fill"></i>
            </div>
            <h5>FAQ</h5>
            <p class="text-muted mb-0">Find quick answers</p>
        </a>

        <a href="mailto:ideanest.ict@gmail.com" class="quick-action">
            <div class="quick-action-icon">
                <i class="bi bi-envelope-fill"></i>
            </div>
            <h5>Direct Email</h5>
            <p class="text-muted mb-0">Contact us directly</p>
        </a>
    </div>

    <!-- New Support Ticket Form -->
    <div id="new-ticket" class="glass-card mb-4">
        <div class="p-4 border-bottom">
            <h5 class="mb-1 fw-bold">
                <i class="bi bi-plus-circle-fill me-2 text-primary"></i>
                Submit New Support Ticket
            </h5>
            <p class="text-muted mb-0">Fill out the form below and we'll get back to you as soon as possible</p>
        </div>

        <div class="p-4">
            <form method="post" id="supportForm" data-loading-message="Submitting support ticket...">
                <!-- Support Category Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold mb-3">
                        <i class="bi bi-tags-fill me-2"></i>
                        What do you need help with? <span class="text-danger">*</span>
                    </label>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="category-card" data-category="technical">
                                <i class="category-icon bi bi-gear-fill"></i>
                                <div class="category-title">Technical Issue</div>
                                <div class="category-desc">Problems with system functionality, bugs, or errors</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="category-card" data-category="account">
                                <i class="category-icon bi bi-person-fill"></i>
                                <div class="category-title">Account Support</div>
                                <div class="category-desc">Account access, permissions, or profile issues</div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="category-card" data-category="project">
                                <i class="category-icon bi bi-kanban-fill"></i>
                                <div class="category-title">Project Management</div>
                                <div class="category-desc">Help with project approval, classification, or workflow</div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="category" id="selectedCategory" required>
                    <div id="categoryError" class="text-danger small" style="display: none;">Please select a category</div>
                </div>

                <!-- Priority Selection -->
                <div class="mb-4">
                    <label class="form-label fw-bold mb-3">
                        <i class="bi bi-flag-fill me-2"></i>
                        Priority Level <span class="text-danger">*</span>
                    </label>
                    <div class="priority-selector">
                        <div class="priority-option priority-low" data-priority="low">
                            <i class="bi bi-flag me-2"></i>
                            <div class="fw-bold">Low</div>
                            <small>General questions</small>
                        </div>
                        <div class="priority-option priority-medium" data-priority="medium">
                            <i class="bi bi-flag-fill me-2"></i>
                            <div class="fw-bold">Medium</div>
                            <small>Affects workflow</small>
                        </div>
                        <div class="priority-option priority-high" data-priority="high">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div class="fw-bold">High</div>
                            <small>System down/urgent</small>
                        </div>
                    </div>
                    <input type="hidden" name="priority" id="selectedPriority" required>
                    <div id="priorityError" class="text-danger small" style="display: none;">Please select a priority level</div>
                </div>

                <!-- Subject -->
                <div class="form-floating-custom">
                    <input type="text" class="form-control" id="subject" name="subject" placeholder="Brief description of your issue" required maxlength="200">
                    <label for="subject">
                        <i class="bi bi-chat-square-text me-2"></i>
                        Subject <span class="text-danger">*</span>
                    </label>
                </div>

                <!-- Message -->
                <div class="form-floating-custom">
                    <textarea class="form-control" id="message" name="message" placeholder="Please provide detailed information about your issue..." required style="height: 150px; resize: vertical;"></textarea>
                    <label for="message">
                        <i class="bi bi-file-text me-2"></i>
                        Detailed Description <span class="text-danger">*</span>
                    </label>
                </div>

                <!-- User Info Display -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Your Information:</strong><br>
                    Name: <?php echo htmlspecialchars($subadmin_name ?: 'N/A'); ?><br>
                    Email: <?php echo htmlspecialchars($subadmin_email ?: 'N/A'); ?><br>
                    Domains: <?php echo htmlspecialchars($domains ?: 'No domains assigned'); ?>
                </div>

                <div class="d-flex gap-3">
                    <button type="submit" name="submit_ticket" class="btn btn-primary btn-lg">
                        <i class="bi bi-send me-2"></i>
                        Submit Support Ticket
                    </button>
                    <button type="reset" class="btn btn-outline-secondary btn-lg" onclick="resetForm()">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Reset Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- My Tickets Section -->
    <div id="my-tickets" class="glass-card mb-4">
        <div class="p-4 border-bottom">
            <h5 class="mb-1 fw-bold">
                <i class="bi bi-ticket-perforated-fill me-2 text-primary"></i>
                My Support Tickets
            </h5>
            <p class="text-muted mb-0">Your recent support requests and their status</p>
        </div>

        <div class="p-4">
            <?php if ($tickets_result && $tickets_result->num_rows > 0) : ?>
                <?php while ($ticket = $tickets_result->fetch_assoc()) : ?>
                    <div class="ticket-card">
                        <div class="ticket-header">
                            <div class="flex-grow-1">
                                <div class="ticket-id">Ticket #<?php echo htmlspecialchars($ticket['id']); ?></div>
                                <div class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                                <div class="ticket-meta">
                                    <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($ticket['category'])); ?></span>
                                    <span class="badge bg-warning"><?php echo htmlspecialchars(ucfirst($ticket['priority'])); ?></span>
                                    <span class="badge status-<?php echo str_replace(' ', '-', $ticket['status']); ?>">
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $ticket['status']))); ?>
                            </span>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($ticket['created_at']))); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <div class="text-center mt-3">
                    <a href="#" class="btn btn-outline-primary">
                        <i class="bi bi-eye me-2"></i>
                        View All Tickets
                    </a>
                </div>
            <?php else : ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted opacity-50"></i>
                    <h4 class="mt-3 text-muted">No Support Tickets Yet</h4>
                    <p class="text-muted">You haven't submitted any support tickets. If you need help, feel free to create one above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FAQ Section -->
    <div id="faq" class="glass-card mt-4 p-4">
        <h3 class="mb-4">
            <i class="bi bi-question-circle-fill me-2 text-primary"></i>
            Frequently Asked Questions
        </h3>

        <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                        How do I update my profile information?
                    </button>
                </h2>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        You can update your profile information by going to the Profile section in your dashboard. Click on the edit button to make changes to your information.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                        How can I review assigned projects?
                    </button>
                </h2>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Navigate to the "Assigned Projects" section in your dashboard. Here you can view all projects assigned to you and take necessary actions.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                        What should I do if I can't access certain features?
                    </button>
                </h2>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        If you're having trouble accessing certain features, first ensure you have the proper permissions. If the issue persists, submit a support ticket and we'll help you resolve it.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                        How long does it take to get a response to my support ticket?
                    </button>
                </h2>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        We aim to respond to all support tickets within 24-48 hours. High priority tickets are typically addressed faster, often within a few hours during business hours.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/support_subadmin.js"></script>

<?php
// Capture the content
$content = ob_get_clean();

if (function_exists('renderLayout')) {
    // Render the page using the layout
    renderLayout('Support & Help', $content, 'support');
} else {
    // Fallback: just echo the content if renderLayout doesn't exist
    echo $content;
}
?>