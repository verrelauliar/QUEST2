/**
 * Admin Utilities - Global Loader & Toast Notifications
 * 
 * Provides:
 * - showGlobalLoader() / hideGlobalLoader() - Full-screen spinner overlay
 * - showToast(message, type) - Bootstrap toast notifications
 * - wrapFetch(url, options) - Fetch wrapper with automatic loading states
 * 
 * @requires Bootstrap 5.3
 */
(function() {
    'use strict';

    var loaderElement = null;
    var toastContainer = null;

    // =========================================================================
    // Initialization - Create DOM elements on load
    // =========================================================================
    document.addEventListener('DOMContentLoaded', function() {
        createLoaderElement();
        createToastContainer();
    });

    function createLoaderElement() {
        if (document.getElementById('adminGlobalLoader')) return;
        
        var loader = document.createElement('div');
        loader.id = 'adminGlobalLoader';
        loader.className = 'position-fixed top-0 start-0 w-100 h-100 d-none';
        loader.style.cssText = 'z-index: 9999; background: rgba(255,255,255,0.8); backdrop-filter: blur(2px);';
        loader.innerHTML = 
            '<div class="d-flex justify-content-center align-items-center h-100">' +
                '<div class="text-center">' +
                    '<div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">' +
                        '<span class="visually-hidden">Loading...</span>' +
                    '</div>' +
                    '<div class="mt-2 text-muted small" id="loaderMessage">Processing...</div>' +
                '</div>' +
            '</div>';
        document.body.appendChild(loader);
        loaderElement = loader;
    }

    function createToastContainer() {
        if (document.getElementById('adminToastContainer')) return;
        
        var container = document.createElement('div');
        container.id = 'adminToastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9998';
        document.body.appendChild(container);
        toastContainer = container;
    }

    // =========================================================================
    // Global Loader Functions
    // =========================================================================
    
    /**
     * Show the global loading overlay
     * @param {string} message - Optional loading message (default: "Processing...")
     */
    window.showGlobalLoader = function(message) {
        if (!loaderElement) createLoaderElement();
        
        var msgEl = loaderElement.querySelector('#loaderMessage');
        if (msgEl) msgEl.textContent = message || 'Processing...';
        
        loaderElement.classList.remove('d-none');
        document.body.style.overflow = 'hidden';
    };

    /**
     * Hide the global loading overlay
     */
    window.hideGlobalLoader = function() {
        if (loaderElement) {
            loaderElement.classList.add('d-none');
            document.body.style.overflow = '';
        }
    };

    // =========================================================================
    // Toast Notification Functions
    // =========================================================================
    
    /**
     * Show a toast notification
     * @param {string} message - The message to display
     * @param {string} type - 'success', 'error', 'warning', 'info' (default: 'info')
     * @param {number} duration - Auto-hide delay in ms (default: 4000, 0 = no auto-hide)
     */
    window.showToast = function(message, type, duration) {
        if (!toastContainer) createToastContainer();
        
        type = type || 'info';
        duration = duration !== undefined ? duration : 4000;
        
        var iconMap = {
            success: '<i class="bi bi-check-circle-fill text-success me-2"></i>',
            error: '<i class="bi bi-x-circle-fill text-danger me-2"></i>',
            warning: '<i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>',
            info: '<i class="bi bi-info-circle-fill text-primary me-2"></i>'
        };
        
        var bgMap = {
            success: 'bg-success-subtle border-success',
            error: 'bg-danger-subtle border-danger',
            warning: 'bg-warning-subtle border-warning',
            info: 'bg-info-subtle border-info'
        };

        var toastId = 'toast-' + Date.now();
        var toastHtml = 
            '<div id="' + toastId + '" class="toast border ' + (bgMap[type] || bgMap.info) + '" role="alert" aria-live="assertive" aria-atomic="true">' +
                '<div class="toast-body d-flex align-items-center">' +
                    (iconMap[type] || iconMap.info) +
                    '<span class="flex-grow-1">' + escapeHtml(message) + '</span>' +
                    '<button type="button" class="btn-close btn-close-sm ms-2" data-bs-dismiss="toast" aria-label="Close"></button>' +
                '</div>' +
            '</div>';
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        var toastEl = document.getElementById(toastId);
        var toast = new bootstrap.Toast(toastEl, { 
            autohide: duration > 0, 
            delay: duration 
        });
        
        // Clean up after hidden
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
        
        toast.show();
        return toast;
    };

    // =========================================================================
    // Enhanced Fetch Wrapper
    // =========================================================================
    
    /**
     * Fetch wrapper with automatic loading states and error handling
     * @param {string} url - The URL to fetch
     * @param {object} options - Fetch options (method, body, etc.)
     * @param {object} config - Additional config { showLoader: true, loaderMessage: '...', successMessage: '...' }
     * @returns {Promise} - Resolves with JSON data or rejects with error
     */
    window.adminFetch = function(url, options, config) {
        options = options || {};
        config = config || {};
        
        // Ensure credentials are included
        options.credentials = options.credentials || 'same-origin';
        
        // Show loader if requested
        if (config.showLoader !== false) {
            showGlobalLoader(config.loaderMessage);
        }
        
        return fetch(url, options)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Server error: ' + response.status);
                }
                
                var contentType = response.headers.get('content-type');
                if (contentType && contentType.indexOf('application/json') !== -1) {
                    return response.json();
                }
                return response.text();
            })
            .then(function(data) {
                hideGlobalLoader();
                
                // Check for API error response
                if (data && typeof data === 'object' && data.status === 'error') {
                    throw new Error(data.message || 'Operation failed');
                }
                
                // Show success message if configured
                if (config.successMessage) {
                    showToast(config.successMessage, 'success');
                }
                
                return data;
            })
            .catch(function(error) {
                hideGlobalLoader();
                
                var errorMessage = error.message || 'An unexpected error occurred';
                if (errorMessage.indexOf('Failed to fetch') !== -1) {
                    errorMessage = 'Network error. Please check your connection.';
                }
                
                showToast(errorMessage, 'error', 6000);
                throw error;
            });
    };

    // =========================================================================
    // Button State Helpers
    // =========================================================================
    
    /**
     * Disable a button and show spinner
     * @param {HTMLElement} btn - The button element
     * @param {string} loadingText - Text to show while loading
     */
    window.setButtonLoading = function(btn, loadingText) {
        if (!btn) return;
        btn.disabled = true;
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + (loadingText || 'Loading...');
    };

    /**
     * Restore button to original state
     * @param {HTMLElement} btn - The button element
     */
    window.resetButton = function(btn) {
        if (!btn) return;
        btn.disabled = false;
        if (btn.dataset.originalText) {
            btn.innerHTML = btn.dataset.originalText;
            delete btn.dataset.originalText;
        }
    };

    // =========================================================================
    // Utility Functions
    // =========================================================================
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})();
