/**
 * Exam Logic - Student Module
 * * Handles exam-taking functionality including:
 * - Question navigation
 * - Autosave with batching/debounce
 * - Timer countdown
 * - Form submission
 * - Radio Toggle (Uncheck) support
 * * @see refactor-plans/REFACTOR_STRATEGY.md
 */

document.addEventListener('DOMContentLoaded', () => {
    const examApp = document.getElementById('exam-app');
    if (!examApp) return;

    // Config
    const config = {
        examId: examApp.dataset.examId,
        csrfToken: examApp.dataset.csrfToken,
        totalQuestions: parseInt(examApp.dataset.totalQuestions, 10),
        timeRemaining: parseInt(examApp.dataset.timeRemaining, 10),
        autosaveEndpoint: `exam.php?exam_id=${examApp.dataset.examId}`
    };

    let currentQuestion = 1;
    let autosaveTimer = null;
    let timeRemaining = config.timeRemaining;
    let pendingSave = null;

    // --- UI CONTROLLER ---

    function updateNavigator(questionNumber) {
        // 1. Update Grid Buttons
        document.querySelectorAll('.q-num').forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.querySelector(`[data-question="${questionNumber}"]`);
        if (activeBtn) activeBtn.classList.add('active');

        // 2. Update Header Info
        const progressPercent = (questionNumber / config.totalQuestions) * 100;
        const progressFill = document.getElementById('progress-fill');
        const currentQText = document.getElementById('current-question');
        
        if (progressFill) progressFill.style.width = `${progressPercent}%`;
        if (currentQText) currentQText.textContent = questionNumber;

        // 3. Update Buttons
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const submitBtn = document.getElementById('submit-btn');

        if (prevBtn) prevBtn.disabled = questionNumber === 1;
        
        if (questionNumber === config.totalQuestions) {
            nextBtn.classList.add('hidden');
            submitBtn.classList.remove('hidden');
        } else {
            nextBtn.classList.remove('hidden');
            submitBtn.classList.add('hidden');
        }
    }

    function showQuestion(questionNumber) {
        // Hide currently visible
        const currentEl = document.querySelector('.question-container:not(.hidden)');
        if (currentEl) currentEl.classList.add('hidden');

        // Show target
        const nextEl = document.getElementById(`question-${questionNumber}`);
        if (nextEl) nextEl.classList.remove('hidden');

        updateNavigator(questionNumber);
        currentQuestion = questionNumber;
        
        // --- SCROLL FIX START ---
        // Instead of scrollIntoView() which is aggressive, we manually check position.
        // If the top of the question card is above the viewport (obscured by navbar),
        // or if we are deep down the page (at the footer), scroll up gently.
        
        const card = document.querySelector('.question-card');
        if (card) {
            const navbarHeight = 80; // Approximate height of fixed navbar
            const cardRect = card.getBoundingClientRect();
            const absoluteCardTop = card.offsetTop;
            
            // Current scroll position
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Check if we are scrolled WAY past the top of the card (e.g., at the footer buttons)
            // If so, scroll back up to show the question text.
            // We use a threshold (e.g. 100px) so small scrolls don't trigger it.
            if (scrollTop > (absoluteCardTop - navbarHeight + 50)) {
                 window.scrollTo({
                    top: absoluteCardTop - navbarHeight - 20, // 20px padding
                    behavior: 'smooth'
                });
            }
        }
        // --- SCROLL FIX END ---
    }

    function markAnsweredOptimistic(questionId, isAnswered) {
        // Map input ID to navigator button
        const input = document.querySelector(`[data-question-id="${questionId}"]`);
        if(!input) return;
        
        const container = input.closest('.question-container');
        if(!container) return;
        
        const qNum = container.id.replace('question-', '');
        const navBtn = document.querySelector(`[data-question="${qNum}"]`);
        
        if (navBtn) {
            if (isAnswered) {
                navBtn.classList.add('answered');
            } else {
                navBtn.classList.remove('answered');
            }
        }
        
        // Update Sidebar Count
        const count = document.querySelectorAll('.q-num.answered').length;
        const el = document.getElementById('answered-count');
        if (el) el.textContent = count;
    }

    // --- NETWORK ---

    function sendSaveRequest(formData) {
        return fetch(config.autosaveEndpoint, { method: 'POST', body: formData })
            .then(r => r.json())
            .catch(err => console.error("Autosave error:", err));
    }

    function flushPendingSave() {
        if (!pendingSave) return;
        if (autosaveTimer) clearTimeout(autosaveTimer);

        const { questionId, answer } = pendingSave;
        pendingSave = null;

        const fd = new FormData();
        fd.append('action', 'autosave');
        fd.append('question_id', questionId);
        fd.append('answer', answer);
        fd.append('csrf_token', config.csrfToken);

        sendSaveRequest(fd);
    }

    function autosaveAnswer(questionId, answer, immediate = false) {
        const hasAnswer = answer !== null && answer !== '';
        markAnsweredOptimistic(questionId, hasAnswer);

        if (autosaveTimer) clearTimeout(autosaveTimer);
        pendingSave = { questionId, answer };

        const execute = () => {
            const fd = new FormData();
            fd.append('action', 'autosave');
            fd.append('question_id', questionId);
            fd.append('answer', answer);
            fd.append('csrf_token', config.csrfToken);
            pendingSave = null;
            sendSaveRequest(fd);
        };

        if (immediate) execute();
        else autosaveTimer = setTimeout(execute, 1000);
    }

    // --- TIMER ---

    function updateTimer() {
        timeRemaining--;
        if (timeRemaining < 0) timeRemaining = 0;

        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        const el = document.getElementById('time-display');
        if(el) {
            el.textContent = display;
            // Visual warning under 5 mins
            if (timeRemaining <= 300) el.parentElement.classList.add('text-danger');
        }

        if (timeRemaining === 0) {
            clearInterval(timerInterval);
            document.getElementById('exam-form').submit();
        }
    }

    // --- EVENTS ---

    // 1. Sidebar Nav
    document.querySelectorAll('.q-num').forEach(btn => {
        btn.addEventListener('click', () => {
            flushPendingSave();
            showQuestion(parseInt(btn.dataset.question, 10));
        });
    });

    // 2. Main Nav Buttons
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    if (prevBtn) prevBtn.addEventListener('click', (e) => {
        e.preventDefault(); // Prevent default scroll jump
        flushPendingSave();
        if (currentQuestion > 1) showQuestion(currentQuestion - 1);
    });

    if (nextBtn) nextBtn.addEventListener('click', (e) => {
        e.preventDefault(); // Prevent default scroll jump
        flushPendingSave();
        if (currentQuestion < config.totalQuestions) showQuestion(currentQuestion + 1);
    });

    // 3. Option Toggle Logic (Uncheck support)
    document.querySelectorAll('input.option-input[type="radio"]').forEach(radio => {
        radio.addEventListener('click', function(e) {
            // Check previous state
            const wasChecked = this.getAttribute('data-was-checked') === 'true';
            
            // Reset all in group
            const name = this.name;
            document.querySelectorAll(`input[name="${name}"]`).forEach(el => {
                el.setAttribute('data-was-checked', 'false');
                el.checked = false; // Visually uncheck all
            });

            if (wasChecked) {
                // Clicked already checked -> Uncheck it
                this.checked = false;
                this.setAttribute('data-was-checked', 'false');
                autosaveAnswer(this.dataset.questionId, '', true); // Save empty
            } else {
                // Clicked unchecked -> Check it
                this.checked = true;
                this.setAttribute('data-was-checked', 'true');
                autosaveAnswer(this.dataset.questionId, this.value, true);
            }
        });
    });

    // 4. Textarea
    document.querySelectorAll('textarea').forEach(txt => {
        txt.addEventListener('input', function() {
            autosaveAnswer(parseInt(this.dataset.questionId, 10), this.value, false);
        });
    });

    // Init
    const timerInterval = setInterval(updateTimer, 1000);
    showQuestion(1);
    
    // Safety Flush
    setInterval(() => { if(pendingSave) flushPendingSave(); }, 5000);
});