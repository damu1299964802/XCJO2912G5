<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Scooter Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" id="add-scooter-btn">
            <i class="fas fa-plus"></i> Add Scooter
        </button>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Scooter List</h6>
        <div class="dropdown no-arrow">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="scooterFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                Filter
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="scooterFilterDropdown">
                <li><a class="dropdown-item filter-scooter" data-status="all" href="#">All Status</a></li>
                <li><a class="dropdown-item filter-scooter" data-status="available" href="#">Available</a></li>
                <li><a class="dropdown-item filter-scooter" data-status="in_use" href="#">In Use</a></li>
                <li><a class="dropdown-item filter-scooter" data-status="maintenance" href="#">Maintenance</a></li>
                <li><a class="dropdown-item filter-scooter" data-status="offline" href="#">Offline</a></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="scooters-table" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Scooter Code</th>
                        <th>Status</th>
                        <th>Battery Level</th>
                        <th>Location</th>
                        <th>Last Maintenance</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Scooter data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scooter Details Modal -->
<div class="modal fade" id="scooter-modal" tabindex="-1" aria-labelledby="scooter-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scooter-modal-label">Scooter Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="scooter-form">
                    <input type="hidden" id="scooter-id">
                    <div class="mb-3">
                        <label for="scooter-code" class="form-label">Scooter Code</label>
                        <input type="text" class="form-control" id="scooter-code">
                    </div>
                    <div class="mb-3">
                        <label for="scooter-status" class="form-label">Status</label>
                        <select class="form-select" id="scooter-status">
                            <option value="available">Available</option>
                            <option value="in_use">In Use</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="offline">Offline</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="battery-level" class="form-label">Battery Level (%)</label>
                        <input type="number" class="form-control" id="battery-level" min="0" max="100">
                    </div>
                    <div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location">
                    </div>
                    <div class="mb-3">
                        <label for="last-maintenance" class="form-label">Last Maintenance Time</label>
                        <input type="date" class="form-control" id="last-maintenance">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" id="delete-scooter">Delete</button>
                <button type="button" class="btn btn-primary" id="save-scooter">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Load scooter data
        loadScooters();
        
        // Add scooter button click event
        $('#add-scooter-btn').on('click', function() {
            openScooterModal();
        });
        
        // Save scooter information
        $('#save-scooter').on('click', function() {
            saveScooter();
        });
        
        // Delete scooter
        $('#delete-scooter').on('click', function() {
            deleteScooter();
        });
        
        // Filter scooters
        $('.filter-scooter').on('click', function() {
            const status = $(this).data('status');
            loadScooters(status);
        });
    });
    
    // Load scooter data
    function loadScooters(status = 'all') {
        const token = localStorage.getItem('admin_token');
        let url = '../api/scooters';
        
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
                    const scooters = response.data;
                    let tableHtml = '';
                    
                    scooters.forEach(function(scooter) {
                        let statusClass = '';
                        let statusText = '';
                        
                        switch (scooter.status) {
                            case 'available':
                                statusClass = 'success';
                                statusText = 'Available';
                                break;
                            case 'in_use':
                                statusClass = 'primary';
                                statusText = 'In Use';
                                break;
                            case 'maintenance':
                                statusClass = 'warning';
                                statusText = 'Maintenance';
                                break;
                            case 'offline':
                                statusClass = 'danger';
                                statusText = 'Offline';
                                break;
                        }
                        
                        tableHtml += `
                            <tr>
                                <td>${scooter.id}</td>
                                <td>${scooter.scooter_code}</td>
                                <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: ${scooter.battery_level}%" 
                                            aria-valuenow="${scooter.battery_level}" aria-valuemin="0" aria-valuemax="100">
                                            ${scooter.battery_level}%
                                        </div>
                                    </div>
                                </td>
                                <td>${scooter.location || 'Unknown'}</td>
                                <td>${scooter.last_maintenance || 'Not Recorded'}</td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-scooter" data-id="${scooter.id}">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    $('#scooters-table tbody').html(tableHtml);
                    
                    // Bind edit button events
                    $('.edit-scooter').on('click', function() {
                        const scooterId = $(this).data('id');
                        openScooterModal(scooterId);
                    });
                }
            },
            error: function(xhr) {
                alert('Failed to load scooter data: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Open scooter details modal
    function openScooterModal(scooterId = null) {
        // Clear form
        $('#scooter-form')[0].reset();
        $('#scooter-id').val('');
        
        if (scooterId) {
            // Edit existing scooter
            const token = localStorage.getItem('admin_token');
            
            $.ajax({
                url: `../api/scooters/${scooterId}`,
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.status === 'success') {
                        const scooter = response.data;
                        
                        $('#scooter-id').val(scooter.id);
                        $('#scooter-code').val(scooter.scooter_code);
                        $('#scooter-status').val(scooter.status);
                        $('#battery-level').val(scooter.battery_level);
                        $('#location').val(scooter.location);
                        $('#last-maintenance').val(scooter.last_maintenance);
                        
                        $('#scooter-modal-label').text('Edit Scooter');
                        $('#delete-scooter').show();
                        $('#scooter-modal').modal('show');
                    }
                },
                error: function(xhr) {
                    alert('Failed to get scooter details: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        } else {
            // Add new scooter
            $('#scooter-modal-label').text('Add New Scooter');
            $('#delete-scooter').hide();
            $('#scooter-modal').modal('show');
        }
    }
    
    // Save scooter information
    function saveScooter() {
        const token = localStorage.getItem('admin_token');
        const scooterId = $('#scooter-id').val();
        
        const scooterData = {
            scooter_code: $('#scooter-code').val(),
            status: $('#scooter-status').val(),
            battery_level: $('#battery-level').val(),
            location: $('#location').val(),
            last_maintenance: $('#last-maintenance').val()
        };
        
        let url = '../api/scooters';
        let method = 'POST';
        
        if (scooterId) {
            // Update existing scooter
            url = `../api/scooters/${scooterId}`;
            method = 'PUT';
            scooterData.id = scooterId;
        }
        
        $.ajax({
            url: url,
            type: method,
            headers: {
                'Authorization': 'Bearer ' + token
            },
            contentType: 'application/json',
            data: JSON.stringify(scooterData),
            success: function(response) {
                if (response.status === 'success') {
                    $('#scooter-modal').modal('hide');
                    loadScooters();
                    alert(scooterId ? 'Scooter updated successfully' : 'Scooter added successfully');
                }
            },
            error: function(xhr) {
                alert('Failed to save scooter: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Delete scooter
    function deleteScooter() {
        const token = localStorage.getItem('admin_token');
        const scooterId = $('#scooter-id').val();
        
        if (!scooterId) return;
        
        if (confirm('Are you sure you want to delete this scooter?')) {
            $.ajax({
                url: `../api/scooters/${scooterId}`,
                type: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#scooter-modal').modal('hide');
                        loadScooters();
                        alert('Scooter deleted successfully');
                    }
                },
                error: function(xhr) {
                    alert('Failed to delete scooter: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }
    }
    
    // Initialize the scooter management page
    function scootersInit() {
        // The page has been loaded via AJAX, this function will run after loading
        loadScooters();
    }
</script>
