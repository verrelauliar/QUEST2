/**
 * Manage Classes - Form Submit Logic
 * 
 * Handles:
 * - Create class form AJAX submission via adminFetch
 * - Loading states on submit button
 * - Toast notifications for success/error
 * 
 * @requires admin_utils.js (adminFetch, showToast, setButtonLoading, resetButton)
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('createClassForm');
        
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var btn = form.querySelector('button[type="submit"]');
            
            // Show button loading state
            if (typeof setButtonLoading === 'function') {
                setButtonLoading(btn, 'Creating...');
            }
            
            adminFetch(form.action, {
                method: 'POST',
                body: new FormData(form)
            }, {
                showLoader: false, // Use button loading instead
                successMessage: 'Class created successfully'
            })
            .then(function(data) {
                if (data.success || data.status === 'success') {
                    // Small delay to show toast before reload
                    setTimeout(function() { 
                        location.reload(); 
                    }, 500);
                } else {
                    // Reset button on error
                    if (typeof resetButton === 'function') {
                        resetButton(btn);
                    }
                }
            })
            .catch(function() {
                // Error already shown by adminFetch, reset button
                if (typeof resetButton === 'function') {
                    resetButton(btn);
                }
            });
        });
    });
})();
