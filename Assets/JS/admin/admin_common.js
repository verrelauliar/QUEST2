/**
 * Admin Common - Sidebar Toggle Logic
 * 
 * Shared toggle logic for all admin pages.
 * Uses data-config element for page-specific settings.
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.admin-sidebar');
        const main = document.querySelector('.admin-main');
        
        if (!toggleBtn || !sidebar || !main) return;

        toggleBtn.addEventListener('click', function() {
            const isClosed = main.style.marginLeft === '0px';
            main.style.marginLeft = isClosed ? '280px' : '0px';
            sidebar.style.transform = isClosed ? 'translateX(0)' : 'translateX(-100%)';
        });

        // Responsive auto-collapse on page load
        if (window.innerWidth < 992) {
            main.style.marginLeft = '0px';
            sidebar.style.transform = 'translateX(-100%)';
        }
    });
})();
