/**
 * Classes Detail - Full Page Logic
 * 
 * Handles:
 * - Curriculum tab (add/edit/delete subject assignments)
 * - Enrollment tab (dual listbox for student roster)
 * - Exams tab (view exams)
 * - Edit class modal
 * 
 * @requires admin_utils.js (adminFetch, showToast, showGlobalLoader, hideGlobalLoader, setButtonLoading, resetButton)
 * 
 * Reads CLASS_ID from #page-config data-class-id attribute
 */
(function() {
    'use strict';

    // Configuration
    var CLASS_ID = 0;
    var API = 'classes_crud.php';
    var TEACHERS_OPTS = [];
    var SUBJECTS_OPTS = [];

    // Roster data for dual listbox
    var rosterData = { available: [], enrolled: [] };

    // =========================================================================
    // Bootstrap Helpers
    // =========================================================================
    function showModal(id) {
        new bootstrap.Modal(document.getElementById(id)).show();
    }

    function hideModal(id) {
        var modalInstance = bootstrap.Modal.getInstance(document.getElementById(id));
        if (modalInstance) modalInstance.hide();
    }

    // =========================================================================
    // Initialization
    // =========================================================================
    document.addEventListener('DOMContentLoaded', function() {
        var configEl = document.getElementById('page-config');
        if (configEl) {
            CLASS_ID = parseInt(configEl.dataset.classId, 10) || 0;
        }

        if (!CLASS_ID) {
            console.error('CLASS_ID not found in page config');
            return;
        }

        loadDropdowns().then(function() {
            loadCurriculum();
            loadEnrollment();
            loadExams();
        });
    });

    // =========================================================================
    // Dropdown Data Loading
    // =========================================================================
    function loadDropdowns() {
        return Promise.all([
            fetch(API + '?action=get_teachers', { credentials: 'same-origin' }).then(function(r) { return r.json(); }),
            fetch(API + '?action=get_subjects', { credentials: 'same-origin' }).then(function(r) { return r.json(); })
        ])
        .then(function(results) {
            TEACHERS_OPTS = results[0].data || [];
            SUBJECTS_OPTS = results[1].data || [];
        })
        .catch(function(e) {
            console.error('Failed to load dropdowns:', e);
            showToast('Failed to load dropdown data', 'error');
        });
    }

    // =========================================================================
    // Curriculum Tab Logic
    // =========================================================================
    function loadCurriculum() {
        var tbody = document.getElementById('curriculum-tbody');
        tbody.innerHTML = '<tr><td colspan="4" class="p-4 text-center text-muted">Loading...</td></tr>';
        
        fetch(API + '?action=get_curriculum&class_id=' + CLASS_ID, { credentials: 'same-origin' })
            .then(function(res) { return res.json(); })
            .then(function(json) {
                tbody.innerHTML = '';
                
                if (!json.data || !json.data.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="p-4 text-center text-muted">No subjects assigned.</td></tr>';
                } else {
                    json.data.forEach(function(r) {
                        tbody.innerHTML += renderRow(r);
                    });
                }
            })
            .catch(function() {
                tbody.innerHTML = '<tr><td colspan="4" class="text-danger text-center">Error loading data</td></tr>';
            });
    }

    function renderRow(d) {
        return '<tr id="row-' + d.id_assignment + '">' +
            '<td><strong>' + escapeHtml(d.subject_name) + '</strong></td>' +
            '<td>' + escapeHtml(d.teacher_name) + '</td>' +
            '<td><span class="status-badge status-active">Active</span></td>' +
            '<td>' +
                '<button class="table-action-btn text-primary" onclick="editRow(' + d.id_assignment + ', ' + d.subject_id + ', ' + d.teacher_id + ')">Edit</button>' +
                '<button class="table-action-btn text-danger" onclick="deleteRow(' + d.id_assignment + ')">Delete</button>' +
            '</td>' +
        '</tr>';
    }

    function renderEditableRow(id, sId, tId) {
        id = id || null;
        sId = sId || '';
        tId = tId || '';

        var sOpts = '<option value="">Select Subject...</option>' + 
            SUBJECTS_OPTS.map(function(s) {
                return '<option value="' + s.id_subject + '"' + (s.id_subject == sId ? ' selected' : '') + '>' + escapeHtml(s.subject_name) + '</option>';
            }).join('');
        
        var tOpts = '<option value="">Select Teacher...</option>' + 
            TEACHERS_OPTS.map(function(t) {
                return '<option value="' + t.id_user + '"' + (t.id_user == tId ? ' selected' : '') + '>' + escapeHtml(t.full_name) + '</option>';
            }).join('');

        return '<td><select class="form-select form-select-sm subject-input">' + sOpts + '</select></td>' +
            '<td><select class="form-select form-select-sm teacher-input">' + tOpts + '</select></td>' +
            '<td><input type="text" class="form-control form-control-sm" value="Active" disabled></td>' +
            '<td>' +
                '<button class="table-action-btn text-success" onclick="saveRow(this, ' + id + ')">Save</button>' +
                '<button class="table-action-btn text-secondary" onclick="cancelRow(this, ' + id + ')">Cancel</button>' +
            '</td>';
    }

    // Expose functions globally
    window.addSubjectRow = function() {
        var tr = document.createElement('tr');
        tr.innerHTML = renderEditableRow();
        document.getElementById('curriculum-tbody').appendChild(tr);
    };

    window.editRow = function(id, sId, tId) {
        document.getElementById('row-' + id).innerHTML = renderEditableRow(id, sId, tId);
    };

    window.saveRow = function(btn, id) {
        var tr = btn.closest('tr');
        var sId = tr.querySelector('.subject-input').value;
        var tId = tr.querySelector('.teacher-input').value;
        
        if (!sId || !tId) {
            showToast('Please select both subject and teacher', 'warning');
            return;
        }
        
        // Show loading state
        if (typeof setButtonLoading === 'function') setButtonLoading(btn, 'Saving...');
        
        var fd = new FormData();
        fd.append('class_id', CLASS_ID);
        fd.append('subject_id', sId);
        fd.append('teacher_id', tId);
        fd.append('action', id ? 'update_assignment' : 'add_assignment');
        if (id) fd.append('assignment_id', id);
        
        adminFetch(API, {
            method: 'POST',
            body: fd
        }, {
            showLoader: false,
            successMessage: id ? 'Assignment updated' : 'Assignment added'
        })
        .then(function(data) {
            if (data.success || data.status !== 'error') {
                loadCurriculum();
            } else {
                if (typeof resetButton === 'function') resetButton(btn);
            }
        })
        .catch(function() {
            if (typeof resetButton === 'function') resetButton(btn);
        });
    };

    window.cancelRow = function(btn, id) {
        if (id) {
            loadCurriculum();
        } else {
            btn.closest('tr').remove();
        }
    };

    window.deleteRow = function(id) {
        if (!confirm('Remove this assignment?')) return;
        
        var fd = new FormData();
        fd.append('action', 'remove_assignment');
        fd.append('assignment_id', id);
        
        adminFetch(API, {
            method: 'POST',
            body: fd
        }, {
            showLoader: true,
            loaderMessage: 'Removing...',
            successMessage: 'Assignment removed'
        })
        .then(function(data) {
            if (data.success || data.status !== 'error') {
                loadCurriculum();
            }
        });
    };

    // =========================================================================
    // Enrollment Tab - Dual Listbox Logic
    // =========================================================================
    window.openManageStudentsModal = function() {
        fetch(API + '?action=get_roster_data&class_id=' + CLASS_ID, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(json) {
                rosterData = json.data;
                sortArrays();
                renderDualLists();
                showModal('manageStudentsModal');
            })
            .catch(function(e) {
                alert('Failed to load students');
            });
    };

    function sortArrays() {
        var sorter = function(a, b) { return a.full_name.localeCompare(b.full_name); };
        rosterData.available.sort(sorter);
        rosterData.enrolled.sort(sorter);
    }

    function renderDualLists() {
        // Render Left (Available)
        var leftHtml = rosterData.available.map(function(s) {
            return '<button type="button" class="list-group-item list-group-item-action" data-id="' + s.id_user + '" onclick="this.classList.toggle(\'active\')">' +
                escapeHtml(s.full_name) + ' <small class="text-muted ms-1">(' + escapeHtml(s.username) + ')</small>' +
            '</button>';
        }).join('');
        
        document.getElementById('availableList').innerHTML = leftHtml || '<div class="p-3 text-muted text-center small">No students available</div>';
        document.getElementById('count-available').textContent = rosterData.available.length;

        // Render Right (Enrolled)
        var rightHtml = rosterData.enrolled.map(function(s) {
            return '<button type="button" class="list-group-item list-group-item-action" data-id="' + s.id_user + '" onclick="this.classList.toggle(\'active\')">' +
                escapeHtml(s.full_name) + ' <small class="text-muted ms-1">(' + escapeHtml(s.username) + ')</small>' +
            '</button>';
        }).join('');
        
        document.getElementById('selectedList').innerHTML = rightHtml || '<div class="p-3 text-muted text-center small">No students enrolled</div>';
        document.getElementById('count-selected').textContent = rosterData.enrolled.length;
    }

    window.moveSelected = function(direction) {
        var sourceListId = direction === 'right' ? 'availableList' : 'selectedList';
        var activeItems = document.querySelectorAll('#' + sourceListId + ' .list-group-item.active');
        
        if (activeItems.length === 0) return;

        var idsToMove = Array.from(activeItems).map(function(el) { 
            return parseInt(el.dataset.id, 10); 
        });

        // Extract items to move
        var sourceArray = direction === 'right' ? rosterData.available : rosterData.enrolled;
        var itemsToMove = sourceArray.filter(function(s) { 
            return idsToMove.indexOf(s.id_user) !== -1; 
        });

        // Update arrays
        if (direction === 'right') {
            rosterData.available = rosterData.available.filter(function(s) { 
                return idsToMove.indexOf(s.id_user) === -1; 
            });
            rosterData.enrolled = rosterData.enrolled.concat(itemsToMove);
        } else {
            rosterData.enrolled = rosterData.enrolled.filter(function(s) { 
                return idsToMove.indexOf(s.id_user) === -1; 
            });
            rosterData.available = rosterData.available.concat(itemsToMove);
        }

        sortArrays();
        renderDualLists();

        // Clear search inputs
        document.querySelectorAll('.dual-list-wrapper input').forEach(function(i) { 
            i.value = ''; 
        });
        filterList({ value: '' }, 'availableList');
        filterList({ value: '' }, 'selectedList');
    };

    window.filterList = function(inp, listId) {
        var filter = inp.value.toLowerCase();
        var items = document.getElementById(listId).querySelectorAll('.list-group-item');
        items.forEach(function(item) {
            var text = item.textContent.toLowerCase();
            item.style.display = text.indexOf(filter) !== -1 ? '' : 'none';
        });
    };

    window.submitStudentRoster = function() {
        var btn = document.querySelector('#manageStudentsModal .btn-primary');
        
        if (typeof setButtonLoading === 'function') {
            setButtonLoading(btn, 'Saving...');
        }

        var ids = rosterData.enrolled.map(function(s) { return s.id_user; });
        var fd = new FormData();
        fd.append('action', 'update_roster');
        fd.append('class_id', CLASS_ID);
        fd.append('student_ids', JSON.stringify(ids));

        adminFetch(API, {
            method: 'POST',
            body: fd
        }, {
            showLoader: false,
            successMessage: 'Student roster updated'
        })
        .then(function(data) {
            if (data.success || data.status !== 'error') {
                hideModal('manageStudentsModal');
                loadEnrollment();
            }
            if (typeof resetButton === 'function') resetButton(btn);
        })
        .catch(function() {
            if (typeof resetButton === 'function') resetButton(btn);
        });
    };

    function loadEnrollment() {
        var div = document.getElementById('enrollment-list');
        
        fetch(API + '?action=get_roster_data&class_id=' + CLASS_ID, { credentials: 'same-origin' })
            .then(function(res) { return res.json(); })
            .then(function(json) {
                if (!json.data || !json.data.enrolled || !json.data.enrolled.length) {
                    div.innerHTML = '<div class="p-4 text-center text-muted">No students enrolled</div>';
                    return;
                }

                var rows = json.data.enrolled.map(function(s) {
                    return '<tr>' +
                        '<td><strong>' + escapeHtml(s.full_name) + '</strong></td>' +
                        '<td>' + escapeHtml(s.username) + '</td>' +
                        '<td><button class="table-action-btn text-danger" onclick="removeStudent(' + s.id_user + ')">Remove</button></td>' +
                    '</tr>';
                }).join('');

                div.innerHTML = '<div class="table-wrapper"><table class="data-table">' +
                    '<thead><tr><th>Name</th><th>Username</th><th>Action</th></tr></thead>' +
                    '<tbody>' + rows + '</tbody>' +
                '</table></div>';
            })
            .catch(function() {
                div.innerHTML = '<div class="p-4 text-center text-danger">Error loading enrollment</div>';
            });
    }

    window.removeStudent = function(id) {
        if (!confirm('Remove student from class?')) return;
        
        var fd = new FormData();
        fd.append('action', 'remove_student');
        fd.append('class_id', CLASS_ID);
        fd.append('student_id', id);
        
        adminFetch(API, {
            method: 'POST',
            body: fd
        }, {
            showLoader: true,
            loaderMessage: 'Removing student...',
            successMessage: 'Student removed from class'
        })
        .then(function(data) {
            if (data.success || data.status !== 'error') {
                loadEnrollment();
            }
        });
    };

    // =========================================================================
    // Exams Tab Logic
    // =========================================================================
    function loadExams() {
        var div = document.getElementById('exam-list');
        
        fetch(API + '?action=get_exams_by_class&class_id=' + CLASS_ID, { credentials: 'same-origin' })
            .then(function(res) { return res.json(); })
            .then(function(json) {
                if (!json.data || !json.data.length) {
                    div.innerHTML = '<div class="p-4 text-center text-muted">No exams found</div>';
                    return;
                }

                var rows = json.data.map(function(e) {
                    var statusClass = e.is_active ? 'status-active' : 'status-inactive';
                    var statusText = e.is_active ? 'Active' : 'Closed';
                    return '<tr>' +
                        '<td><strong>' + escapeHtml(e.title) + '</strong></td>' +
                        '<td>' + new Date(e.start_time).toLocaleDateString() + '</td>' +
                        '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>' +
                    '</tr>';
                }).join('');

                div.innerHTML = '<div class="table-wrapper"><table class="data-table">' +
                    '<thead><tr><th>Title</th><th>Start</th><th>Status</th></tr></thead>' +
                    '<tbody>' + rows + '</tbody>' +
                '</table></div>';
            })
            .catch(function() {
                div.innerHTML = '<div class="p-4 text-center text-danger">Error loading exams</div>';
            });
    }

    // =========================================================================
    // Edit Class Modal
    // =========================================================================
    window.submitEditClass = function() {
        var btn = document.querySelector('#editClassModal .btn-primary');
        var fd = new FormData(document.getElementById('editClassForm'));
        
        if (typeof setButtonLoading === 'function') {
            setButtonLoading(btn, 'Saving...');
        }
        
        adminFetch(API, {
            method: 'POST',
            body: fd
        }, {
            showLoader: false,
            successMessage: 'Class updated successfully'
        })
        .then(function(data) {
            if (data.success || data.status !== 'error') {
                setTimeout(function() {
                    location.reload();
                }, 500);
            } else {
                if (typeof resetButton === 'function') resetButton(btn);
            }
        })
        .catch(function() {
            if (typeof resetButton === 'function') resetButton(btn);
        });
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
