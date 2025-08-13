<?php
session_start();
if (!isset($_SESSION['subadmin_logged_in']) || !$_SESSION['subadmin_logged_in']) {
    header("Location: ../../Login/Login/login.php");
    exit();
}

include_once "../../Login/Login/db.php";
require_once "sidebar_subadmin.php"; // Include the layout file

$subadmin_id = $_SESSION['subadmin_id'];
$action_message = '';
$message_type = '';

// Fetch subadmin details
$stmt = $conn->prepare("SELECT name, email, software_classification, hardware_classification FROM subadmins WHERE id = ?");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$stmt->bind_result($subadmin_name, $subadmin_email, $software_classification, $hardware_classification);
$stmt->fetch();
$stmt->close();

// Handle support ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $subject = trim($_POST['subject']);
    $category = trim($_POST['category']);
    $priority = trim($_POST['priority']);
    $message = trim($_POST['message']);

    if (!empty($subject) && !empty($category) && !empty($priority) && !empty($message)) {
        // Insert support ticket into database
        $stmt = $conn->prepare("INSERT INTO support_tickets (subadmin_id, subadmin_name, subadmin_email, subject, category, priority, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'open', NOW())");
        $stmt->bind_param("issssss", $subadmin_id, $subadmin_name, $subadmin_email, $subject, $category, $priority, $message);

        if ($stmt->execute()) {
            $ticket_id = $conn->insert_id;
            $action_message = "Support ticket #$ticket_id has been submitted successfully. We'll get back to you within 24-48 hours.";
            $message_type = 'success';

            // Optional: Send email notification to admin
            // You can implement email notification here
        } else {
            $action_message = "Failed to submit support ticket. Please try again.";
            $message_type = 'danger';
        }
        $stmt->close();
    } else {
        $action_message = "Please fill in all required fields.";
        $message_type = 'warning';
    }
}

// Fetch user's support tickets
$stmt = $conn->prepare("SELECT id, subject, category, priority, status, created_at FROM support_tickets WHERE subadmin_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->bind_param("i", $subadmin_id);
$stmt->execute();
$tickets_result = $stmt->get_result();

// Start output buffering to capture the content
ob_start();
?>

    <!-- Page specific styles -->
    <style>
        .support-hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: white;
            border-radius: 1rem;
            padding: 3rem 2rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: var(--shadow-xl);
        }

        .support-hero h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            letter-spacing: -0.025em;
        }

        .support-hero p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 0;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .support-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }

        .category-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid var(--border-color);
            height: 100%;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }

        .category-card.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(99, 102, 241, 0.05) 100%);
        }

        .category-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: block;
        }

        .category-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
        }

        .category-desc {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .priority-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .priority-option {
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: white;
        }

        .priority-option:hover {
            border-color: var(--primary-color);
        }

        .priority-option.selected {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }

        .priority-low { border-color: #10b981; }
        .priority-low.selected { background: #10b981; border-color: #10b981; }

        .priority-medium { border-color: #f59e0b; }
        .priority-medium.selected { background: #f59e0b; border-color: #f59e0b; }

        .priority-high { border-color: #ef4444; }
        .priority-high.selected { background: #ef4444; border-color: #ef4444; }

        .ticket-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
        }

        .ticket-card:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
        }

        .ticket-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .ticket-id {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .ticket-subject {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0.25rem 0;
        }

        .ticket-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .status-open { background: #dbeafe; color: #1e40af; }
        .status-in-progress { background: #fef3c7; color: #92400e; }
        .status-resolved { background: #d1fae5; color: #065f46; }
        .status-closed { background: #f3f4f6; color: #374151; }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .quick-action {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            border: 2px solid var(--border-color);
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s;
        }

        .quick-action:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            text-decoration: none;
        }

        .quick-action-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .form-floating-custom {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-floating-custom .form-control,
        .form-floating-custom .form-select {
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
            height: auto;
            min-height: 3.5rem;
        }

        .form-floating-custom .form-control:focus,
        .form-floating-custom .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.25);
        }

        .form-floating-custom label {
            padding: 1rem 0.75rem 0.25rem;
            color: var(--text-secondary);
            font-weight: 600;
        }
    </style>

    <!-- Support Hero Section -->
    <div class="support-hero">
        <div class="support-icon">
            <i class="bi bi-headset"></i>
        </div>
        <h2>How can we help you?</h2>
        <p>Get the support you need to manage your projects effectively. Our team is here to assist you 24/7.</p>
    </div>

    <!-- Action Message Alert -->
<?php if ($action_message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
        <?php echo $action_message; ?>
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

        <a href="mailto:admin@ideanest.com" class="quick-action">
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
            <form method="post" id="supportForm">
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
                    Name: <?php echo htmlspecialchars($subadmin_name); ?><br>
                    Email: <?php echo htmlspecialchars($subadmin_email); ?><br>
                    Classifications: <?php echo htmlspecialchars($software_classification . ', ' . $hardware_classification); ?>
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
            <?php if ($tickets_result->num_rows > 0): ?>
                <?php while($ticket = $tickets_result->fetch_assoc()): ?>
                    <div class="ticket-card">
                        <div class="ticket-header">
                            <div class="flex-grow-1">
                                <div class="ticket-id">Ticket #<?php echo $ticket['id']; ?></div>
                                <div class="ticket-subject"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                                <div class="ticket-meta">
                                    <span class="badge bg-info"><?php echo ucfirst($ticket['category']); ?></span>
                                    <span class="badge bg-warning"><?php echo ucfirst($ticket['priority']); ?></span>
                                    <span class="badge status-<?php echo str_replace(' ', '-', $ticket['status']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                </span>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?>
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
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-1 text-muted opacity-50"></i>
                    <h4 class="mt-3 text-muted">No Support Tickets Yet</h4>
                    <p class="text-muted">You haven't submitted any support tickets. If you need help, feel free to create one above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FAQ Section -->
    <div id="faq" class="glass-card">
        <div class="p-4 border-bottom">
            <h5 class="mb-1 fw-bold">
                <i class="bi bi-question-circle-fill me-2 text-primary"></i>
                Frequently Asked Questions
            </h5>
            <p class="text-muted mb-0">Quick answers to common questions</p>
        </div>

        <div class="p-4">
            <div class="accordion" id="faqAccordion">
                <div class="accordion-item border-0 mb-3 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                            How do I approve or reject projects?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Go to the "Assigned Projects" section where you'll see all projects matching your classification. For pending projects, you can click the "Approve" button to approve or "Reject" button to reject with a reason.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-0 mb-3 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                            What projects are assigned to me?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Projects are automatically assigned based on your software and hardware classifications. You'll only see projects that match your expertise areas.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-0 mb-3 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                            How long do I have to review projects?
                        </button>
                    </h2>
                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            There's no strict deadline, but we recommend reviewing projects within 2-3 business days to ensure timely feedback to project submitters.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-0 mb-3 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                            Can I change my classification?
                        </button>
                    </h2>
                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Classification changes need to be requested through your admin. Please submit a support ticket with your requested changes and reasoning.
                        </div>
                    </div>
                </div>

                <div class="accordion-item border-0 mb-3 rounded">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                            How do I reset my password?
                        </button>
                    </h2>
                    <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            Contact your administrator or submit a support ticket for password reset assistance. For security reasons, password resets must be handled by the admin team.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Category selection functionality
        document.addEventListener('DOMContentLoaded', function() {
            const categoryCards = document.querySelectorAll('.category-card');
            const priorityOptions = document.querySelectorAll('.priority-option');

            // Category selection
            categoryCards.forEach(card => {
                card.addEventListener('click', function() {
                    categoryCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('selectedCategory').value = this.dataset.category;
                });
            });

            // Priority selection
            priorityOptions.forEach(option => {
                option.addEventListener('click', function() {
                    priorityOptions.forEach(o => o.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('selectedPriority').value = this.dataset.priority;
                });
            });
        });

        function scrollToElement(elementId) {
            document.getElementById(elementId).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        function resetForm() {
            document.querySelectorAll('.category-card.selected').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelectorAll('.priority-option.selected').forEach(option => {
                option.classList.remove('selected');
            });
            document.getElementById('selectedCategory').value = '';
            document.getElementById('selectedPriority').value = '';
        }

        // Form validation
        document.getElementById('supportForm').addEventListener('submit', function(e) {
            const category = document.getElementById('selectedCategory').value;
            const priority = document.getElementById('selectedPriority').value;

            if (!category) {
                e.preventDefault();
                alert('Please select a support category.');
                scrollToElement('new-ticket');
                return;
            }

            if (!priority) {
                e.preventDefault();
                alert('Please select a priority level.');
                scrollToElement('new-ticket');
                return;
            }
        });
    </script>

<?php
// Capture the content
$content = ob_get_clean();

// Render the page using the layout
renderLayout('Support & Help', $content, 'support');
?>