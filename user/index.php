<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sidebar with Search List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
    body {
        margin: 0;
        padding: 0;
        display: flex;
        background: #f8f9fa;
    }

    .sidebar {
        width: 250px;
        height: 100vh;
        background: #222;
        color: white;
        position: fixed;
        top: 0;
        left: 0;
        padding: 20px;
    }

    .sidebar h3 {
        text-align: center;
        margin-bottom: 20px;
    }

    .sidebar a {
        display: flex;
        align-items: center;
        color: white;
        padding: 12px;
        text-decoration: none;
        border-radius: 5px;
        transition: 0.3s;
    }

    .sidebar a:hover,
    .sidebar a.active {
        background: #007bff;
    }

    .sidebar a i {
        width: 20px;
        margin-right: 15px;
    }

    .content {
        margin-left: 270px;
        padding: 20px;
        flex-grow: 1;
        display: flex;
        justify-content: space-between;
    }

    .search-container {
        width: 300px;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        right: 20px;
        top: 20px;
    }

    .search-container input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    .search-list {
        max-height: 200px;
        overflow-y: auto;
    }

    .search-list li {
        padding: 10px;
        border-bottom: 1px solid #ddd;
        cursor: pointer;
        transition: 0.3s;
    }

    .search-list li:hover {
        background: #007bff;
        color: white;
    }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h3>Dashboard</h3>
        <a href="#" class="active"><i class="fas fa-home"></i> Overview</a>
        <a href="#"><i class="fas fa-folder"></i> Projects</a>
        <a href="#"><i class="fas fa-sync"></i> Recent Updates</a>
        <a href="#"><i class="fas fa-book"></i> Read Blogs</a>
        <a href="#"><i class="fas fa-edit"></i> Create Blog</a>
        <a href="#"><i class="fas fa-search"></i> Search</a>
        <a href="#"><i class="fas fa-bookmark"></i> Bookmarks</a>
        <a href="#"><i class="fas fa-user"></i> Account</a>
        <a href="#"><i class="fas fa-graduation-cap"></i> Mentor Support</a>
        <a href="#"><i class="fas fa-cog"></i> Settings</a>
    </div>

    <!-- Main Content -->
    <div class="content">
        <h1>Welcome to the Dashboard</h1><br>
        <p>This is the main content area. The sidebar remains static on the left, and the search list stays on the
            right.</p>
        <p>there are so many thing which you have to learn from me so take care of oyu r self have a cnied</p>
    </div>




</body>

</html>