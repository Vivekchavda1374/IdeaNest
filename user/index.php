<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

    <link href="style.css" rel="stylesheet">
</head>

<body>
<div class="overlay" id="overlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-cubes"></i>
        <div class="logo">Dashboard</div>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="#" class="active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="projects_view.php">
                <i class="fas fa-project-diagram"></i>
                <span>Projects</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-file-alt"></i>
                <span>Blog Posts</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-user-graduate"></i>
                <span>Mentorship</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-bookmark"></i>
                <span>Bookmarks</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-chart-bar"></i>
                <span>Analytics</span>
            </a>
        </li>
        <li>
            <a href="#">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content" id="mainContent">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="d-flex align-items-center w-100">
            <button class="btn btn-light me-3" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>

            <div class="search-bar me-auto search-container ">
                <i class="fas fa-search"></i>
                <input type="text" id="search" class="form-control" placeholder="Search projects, blogs, mentors..." onkeyup="fetchResults()">
                <div id="searchResults" class="search-results"></div>
            </div>


            <div class="d-flex align-items-center">
                <div class="position-relative me-4">
                    <button class="btn btn-light position-relative">
                        <i class="far fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                </div>

                <div class="dropdown user-dropdown">
                    <button class="btn btn-light dropdown-toggle d-flex align-items-center" type="button"
                            id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="me-2"
                             style="width: 32px; height: 32px; border-radius: 50%; background-color: #4361ee; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                            V
                        </div>
                        <span>Vivek Chavda</span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="userMenu">
                        <li class="dropdown-header">
                            <h6 class="mb-0">Vivek Chavda</h6>
                            <small class="text-muted">viveksinhchavda@gmail.com</small>
                        </li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Account Settings</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-sign-out-alt"></i> Sign
                                Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Welcome back, Vivek!</h2>
            <a href="./forms/new_project_add.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> New Project</a>
        </div>

        <!-- Quick Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card bg-primary">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 text-white-50">My Projects</p>
                                <h3 class="mt-2 mb-0 text-white">12</h3>
                            </div>
                            <div class="icon">
                                <i class="fas fa-project-diagram"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card" style="background-color: #4cc9f0;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 text-white-50"> My Published Blogs</p>
                                <h3 class="mt-2 mb-0 text-white">24</h3>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card" style="background-color: #f72585;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 text-white-50">Upcoming Featuer </p>
                                <h3 class="mt-2 mb-0 text-white"> Mentor Sessions</h3>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card" style="background-color: #3f37c9;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="mb-0 text-white-50">Bookmarks</p>
                                <h3 class="mt-2 mb-0 text-white">17</h3>
                            </div>
                            <div class="icon">
                                <i class="fas fa-bookmark"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Cards -->
        <div class="row g-4 mb-4">
            <!-- Projects Progress -->
            <div class="col-lg-14">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Project Classification </h5>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" type="button" id="projectDropdown"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-ellipsis-h"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="projectDropdown">
                                <li><a class="dropdown-item" href="#">View All</a></li>
                                <li><a class="dropdown-item" href="#">Sort by Date</a></li>
                                <li><a class="dropdown-item" href="#">Sort by Priority</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-medium">Web Application</span>
                                <span class="badge bg-success badge-custom">75</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 75%"
                                     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-medium">Mobile Application</span>
                                <span class="badge bg-primary badge-custom">45</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 45%"
                                     aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-medium">IOT </span>
                                <span class="badge bg-warning badge-custom">30</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: 30%"
                                     aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-medium">Ardino</span>
                                <span class="badge bg-info badge-custom">90</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info" role="progressbar" style="width: 90%"
                                     aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-medium">Backend API </span>
                                <span class="badge bg-danger badge-custom">15</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: 15%"
                                     aria-valuenow="15" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0 text-center">
                        <button class="btn btn-outline-primary btn-sm">View All Projects</button>
                    </div>
                </div>
            </div>
            <!--
            Upcoming Mentor Sessions
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Upcoming Mentor Sessions</h5>
                        <button class="btn btn-sm btn-light"><i class="fas fa-plus"></i></button>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center p-3 border rounded mb-3">
                            <div class="me-3">
                                <div
                                    style="width: 45px; height: 45px; background-color: #4cc9f0; color: white; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <span style="font-size: 14px; font-weight: bold;">MAR</span>
                                    <span style="font-size: 16px; font-weight: bold;">03</span>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1">Project Review</h6>
                                <div class="text-muted small">10:00 AM - 11:00 AM</div>
                                <div class="text-primary small">With: Sarah Johnson</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center p-3 border rounded mb-3">
                            <div class="me-3">
                                <div
                                    style="width: 45px; height: 45px; background-color: #4361ee; color: white; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <span style="font-size: 14px; font-weight: bold;">MAR</span>
                                    <span style="font-size: 16px; font-weight: bold;">05</span>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1">Code Review</h6>
                                <div class="text-muted small">2:00 PM - 3:30 PM</div>
                                <div class="text-primary small">With: David Chen</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center p-3 border rounded">
                            <div class="me-3">
                                <div
                                    style="width: 45px; height: 45px; background-color: #f72585; color: white; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                                    <span style="font-size: 14px; font-weight: bold;">MAR</span>
                                    <span style="font-size: 16px; font-weight: bold;">10</span>
                                </div>
                            </div>
                            <div>
                                <h6 class="mb-1">Portfolio Review</h6>
                                <div class="text-muted small">11:00 AM - 12:00 PM</div>
                                <div class="text-primary small">With: Michelle Wong</div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top-0 text-center">
                        <button class="btn btn-outline-primary btn-sm">Schedule New Session</button>
                    </div>
                </div>
            </div>
        </div> -->

            <!-- Recent Activity and Blog Posts -->
            <div class="row g-4">
                <!-- Recent Activity -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Activity</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" id="activityDropdown"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="activityDropdown">
                                    <li><a class="dropdown-item" href="#">All Activities</a></li>
                                    <li><a class="dropdown-item" href="#">Filter by Type</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="recent-activity">
                                <div class="item p-3">
                                    <div class="avatar" style="background-color: #4cc9f0;">
                                        <i class="fas fa-comment-alt"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1">You commented on <span class="fw-medium">Mobile App
                                                    Design</span></p>
                                        <p class="text-muted small mb-0">2 hours ago</p>
                                    </div>
                                </div>
                                <div class="item p-3">
                                    <div class="avatar" style="background-color: #f72585;">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1">You published a new blog <span class="fw-medium">UX Design
                                                    Principles</span></p>
                                        <p class="text-muted small mb-0">Yesterday at 3:45 PM</p>
                                    </div>
                                </div>
                                <div class="item p-3">
                                    <div class="avatar" style="background-color: #4361ee;">
                                        <i class="fas fa-trophy"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1">You completed <span class="fw-medium">Frontend
                                                    Development</span> milestone</p>
                                        <p class="text-muted small mb-0">Feb 28, 2025</p>
                                    </div>
                                </div>
                                <div class="item p-3">
                                    <div class="avatar" style="background-color: #3f37c9;">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div>
                                        <p class="mb-1">Mentor session with <span class="fw-medium">Sarah
                                                    Johnson</span>
                                            completed</p>
                                        <p class="text-muted small mb-0">Feb 27, 2025</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0 text-center">
                            <button class="btn btn-outline-primary btn-sm">View All Activity</button>
                        </div>
                    </div>
                </div>

                <!-- Latest Blog Posts -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Latest Blog Posts</h5>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" id="blogDropdown"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="blogDropdown">
                                    <li><a class="dropdown-item" href="#">My Posts</a></li>
                                    <li><a class="dropdown-item" href="#">Bookmarked Posts</a></li>
                                    <li><a class="dropdown-item" href="#">Popular Posts</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <a href="#" class="list-group-item list-group-item-action p-3">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <h6 class="mb-1">Best Practices for React Development</h6>
                                        <span class="badge bg-primary badge-custom">New</span>
                                    </div>
                                    <p class="text-muted small mb-2">Tips and tricks for optimizing React
                                        applications
                                        and improving performance.</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">
                                            <i class="far fa-eye me-1"></i> 245 views
                                            <i class="far fa-comment ms-3 me-1"></i> 18 comments
                                        </div>
                                        <span class="text-muted small">Feb 28, 2025</span>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action p-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">UI Design Principles for Developers</h6>
                                    </div>
                                    <p class="text-muted small mb-2">Essential design concepts every developer
                                        should
                                        know about.</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">
                                            <i class="far fa-eye me-1"></i> 187 views
                                            <i class="far fa-comment ms-3 me-1"></i> 12 comments
                                        </div>
                                        <span class="text-muted small">Feb 25, 2025</span>
                                    </div>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action p-3">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Getting Started with Backend Development</h6>
                                    </div>
                                    <p class="text-muted small mb-2">A comprehensive guide to building robust
                                        server-side applications.</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted small">
                                            <i class="far fa-eye me-1"></i> 324 views
                                            <i class="far fa-comment ms-3 me-1"></i> 24 comments
                                        </div>
                                        <span class="text-muted small">Feb 20, 2025</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-top-0 text-center">
                            <button class="btn btn-outline-primary btn-sm">Create New Blog</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>

</body>
