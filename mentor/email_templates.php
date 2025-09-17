<?php
class MentorEmailTemplates {
    
    public static function getBaseTemplate($title, $content, $mentor_name = '') {
        return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$title}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; }
                .email-container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { font-size: 28px; margin-bottom: 10px; }
                .header p { font-size: 16px; opacity: 0.9; }
                .content { padding: 30px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6; }
                .btn { display: inline-block; background: #667eea; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 10px 0; }
                .btn:hover { background: #5a6fd8; }
                .card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #667eea; }
                .highlight { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .text-center { text-align: center; }
                .text-muted { color: #6c757d; }
                .mb-3 { margin-bottom: 1rem; }
                .mt-3 { margin-top: 1rem; }
                ul { padding-left: 20px; }
                li { margin-bottom: 8px; }
                .emoji { font-size: 1.2em; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                {$content}
                <div class='footer'>
                    <p class='text-muted'>
                        This email was sent from the IdeaNest Mentor System<br>
                        <small>© 2025 IdeaNest - Empowering Academic Collaboration</small>
                    </p>
                    " . ($mentor_name ? "<p class='mt-3'><strong>Your Mentor: {$mentor_name}</strong></p>" : "") . "
                </div>
            </div>
        </body>
        </html>";
    }
    
    public static function getSessionInvitationContent($student_name, $mentor_name, $session_data) {
        $session_date = date('F j, Y', strtotime($session_data['session_date']));
        $session_time = date('g:i A', strtotime($session_data['session_date']));
        $duration = $session_data['duration'] ?? 60;
        $topic = $session_data['topic'] ?? 'General Mentoring Session';
        $meeting_link = $session_data['meeting_link'] ?? '';
        
        return "
        <div class='header'>
            <h1><span class='emoji'>🎓</span> New Mentoring Session</h1>
            <p>You've been invited to a mentoring session</p>
        </div>
        <div class='content'>
            <h2>Hello {$student_name}!</h2>
            <p class='mb-3'>Your mentor <strong>{$mentor_name}</strong> has scheduled a new mentoring session with you.</p>
            
            <div class='card'>
                <h3><span class='emoji'>📅</span> Session Details</h3>
                <p><strong>Date:</strong> {$session_date}</p>
                <p><strong>Time:</strong> {$session_time}</p>
                <p><strong>Duration:</strong> {$duration} minutes</p>
                <p><strong>Topic:</strong> {$topic}</p>
                " . ($meeting_link ? "<p><strong>Meeting Link:</strong> <a href='{$meeting_link}' style='color: #667eea;'>Join Session</a></p>" : "") . "
            </div>
            
            <div class='highlight'>
                <h4><span class='emoji'>📝</span> Please Prepare:</h4>
                <ul>
                    <li>Any questions you'd like to discuss</li>
                    <li>Updates on your current projects</li>
                    <li>Challenges you're facing</li>
                    <li>Goals for the upcoming period</li>
                </ul>
            </div>
            
            <div class='text-center mt-3'>
                " . ($meeting_link ? "<a href='{$meeting_link}' class='btn'>Join Session</a>" : "<a href='http://localhost/IdeaNest/user/dashboard.php' class='btn'>View Dashboard</a>") . "
            </div>
            
            <p class='mt-3'>Looking forward to our session together!</p>
        </div>";
    }
    
    public static function getProjectFeedbackContent($student_name, $mentor_name, $project, $feedback) {
        $rating_stars = isset($feedback['rating']) ? str_repeat('⭐', $feedback['rating']) : '';
        
        return "
        <div class='header' style='background: linear-gradient(135deg, #10b981 0%, #059669 100%);'>
            <h1><span class='emoji'>📝</span> Project Feedback</h1>
            <p>Your mentor has reviewed your project</p>
        </div>
        <div class='content'>
            <h2>Hello {$student_name}!</h2>
            <p class='mb-3'>Your mentor <strong>{$mentor_name}</strong> has provided feedback on your project.</p>
            
            <div class='card' style='border-left-color: #10b981;'>
                <h3><span class='emoji'>🚀</span> Project: {$project['project_name']}</h3>
                <p><strong>Classification:</strong> {$project['classification']}</p>
                <p><strong>Description:</strong> " . substr($project['description'], 0, 200) . "...</p>
            </div>
            
            <div class='highlight' style='background: #e6fffa;'>
                <h3><span class='emoji'>💬</span> Mentor Feedback</h3>
                <p>" . nl2br(htmlspecialchars($feedback['message'])) . "</p>
                " . ($rating_stars ? "<p class='mt-3'><strong>Rating:</strong> {$rating_stars} ({$feedback['rating']}/5)</p>" : "") . "
                " . (isset($feedback['suggestions']) && $feedback['suggestions'] ? "<div class='mt-3'><strong>Suggestions for Improvement:</strong><br>" . nl2br(htmlspecialchars($feedback['suggestions'])) . "</div>" : "") . "
            </div>
            
            <div class='text-center mt-3'>
                <a href='http://localhost/IdeaNest/user/dashboard.php' class='btn' style='background: #10b981;'>View Project</a>
            </div>
            
            <p class='mt-3'>Keep up the excellent work! Your dedication to learning is inspiring.</p>
        </div>";
    }
    
    public static function getProgressUpdateContent($student_name, $mentor_name, $progress_data) {
        $completion = $progress_data['completion_percentage'] ?? 0;
        $achievements = $progress_data['achievements'] ?? [];
        $next_steps = $progress_data['next_steps'] ?? [];
        $notes = $progress_data['notes'] ?? '';
        
        return "
        <div class='header' style='background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);'>
            <h1><span class='emoji'>📊</span> Progress Update</h1>
            <p>Your learning journey continues</p>
        </div>
        <div class='content'>
            <h2>Hello {$student_name}!</h2>
            <p class='mb-3'>Here's an update on your progress from your mentor <strong>{$mentor_name}</strong>.</p>
            
            <div class='card' style='border-left-color: #f59e0b;'>
                <h3><span class='emoji'>📈</span> Overall Progress</h3>
                <div style='background: #e5e7eb; height: 20px; border-radius: 10px; overflow: hidden; margin: 15px 0;'>
                    <div style='background: #f59e0b; height: 100%; width: {$completion}%; transition: width 0.3s ease;'></div>
                </div>
                <p style='text-align: center; font-weight: bold; color: #f59e0b;'>{$completion}% Complete</p>
                
                " . (!empty($achievements) ? "
                <h4><span class='emoji'>✅</span> Recent Achievements</h4>
                <ul>
                    " . implode('', array_map(function($achievement) {
                        return "<li>" . htmlspecialchars($achievement) . "</li>";
                    }, $achievements)) . "
                </ul>
                " : "") . "
                
                " . (!empty($next_steps) ? "
                <h4><span class='emoji'>🎯</span> Next Steps</h4>
                <ul>
                    " . implode('', array_map(function($step) {
                        return "<li>" . htmlspecialchars($step) . "</li>";
                    }, $next_steps)) . "
                </ul>
                " : "") . "
                
                " . ($notes ? "<h4><span class='emoji'>📝</span> Mentor Notes</h4><p>" . nl2br(htmlspecialchars($notes)) . "</p>" : "") . "
            </div>
            
            <div class='text-center mt-3'>
                <a href='http://localhost/IdeaNest/user/dashboard.php' class='btn' style='background: #f59e0b;'>View Dashboard</a>
            </div>
            
            <p class='mt-3'>You're making great progress! Keep up the momentum.</p>
        </div>";
    }
    
    public static function getWelcomeContent($student_name, $mentor_name, $mentor_department = '') {
        return "
        <div class='header' style='background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);'>
            <h1><span class='emoji'>🎉</span> Welcome to Your Mentoring Journey!</h1>
            <p>Let's achieve great things together</p>
        </div>
        <div class='content'>
            <h2>Hello {$student_name}!</h2>
            <p class='mb-3'>Welcome to IdeaNest! I'm <strong>{$mentor_name}</strong>, and I'm excited to be your mentor and guide you through your academic journey.</p>
            
            <div class='card' style='border-left-color: #8b5cf6;'>
                <h3><span class='emoji'>👨🏫</span> About Your Mentor</h3>
                <p><strong>Name:</strong> {$mentor_name}</p>
                " . ($mentor_department ? "<p><strong>Department:</strong> {$mentor_department}</p>" : "") . "
                
                <h4 class='mt-3'><span class='emoji'>🎯</span> What to Expect</h4>
                <ul>
                    <li>Regular mentoring sessions to discuss your progress</li>
                    <li>Personalized project guidance and feedback</li>
                    <li>Career advice and academic support</li>
                    <li>Skill development recommendations</li>
                    <li>Goal setting and achievement tracking</li>
                </ul>
            </div>
            
            <div class='highlight'>
                <h4><span class='emoji'>🚀</span> Getting Started</h4>
                <ul>
                    <li>Complete your profile in the IdeaNest dashboard</li>
                    <li>Upload your current projects for review</li>
                    <li>Set your learning goals and objectives</li>
                    <li>Schedule your first mentoring session</li>
                    <li>Connect your GitHub profile to showcase your work</li>
                </ul>
            </div>
            
            <div class='text-center mt-3'>
                <a href='http://localhost/IdeaNest/user/dashboard.php' class='btn' style='background: #8b5cf6;'>Get Started</a>
            </div>
            
            <p class='mt-3'>I'm here to support you every step of the way. Don't hesitate to reach out if you have any questions or need guidance!</p>
            
            <p class='mt-3'>Looking forward to working with you and helping you achieve your academic goals.</p>
        </div>";
    }
    
    public static function getSessionReminderContent($student_name, $mentor_name, $session_data) {
        $session_date = date('F j, Y', strtotime($session_data['session_date']));
        $session_time = date('g:i A', strtotime($session_data['session_date']));
        $duration = $session_data['duration'] ?? 60;
        $topic = $session_data['topic'] ?? 'General Mentoring Session';
        $meeting_link = $session_data['meeting_link'] ?? '';
        
        return "
        <div class='header' style='background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);'>
            <h1><span class='emoji'>⏰</span> Session Reminder</h1>
            <p>Don't miss your upcoming mentoring session!</p>
        </div>
        <div class='content'>
            <h2>Hello {$student_name}!</h2>
            
            <div class='highlight' style='background: #fef2f2; border: 1px solid #fecaca;'>
                <p><strong><span class='emoji'>🚨</span> Reminder:</strong> You have a mentoring session coming up in less than 24 hours!</p>
            </div>
            
            <div class='card' style='border-left-color: #ef4444;'>
                <h3><span class='emoji'>📅</span> Session Details</h3>
                <p><strong>Date:</strong> {$session_date}</p>
                <p><strong>Time:</strong> {$session_time}</p>
                <p><strong>Duration:</strong> {$duration} minutes</p>
                <p><strong>Mentor:</strong> {$mentor_name}</p>
                <p><strong>Topic:</strong> {$topic}</p>
                " . ($meeting_link ? "<p><strong>Meeting Link:</strong> <a href='{$meeting_link}' style='color: #ef4444;'>Join Session</a></p>" : "") . "
            </div>
            
            <div class='highlight'>
                <h4><span class='emoji'>📝</span> Please Prepare:</h4>
                <ul>
                    <li>Any questions you'd like to discuss</li>
                    <li>Updates on your current projects</li>
                    <li>Challenges you're facing</li>
                    <li>Goals for the upcoming period</li>
                    <li>Any materials or documents to review</li>
                </ul>
            </div>
            
            <div class='text-center mt-3'>
                " . ($meeting_link ? "<a href='{$meeting_link}' class='btn' style='background: #ef4444;'>Join Session Now</a>" : "<a href='http://localhost/IdeaNest/user/dashboard.php' class='btn' style='background: #ef4444;'>View Dashboard</a>") . "
            </div>
            
            <p class='mt-3'>See you soon! I'm looking forward to our productive session together.</p>
        </div>";
    }
}
?>