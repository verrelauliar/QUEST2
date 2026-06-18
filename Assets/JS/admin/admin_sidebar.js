/**
 * Admin Sidebar - Navigation Logic
 * 
 * Handles:
 * - Path resolution for dynamic base path
 * - Sidebar state persistence via LocalStorage
 * - Navigation via data-href attributes
 */
(function() {
    'use strict';

    // =========================================================================
    // 1. Path Resolution Logic (reads ADMIN_BASE from data attribute)
    // =========================================================================
    const configEl = document.getElementById('admin-config');
    const adminBase = configEl ? configEl.dataset.adminBase : '/Admin/';
    
    window.__ADMIN_BASE__ = adminBase;
    
    window.resolveAdminPath = function(rel) {
        var base = window.__ADMIN_BASE__ || '/Admin/';
        return base + rel.replace(/^\/+/, '');
    };

    document.addEventListener('click', function(e) {
        var target = e.target.closest('[data-href]');
        if (target) {
            e.preventDefault();
            var rel = target.getAttribute('data-href');
            if (rel) window.location.href = window.resolveAdminPath(rel);
        }
    });

    // =========================================================================
    // 2. Sidebar State Persistence Logic (LocalStorage)
    // =========================================================================
    const STORAGE_KEY = 'admin_sidebar_state';

    function saveState() {
        const openMenus = [];
        document.querySelectorAll('.collapse.show').forEach(function(el) {
            if (el.id) openMenus.push(el.id);
        });
        localStorage.setItem(STORAGE_KEY, JSON.stringify(openMenus));
    }

    function restoreState() {
        try {
            const openMenus = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
            openMenus.forEach(function(id) {
                const element = document.getElementById(id);
                if (element) {
                    // Add 'show' class to content
                    element.classList.add('show');
                    
                    // Update the trigger button (arrow rotation/color)
                    const trigger = document.querySelector('[data-bs-target="#' + id + '"]');
                    if (trigger) {
                        trigger.classList.remove('collapsed');
                        trigger.setAttribute('aria-expanded', 'true');
                    }
                }
            });
        } catch (e) {
            console.error('Failed to restore sidebar state:', e);
        }
    }

    // =========================================================================
    // 3. Initialize on DOM Ready
    // =========================================================================
    document.addEventListener('DOMContentLoaded', function() {
        restoreState();

        // Listen for Bootstrap toggle events to update storage
        var collapses = document.querySelectorAll('.admin-sidebar .collapse');
        collapses.forEach(function(el) {
            el.addEventListener('shown.bs.collapse', saveState);
            el.addEventListener('hidden.bs.collapse', saveState);
        });
    });
})();
