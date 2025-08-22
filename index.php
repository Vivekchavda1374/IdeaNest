
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest - Academic Project Management Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .testimonial-card.animate {
            opacity: 1;
            transform: translateY(0);
            transition: all 0.6s ease;
        }

        .testimonial-card {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        /* Fix for feature cards animation too */
        .feature-card.animate {
            opacity: 1;
            transform: translateY(0);
            transition: all 0.6s ease;
        }

        .feature-card {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
    </style>
</head>
<body>

<script>

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.2
    });

    // Observe features cards
    document.querySelectorAll('.feature-card').forEach(card => {
        observer.observe(card);
    });

    // Observe testimonial cards
    document.querySelectorAll('.testimonial-card').forEach(card => {
        observer.observe(card);
    });
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IdeaNest - Academic Project Management Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #8B5CF6;
            --primary-dark: #7C3AED;
            --secondary: #EC4899;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
            background-color: var(--light);
        }

        /* Header Styles */
        .header {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            z-index: 1000;
            padding: 1rem 0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .header.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a:after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transition: width 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-links a:hover:after {
            width: 100%;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .login-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
        }

        .register-btn {
            background: transparent;
            color: var(--primary);
            padding: 0.75rem 2rem;
            border: 2px solid var(--primary);
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .register-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--dark);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.15"/><circle cx="20" cy="60" r="0.5" fill="white" opacity="0.15"/><circle cx="80" cy="30" r="0.5" fill="white" opacity="0.15"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-text {
            color: white;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            animation: fadeInUp 1s ease;
        }

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease 0.3s both;
        }

        .hero-description {
            font-size: 1.1rem;
            margin-bottom: 3rem;
            opacity: 0.8;
            animation: fadeInUp 1s ease 0.6s both;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            animation: fadeInUp 1s ease 0.9s both;
        }

        .btn-primary {
            background: white;
            color: var(--primary);
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(255, 255, 255, 0.3);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            padding: 1rem 2.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: white;
        }

        .hero-visual {
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeInRight 1s ease 0.5s both;
        }

        .floating-cards {
            position: relative;
            width: 400px;
            height: 400px;
        }

        .card {
            position: absolute;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 2rem;
            color: white;
            animation: float 3s ease-in-out infinite;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .card:nth-child(1) {
            top: 0;
            left: 0;
            width: 200px;
            animation-delay: 0s;
        }

        .card:nth-child(2) {
            top: 50px;
            right: 0;
            width: 180px;
            animation-delay: 1s;
        }

        .card:nth-child(3) {
            bottom: 50px;
            left: 50px;
            width: 220px;
            animation-delay: 2s;
        }

        /* Features Section */
        .features {
            padding: 8rem 0;
            background: var(--light);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .section-title {
            text-align: center;
            font-size: 3rem;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .section-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: var(--gray);
            margin-bottom: 4rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 3rem;
        }

        .feature-card {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(139, 92, 246, 0.1);
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .feature-card.animate {
            opacity: 1;
            transform: translateY(0);
        }

        .feature-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            transition: height 0.3s ease;
        }

        .feature-card:hover:before {
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(139, 92, 246, 0.15);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            color: white;
            position: relative;
            z-index: 1;
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--dark);
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .feature-description {
            color: var(--gray);
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }

        /* Stats Section */
        .stats {
            padding: 6rem 0;
            background: linear-gradient(135deg, var(--dark), #334155);
            color: white;
            position: relative;
            z-index: 5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 3.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Testimonials */
        .testimonials {
            padding: 8rem 0;
            background: #fff;
            position: relative;
            z-index: 10;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .testimonial-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(139, 92, 246, 0.1);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .testimonial-card.animate {
            opacity: 1;
            transform: translateY(0);
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(139, 92, 246, 0.15);
        }

        .testimonial-content {
            position: relative;
        }

        .testimonial-text {
            font-size: 1.1rem;
            color: #334155;
            line-height: 1.8;
            margin-bottom: 2rem;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #8B5CF6, #A855F7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .author-details h4 {
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .author-details p {
            color: #64748b;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .testimonials-grid {
                grid-template-columns: 1fr;
            }
        }



        /* CTA Section */
        .cta-section {
            padding: 8rem 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            text-align: center;
        }

        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .cta-title {
            font-size: 3rem;
            margin-bottom: 1.5rem;
        }

        .cta-text {
            font-size: 1.2rem;
            margin-bottom: 3rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: var(--dark);
            color: white;
            padding: 4rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .footer-section p {
            color: #94a3b8;
            margin-bottom: 1rem;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .footer-section ul li a:hover {
            color: var(--primary);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid #334155;
            color: #94a3b8;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .modal.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 500px;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.2);
            transform: translateY(50px);
            opacity: 0;
            transition: all 0.3s ease;
            position: relative;
        }

        .modal.active .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }

        .modal-title {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--dark);
        }

        .modal-text {
            color: var(--gray);
            margin-bottom: 2rem;
        }

        .modal-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: var(--dark);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.2);
        }

        .form-submit {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .form-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(139, 92, 246, 0.3);
        }

        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--gray);
        }

        .form-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .hero-title {
                font-size: 3rem;
            }

            .nav-links {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .auth-buttons {
                display: none;
            }

            .mobile-auth {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                margin-top: 2rem;
            }
        }

        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .floating-cards {
                width: 100px;
                height: 100px;
            }

            .section-title {
                font-size: 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }



            .testimonials-grid,
            .pricing-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Mobile Menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            right: -100%;
            width: 80%;
            max-width: 400px;
            height: 100vh;
            background: white;
            z-index: 2000;
            padding: 2rem;
            transition: right 0.3s ease;
            box-shadow: -5px 0 30px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
        }

        .mobile-menu.active {
            right: 0;
        }

        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .mobile-menu-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .mobile-nav-links {
            list-style: none;
            margin-bottom: 2rem;
        }

        .mobile-nav-links li {
            margin-bottom: 1rem;
        }

        .mobile-nav-links a {
            display: block;
            padding: 1rem;
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .mobile-nav-links a:hover {
            background: rgba(139, 92, 246, 0.1);
            color: var(--primary);
        }
    </style>
</head>
<body>
<!-- Header -->
<header class="header">
    <div class="nav-container">
        <div class="logo">
            IdeaNest
        </div>
        <nav>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#testimonials">Testimonials</a></li>

                <li><a href="#about">About</a></li>
            </ul>
        </nav>
        <div class="auth-buttons">
            <a href="./Login/Login/login.php" class="login-btn" >Login</a>
            <a href="./Login/Login/register.php" class="register-btn">Register</a>
        </div>
        <button class="mobile-menu-btn" onclick="openMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <div class="logo">
            <i class="fas fa-brain"></i> IdeaNest
        </div>
        <button class="mobile-menu-close" onclick="closeMobileMenu()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <ul class="mobile-nav-links">
        <li><a href="#home" onclick="closeMobileMenu()">Home</a></li>
        <li><a href="#features" onclick="closeMobileMenu()">Features</a></li>
        <li><a href="#testimonials" onclick="closeMobileMenu()">Testimonials</a></li>
        <li><a href="#about" onclick="closeMobileMenu()">About</a></li>
    </ul>
    <div class="mobile-auth">
        <a href="./Login/Login/login.php" class="login-btn" >Login</a>
        <a href="./Login/Login/register.php" class="register-btn">Register</a>
    </div>
</div>

<!-- Hero Section -->
<section class="hero" id="home">
    <div class="hero-content">
        <div class="hero-text">
            <h1 class="hero-title">IdeaNest</h1>
            <h2 class="hero-subtitle">Where Academic Ideas Take Flight</h2>
            <p class="hero-description">
                The ultimate platform for managing, sharing, and reviewing academic projects.
                Streamline your project workflow with our comprehensive solution for students,
                sub-admins, and administrators.
            </p>
            <div class="cta-buttons">
                <a href="./Login/Login/login.php" class="btn-primary" >
                    <i class="fas fa-rocket"></i> Get Started
                </a>
                <a href="#features" class="btn-secondary">
                    <i class="fas fa-info-circle"></i> Learn More
                </a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="floating-cards">
                <div class="card" >
                    <h3>📚 Projects</h3>
                    <p>Manage & Submit</p>
                </div>
                <div class="card" >
                    <h3>👥 Collaborate</h3>
                    <p>Team Work</p>
                </div>
                <div class="card" >
                    <h3>🚀 Innovation</h3>
                    <p>Ideas to Reality</p>
                </div>
            </div>
        </div>
    </div>
</section>
<section class="features" id="features">
    <div class="container">
        <h2 class="section-title">Powerful Features</h2>
        <p class="section-subtitle">Everything you need to manage academic projects efficiently</p>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3 class="feature-title">Project Management</h3>
                <p class="feature-description">
                    Submit, edit, and track your projects with ease. Support for multiple file types
                    including images, videos, code files, and documentation.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3 class="feature-title">Advanced Search</h3>
                <p class="feature-description">
                    Find projects quickly with powerful search and filtering capabilities.
                    Sort by type, category, or custom criteria.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👨‍💼</div>
                <h3 class="feature-title">Multi-Role Support</h3>
                <p class="feature-description">
                    Designed for students, sub-admins, and administrators with role-based
                    access control and specialized features for each user type.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📁</div>
                <h3 class="feature-title">File Management</h3>
                <p class="feature-description">
                    Secure file upload system with organized storage structure.
                    Support for various file formats with validation and security.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">✅</div>
                <h3 class="feature-title">Review System</h3>
                <p class="feature-description">
                    Streamlined project review process with feedback mechanisms,
                    approval workflows, and detailed tracking.
                </p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h3 class="feature-title">Secure & Reliable</h3>
                <p class="feature-description">
                    Built with security in mind featuring secure authentication,
                    session management, and data protection.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats" id="stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number" data-target="1200">0</div>
                <div class="stat-label">Projects</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-target="850">0</div>
                <div class="stat-label">Active Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-target="95">0</div>
                <div class="stat-label">Universities</div>
            </div>
            <div class="stat-item">
                <div class="stat-number" data-target="120">0</div>
                <div class="stat-label">Mentors</div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials" id="testimonials">
    <div class="container">
        <h2 class="section-title">What Students Say</h2>
        <p class="section-subtitle">Real feedback from IdeaNest users</p>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p class="testimonial-text">"IdeaNest helped our team coordinate and submit an amazing project — reviewers were clear and fast. Highly recommended!"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">A</div>
                        <div class="author-details">
                            <h4>Aarav Patel</h4>
                            <p>Computer Engineering - Final Year</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p class="testimonial-text">"The versioned reviews made it simple to track changes. Best platform for academic projects."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">S</div>
                        <div class="author-details">
                            <h4>Shreya Rao</h4>
                            <p>Electronics Dept.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p class="testimonial-text">"Search & discover features saved us days of research — found relevant projects and reused ideas legally."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">V</div>
                        <div class="author-details">
                            <h4>Vivek Chavda</h4>
                            <p>MERN Stack Dev</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section" id="cta">
    <div class="container cta-content">
        <h2 class="cta-title">Ready to bring your idea to life?</h2>
        <p class="cta-text">Join thousands of students and mentors using IdeaNest to collaborate, learn and ship better projects.</p>
        <div>
            <a href="./Login/Login/register.php" class="btn-primary" "><i class="fas fa-rocket"></i> Create Account</a>
            <a href="#features" class="btn-secondary"><i class="fas fa-info-circle"></i> Explore Features</a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer" id="about">
    <div class="container footer-content">
        <div class="footer-section">
            <h3>IdeaNest</h3>
            <p>Build, share and learn from academic projects. Empowering student innovators.</p>
        </div>
        <div class="footer-section">
            <h3>Product</h3>
            <ul>
                <li><a href="#features">Features</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Company</h3>
            <ul>
                <li><a href="#about">About</a></li>
                <li><a href="mailto:ideanest.ict@gmail.com" onclick="openModal('contact')">Contact</a></li>
                <li><a href="mailto:ideanest.ict@gmail.com">Request Demo</a></li>

            </ul>
        </div>
        <div class="footer-section">
            <h3>Follow</h3>
            <div class="social-links">

                <a href="https://www.linkedin.com/in/vivek-chavda-018380220/" aria-label="linkedin"><i class="fab fa-linkedin"></i></a>
                <a href="https://github.com/Vivekchavda1374" aria-label="github"><i class="fab fa-github"></i></a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        &copy; <span id="year"></span> IdeaNest • Built with ❤️
    </div>
</footer>

<!-- Modals -->
<div class="modal" id="modal-login" aria-hidden="true">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('login')">&times;</button>
        <h3 class="modal-title">Login</h3>
        <p class="modal-text">Sign in to your IdeaNest account</p>
        <form class="modal-form" onsubmit="event.preventDefault(); alert('Login submitted');">
            <div class="form-group">
                <label>Email</label>
                <input type="email" required />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" required />
            </div>
            <button class="form-submit" type="submit">Login</button>
        </form>
    </div>
</div>

<div class="modal" id="modal-register" aria-hidden="true">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('register')">&times;</button>
        <h3 class="modal-title">Register</h3>
        <p class="modal-text">Create an account to start submitting projects</p>
        <form class="modal-form" onsubmit="event.preventDefault(); alert('Registration submitted');">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" required />
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" required />
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" required />
            </div>
            <button class="form-submit" type="submit">Register</button>
            <div class="form-footer">Already have an account? <a href="#" onclick="closeModal('register'); openModal('login')">Login</a></div>
        </form>
    </div>
</div>

<!-- JavaScript: place just before </body> -->
<script>
    // small helpers for modals and mobile menu
    function openModal(name) {
        const id = 'modal-' + name;
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add('active');
        el.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(name) {
        const id = 'modal-' + name;
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove('active');
        el.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    // Mobile menu
    function openMobileMenu() {
        document.getElementById('mobileMenu').classList.add('active');
    }
    function closeMobileMenu() {
        document.getElementById('mobileMenu').classList.remove('active');
    }

    // Close modal when clicking overlay
    document.addEventListener('click', function (e) {
        if (e.target.classList && e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
            e.target.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    });

    // Header scroll behavior
    const header = document.querySelector('.header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 40) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // IntersectionObserver for reveal animations (features & testimonials & cards)
    const io = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.18 });

    document.querySelectorAll('.feature-card, .testimonial-card, .card').forEach(el => {
        io.observe(el);
    });

    // Counters animation for stats
    function animateCounter(el, target, duration = 1500) {
        let start = 0;
        const stepTime = Math.abs(Math.floor(duration / target));
        const timer = setInterval(() => {
            start += Math.ceil(target / (duration / stepTime));
            if (start >= target) {
                el.textContent = target;
                clearInterval(timer);
            } else {
                el.textContent = start;
            }
        }, stepTime);
    }

    const statEls = document.querySelectorAll('.stat-number');
    const statObserver = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.getAttribute('data-target'), 10) || 0;
                animateCounter(el, target, 1400);
                obs.unobserve(el);
            }
        });
    }, { threshold: 0.4 });

    statEls.forEach(el => statObserver.observe(el));

    // Set current year in footer
    document.getElementById('year').textContent = new Date().getFullYear();

    // keyboard accessibility: close modals with Esc
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.active').forEach(m => {
                m.classList.remove('active');
                m.setAttribute('aria-hidden', 'true');
            });
            document.body.style.overflow = '';
        }
    });
</script>


<div class="modal" id="modal-demo" aria-hidden="true">
    <div class="modal-content">
        <button class="modal-close" onclick="closeModal('demo')">&times;</button>
        <h3 class="modal-title">Request Demo</h3>
        <p class="modal-text">Tell us a bit about your institution and we'll arrange a demo.</p>
        <form class="modal-form" onsubmit="event.preventDefault(); alert('Demo requested');">
            <div class="form-group">
                <label>Institution / Company</label>
                <input type="text" required />
            </div>
            <div class="form-group">
                <label>Contact Email</label>
                <input type="email" required />
            </div>
            <button class="form-submit" type="submit">Request Demo</button>
        </form>
    </div>
</div>


