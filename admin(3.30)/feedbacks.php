<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Feedback Management</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary filter-feedback" data-status="all">All</button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-feedback" data-status="pending">Pending</button>
            <button type="button" class="btn btn-sm btn-outline-secondary filter-feedback" data-status="processed">Processed</button>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Feedback List</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="feedbacks-table" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Scooter</th>
                        <th>Type</th>
                        <th>Content</th>
                        <th>Submission Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Feedback data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Feedback Details Modal -->
<div class="modal fade" id="feedback-modal" tabindex="-1" aria-labelledby="feedback-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedback-modal-label">Feedback Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="feedback-form">
                    <input type="hidden" id="feedback-id">
                    <div class="mb-3">
                        <label for="user-name" class="form-label">User</label>
                        <input type="text" class="form-control" id="user-name" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="scooter-code" class="form-label">Scooter</label>
                        <input type="text" class="form-control" id="scooter-code" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="feedback-type" class="form-label">Type</label>
                        <input type="text" class="form-control" id="feedback-type" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="feedback-content" class="form-label">Content</label>
                        <textarea class="form-control" id="feedback-content" rows="3" readonly></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="feedback-status" class="form-label">Status</label>
                        <select class="form-select" id="feedback-status">
                            <option value="pending">Pending</option>
                            <option value="processed">Processed</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="admin-reply" class="form-label">Admin Reply</label>
                        <textarea class="form-control" id="admin-reply" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="save-feedback">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Load feedback data
        loadFeedbacks();
        
        // Filter feedbacks
        $('.filter-feedback').on('click', function() {
            $('.filter-feedback').removeClass('active');
            $(this).addClass('active');
            
            const status = $(this).data('status');
            loadFeedbacks(status);
        });
        
        // Save feedback information
        $('#save-feedback').on('click', function() {
            saveFeedback();
        });
    });
    
    // Load feedback data
    function loadFeedbacks(status = 'all') {
        const token = localStorage.getItem('admin_token');
        let url = '../api/admin/feedbacks';
        
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
                    const feedbacks = response.data;
                    let tableHtml = '';
                    
                    feedbacks.forEach(function(feedback) {
                        let statusClass = feedback.status === 'pending' ? 'warning' : 'success';
                        let statusText = feedback.status === 'pending' ? 'Pending' : 'Processed';
                        
                        let typeText = '';
                        switch (feedback.type) {
                            case 'issue':
                                typeText = 'Issue Report';
                                break;
                            case 'suggestion':
                                typeText = 'Suggestion';
                                break;
                            case 'complaint':
                                typeText = 'Complaint';
                                break;
                            default:
                                typeText = feedback.type;
                        }
                        
                        // Truncate content, only show first 30 characters
                        const shortContent = feedback.content.length > 30 
                            ? feedback.content.substring(0, 30) + '...' 
                            : feedback.content;
                        
                        tableHtml += `
                            <tr>
                                <td>${feedback.id}</td>
                                <td>${feedback.username}</td>
                                <td>${feedback.scooter_code || 'None'}</td>
                                <td>${typeText}</td>
                                <td>${shortContent}</td>
                                <td>${feedback.created_at}</td>
                                <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary view-feedback" data-id="${feedback.id}">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    $('#feedbacks-table tbody').html(tableHtml);
                    
                    // Bind view button events
                    $('.view-feedback').on('click', function() {
                        const feedbackId = $(this).data('id');
                        openFeedbackModal(feedbackId);
                    });
                }
            },
            error: function(xhr) {
                alert('Failed to load feedback data: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Open feedback details modal
    function openFeedbackModal(feedbackId) {
        const token = localStorage.getItem('admin_token');
        
        $.ajax({
            url: `../api/admin/feedbacks/${feedbackId}`,
            type: 'GET',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            success: function(response) {
                if (response.status === 'success') {
                    const feedback = response.data;
                    
                    $('#feedback-id').val(feedback.id);
                    $('#user-name').val(feedback.username);
                    $('#scooter-code').val(feedback.scooter_code || 'None');
                    
                    let typeText = '';
                    switch (feedback.type) {
                        case 'issue':
                            typeText = 'Issue Report';
                            break;
                        case 'suggestion':
                            typeText = 'Suggestion';
                            break;
                        case 'complaint':
                            typeText = 'Complaint';
                            break;
                        default:
                            typeText = feedback.type;
                    }
                    
                    $('#feedback-type').val(typeText);
                    $('#feedback-content').val(feedback.content);
                    $('#feedback-status').val(feedback.status);
                    $('#admin-reply').val(feedback.admin_reply || '');
                    
                    $('#feedback-modal').modal('show');
                }
            },
            error: function(xhr) {
                alert('Failed to get feedback details: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Save feedback information
    function saveFeedback() {
        const token = localStorage.getItem('admin_token');
        const feedbackId = $('#feedback-id').val();
        
        const feedbackData = {
            id: feedbackId,
            status: $('#feedback-status').val(),
            admin_reply: $('#admin-reply').val()
        };
        
        $.ajax({
            url: '../api/admin/feedbacks/update',
            type: 'PUT',
            headers: {
                'Authorization': 'Bearer ' + token
            },
            contentType: 'application/json',
            data: JSON.stringify(feedbackData),
            success: function(response) {
                if (response.status === 'success') {
                    $('#feedback-modal').modal('hide');
                    loadFeedbacks();
                    alert('Feedback updated successfully');
                }
            },
            error: function(xhr) {
                alert('Failed to update feedback: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }
    
    // Initialize the feedback management page
    function feedbacksInit() {
        // The page has been loaded via AJAX, this function will run after loading
        loadFeedbacks();
    }
</script>
