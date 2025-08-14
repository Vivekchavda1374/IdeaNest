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
        :root {
            --primary-color: #4f46e5;
            --primary-light: #6366f1;
            --text-primary: #111827;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

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
            justify-content: space-between;
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
            display: block;
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

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 768px) {
            .support-hero h2 {
                font-size: 2rem;
            }

            .priority-selector {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .category-card {
                padding: 1.5rem;
            }
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
                    Name: <?php echo htmlspecialchars($subadmin_name ?? 'N/A'); ?><br>
                    Email: <?php echo htmlspecialchars($subadmin_email ?? 'N/A'); ?><br>
                    Classifications: <?php echo htmlspecialchars(($software_classification ?? 'N/A') . ', ' . ($hardware_classification ?? 'N/A')); ?>
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
            <?php if ($tickets_result && $tickets_result->num_rows > 0): ?>
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

    <script>
        // Category and priority selection functionality
        document.addEventListener('DOMContentLoaded', function() {
            const categoryCards = document.querySelectorAll('.category-card');
            const priorityOptions = document.querySelectorAll('.priority-option');
            const supportForm = document.getElementById('supportForm');

            // Category selection
            categoryCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    categoryCards.forEach(c => c.classList.remove('selected'));
                    // Add selected class to clicked card
                    this.classList.add('selected');
                    // Set hidden input value
                    document.getElementById('selectedCategory').value = this.dataset.category;
                    // Hide error message
                    document.getElementById('categoryError').style.display = 'none';
                });
            });

            // Priority selection
            priorityOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    priorityOptions.forEach(o => o.classList.remove('selected'));
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    // Set hidden input value
                    document.getElementById('selectedPriority').value = this.dataset.priority;
                    // Hide error message
                    document.getElementById('priorityError').style.display = 'none';
                });
            });

            // Form validation
            supportForm.addEventListener('submit', function(e) {
                let isValid = true;
                const category = document.getElementById('selectedCategory').value;
                const priority = document.getElementById('selectedPriority').value;
                const subject = document.getElementById('subject').value.trim();
                const message = document.getElementById('message').value.trim();

                // Reset error messages
                document.getElementById('categoryError').style.display = 'none';
                document.getElementById('priorityError').style.display = 'none';

                // Validate category
                if (!category) {
                    document.getElementById('categoryError').style.display = 'block';
                    isValid = false;
                }

                // Validate priority
                if (!priority) {
                    document.getElementById('priorityError').style.display = 'block';
                    isValid = false;
                }

                // Validate subject
                if (!subject) {
                    document.getElementById('subject').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('subject').classList.remove('is-invalid');
                }

                // Validate message
                if (!message) {
                    document.getElementById('message').classList.add('is-invalid');
                    isValid = false;
                } else {
                    document.getElementById('message').classList.remove('is-invalid');
                }

                // Prevent form submission if validation fails
                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    if (!category) {
                        scrollToElement('new-ticket');
                    }
                    return false;
                }
            });
        });

        // Smooth scroll function
        function scrollToElement(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Reset form function
        function resetForm() {
            // Remove selected classes
            document.querySelectorAll('.category-card.selected').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelectorAll('.priority-option.selected').forEach(option => {
                option.classList.remove('selected');
            });

            // Clear hidden inputs
            document.getElementById('selectedCategory').value = '';
            document.getElementById('selectedPriority').value = '';

            // Hide error messages
            document.getElementById('categoryError').style.display = 'none';
            document.getElementById('priorityError').style.display = 'none';

            // Remove validation classes
            document.getElementById('subject').classList.remove('is-invalid');
            document.getElementById('message').classList.remove('is-invalid');
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.querySelector('.btn-close')) {
                        alert.querySelector('.btn-close').click();
                    }
                }, 5000);
            });
        });
    </script>

<?php
// Capture the content
$content = ob_get_clean();

// Render the page using the layout
renderLayout('Support & Help', $content, 'support');
?>