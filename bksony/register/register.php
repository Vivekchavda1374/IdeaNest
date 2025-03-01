<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background: url('ict4.png') no-repeat center center/cover;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .register-container {
        display: flex;
        justify-content: flex-start;
        align-items: center;
        width: 80%;
        max-width: 900px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 10px;
        padding: 40px;
        box-shadow: 0px 0px 20px rgba(0, 0, 0, 0.2);
    }

    .register-box {
        flex: 1;
        padding: 20px;
    }

    .register-box h2 {
        color: rgb(0, 0, 0);
        font-weight: bold;
        margin-bottom: 20px;
    }

    .form-control {
        border-radius: 5px;
        margin-bottom: 15px;
        background: rgba(255, 255, 255, 0.5);

    }

    .btn-register {
        background: #00838f;
        color: white;
        width: 100%;
        padding: 10px;
        border-radius: 5px;
        font-weight: bold;
        border: none;
        cursor: pointer;
    }

    .btn-register:hover {
        background: #005f6b;
    }

    .login-link {
        display: block;
        margin-top: 10px;
        color: #000;
        text-decoration: none;
    }
    </style>
</head>

<body>

    <div class="register-container">
        <div class="register-box">
            <h2>REGISTER</h2>
            <p>Please fill in the details to create an account</p>
            <form action="register.php" method="post">
                <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                <input type="email" name="email" class="form-control" placeholder="Email" required>
                <input type="text" name="er_number" class="form-control" placeholder="ER Number" required>
                <input type="text" name="gr_number" class="form-control" placeholder="GR Number" required>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password"
                    required>

                <button type="submit" class="btn btn-register">REGISTER</button>

                <a href="../login/login.php" class="login-link">Already have an account? Login here</a>
            </form>
        </div>
    </div>

</body>

</html>