<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electric Scooter Rental System - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom styles -->
    <style>
        body {
            font-size: .875rem;
        }
        
        .feather {
            width: 16px;
            height: 16px;
            vertical-align: text-bottom;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        
        @media (max-width: 767.98px) {
            .sidebar {
                top: 5rem;
            }
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        
        .sidebar .nav-link.active {
            color: #2470dc;
        }
        
        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
        }
        
        /* Navbar */
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }
        
        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">Electric Scooter Rental System</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="w-100"></div>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="#" id="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" aria-current="page" href="#" data-page="dashboard">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-page="users">
                                <i class="fas fa-users"></i>
                                User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-page="scooters">
                                <i class="fas fa-motorcycle"></i>
                                Scooter Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-page="orders">
                                <i class="fas fa-clipboard-list"></i>
                                Order Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-page="feedbacks">
                                <i class="fas fa-comment-alt"></i>
                                Feedback Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-page="stats">
                                <i class="fas fa-chart-bar"></i>
                                Statistics
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div id="content-area">
                    <!-- Content will be loaded via AJAX -->
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Dashboard</h1>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-3 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Users</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-users">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Scooters</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-scooters">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-motorcycle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Total Orders</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-orders">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Feedbacks</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-feedbacks">0</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-comment-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="recent-orders-table" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>User</th>
                                                    <th>Scooter</th>
                                                    <th>Status</th>
                                                    <th>Start Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-6">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Recent Feedbacks</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="recent-feedbacks-table" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Feedback ID</th>
                                                    <th>User</th>
                                                    <th>Status</th>
                                                    <th>Content</th>
                                                    <th>Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Data will be loaded via AJAX -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        $(document).ready(function() {
            // Check if already logged in
            const token = localStorage.getItem('admin_token');
            if (!token) {
                window.location.href = 'index.php';
                return;
            }
            
            // Load dashboard data
            loadDashboardData();
            
            // Navigation menu click event
            $('.nav-link').on('click', function(e) {
                e.preventDefault();
                
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
                
                const page = $(this).data('page');
                
                if (page === 'dashboard') {
                    loadDashboardContent();
                } else {
                    loadPage(page);
                }
            });
            
            // Logout
            $('#logout-btn').on('click', function(e) {
                e.preventDefault();
                
                localStorage.removeItem('admin_token');
                window.location.href = 'index.php';
            });
        });
        
        // Load dashboard data
        function loadDashboardData() {
            const token = localStorage.getItem('admin_token');
            
            // Get total users
            $.ajax({
                url: '../api/admin/users',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#total-users').text(response.data.length);
                    }
                }
            });
            
            // Get total scooters
            $.ajax({
                url: '../api/scooters',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#total-scooters').text(response.data.length);
                    }
                }
            });
            
            // Get total orders and recent orders
            $.ajax({
                url: '../api/admin/orders',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#total-orders').text(response.data.length);
                        
                        // Display recent 5 orders
                        const recentOrders = response.data.slice(0, 5);
                        let tableHtml = '';
                        
                        recentOrders.forEach(order => {
                            let statusClass = '';
                            let statusText = '';
                            
                            switch (order.status) {
                                case 'pending':
                                    statusClass = 'warning';
                                    statusText = 'Pending';
                                    break;
                                case 'ongoing':
                                    statusClass = 'primary';
                                    statusText = 'Ongoing';
                                    break;
                                case 'completed':
                                    statusClass = 'success';
                                    statusText = 'Completed';
                                    break;
                                case 'cancelled':
                                    statusClass = 'danger';
                                    statusText = 'Cancelled';
                                    break;
                                default:
                                    statusClass = 'secondary';
                                    statusText = order.status;
                            }
                            
                            tableHtml += `
                                <tr>
                                    <td>${order.id}</td>
                                    <td>${order.user_name}</td>
                                    <td>${order.scooter_code}</td>
                                    <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                                    <td>${order.start_time}</td>
                                </tr>
                            `;
                        });
                        
                        $('#recent-orders-table tbody').html(tableHtml);
                    }
                }
            });
            
            // Get total feedbacks and recent feedbacks
            $.ajax({
                url: '../api/admin/feedbacks',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#total-feedbacks').text(response.data.length);
                        
                        // Display recent 5 feedbacks
                        const recentFeedbacks = response.data.slice(0, 5);
                        let tableHtml = '';
                        
                        recentFeedbacks.forEach(feedback => {
                            // Truncate long content
                            const content = feedback.content.length > 30 ? 
                                feedback.content.substring(0, 30) + '...' : 
                                feedback.content;
                            
                            tableHtml += `
                                <tr>
                                    <td>${feedback.id}</td>
                                    <td>${feedback.username}</td>
                                    <td>${feedback.status}/5</td>
                                    <td>${content}</td>
                                    <td>${feedback.created_at}</td>
                                </tr>
                            `;
                        });
                        
                        $('#recent-feedbacks-table tbody').html(tableHtml);
                    }
                }
            });
        }
        
        // Load dashboard content
        function loadDashboardContent() {
            // Reload dashboard data
            loadDashboardData();
            
            // Display dashboard content (already defined in HTML)
            $('#content-area').show();
        }
        
        // Load page
        function loadPage(page) {
            $.ajax({
                url: page + '.php',
                type: 'GET',
                success: function(response) {
                    $('#content-area').html(response);
                    
                    // Initialize page-specific JavaScript
                    if (typeof window[page + 'Init'] === 'function') {
                        window[page + 'Init']();
                    }
                },
                error: function() {
                    $('#content-area').html('<div class="alert alert-danger">Failed to load page</div>');
                }
            });
        }
    </script>
</body>
</html>
