<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electric Scooter Rental System - Admin Login</title>
    <link href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.2.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo h1 {
            font-size: 24px;
            color: #333;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        .alert {
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <h1>Electric Scooter Rental System</h1>
            <p class="text-muted">Admin Login</p>
        </div>
        
        <div class="alert alert-danger" id="error-message" role="alert"></div>
        
        <form id="login-form">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.bootcdn.net/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.2.3/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Check if already logged in
            const token = localStorage.getItem('admin_token');
            if (token) {
                window.location.href = '../admin/dashboard.php';
            }
            
            // Login form submission
            $('#login-form').on('submit', function(e) {
                e.preventDefault();
                
                const username = $('#username').val();
                const password = $('#password').val();
                
                // Send login request
                $.ajax({
                    url: '../api/admin/login',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        username: username,
                        password: password
                    }),
                    success: function(response) {
                        if (response.status === 'success') {
                            // Save token and admin info
                            localStorage.setItem('admin_token', response.data.token);
                            localStorage.setItem('admin_info', JSON.stringify(response.data.admin));
                            
                            // Redirect to dashboard
                            window.location.href = '../admin/dashboard.php';
                        } else {
                            $('#error-message').text(response.message).show();
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Login failed, please try again later';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        $('#error-message').text(errorMessage).show();
                    }
                });
            });
        });
    </script>
</body>
</html>
