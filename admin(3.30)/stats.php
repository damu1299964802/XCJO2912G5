<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Statistics</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary time-period" data-period="day">Day</button>
            <button type="button" class="btn btn-sm btn-outline-secondary time-period" data-period="week">Week</button>
            <button type="button" class="btn btn-sm btn-outline-secondary time-period active" data-period="month">Month</button>
        </div>
    </div>
</div>

<div class="row">
    <!-- Order Statistics -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Order Statistics</h6>
            </div>
            <div class="card-body">
                <div class="chart-container" style="position: relative; height:300px;">
                    <canvas id="orders-chart"></canvas>
                </div>
                <hr>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Order Status Distribution</h6>
                        <div class="chart-container" style="position: relative; height:200px;">
                            <canvas id="order-status-chart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Rental Duration Distribution</h6>
                        <div class="chart-container" style="position: relative; height:200px;">
                            <canvas id="rental-duration-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scooter Statistics -->
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Scooter Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Scooter Status Distribution</h6>
                        <div class="chart-container" style="position: relative; height:200px;">
                            <canvas id="scooter-status-chart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="font-weight-bold">Battery Level Distribution</h6>
                        <div class="chart-container" style="position: relative; height:200px;">
                            <canvas id="battery-level-chart"></canvas>
                        </div>
                    </div>
                </div>
                <hr>
                <h6 class="font-weight-bold mt-4">Most Frequently Used Scooters</h6>
                <div class="table-responsive">
                    <table class="table table-bordered" id="top-scooters-table" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Scooter Code</th>
                                <th>Usage Count</th>
                                <th>Total Usage Time</th>
                                <th>Current Status</th>
                                <th>Battery Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Scooter data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Chart objects
    let ordersChart = null;
    let orderStatusChart = null;
    let rentalDurationChart = null;
    let scooterStatusChart = null;
    let batteryLevelChart = null;
    
    $(document).ready(function() {
        // Load monthly data by default
        loadStats('month');
        
        // Time period switch
        $('.time-period').on('click', function() {
            $('.time-period').removeClass('active');
            $(this).addClass('active');
            
            const period = $(this).data('period');
            loadStats(period);
        });
    });
    
    // Load statistics data
    function loadStats(period) {
        const token = localStorage.getItem('admin_token');
        
        // Load order statistics
        $.ajax({
            url: `../api/admin/stats/orders?period=${period}`,
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.status === 'success') {
                    const data = response.data;
                    
                    // Order time distribution chart
                    renderOrdersChart(data.time_distribution);
                    
                    // Order status distribution chart
                    renderOrderStatusChart(data.status_distribution);
                    
                    // Rental duration distribution chart
                    renderRentalDurationChart(data.duration_distribution);
                }
            },
            error: function(xhr) {
                alert('Failed to load order statistics data: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
        
        // Load scooter statistics
        $.ajax({
            url: '../api/admin/stats/scooters',
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.status === 'success') {
                    const data = response.data;
                    
                    // Scooter status distribution chart
                    renderScooterStatusChart(data.status_distribution);
                    
                    // Battery level distribution chart
                    renderBatteryLevelChart(data.scooter_usage);
                    
                    // Most frequently used scooters
                    renderTopScooters(data.scooter_usage);
                }
            },
            error: function(xhr) {
                alert('Failed to load scooter statistics data: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Render order time distribution chart
    function renderOrdersChart(data) {
        const ctx = document.getElementById('orders-chart').getContext('2d');
        
        // Sort by time
        data.sort((a, b) => a.time_period.localeCompare(b.time_period));
        
        const labels = data.map(item => item.time_period);
        const values = data.map(item => item.order_count);
        
        // Destroy existing chart if it exists
        if (ordersChart) {
            ordersChart.destroy();
        }
        
        // Create new chart
        ordersChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Order Count',
                    data: values,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        backgroundColor: 'rgb(255, 255, 255)',
                        bodyColor: '#858796',
                        titleMarginBottom: 10,
                        titleColor: '#6e707e',
                        titleFont: {
                            size: 14
                        },
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: false
                    }
                }
            }
        });
    }
    
    // Render order status distribution chart
    function renderOrderStatusChart(data) {
        const ctx = document.getElementById('order-status-chart').getContext('2d');
        
        // Map status names for display
        const statusMap = {
            'pending': 'Pending',
            'ongoing': 'Ongoing',
            'completed': 'Completed',
            'cancelled': 'Cancelled'
        };
        
        const statusColors = {
            'pending': '#f6c23e',
            'ongoing': '#4e73df',
            'completed': '#1cc88a',
            'cancelled': '#e74a3b'
        };
        
        const labels = Object.keys(data).map(key => statusMap[key] || key);
        const values = Object.values(data);
        const backgroundColor = Object.keys(data).map(key => statusColors[key] || '#36b9cc');
        
        // Destroy existing chart if it exists
        if (orderStatusChart) {
            orderStatusChart.destroy();
        }
        
        // Create new chart
        orderStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: backgroundColor,
                    hoverBackgroundColor: backgroundColor,
                    hoverBorderColor: 'rgba(234, 236, 244, 1)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        backgroundColor: 'rgb(255, 255, 255)',
                        bodyColor: '#858796',
                        bodyFont: {
                            size: 14
                        },
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: false
                    }
                }
            }
        });
    }
    
    // Render rental duration distribution chart
    function renderRentalDurationChart(data) {
        const ctx = document.getElementById('rental-duration-chart').getContext('2d');
        
        // Transform data
        const labels = Object.keys(data).map(key => `${key} hours`);
        const values = Object.values(data);
        
        // Destroy existing chart if it exists
        if (rentalDurationChart) {
            rentalDurationChart.destroy();
        }
        
        // Create new chart
        rentalDurationChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Order Count',
                    data: values,
                    backgroundColor: 'rgba(54, 185, 204, 0.8)',
                    borderColor: 'rgba(54, 185, 204, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgb(255, 255, 255)',
                        bodyColor: '#858796',
                        titleMarginBottom: 10,
                        titleColor: '#6e707e',
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 14
                        },
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: false
                    }
                }
            }
        });
    }
    
    // Render scooter status distribution chart
    function renderScooterStatusChart(data) {
        const ctx = document.getElementById('scooter-status-chart').getContext('2d');
        
        // Map status names for display
        const statusMap = {
            'available': 'Available',
            'in_use': 'In Use',
            'maintenance': 'Maintenance',
            'offline': 'Offline'
        };
        
        const statusColors = {
            'available': '#1cc88a',
            'in_use': '#4e73df',
            'maintenance': '#f6c23e',
            'offline': '#e74a3b'
        };
        
        const labels = Object.keys(data).map(key => statusMap[key] || key);
        const values = Object.values(data);
        const backgroundColor = Object.keys(data).map(key => statusColors[key] || '#36b9cc');
        
        // Destroy existing chart if it exists
        if (scooterStatusChart) {
            scooterStatusChart.destroy();
        }
        
        // Create new chart
        scooterStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: backgroundColor,
                    hoverBackgroundColor: backgroundColor,
                    hoverBorderColor: 'rgba(234, 236, 244, 1)',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        backgroundColor: 'rgb(255, 255, 255)',
                        bodyColor: '#858796',
                        bodyFont: {
                            size: 14
                        },
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: false
                    }
                }
            }
        });
    }
    
    // Render battery level distribution chart
    function renderBatteryLevelChart(data) {
        const ctx = document.getElementById('battery-level-chart').getContext('2d');
        
        // Aggregate scooters by battery level ranges
        const batteryRanges = {
            '0-20%': 0,
            '21-40%': 0,
            '41-60%': 0,
            '61-80%': 0,
            '81-100%': 0
        };
        
        data.forEach(scooter => {
            const batteryLevel = scooter.battery_level;
            if (batteryLevel <= 20) {
                batteryRanges['0-20%']++;
            } else if (batteryLevel <= 40) {
                batteryRanges['21-40%']++;
            } else if (batteryLevel <= 60) {
                batteryRanges['41-60%']++;
            } else if (batteryLevel <= 80) {
                batteryRanges['61-80%']++;
            } else {
                batteryRanges['81-100%']++;
            }
        });
        
        const labels = Object.keys(batteryRanges);
        const values = Object.values(batteryRanges);
        
        // Gradient colors based on battery level
        const backgroundColor = [
            '#e74a3b',
            '#f6c23e',
            '#f6c23e',
            '#1cc88a',
            '#1cc88a'
        ];
        
        // Destroy existing chart if it exists
        if (batteryLevelChart) {
            batteryLevelChart.destroy();
        }
        
        // Create new chart
        batteryLevelChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Scooter Count',
                    data: values,
                    backgroundColor: backgroundColor,
                    borderColor: backgroundColor,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgb(255, 255, 255)',
                        bodyColor: '#858796',
                        titleMarginBottom: 10,
                        titleColor: '#6e707e',
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 14
                        },
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        padding: 15,
                        displayColors: false
                    }
                }
            }
        });
    }
    
    // Render top scooters table
    function renderTopScooters(data) {
        // Sort by usage count (descending)
        data.sort((a, b) => b.usage_count - a.usage_count);
        
        // Get top 5 scooters
        const topScooters = data.slice(0, 5);
        
        // Map status names for display
        const statusMap = {
            'available': 'Available',
            'in_use': 'In Use',
            'maintenance': 'Maintenance',
            'offline': 'Offline'
        };
        
        const statusClassMap = {
            'available': 'success',
            'in_use': 'primary',
            'maintenance': 'warning',
            'offline': 'danger'
        };
        
        let tableHtml = '';
        
        topScooters.forEach(scooter => {
            const statusText = statusMap[scooter.status] || scooter.status;
            const statusClass = statusClassMap[scooter.status] || 'secondary';
            
            tableHtml += `
                <tr>
                    <td>${scooter.scooter_code}</td>
                    <td>${scooter.usage_count}</td>
                    <td>${scooter.total_usage_time} hours</td>
                    <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                    <td>
                        <div class="progress">
                            <div class="progress-bar bg-${scooter.battery_level <= 20 ? 'danger' : scooter.battery_level <= 60 ? 'warning' : 'success'}" 
                                role="progressbar" style="width: ${scooter.battery_level}%" 
                                aria-valuenow="${scooter.battery_level}" aria-valuemin="0" aria-valuemax="100">
                                ${scooter.battery_level}%
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        $('#top-scooters-table tbody').html(tableHtml);
    }
    
    // Initialize the statistics page
    function statsInit() {
        // The page has been loaded via AJAX, this function will run after loading
        loadStats('month');
    }
</script>
