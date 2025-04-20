<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">User Management</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">User List</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="users-table" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Registration Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- User data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="user-modal" tabindex="-1" aria-labelledby="user-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="user-modal-label">User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="user-form">
                    <input type="hidden" id="user-id">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" id="phone">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status">
                            <option value="active">Active</option>
                            <option value="inactive">Disabled</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save-user">Save</button>
                <button type="button" class="btn btn-warning" id="reset-password">Reset Password</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Load user data
        loadUsers();
        
        // Save user information
        $('#save-user').on('click', function() {
            updateUser();
        });
        
        // Reset password
        $('#reset-password').on('click', function() {
            resetPassword();
        });
    });
    
    // Load user data
    function loadUsers() {
        const token = localStorage.getItem('admin_token');
        
        $.ajax({
            url: '../api/admin/users',
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.status === 'success') {
                    const users = response.data;
                    let tableHtml = '';
                    
                    users.forEach(function(user) {
                        const statusClass = user.status === 'active' ? 'success' : 'danger';
                        const statusText = user.status === 'active' ? 'Active' : 'Disabled';
                        
                        tableHtml += `
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.username}</td>
                                <td>${user.phone}</td>
                                <td>${user.email}</td>
                                <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                                <td>${user.created_at}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-user" data-id="${user.id}">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    $('#users-table tbody').html(tableHtml);
                    
                    // Bind edit button events
                    $('.edit-user').on('click', function() {
                        const userId = $(this).data('id');
                        openUserModal(userId);
                    });
                }
            },
            error: function(xhr) {
                alert('Failed to load user data: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Open user details modal
    function openUserModal(userId) {
        const token = localStorage.getItem('admin_token');
        
        $.ajax({
            url: `../api/admin/users/${userId}`,
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.status === 'success') {
                    const user = response.data;
                    
                    $('#user-id').val(user.id);
                    $('#username').val(user.username);
                    $('#phone').val(user.phone);
                    $('#email').val(user.email);
                    $('#status').val(user.status);
                    
                    $('#user-modal').modal('show');
                }
            },
            error: function(xhr) {
                alert('Failed to get user details: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Update user information
    function updateUser() {
        const token = localStorage.getItem('admin_token');
        const userId = $('#user-id').val();
        
        const userData = {
            id: userId,
            phone: $('#phone').val(),
            email: $('#email').val(),
            status: $('#status').val()
        };
        
        $.ajax({
            url: '../api/admin/users/update',
            type: 'PUT',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            contentType: 'application/json',
            data: JSON.stringify(userData),
            success: function(response) {
                if (response.status === 'success') {
                    $('#user-modal').modal('hide');
                    loadUsers();
                    alert('User information updated successfully');
                }
            },
            error: function(xhr) {
                alert('Failed to update user information: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Reset user password
    function resetPassword() {
        const token = localStorage.getItem('admin_token');
        const userId = $('#user-id').val();
        
        if (confirm('Are you sure you want to reset this user\'s password?')) {
            $.ajax({
                url: '../api/admin/users/reset-password',
                type: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                contentType: 'application/json',
                data: JSON.stringify({
                    id: userId
                }),
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Password reset successfully. New password: ' + response.data.new_password);
                    }
                },
                error: function(xhr) {
                    alert('Failed to reset password: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }
    }
    
    // Initialize the user management page
    function usersInit() {
        // The page has been loaded via AJAX, this function will run after loading
        loadUsers();
    }
</script>
