<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest - Project Collaboration Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    /* Custom Styles */
    :root {
        --primary: #4e73df;
        --secondary: #2e59d9;
        --accent: #f8f9fc;
        --dark: #1a202c;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        scroll-behavior: smooth;
    }

    /* Navbar Styling */
    .navbar {
        padding: 1rem 0;
        transition: all 0.3s ease;
    }

    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
    }

    .navbar-brand span {
        color: var(--primary);
    }

    .nav-link {
        font-weight: 500;
        margin: 0 10px;
        position: relative;
    }

    .nav-link::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        background: var(--primary);
        left: 0;
        bottom: -5px;
        transition: width 0.3s;
    }

    .nav-link:hover::after {
        width: 100%;
    }

    /* Hero Section */
    .hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        height: 100vh;
        position: relative;
        overflow: hidden;
    }

    .hero::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,160L48,154.7C96,149,192,139,288,149.3C384,160,480,192,576,202.7C672,213,768,203,864,165.3C960,128,1056,64,1152,64C1248,64,1344,128,1392,160L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        background-size: cover;
        background-position: center;
    }

    .hero-content {
        position: relative;
        z-index: 1;
        padding-top: 100px;
    }

    .hero h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        animation: fadeInDown 1s ease;
    }

    .hero p {
        font-size: 1.5rem;
        margin-bottom: 2rem;
        opacity: 0.9;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        animation: fadeInUp 1s ease 0.3s;
        animation-fill-mode: both;
    }

    .btn-hero {
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        animation: fadeInUp 1s ease 0.6s;
        animation-fill-mode: both;
    }

    .btn-hero:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    /* Section Styling */
    section {
        padding: 100px 0;
    }

    section h2 {
        font-weight: 700;
        margin-bottom: 50px;
        position: relative;
        display: inline-block;
    }

    section h2::after {
        content: '';
        position: absolute;
        width: 70px;
        height: 3px;
        background: var(--primary);
        bottom: -15px;
        left: 50%;
        transform: translateX(-50%);
    }

    /* About Section */
    #about {
        background-color: #fff;
    }

    .about-content {
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        margin-top: 30px;
    }

    .about-icon {
        font-size: 50px;
        color: var(--primary);
        margin-bottom: 20px;
    }

    /* Team Section */
    #team {
        background-color: var(--accent);
    }

    .team-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        margin-bottom: 30px;
    }

    .team-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    .team-img-container {
        height: 600px;
        overflow: hidden;
    }

    .team-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .team-card:hover .team-img {
        transform: scale(1.1);
    }

    .team-info {
        padding: 25px;
        text-align: center;
    }

    .team-info h4 {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .team-info p {
        color: #666;
        margin-bottom: 15px;
    }

    .social-links a {
        display: inline-block;
        width: 35px;
        height: 35px;
        line-height: 35px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        margin: 0 5px;
        transition: all 0.3s ease;
    }

    .social-links a:hover {
        background: var(--secondary);
        transform: translateY(-3px);
    }

    /* Features Section */
    .feature-card {
        background: white;
        padding: 40px 30px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        text-align: center;
        height: 100%;
        transition: all 0.3s ease;
        position: relative;
        z-index: 1;
        overflow: hidden;
    }

    .feature-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 0;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        transition: all 0.5s ease;
        z-index: -1;
        opacity: 0;
    }

    .feature-card:hover::before {
        height: 100%;
        opacity: 1;
    }

    .feature-card:hover {
        transform: translateY(-10px);
    }

    .feature-card:hover h4,
    .feature-card:hover p,
    .feature-card:hover .feature-icon {
        color: white;
    }

    .feature-icon {
        font-size: 50px;
        color: var(--primary);
        margin-bottom: 25px;
        transition: all 0.3s ease;
    }

    .feature-card h4 {
        font-weight: 600;
        margin-bottom: 15px;
        transition: all 0.3s ease;
    }

    .feature-card p {
        color: #666;
        margin-bottom: 0;
        transition: all 0.3s ease;
    }

    /* Contact Section */
    #contact {
        background-color: var(--accent);
    }

    .contact-form {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .form-control {
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #e0e0e0;
    }

    .form-control:focus {
        box-shadow: none;
        border-color: var(--primary);
    }

    .contact-info {
        padding: 40px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        border-radius: 15px;
        color: white;
        height: 100%;
    }

    .contact-info h4 {
        margin-bottom: 25px;
        font-weight: 600;
    }

    .contact-info i {
        margin-right: 10px;
        font-size: 20px;
    }

    .contact-info p {
        margin-bottom: 20px;
    }

    /* Footer */
    footer {
        background: var(--dark);
        color: white;
        padding: 50px 0 20px;
    }

    .footer-links h5 {
        font-weight: 600;
        margin-bottom: 25px;
    }

    .footer-links ul {
        list-style: none;
        padding: 0;
    }

    .footer-links li {
        margin-bottom: 10px;
    }

    .footer-links a {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .footer-links a:hover {
        color: white;
        text-decoration: none;
        padding-left: 5px;
    }

    .copyright {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding-top: 20px;
        margin-top: 50px;
    }

    /* Animations */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Counter Section */
    .counter-box {
        text-align: center;
        padding: 30px 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .counter-number {
        font-size: 3rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .counter-text {
        font-weight: 500;
        color: #555;
    }

    /* Newsletter */
    .newsletter {
        padding: 80px 0;
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        color: white;
    }

    .newsletter h3 {
        font-weight: 700;
        margin-bottom: 20px;
    }

    .newsletter-form {
        position: relative;
        max-width: 550px;
        margin: 0 auto;
    }

    .newsletter-input {
        padding: 15px 25px;
        border-radius: 50px;
        width: 100%;
        border: none;
        font-size: 16px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .newsletter-btn {
        position: absolute;
        right: 5px;
        top: 5px;
        padding: 10px 25px;
        border-radius: 50px;
        background: var(--primary);
        color: white;
        border: none;
        font-weight: 500;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .newsletter-btn:hover {
        background: var(--secondary);
    }

    /* Example Projects */
    .project-card {
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        transition: all 0.3s ease;
    }

    .project-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
    }

    .project-img {
        height: 200px;
        overflow: hidden;
    }

    .project-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .project-card:hover .project-img img {
        transform: scale(1.1);
    }

    .project-info {
        padding: 20px;
        background: white;
    }

    .project-info h4 {
        font-weight: 600;
        margin-bottom: 10px;
    }

    .project-info .badge {
        margin-right: 5px;
        font-weight: 500;
    }

    .project-footer {
        padding: 15px 20px;
        background: #f8f9fa;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .user-info {
        display: flex;
        align-items: center;
    }

    .user-info img {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        margin-right: 10px;
    }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">Idea<span>Nest</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="#projects">Projects</a></li>
                    <li class="nav-item"><a class="nav-link" href="#team">Team</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-primary ms-3 px-4" href="login.php">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1>Learn, Share, and Grow Together</h1>
                    <p>The ultimate platform for students and beginners to discover projects, share ideas, and learn
                        from each other</p>
                    <div class="d-flex gap-3">
                        <a href="register.php" class="btn btn-light btn-hero">Join for Free</a>
                        <a href="#features" class="btn btn-outline-light btn-hero">Explore Features</a>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block">
                    <img src="ict.png" alt="Project Collaboration Platform" class="img-fluid rounded-3 shadow">
                </div>
            </div>
        </div>
    </header>

    <!-- About Section -->
    <section id="about">
        <div class="container">
            <div class="text-center mb-5">
                <h2>What is IdeaNest?</h2>
                <p class="lead w-75 mx-auto">A student-created platform where beginners can learn, create and
                    collaborate on projects</p>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="about-content text-center">
                        <i class="fas fa-lightbulb about-icon"></i>
                        <h4>Find Project Ideas</h4>
                        <p>Discover project problem statements and get inspired to start building your next creation.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="about-content text-center">
                        <i class="fas fa-code about-icon"></i>
                        <h4>Learn By Example</h4>
                        <p>Explore projects created by others to understand development processes and best practices.
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="about-content text-center">
                        <i class="fas fa-share-alt about-icon"></i>
                        <h4>Showcase Your Work</h4>
                        <p>Upload your completed projects to build your portfolio and help others learn from your
                            experience.</p>
                    </div>
                </div>
            </div>

            <!-- Counter Section -->
            <div class="row mt-5 pt-5">
                <div class="col-md-3 col-6 mb-4">
                    <div class="counter-box">
                        <div class="counter-number">100+</div>
                        <div class="counter-text">Active Projects</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="counter-box">
                        <div class="counter-number">50+</div>
                        <div class="counter-text">Problem Statements</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="counter-box">
                        <div class="counter-number">200+</div>
                        <div class="counter-text">Student Users</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-4">
                    <div class="counter-box">
                        <div class="counter-number">20+</div>
                        <div class="counter-text">Categories</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Platform Features</h2>
                <p class="lead w-75 mx-auto">Discover how IdeaNest helps you learn programming and project development
                </p>
            </div>

            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-search feature-icon"></i>
                        <h4>Problem Statement Library</h4>
                        <p>Browse through our collection of project ideas and problem statements to find inspiration for
                            your next development journey.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-project-diagram feature-icon"></i>
                        <h4>Project Showcase</h4>
                        <p>Explore projects created by other students to learn different approaches to solving
                            programming challenges.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-upload feature-icon"></i>
                        <h4>Easy Project Upload</h4>
                        <p>Share your completed projects with detailed descriptions, code repositories, and
                            documentation.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-comments feature-icon"></i>
                        <h4>Feedback & Comments</h4>
                        <p>Get constructive feedback on your projects from peers and experienced developers to improve
                            your skills.</p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-user-graduate feature-icon"></i>
                        <h4>Learning Resources</h4>
                        <p>Access tutorials, guides, and best practices to help you through your development process.
                        </p>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-users feature-icon"></i>
                        <h4>Team Collaboration</h4>
                        <p>Find teammates with complementary skills to work on larger projects and learn together.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Projects Showcase Section -->
    <section id="projects" class="bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Featured Projects</h2>
                <p class="lead w-75 mx-auto">Explore some of the outstanding projects created by our community members
                </p>
            </div>

            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="project-card">
                        <div class="project-img">
                            <img src="/api/placeholder/400/200" alt="Project 1">
                        </div>
                        <div class="project-info">
                            <h4>Weather Forecast App</h4>
                            <p>A responsive web application that provides real-time weather updates using a public API.
                            </p>
                            <div class="mt-3">
                                <span class="badge bg-primary">JavaScript</span>
                                <span class="badge bg-info">React</span>
                                <span class="badge bg-secondary">API</span>
                            </div>
                        </div>
                        <div class="project-footer">
                            <div class="user-info">
                                <img src="/api/placeholder/30/30" alt="User">
                                <span>Ravi Kumar</span>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary">View Project</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="project-card">
                        <div class="project-img">
                            <img src="/api/placeholder/400/200" alt="Project 2">
                        </div>
                        <div class="project-info">
                            <h4>Student Management System</h4>
                            <p>A comprehensive system for managing student records, attendance, and performance
                                tracking.</p>
                            <div class="mt-3">
                                <span class="badge bg-success">PHP</span>
                                <span class="badge bg-danger">MySQL</span>
                                <span class="badge bg-warning text-dark">Bootstrap</span>
                            </div>
                        </div>
                        <div class="project-footer">
                            <div class="user-info">
                                <img src="/api/placeholder/30/30" alt="User">
                                <span>Anjali Patel</span>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary">View Project</a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="project-card">
                        <div class="project-img">
                            <img src="/api/placeholder/400/200" alt="Project 3">
                        </div>
                        <div class="project-info">
                            <h4>E-commerce Mobile App</h4>
                            <p>A fully functional e-commerce application with product catalog, cart, and payment
                                integration.</p>
                            <div class="mt-3">
                                <span class="badge bg-primary">Flutter</span>
                                <span class="badge bg-success">Firebase</span>
                                <span class="badge bg-info">Dart</span>
                            </div>
                        </div>
                        <div class="project-footer">
                            <div class="user-info">
                                <img src="/api/placeholder/30/30" alt="User">
                                <span>Rohan Shah</span>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary">View Project</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <a href="#" class="btn btn-primary btn-lg">Explore All Projects</a>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section id="team">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Our Team</h2>
                <p class="lead w-75 mx-auto">Meet the student developers behind IdeaNest</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="team-card">
                        <div class="team-img-container">
                            <img src="me 4.png" class="team-img" alt="Bhavik Kaladiya">
                        </div>
                        <div class="team-info">
                            <h4>Bhavik Kaladiya</h4>
                            <p>Lead Developer & Co-founder</p>
                            <p class="small text-muted mb-3">Computer Science Student passionate about creating
                                educational platforms for beginner developers.</p>
                            <div class="social-links">
                                <a href="www.linkedin.com/in/bhavik-kaladiya"><i class="fab fa-linkedin-in"></i></a>
                                <a href=""><i class="fab fa-github"></i></a>
                                <a href="#"><i class="fab fa-twitter"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6">
                    <div class="team-card">
                        <div class="team-img-container">
                            <img src="vivek.jpeg" class="team-img" alt="Viveksinh Chaavada">
                        </div>
                        <div class="team-info">
                            <h4>Viveksinh Chaavada</h4>
                            <p>UI/UX Designer & Co-founder</p>
                            <p class="small text-muted mb-3">Design enthusiast with a focus on creating intuitive and
                                accessible user experiences for educational platforms.</p>
                            <div class="social-links">
                                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#"><i class="fab fa-dribbble"></i></a>
                                <a href="#"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        </div>
    </section>

    <!-- Newsletter Section -->
    <section class="newsletter">
        <div class="container text-center">
            <h3>Stay Updated with IdeaNest</h3>
            <p class="mb-4">Subscribe to our newsletter for new project ideas, learning resources, and platform updates.
            </p>
            <form class="newsletter-form">
                <input type="email" class="newsletter-input" placeholder="Enter your email address">
                <button type="submit" class="newsletter-btn">Subscribe</button>
            </form>
        </div>
    </section>

    <!-- Contact Section -->