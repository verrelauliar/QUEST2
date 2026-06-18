/**
 * Manage Users - Form, Filter, and CRUD Logic
 */
(function() {
    'use strict';

    var API_URL = 'users_crud.php';

    document.addEventListener('DOMContentLoaded', function() {
        var panelGrid = document.getElementById('panelGrid');
        var configEl = document.getElementById('page-config');
        var userForm = document.getElementById('userForm');
        
        if (!panelGrid) return;

        // --- Highlight Logic with Auto-Remove ---
        if (window.highlightedUserIds && window.highlightedUserIds.length > 0) {
            console.log('Highlighting users:', window.highlightedUserIds); // Debug log
            
            window.highlightedUserIds.forEach(function(id) {
                var row = document.querySelector('tr[data-id="' + id + '"]');
                if (row) {
                    row.classList.add('row-highlight');
                    
                    // Scroll to first highlighted row only
                    if (window.highlightedUserIds.indexOf(id) === 0) {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    
                    // Remove highlight after 3 seconds
                    setTimeout(function() {
                        row.classList.remove('row-highlight');
                    }, 3000);
                }
            });
            
            // Clear the global variable to prevent re-highlighting on DOM changes
            window.highlightedUserIds = [];
        }

        // Read server-side state from data attributes
        var isEditing = configEl && configEl.dataset.isEditing === 'true';
        var isNewMode = configEl && configEl.dataset.isNewMode === 'true';

        window.openForm = function() {
            panelGrid.classList.add('form-active');
        };

        window.closeForm = function() {
            panelGrid.classList.remove('form-active');
            var url = new URL(window.location);
            url.searchParams.delete('edit');
            url.searchParams.delete('new');
            window.history.pushState({}, '', url);
        };

        window.handleAddNew = function() {
            if (isEditing) {
                window.location.href = 'manage_users.php?new=1';
            } else {
                window.openForm();
            }
        };

        if (isEditing || isNewMode) {
            window.openForm();
        }

        // --- Role Filter Logic ---
        var roleBtns = document.querySelectorAll('.role-btn');
        roleBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                roleBtns.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                var role = btn.dataset.role;
                document.querySelectorAll('tbody tr').forEach(function(row) {
                    row.style.display = (role === 'all' || row.dataset.role === role) ? '' : 'none';
                });
            });
        });

        // --- Form Submission via AJAX ---
        if (userForm) {
            userForm.addEventListener('submit', function(e) {
                e.preventDefault();
                if (!userForm.checkValidity()) {
                    e.stopPropagation();
                    userForm.classList.add('was-validated');
                    return;
                }
                
                var submitBtn = userForm.querySelector('button[type="submit"]');
                var formData = new FormData(userForm);
                var action = formData.get('action');
                var successMsg = action === 'add' ? 'User created successfully' : 'User updated successfully';
                
                if (typeof setButtonLoading === 'function') {
                    setButtonLoading(submitBtn, 'Saving...');
                }
                
                adminFetch(API_URL, {
                    method: 'POST',
                    body: formData
                }, {
                    showLoader: false,
                    successMessage: successMsg
                })
                .then(function(data) {
                    if (data.success) {
                        setTimeout(function() {
                            window.location.href = 'manage_users.php';
                        }, 800);
                    } else if (typeof resetButton === 'function') {
                        resetButton(submitBtn);
                    }
                })
                .catch(function() {
                    if (typeof resetButton === 'function') {
                        resetButton(submitBtn);
                    }
                });
            });
        }
    });

    window.deleteUser = function(userId, userName) {
        if (!confirm('Are you sure you want to delete "' + userName + '"? This action cannot be undone.')) {
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', userId);
        
        adminFetch(API_URL, {
            method: 'POST',
            body: formData
        }, {
            showLoader: true,
            loaderMessage: 'Deleting user...',
            successMessage: 'User deleted successfully'
        })
        .then(function(data) {
            if (data.success) {
                setTimeout(function() {
                    window.location.reload();
                }, 800);
            }
        });
    };
})();