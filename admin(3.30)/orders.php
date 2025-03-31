<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Order Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary filter-order" data-status="all">All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-order" data-status="pending">Pending</button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-order" data-status="ongoing">Ongoing</button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-order" data-status="completed">Completed</button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-order" data-status="cancelled">Cancelled</button>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Order List</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="orders-table" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Scooter</th>
                        <th>Rental Duration</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Order data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="order-modal" tabindex="-1" aria-labelledby="order-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="order-modal-label">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="order-form">
                    <input type="hidden" id="order-id">
                    <div class="mb-3">
                        <label for="user-info" class="form-label">User Information</label>
                        <input type="text" class="form-control" id="user-info" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="scooter-info" class="form-label">Scooter Information</label>
                        <input type="text" class="form-control" id="scooter-info" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="rental-duration" class="form-label">Rental Duration (hours)</label>
                        <input type="number" class="form-control" id="rental-duration">
                    </div>
                    <div class="mb-3">
                        <label for="start-time" class="form-label">Start Time</label>
                        <input type="datetime-local" class="form-control" id="start-time">
                    </div>
                    <div class="mb-3">
                        <label for="end-time" class="form-label">End Time</label>
                        <input type="datetime-local" class="form-control" id="end-time">
                    </div>
                    <div class="mb-3">
                        <label for="order-status" class="form-label">Status</label>
                        <select class="form-select" id="order-status">
                            <option value="pending">Pending</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="delete-order">Delete</button>
                <button type="button" class="btn btn-primary" id="save-order">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Load order data
        loadOrders();
        
        // Filter orders
        $('.filter-order').on('click', function() {
            $('.filter-order').removeClass('active');
            $(this).addClass('active');
            
            const status = $(this).data('status');
            loadOrders(status);
        });
        
        // Save order information
        $('#save-order').on('click', function() {
            saveOrder();
        });
        
        // Delete order
        $('#delete-order').on('click', function() {
            deleteOrder();
        });
    });
    
    // Load order data
    function loadOrders(status = 'all') {
        const token = localStorage.getItem('admin_token');
        let url = '../api/admin/orders';
        
        if (status !== 'all') {
            url += `?status=${status}`;
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.status === 'success') {
                    const orders = response.data;
                    let tableHtml = '';
                    
                    orders.forEach(function(order) {
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
                        }
                        
                        tableHtml += `
                            <tr>
                                <td>${order.id}</td>
                                <td>${order.username}</td>
                                <td>${order.scooter_code}</td>
                                <td>${order.rental_duration} hours</td>
                                <td>${order.start_time || 'Not Started'}</td>
                                <td>${order.end_time || 'Not Ended'}</td>
                                <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-order" data-id="${order.id}">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    $('#orders-table tbody').html(tableHtml);
                    
                    // Bind edit button events
                    $('.edit-order').on('click', function() {
                        const orderId = $(this).data('id');
                        openOrderModal(orderId);
                    });
                }
            },
            error: function(xhr) {
                alert('Failed to load order data: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Open order details modal
    function openOrderModal(orderId) {
        const token = localStorage.getItem('admin_token');
        
        $.ajax({
            url: `../api/admin/orders/${orderId}`,
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.status === 'success') {
                    const order = response.data;
                    
                    $('#order-id').val(order.id);
                    $('#user-info').val(`${order.username} (ID: ${order.user_id})`);
                    $('#scooter-info').val(`${order.scooter_code} (ID: ${order.scooter_id})`);
                    $('#rental-duration').val(order.rental_duration);
                    
                    if (order.start_time) {
                        $('#start-time').val(formatDatetimeLocal(order.start_time));
                    }
                    
                    if (order.end_time) {
                        $('#end-time').val(formatDatetimeLocal(order.end_time));
                    }
                    
                    $('#order-status').val(order.status);
                    
                    $('#order-modal').modal('show');
                }
            },
            error: function(xhr) {
                alert('Failed to get order details: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Format datetime for datetime-local input
    function formatDatetimeLocal(datetimeStr) {
        const dt = new Date(datetimeStr);
        return dt.getFullYear() + '-' + 
               String(dt.getMonth() + 1).padStart(2, '0') + '-' + 
               String(dt.getDate()).padStart(2, '0') + 'T' + 
               String(dt.getHours()).padStart(2, '0') + ':' + 
               String(dt.getMinutes()).padStart(2, '0');
    }
    
    // Save order information
    function saveOrder() {
        const token = localStorage.getItem('admin_token');
        const orderId = $('#order-id').val();
        
        const orderData = {
            id: orderId,
            rental_duration: $('#rental-duration').val(),
            start_time: $('#start-time').val(),
            end_time: $('#end-time').val(),
            status: $('#order-status').val()
        };
        
        $.ajax({
            url: '../api/admin/orders/update',
            type: 'PUT',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            contentType: 'application/json',
            data: JSON.stringify(orderData),
            success: function(response) {
                if (response.status === 'success') {
                    $('#order-modal').modal('hide');
                    loadOrders();
                    alert('Order updated successfully');
                }
            },
            error: function(xhr) {
                alert('Failed to update order: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Delete order
    function deleteOrder() {
        const token = localStorage.getItem('admin_token');
        const orderId = $('#order-id').val();
        
        if (confirm('Are you sure you want to delete this order?')) {
            $.ajax({
                url: `../api/admin/orders/${orderId}`,
                type: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#order-modal').modal('hide');
                        loadOrders();
                        alert('Order deleted successfully');
                    }
                },
                error: function(xhr) {
                    alert('Failed to delete order: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }
    }
    
    // Initialize the order management page
    function ordersInit() {
        // The page has been loaded via AJAX, this function will run after loading
        loadOrders();
    }
</script>
