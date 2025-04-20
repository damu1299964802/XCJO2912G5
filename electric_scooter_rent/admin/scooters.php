<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Scooter Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-sm btn-primary" id="add-scooter-btn">
            <i class="fas fa-plus"></i> Add Scooter
        </button>
    </div>
</div>

<!-- 引入地图API -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBme4JfbM7f4T5743sDyrGa7HUqW-v54kc&libraries=places"></script>

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
                <li><a class="dropdown-item filter-scooter" data-status="maintenance" href="#">Maintenance</a></li>
                <li><a class="dropdown-item filter-scooter" data-status="disabled" href="#">Disabled</a></li>
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
                        <th>Hourly Rate</th>
                        <th>Location</th>
                        <th>Updated Time</th>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scooter-modal-label">Scooter Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="scooter-form">
                    <input type="hidden" id="scooter-id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="scooter-code" class="form-label">Scooter Code</label>
                            <input type="text" class="form-control" id="scooter-code">
                        </div>
                        <div class="col-md-6">
                            <label for="scooter-status" class="form-label">Status</label>
                            <select class="form-select" id="scooter-status">
                                <option value="available">Available</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="battery-level" class="form-label">Battery Level (%)</label>
                            <input type="number" class="form-control" id="battery-level" min="0" max="100">
                        </div>
                        <div class="col-md-6">
                            <label for="hourly-rate" class="form-label">Hourly Rate (€)</label>
                            <input type="number" class="form-control" id="hourly-rate" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="text" class="form-control" id="latitude" placeholder="e.g. 53.8074000">
                        </div>
                        <div class="col-md-6">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="text" class="form-control" id="longitude" placeholder="e.g. -1.5553000">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location on Map</label>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="location-search" placeholder="Search location">
                            <button class="btn btn-outline-primary" type="button" id="search-location-btn">Search</button>
                        </div>
                        <div id="map" style="height: 300px; width: 100%;"></div>
                        <small class="form-text text-muted">Click on the map to set the scooter location</small>
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
    let map;
    let marker;
    let searchBox;
    
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
        
        // Search location
        $('#search-location-btn').on('click', function() {
            searchLocation();
        });
        
        // Filter scooters
        $('.filter-scooter').on('click', function() {
            const status = $(this).data('status');
            loadScooters(status);
        });
    });
    
    // Initialize map
    function initMap(latitude = 53.8074000, longitude = -1.5553000) {
        const mapOptions = {
            center: { lat: parseFloat(latitude), lng: parseFloat(longitude) },
            zoom: 14
        };
        
        map = new google.maps.Map(document.getElementById('map'), mapOptions);
        
        // Add marker for scooter location
        marker = new google.maps.Marker({
            position: { lat: parseFloat(latitude), lng: parseFloat(longitude) },
            map: map,
            draggable: true,
            title: "Scooter Location"
        });
        
        // Update form fields when marker is dragged
        google.maps.event.addListener(marker, 'dragend', function() {
            const position = marker.getPosition();
            $('#latitude').val(position.lat().toFixed(7));
            $('#longitude').val(position.lng().toFixed(7));
        });
        
        // Add click event to map to place marker
        google.maps.event.addListener(map, 'click', function(event) {
            marker.setPosition(event.latLng);
            $('#latitude').val(event.latLng.lat().toFixed(7));
            $('#longitude').val(event.latLng.lng().toFixed(7));
        });
        
        // Initialize search box
        const input = document.getElementById('location-search');
        searchBox = new google.maps.places.SearchBox(input);
        
        // Bias the SearchBox results towards current map's viewport
        map.addListener('bounds_changed', function() {
            searchBox.setBounds(map.getBounds());
        });
        
        // Listen for the event fired when the user selects a prediction
        searchBox.addListener('places_changed', function() {
            const places = searchBox.getPlaces();
            
            if (places.length === 0) {
                return;
            }
            
            const place = places[0];
            
            // Update marker position
            marker.setPosition(place.geometry.location);
            map.setCenter(place.geometry.location);
            
            // Update form fields
            $('#latitude').val(place.geometry.location.lat().toFixed(7));
            $('#longitude').val(place.geometry.location.lng().toFixed(7));
        });
    }
    
    // Search for a location
    function searchLocation() {
        const query = $('#location-search').val();
        
        if (!query) {
            alert('Please enter a location to search');
            return;
        }
        
        const geocoder = new google.maps.Geocoder();
        
        geocoder.geocode({ address: query }, function(results, status) {
            if (status === 'OK') {
                map.setCenter(results[0].geometry.location);
                marker.setPosition(results[0].geometry.location);
                
                // Update form fields
                $('#latitude').val(results[0].geometry.location.lat().toFixed(7));
                $('#longitude').val(results[0].geometry.location.lng().toFixed(7));
            } else {
                alert('Location search failed: ' + status);
            }
        });
    }
    
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
                        
                        switch (scooter.status) {
                            case 'available':
                                statusClass = 'success';
                                break;
                            case 'maintenance':
                                statusClass = 'warning';
                                break;
                            case 'disabled':
                                statusClass = 'danger';
                                break;
                        }
                        
                        // Format location for display
                        let locationDisplay = 'Not set';
                        if (scooter.latitude && scooter.longitude) {
                            locationDisplay = `${scooter.latitude}, ${scooter.longitude}`;
                        }
                        
                        tableHtml += `
                            <tr>
                                <td>${scooter.id}</td>
                                <td>${scooter.scooter_code}</td>
                                <td><span class="badge bg-${statusClass}">${scooter.status}</span></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: ${scooter.battery_level}%" 
                                            aria-valuenow="${scooter.battery_level}" aria-valuemin="0" aria-valuemax="100">
                                            ${scooter.battery_level}%
                                        </div>
                                    </div>
                                </td>
                                <td>€${scooter.hourly_rate}</td>
                                <td>${locationDisplay}</td>
                                <td>${scooter.updated_at}</td>
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
                        $('#hourly-rate').val(scooter.hourly_rate);
                        $('#latitude').val(scooter.latitude || '');
                        $('#longitude').val(scooter.longitude || '');
                        
                        // Initialize map with scooter location if available
                        let lat = scooter.latitude ? parseFloat(scooter.latitude) : 53.8074000;
                        let lng = scooter.longitude ? parseFloat(scooter.longitude) : -1.5553000;
                        
                        // Show the modal first so the map can render properly
                        $('#scooter-modal').modal('show');
                        
                        // Initialize map after modal is shown
                        setTimeout(() => {
                            initMap(lat, lng);
                        }, 500);
                        
                        $('#scooter-modal-label').text('Edit Scooter');
                        $('#delete-scooter').show();
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
            
            // Show the modal
            $('#scooter-modal').modal('show');
            
            // Initialize map with default location
            setTimeout(() => {
                initMap();
            }, 500);
        }
    }
    
    // Save scooter information
    function saveScooter() {
        const token = localStorage.getItem('admin_token');
        const scooterId = $('#scooter-id').val();
        
        const scooterData = {
            scooter_code: $('#scooter-code').val(),
            status: $('#scooter-status').val(),
            battery_level: parseInt($('#battery-level').val()),
            hourly_rate: parseFloat($('#hourly-rate').val()),
            latitude: $('#latitude').val() ? parseFloat($('#latitude').val()) : null,
            longitude: $('#longitude').val() ? parseFloat($('#longitude').val()) : null
        };
        
        if (scooterId) {
            // Update existing scooter
            scooterData.id = scooterId;
            
            $.ajax({
                url: '../api/admin/scooters/update',
                type: 'PUT',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                contentType: 'application/json',
                data: JSON.stringify(scooterData),
                success: function(response) {
                    if (response.status === 'success') {
                        $('#scooter-modal').modal('hide');
                        loadScooters();
                        alert('Scooter updated successfully');
                    }
                },
                error: function(xhr) {
                    alert('Failed to update scooter: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        } else {
            // Create new scooter
            $.ajax({
                url: '../api/admin/scooters/create',
                type: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                contentType: 'application/json',
                data: JSON.stringify(scooterData),
                success: function(response) {
                    if (response.status === 'success') {
                        $('#scooter-modal').modal('hide');
                        loadScooters();
                        alert('Scooter created successfully');
                    }
                },
                error: function(xhr) {
                    alert('Failed to create scooter: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }
    }
    
    // Delete scooter
    function deleteScooter() {
        if (!confirm('Are you sure you want to delete this scooter?')) {
            return;
        }
        
        const token = localStorage.getItem('admin_token');
        const scooterId = $('#scooter-id').val();
        
        $.ajax({
            url: `../api/admin/scooters/delete?id=${scooterId}`,
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
</script>
