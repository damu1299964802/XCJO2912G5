
<body>
    <div class="container-fluid">
        <div class="row">

            
            <!-- Main Content Area -->
            <div class="col-md-10 content">
                <h2>Statistics</h2>
                
                <!-- Time Period Selection -->
                <div class="btn-group mb-4">
                    <button type="button" class="btn btn-outline-primary period-btn" data-period="day">Day</button>
                    <button type="button" class="btn btn-outline-primary period-btn" data-period="week">Week</button>
                    <button type="button" class="btn btn-outline-primary period-btn active" data-period="month">Month</button>
                </div>
                
                <!-- Order Statistics -->
                <h3>Order Statistics</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Order Count Trend
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="ordersChart"></canvas>
                                </div>
        </div>
    </div>
</div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Order Status Distribution
            </div>
            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="orderStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Rental Duration Distribution
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="rentalDurationChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Weekly Usage Frequency
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="weeklyUsageChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Scooter Statistics -->
                <h3 class="mt-4">Scooter Statistics</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Scooter Status Distribution
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="scooterStatusChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Battery Level Distribution
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="batteryLevelChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Hourly Usage Frequency
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="hourlyUsageChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Popular Rental Locations
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="popularLocationsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Most Used Scooters -->
                <div class="card mt-4">
                    <div class="card-header">
                        Most Frequently Used Scooters
                    </div>
                    <div class="card-body">
                <div class="table-responsive">
                            <table class="table table-striped">
                        <thead>
                            <tr>
                                        <th>Scooter Code</th>
                                        <th>Status</th>
                                        <th>Battery</th>
                                        <th>Usage Count</th>
                                        <th>Total Usage Time (hours)</th>
                            </tr>
                        </thead>
                                <tbody id="topScootersTable">
                                    <tr>
                                        <td colspan="5" class="text-center">Loading...</td>
                                    </tr>
                        </tbody>
                    </table>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>

                <!-- Revenue Statistics -->
                <h3 class="mt-4">Revenue Statistics</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Rental Duration Revenue (Last 7 Days)
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="rentalOptionsRevenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Daily Revenue (Last 7 Days)
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="dailyRevenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                Weekly Revenue Trend
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="weeklyRevenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
        // Chart instances
        let ordersChart;
        let orderStatusChart;
        let rentalDurationChart;
        let weeklyUsageChart;
        let scooterStatusChart;
        let batteryLevelChart;
        let hourlyUsageChart;
        let popularLocationsChart;
        
        // Revenue charts
        let rentalOptionsRevenueChart;
        let dailyRevenueChart;
        let weeklyRevenueChart;
        
        // Current time period
        let currentPeriod = 'month';
        
        // Load statistics data when page loads
        $(document).ready(function() {
            // Load initial data
            loadStats(currentPeriod);
            loadRevenueStats();
            
            // Time period selection button click event
            $('.period-btn').click(function() {
                $('.period-btn').removeClass('active');
                $(this).addClass('active');
                currentPeriod = $(this).data('period');
                loadStats(currentPeriod);
            });
        });
        
        // Load statistics data
        function loadStats(period) {
            // Get order statistics
        $.ajax({
                url: '../api/stats/orders',
                method: 'GET',
                data: { period: period },
            success: function(response) {
                if (response.status === 'success') {
                        renderOrdersChart(response.data.time_distribution);
                        renderOrderStatusChart(response.data.status_distribution);
                        renderRentalDurationChart(response.data.duration_distribution);
                        renderWeeklyUsageChart(response.data.weekly_distribution);
                    } else {
                        alert('Failed to load order statistics: ' + response.message);
                }
            },
            error: function(xhr) {
                    alert('Error loading order statistics: ' + xhr.statusText);
            }
        });
        
            // Get scooter statistics
        $.ajax({
                url: '../api/stats/scooters',
                method: 'GET',
                data: { period: period },
            success: function(response) {
                if (response.status === 'success') {
                        renderScooterStatusChart(response.data.status_distribution);
                        renderBatteryLevelChart(response.data.scooter_usage);
                        renderTopScooters(response.data.scooter_usage);
                        renderHourlyUsageChart(response.data.hourly_usage);
                        renderPopularLocationsChart(response.data.popular_locations);
                    } else {
                        alert('Failed to load scooter statistics: ' + response.message);
                }
            },
            error: function(xhr) {
                    alert('Error loading scooter statistics: ' + xhr.statusText);
            }
        });
    }
    
        // Render order count trend chart
    function renderOrdersChart(data) {
            const ctx = document.getElementById('ordersChart').getContext('2d');
        
            // Prepare data
        const labels = data.map(item => item.time_period);
        const values = data.map(item => item.order_count);
        
            // Destroy old chart if exists
        if (ordersChart) {
            ordersChart.destroy();
        }
        
            // Create chart
        ordersChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Order Count',
                    data: values,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        tension: 0.1,
                        fill: true
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
                }
            }
        });
    }
    
        // Render order status distribution chart
    function renderOrderStatusChart(data) {
            const ctx = document.getElementById('orderStatusChart').getContext('2d');
            
            // Prepare data
            const statusLabels = {
                'pending': 'Pending',
                'paid': 'Paid',
                'ongoing': 'Ongoing',
                'completed': 'Completed',
                'cancelled': 'Cancelled'
            };
            
            const labels = Object.keys(data).map(key => statusLabels[key] || key);
            const values = Object.values(data);
            
            // Destroy old chart if exists
        if (orderStatusChart) {
            orderStatusChart.destroy();
        }
        
            // Create chart
        orderStatusChart = new Chart(ctx, {
                type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(153, 102, 255, 0.6)'
                        ],
                        borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                            position: 'right'
                        }
                }
            }
        });
    }
    
        // Render rental duration distribution chart
    function renderRentalDurationChart(data) {
            const ctx = document.getElementById('rentalDurationChart').getContext('2d');
            
            // Prepare data
            const labels = Object.keys(data);
            const values = Object.values(data);
            
            // Destroy old chart if exists
        if (rentalDurationChart) {
            rentalDurationChart.destroy();
        }
        
            // Create chart
        rentalDurationChart = new Chart(ctx, {
                type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                        label: 'Rental Duration (hours)',
                    data: values,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
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
                }
            }
        });
    }
        
        // Render weekly usage frequency chart
        function renderWeeklyUsageChart(data) {
            const ctx = document.getElementById('weeklyUsageChart').getContext('2d');
            
            // Prepare data
            const weekDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const englishDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const values = englishDays.map(day => data[day] || 0);
            
            // Destroy old chart if exists
            if (weeklyUsageChart) {
                weeklyUsageChart.destroy();
            }
            
            // Create chart
            weeklyUsageChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: weekDays,
                    datasets: [{
                        label: 'Weekly Usage Frequency',
                        data: values,
                        backgroundColor: 'rgba(153, 102, 255, 0.6)',
                        borderColor: 'rgba(153, 102, 255, 1)',
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
                    }
                }
            });
        }
        
        // Render scooter status distribution chart
        function renderScooterStatusChart(data) {
            const ctx = document.getElementById('scooterStatusChart').getContext('2d');
            
            // Prepare data
            const statusLabels = {
                'available': 'Available',
                'in_use': 'In Use',
                'maintenance': 'Maintenance',
                'charging': 'Charging',
                'offline': 'Offline'
            };
            
            const labels = Object.keys(data).map(key => statusLabels[key] || key);
            const values = Object.values(data);
            
            // Destroy old chart if exists
        if (scooterStatusChart) {
            scooterStatusChart.destroy();
        }
        
            // Create chart
        scooterStatusChart = new Chart(ctx, {
                type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(54, 162, 235, 0.6)',
                            'rgba(255, 206, 86, 0.6)',
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(153, 102, 255, 0.6)'
                        ],
                        borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                            position: 'right'
                        }
                }
            }
        });
    }
    
        // Render battery level distribution chart
    function renderBatteryLevelChart(data) {
            const ctx = document.getElementById('batteryLevelChart').getContext('2d');
            
            // Prepare data
            // Count scooters in each battery range
            const batteryRanges = {
                '0-20%': 0,
                '21-40%': 0,
                '41-60%': 0,
                '61-80%': 0,
                '81-100%': 0
        };
        
        data.forEach(scooter => {
                const level = scooter.battery_level;
                if (level <= 20) {
                    batteryRanges['0-20%']++;
                } else if (level <= 40) {
                    batteryRanges['21-40%']++;
                } else if (level <= 60) {
                    batteryRanges['41-60%']++;
                } else if (level <= 80) {
                    batteryRanges['61-80%']++;
            } else {
                    batteryRanges['81-100%']++;
            }
        });
        
            const labels = Object.keys(batteryRanges);
            const values = Object.values(batteryRanges);
        
            // Destroy old chart if exists
        if (batteryLevelChart) {
            batteryLevelChart.destroy();
        }
        
            // Create chart
        batteryLevelChart = new Chart(ctx, {
                type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.6)',
                            'rgba(255, 159, 64, 0.6)',
                            'rgba(255, 205, 86, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(54, 162, 235, 0.6)'
                        ],
                        borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }
        
        // Render hourly usage frequency chart
        function renderHourlyUsageChart(data) {
            const ctx = document.getElementById('hourlyUsageChart').getContext('2d');
            
            // Prepare data
            const labels = Array.from({length: 24}, (_, i) => `${i}h`);
            const values = Array.isArray(data) ? data : Array(24).fill(0);
            
            // Destroy old chart if exists
            if (hourlyUsageChart) {
                hourlyUsageChart.destroy();
            }
            
            // Create chart
            hourlyUsageChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Order Count',
                        data: values,
                        backgroundColor: 'rgba(255, 159, 64, 0.2)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1,
                        tension: 0.1,
                        fill: true
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
                }
            }
        });
    }
        
        // Render popular locations chart
        function renderPopularLocationsChart(data) {
            const ctx = document.getElementById('popularLocationsChart').getContext('2d');
            
            if (!data || !Array.isArray(data)) {
                console.error('Popular locations data is not available or not an array');
                return;
            }
            
            // Prepare data
            const labels = data.map(item => item.location || 'Unknown');
            const values = data.map(item => item.order_count);
            
            // Destroy old chart if exists
            if (popularLocationsChart) {
                popularLocationsChart.destroy();
            }
            
            // Create chart
            popularLocationsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Order Count',
                        data: values,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
        
        // Render top scooters table
        function renderTopScooters(data) {
            const tableBody = $('#topScootersTable');
            tableBody.empty();
            
            // Sort by usage count (descending)
            data.sort((a, b) => b.usage_count - a.usage_count);
            
            // Show only top 10
            const topScooters = data.slice(0, 10);
            
            // Status mapping
            const statusMap = {
                'available': 'Available',
                'in_use': 'In Use',
                'maintenance': 'Maintenance',
                'charging': 'Charging',
                'offline': 'Offline'
            };
            
            // Add table rows
            topScooters.forEach(scooter => {
                const statusText = statusMap[scooter.status] || scooter.status;
                const row = `
                <tr>
                    <td>${scooter.scooter_code}</td>
                        <td>${statusText}</td>
                        <td>${scooter.battery_level}%</td>
                        <td>${scooter.usage_count}</td>
                        <td>${scooter.total_usage_time}</td>
                </tr>
            `;
                tableBody.append(row);
        });
        
            // Show message if no data
            if (topScooters.length === 0) {
                tableBody.append('<tr><td colspan="5" class="text-center">No data available</td></tr>');
            }
    }

    // Revenue Charts
    function loadRevenueStats() {
        // Load rental options revenue
        $.ajax({
            url: '../api/admin/stats/revenue/rental-options',
            method: 'GET',
            success: function(response) {
                console.log('Rental options response:', response);
                if (response.status === 'success') {
                    renderRentalOptionsRevenueChart(response);
                } else {
                    console.error('Failed to load rental options revenue:', response.message);
                }
            },
            error: function(xhr) {
                console.error('Error loading rental options revenue:', xhr.statusText);
            }
        });

        // Load daily revenue
        $.ajax({
            url: '../api/admin/stats/revenue/daily',
            method: 'GET',
            success: function(response) {
                console.log('Daily revenue response:', response);
                if (response.status === 'success') {
                    renderDailyRevenueChart(response);
                } else {
                    console.error('Failed to load daily revenue:', response.message);
                }
            },
            error: function(xhr) {
                console.error('Error loading daily revenue:', xhr.statusText);
            }
        });

        // Load weekly revenue
        $.ajax({
            url: '../api/admin/stats/revenue/weekly',
            method: 'GET',
            success: function(response) {
                console.log('Weekly revenue response:', response);
                if (response.status === 'success') {
                    renderWeeklyRevenueChart(response);
                } else {
                    console.error('Failed to load weekly revenue:', response.message);
                }
            },
            error: function(xhr) {
                console.error('Error loading weekly revenue:', xhr.statusText);
            }
        });
    }

    function renderRentalOptionsRevenueChart(response) {
        const ctx = document.getElementById('rentalOptionsRevenueChart').getContext('2d');
        
        // 处理嵌套的`data`字段
        const data = response.data.data || [];
        
        const labels = data.map(item => item.duration_category);
        const revenue = data.map(item => parseFloat(item.total_revenue));
        const orderCount = data.map(item => parseInt(item.order_count));
        
        if (rentalOptionsRevenueChart) {
            rentalOptionsRevenueChart.destroy();
        }
        
        rentalOptionsRevenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue (€)',
                        data: revenue,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Order Count',
                        data: orderCount,
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (€)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Order Count'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    function renderDailyRevenueChart(response) {
        const ctx = document.getElementById('dailyRevenueChart').getContext('2d');
        
        // 处理嵌套的`data`字段
        const data = response.data.data || [];
        
        const labels = data.map(item => item.date);
        const revenue = data.map(item => parseFloat(item.total_revenue));
        const avgDuration = data.map(item => parseFloat(item.avg_duration));
        
        if (dailyRevenueChart) {
            dailyRevenueChart.destroy();
        }
        
        dailyRevenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue (€)',
                        data: revenue,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        yAxisID: 'y',
                        fill: true
                    },
                    {
                        label: 'Avg Duration (hours)',
                        data: avgDuration,
                        backgroundColor: 'rgba(153, 102, 255, 0.6)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (€)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Avg Duration (hours)'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }

    function renderWeeklyRevenueChart(response) {
        const ctx = document.getElementById('weeklyRevenueChart').getContext('2d');
        
        // 处理嵌套的`data`字段
        const data = response.data.data || [];
        
        const labels = data.map(item => 'Week ' + item.week);
        const revenue = data.map(item => parseFloat(item.total_revenue));
        const orderCount = data.map(item => parseInt(item.order_count));
        
        if (weeklyRevenueChart) {
            weeklyRevenueChart.destroy();
        }
        
        weeklyRevenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Revenue (€)',
                        data: revenue,
                        backgroundColor: 'rgba(255, 159, 64, 0.6)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Order Count',
                        data: orderCount,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (€)'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Order Count'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    }
</script>
</body>
</html>
