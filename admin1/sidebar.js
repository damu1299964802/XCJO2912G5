/* static/js/sidebar.js */
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const mobileSidebarToggle = document.getElementById('mobile-sidebar-toggle');
    
    // Desktop sidebar toggle
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('sidebar-collapsed');
    });
    
    // Mobile sidebar toggle
    mobileSidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('mobile-collapsed');
        document.body.classList.toggle('sidebar-open');
    });

    // Window resize handler
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-collapsed');
            document.body.classList.remove('sidebar-open');
        }
    });
});