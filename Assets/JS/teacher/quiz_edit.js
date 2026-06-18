
/* Create a Multiple Choice question card */
function addMultipleChoice(){
  qIndex++;
  const idx = qIndex;
  const container = document.getElementById('questions-container');

  const card = document.createElement('div');
  card.className = 'card border-0 shadow-sm';
  card.id = `qcard-${idx}`;
  card.innerHTML = `
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-start mb-4">
        <div class="d-flex align-items-center gap-3">
          <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">Question ${idx}</span>
          <div>
            <h6 class="fw-bold mb-0 text-dark">Multiple Choice</h6>
            <small class="text-muted">Enter the question and options below</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="duplicateQuestion(${idx})">Duplicate</button>
          <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" onclick="removeQuestion(${idx})">Delete</button>
        </div>
      </div>

      <div class="form-floating mb-3">
        <textarea name="questions[${idx}][text]" required class="form-control" placeholder="Enter your question here" style="height: 100px"></textarea>
        <label>Question Text *</label>
      </div>

      <div class="mb-4">
        <label class="form-label small text-muted">Question Image (Optional)</label>
        <input class="form-control" type="file" id="qimg-${idx}" name="questions[${idx}][image]" accept="image/*">
        <div class="form-text small">PNG, JPG, GIF up to 10MB</div>
      </div>

      <div class="vstack gap-2" id="options-list-${idx}">
        <div class="option-row input-group">
          <span class="input-group-text bg-light fw-bold text-secondary" style="width: 40px; justify-content: center;">A</span>
          <input type="text" class="form-control" name="questions[${idx}][a]" placeholder="Option 1" required>
          <div class="input-group-text bg-white">
            <input class="form-check-input mt-0" type="radio" name="questions[${idx}][correct]" value="A" required checked>
          </div>
          <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
             <i class="bi bi-x-lg">✕</i>
          </button>
        </div>
        
        <div class="option-row input-group">
          <span class="input-group-text bg-light fw-bold text-secondary" style="width: 40px; justify-content: center;">B</span>
          <input type="text" class="form-control" name="questions[${idx}][b]" placeholder="Option 2" required>
          <div class="input-group-text bg-white">
            <input class="form-check-input mt-0" type="radio" name="questions[${idx}][correct]" value="B" required>
          </div>
          <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
             <i class="bi bi-x-lg">✕</i>
          </button>
        </div>

        <div class="option-row input-group">
          <span class="input-group-text bg-light fw-bold text-secondary" style="width: 40px; justify-content: center;">C</span>
          <input type="text" class="form-control" name="questions[${idx}][c]" placeholder="Option 3" required>
          <div class="input-group-text bg-white">
            <input class="form-check-input mt-0" type="radio" name="questions[${idx}][correct]" value="C" required>
          </div>
          <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
             <i class="bi bi-x-lg">✕</i>
          </button>
        </div>

        <div class="option-row input-group">
          <span class="input-group-text bg-light fw-bold text-secondary" style="width: 40px; justify-content: center;">D</span>
          <input type="text" class="form-control" name="questions[${idx}][d]" placeholder="Option 4" required>
          <div class="input-group-text bg-white">
            <input class="form-check-input mt-0" type="radio" name="questions[${idx}][correct]" value="D" required>
          </div>
          <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
             <i class="bi bi-x-lg">✕</i>
          </button>
        </div>
      </div>
      
      <div class="d-flex justify-content-end mt-3">
        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="addOption(${idx})">+ Add Option</button>
      </div>
    </div>
    <input type="hidden" name="questions[${idx}][type]" value="multiple">
  `;
  container.appendChild(card);
  scrollIntoViewSmooth(card);
}

function addEssay(){
  qIndex++;
  const idx = qIndex;
  const container = document.getElementById('questions-container');

  const card = document.createElement('div');
  card.className = 'card border-0 shadow-sm';
  card.id = `qcard-${idx}`;
  card.innerHTML = `
    <div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-start mb-4">
        <div class="d-flex align-items-center gap-3">
          <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">Question ${idx}</span>
          <div>
            <h6 class="fw-bold mb-0 text-dark">Essay</h6>
            <small class="text-muted">Open-ended question — students will type their answer</small>
          </div>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="duplicateQuestion(${idx})">Duplicate</button>
          <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" onclick="removeQuestion(${idx})">Delete</button>
        </div>
      </div>

      <div class="form-floating mb-3">
        <textarea name="questions[${idx}][text]" required class="form-control" placeholder="Enter your question here..." style="height: 100px"></textarea>
        <label>Question Text *</label>
      </div>

      <div class="mb-4">
        <label class="form-label small text-muted">Question Image (Optional)</label>
        <input class="form-control" type="file" id="qimg-${idx}" name="questions[${idx}][image]" accept="image/*">
        <div class="form-text small">PNG, JPG, GIF up to 10MB</div>
      </div>
    </div>
    <input type="hidden" name="questions[${idx}][type]" value="essay">
  `;
  container.appendChild(card);
  scrollIntoViewSmooth(card);
}

function addOption(qid){
  const list = document.getElementById(`options-list-${qid}`);
  if(!list) return;
  const rows = list.querySelectorAll('.option-row').length;
  if(rows >= 6){
    alert('Maximum reached');
    return;
  }
  const nextLetter = String.fromCharCode(65 + rows);
  const inputName = `questions[${qid}][${nextLetter.toLowerCase()}]`;
  const radioName = `questions[${qid}][correct]`;

  const row = document.createElement('div');
  row.className = 'option-row input-group';
  row.innerHTML = `
    <span class="input-group-text bg-light fw-bold text-secondary" style="width: 40px; justify-content: center;">${nextLetter}</span>
    <input type="text" class="form-control" name="${inputName}" placeholder="Option ${rows+1}">
    <div class="input-group-text bg-white">
      <input class="form-check-input mt-0" type="radio" name="${radioName}" value="${nextLetter}">
    </div>
    <button type="button" class="btn btn-outline-danger" onclick="removeOption(this)">
        <i class="bi bi-x-lg">✕</i>
    </button>
  `;
  list.appendChild(row);
}

function removeOption(btn){
  const row = btn.closest('.option-row');
  if(!row) return;
  row.parentNode.removeChild(row);
}

function removeQuestion(idx){
  if(!confirm('Delete this question?')) return;
  const card = document.getElementById(`qcard-${idx}`);
  if(card) card.remove();
}

function duplicateQuestion(idx){
  const original = document.getElementById(`qcard-${idx}`);
  if(!original) return;
  qIndex++;
  const clone = original.cloneNode(true);
  const newIdx = qIndex;
  clone.id = `qcard-${newIdx}`;
  
  // Update badge
  const badge = clone.querySelector('.badge');
  if(badge) badge.textContent = `Question ${newIdx}`;
  
  // Update inputs
  const inputs = clone.querySelectorAll('input,textarea,select');
  inputs.forEach(el=>{
    if(el.name){
      el.name = el.name.replace(`[${idx}]`, `[${newIdx}]`);
      if(el.type === 'radio' || el.type === 'checkbox') el.checked = false;
      if(el.type==='file') el.value = '';
      
      // Clear values for ID if duplicated, so it's treated as new
      if(el.name.includes('[id]')) {
          el.value = '';
      }
    }
  });
  
  // Update duplicate/delete buttons onclick
  const buttons = clone.querySelectorAll('button');
  buttons.forEach(btn => {
    const onclick = btn.getAttribute('onclick');
    if(onclick) {
        if(onclick.includes('duplicateQuestion')) {
            btn.setAttribute('onclick', `duplicateQuestion(${newIdx})`);
        } else if (onclick.includes('removeQuestion')) {
             btn.setAttribute('onclick', `removeQuestion(${newIdx})`);
        } else if (onclick.includes('addOption')) {
            btn.setAttribute('onclick', `addOption(${newIdx})`);
        }
    }
  });

  // Update IDs for options-list if it exists
  const optionsList = clone.querySelector(`[id^="options-list-"]`);
  if(optionsList) {
      optionsList.id = `options-list-${newIdx}`;
  }

  original.parentNode.insertBefore(clone, original.nextSibling);
  scrollIntoViewSmooth(clone);
}

function scrollIntoViewSmooth(el){
  setTimeout(()=>{el.scrollIntoView({behavior:'smooth',block:'center'})},120);
}
