// sidebar
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebar-toggle');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            
            if (sidebar && mainContent) {
                sidebar.classList.toggle('collapsed');
                
                if (sidebar.classList.contains('collapsed')) {
                    mainContent.classList.add('ml-0');
                } else {
                    mainContent.classList.remove('ml-0');
                }
            }
        });
    }
});