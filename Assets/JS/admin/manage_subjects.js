/**
 * Manage Subjects - Modal Logic
 * 
 * Handles:
 * - Opening subject modal (new/edit)
 * - AJAX loading of form content
 * - Auto-open from URL parameter
 * - Form submission with loading states
 * 
 * @requires admin_utils.js (showGlobalLoader, hideGlobalLoader, showToast)
 */
(function() {
    'use strict';

    let subjectModal = null;
    let modalContent = null;

    /**
     * Open the subject modal (new or edit)
     * @param {number|null} editId - Subject ID to edit, or null for new
     */
    window.openSubjectModal = function(editId) {
        if (!subjectModal || !modalContent) return;

        // Get form URL from data attribute (JavaScript Decoupling)
        var formUrl = document.querySelector('[data-form-url]')?.dataset.formUrl || 'subjects_form.php';
        const url = formUrl + '?modal=1' + (editId ? '&edit=' + editId : '');
        
        // Show loading state in modal
        modalContent.innerHTML = 
            '<div class="modal-body text-center py-5">' +
                '<div class="spinner-border text-primary" role="status"></div>' +
                '<div class="mt-2 text-muted small">Loading form...</div>' +
            '</div>';
        
        subjectModal.show();

        fetch(url, { credentials: 'same-origin' })
            .then(function(response) {
                if (!response.ok) throw new Error('Server error: ' + response.status);
                return response.text();
            })
            .then(function(html) {
                modalContent.innerHTML = html;
                
                // Auto focus first input
                var firstInput = modalContent.querySelector('input');
                if (firstInput) firstInput.focus();

                // Attach form submit handler with loading state
                var form = modalContent.querySelector('form');
                if (form) {
                    form.addEventListener('submit', handleFormSubmit);
                }
            })
            .catch(function(error) {
                console.error('Error:', error);
                modalContent.innerHTML = 
                    '<div class="modal-header border-bottom-0">' +
                        '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
                    '</div>' +
                    '<div class="modal-body text-center py-4">' +
                        '<i class="bi bi-exclamation-circle text-danger fs-1 mb-3 d-block"></i>' +
                        '<p class="text-danger mb-0">Failed to load form. Please try again.</p>' +
                    '</div>';
                
                if (typeof showToast === 'function') {
                    showToast('Failed to load form: ' + error.message, 'error');
                }
            });
    };

    /**
     * Handle form submission with loading state and AJAX
     */
    function handleFormSubmit(event) {
        event.preventDefault(); // Prevent default form submission
        
        // DEBUG: Log form submission event
        console.log('=== Form Submitted ===');
        console.log('Event:', event);
        console.log('Event submitter:', event.submitter);
        console.log('Active element:', document.activeElement);
        
        var form = event.target;
        
        // Bootstrap validation
        if (!form.checkValidity()) {
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        // Show loading on submit button
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && typeof setButtonLoading === 'function') {
            setButtonLoading(submitBtn, 'Saving...');
        }
        
        // Create FormData and submit via AJAX
        var formData = new FormData(form);
        
        // DEBUG: Log FormData before adding button
        console.log('FormData before adding button:', Array.from(formData.entries()));
        
        // CRITICAL: Add the submit button's name and value to FormData
        // This is required because event.preventDefault() prevents the button from being included automatically
        var submitButton = event.submitter;
        
        // DEBUG: Log submit button details
        console.log('Submit button found:', submitButton);
        if (submitButton) {
            console.log('Submit button name:', submitButton.name);
            console.log('Submit button value:', submitButton.value);
        }
        
        // Add browser compatibility fallback
        if (!submitButton) {
            console.log('event.submitter not available, using document.activeElement fallback');
            submitButton = document.activeElement;
            console.log('Fallback active element:', submitButton);
        }
        
        if (submitButton && submitButton.name) {
            formData.append(submitButton.name, submitButton.value || '');
            console.log('Added button to FormData:', submitButton.name, '=', submitButton.value);
        } else {
            console.log('WARNING: No submit button with name found!');
        }
        
        // DEBUG: Log FormData after adding button
        console.log('FormData after adding button:', Array.from(formData.entries()));
        
        // Check if action_type hidden field exists (more reliable approach)
        var actionType = formData.get('action_type');
        console.log('action_type from hidden field:', actionType);
        
        // Submit via fetch to handle JSON response
        fetch('subjects_crud.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin' // Important for session cookies
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                // Success: Show toast, close modal, reload page
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Subject saved successfully', 'success');
                } else {
                    alert(data.message || 'Subject saved successfully');
                }
                
                // Close the modal
                if (subjectModal) {
                    subjectModal.hide();
                }
                
                // Reload page after a short delay to show updated data
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
                
            } else {
                // Error: Show error message and re-enable button
                if (typeof showToast === 'function') {
                    showToast(data.message || 'An error occurred', 'error');
                } else {
                    alert(data.message || 'An error occurred');
                }
                
                // Re-enable button
                if (submitBtn && typeof resetButton === 'function') {
                    resetButton(submitBtn);
                }
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            
            // Show error message
            if (typeof showToast === 'function') {
                showToast('An unexpected error occurred. Please try again.', 'error');
            } else {
                alert('An unexpected error occurred. Please try again.');
            }
            
            // Re-enable button
            if (submitBtn && typeof resetButton === 'function') {
                resetButton(submitBtn);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var modalEl = document.getElementById('subjectModal');
        modalContent = document.getElementById('modalContent');
        
        if (modalEl) {
            subjectModal = new bootstrap.Modal(modalEl);
        }

        // Auto-open if query param exists (e.g. from sidebar link)
        var params = new URLSearchParams(window.location.search);
        if (params.has('edit')) {
            window.openSubjectModal(params.get('edit'));
        }
    });
    /**
     * Delete subject with AJAX confirmation
     * @param {number} subjectId - Subject ID to delete
     * @param {string} subjectName - Subject name for confirmation message
     */
    window.deleteSubject = function(subjectId, subjectName, event) {
        if (!confirm(`Are you sure you want to delete the subject "${subjectName}"? This action cannot be undone.`)) {
            return;
        }
        
        // Show loading state
        const deleteButton = event.currentTarget;
        if (typeof setButtonLoading === 'function') {
            setButtonLoading(deleteButton, 'Loading...');
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('delete', subjectId);
        formData.append('action_type', 'delete');
        
        // Submit via fetch
        fetch('subjects_crud.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Subject deleted successfully', 'success');
                } else {
                    alert(data.message || 'Subject deleted successfully');
                }
                // Reload page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                if (typeof showToast === 'function') {
                    showToast(data.message || 'Failed to delete subject', 'error');
                } else {
                    alert(data.message || 'Failed to delete subject');
                }
                if (typeof resetButton === 'function') {
                    resetButton(deleteButton);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof showToast === 'function') {
                showToast('An unexpected error occurred. Please try again.', 'error');
            } else {
                alert('An unexpected error occurred. Please try again.');
            }
            if (typeof resetButton === 'function') {
                resetButton(deleteButton);
            }
        });
    };
    
})();
