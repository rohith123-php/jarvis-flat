/**
 * Admin Panel Application Logic
 * Authentication, Form Bindings, Real-time Theme Controls, File Uploads, and Inbox Management.
 */

let appContent = null;
let currentTab = 'tab-hero';

document.addEventListener('DOMContentLoaded', () => {
  initAuth();
  setupTabs();
  setupGlobalActions();
  setupPasswordForm();
  setupProfileUpload();
  setupResumeUpload();
});

/* ==========================================================================
   AUTHENTICATION & SESSION
   ========================================================================== */
async function initAuth() {
  const loginForm = document.getElementById('loginForm');
  loginForm.addEventListener('submit', handleLogin);

  const logoutBtn = document.getElementById('logoutBtn');
  logoutBtn.addEventListener('click', handleLogout);

  try {
    const res = await fetch('/api/admin/status');
    const data = await res.json();
    if (data.authenticated) {
      showDashboard();
    } else {
      showLogin();
    }
  } catch (err) {
    showLogin();
  }
}

async function handleLogin(e) {
  e.preventDefault();
  const username = document.getElementById('loginUsername').value.trim();
  const password = document.getElementById('loginPassword').value;
  const errorBox = document.getElementById('loginError');
  const submitBtn = document.getElementById('loginSubmitBtn');

  errorBox.style.display = 'none';
  submitBtn.disabled = true;
  submitBtn.querySelector('span').textContent = 'Authenticating...';

  try {
    const res = await fetch('/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });
    const result = await res.json();

    if (!res.ok) throw new Error(result.error || 'Authentication failed');

    showDashboard();
  } catch (err) {
    errorBox.textContent = err.message;
    errorBox.style.display = 'block';
  } finally {
    submitBtn.disabled = false;
    submitBtn.querySelector('span').textContent = 'Authenticate & Access Dashboard';
  }
}

async function handleLogout() {
  try {
    await fetch('/api/admin/logout', { method: 'POST' });
  } catch (e) {}
  showLogin();
}

function showLogin() {
  document.getElementById('loginScreen').style.display = 'flex';
  document.getElementById('adminApp').style.display = 'none';
}

function showDashboard() {
  document.getElementById('loginScreen').style.display = 'none';
  document.getElementById('adminApp').style.display = 'flex';
  loadContentData();
  loadInboxMessages();
}

/* ==========================================================================
   TAB NAVIGATION
   ========================================================================== */
function setupTabs() {
  const tabs = document.querySelectorAll('.nav-tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      const target = tab.getAttribute('data-tab');
      tabs.forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

      tab.classList.add('active');
      const panel = document.getElementById(target);
      if (panel) panel.classList.add('active');
      currentTab = target;

      if (target === 'tab-inbox') {
        loadInboxMessages();
      }
    });
  });
}

/* ==========================================================================
   CONTENT LOADING & POPULATING
   ========================================================================== */
async function loadContentData() {
  try {
    const res = await fetch('/api/content?t=' + Date.now());
    if (!res.ok) throw new Error('Failed to load site content');
    appContent = await res.json();
    populateForms(appContent);
    showToast('Loaded site content successfully', 'success');
  } catch (err) {
    showToast('Error loading content: ' + err.message, 'error');
  }
}

function populateForms(data) {
  if (!data) return;

  // 1. Hero
  const hero = data.hero || {};
  setCheckbox('heroEnabled', hero.enabled !== false);
  setInputValue('heroNameInput', hero.name || '');
  setInputValue('heroTitleInput', hero.title || '');
  setInputValue('heroStatusInput', hero.status || '');
  setInputValue('heroTaglineInput', hero.tagline || '');
  setInputValue('heroCtaTextInput', hero.ctaText || '');
  setInputValue('heroCtaLinkInput', hero.ctaLink || '');
  setInputValue('heroSecondaryCtaTextInput', hero.secondaryCtaText || '');
  setInputValue('heroSecondaryCtaLinkInput', hero.secondaryCtaLink || '');
  if (hero.image) {
    document.getElementById('heroImagePreview').src = hero.image;
  }

  // 2. About
  const about = data.about || {};
  setCheckbox('aboutEnabled', about.enabled !== false);
  setInputValue('aboutTitleInput', about.sectionTitle || '');
  setInputValue('aboutSubtitleInput', about.sectionSubtitle || '');
  setInputValue('aboutBioInput', about.bio || '');
  setInputValue('aboutLocationInput', about.location || '');
  setInputValue('aboutAvailabilityInput', about.availability || '');
  renderHighlightsEditor(about.highlights || []);

  // 3. Skills
  const skills = data.skills || {};
  setCheckbox('skillsEnabled', skills.enabled !== false);
  setInputValue('skillsTitleInput', skills.sectionTitle || '');
  setInputValue('skillsSubtitleInput', skills.sectionSubtitle || '');
  renderSkillsCategoriesEditor(skills.categories || []);

  // 4. Education
  const education = data.education || {};
  setCheckbox('educationEnabled', education.enabled !== false);
  setInputValue('educationTitleInput', education.sectionTitle || '');
  setInputValue('educationSubtitleInput', education.sectionSubtitle || '');
  renderEducationEditor(education.entries || []);

  // 5. Projects
  const projects = data.projects || {};
  setCheckbox('projectsEnabled', projects.enabled !== false);
  setInputValue('projectsTitleInput', projects.sectionTitle || '');
  setInputValue('projectsSubtitleInput', projects.sectionSubtitle || '');
  renderProjectsEditor(projects.items || []);

  // 6. Resume
  const resume = data.resume || {};
  setCheckbox('resumeEnabled', resume.enabled !== false);
  setInputValue('resumeTitleInput', resume.sectionTitle || '');
  setInputValue('resumeDescInput', resume.description || '');
  setInputValue('resumeDisplayModeSelect', resume.displayMode || 'both');
  document.getElementById('resumeCurrentPathText').textContent = resume.file || '/uploads/resume.pdf';
  document.getElementById('previewResumeFileLink').href = resume.file || '/uploads/resume.pdf';

  // 7. Contact
  const contact = data.contact || {};
  setCheckbox('contactEnabled', contact.enabled !== false);
  setInputValue('contactTitleInput', contact.sectionTitle || '');
  setInputValue('contactEmailInput', contact.email || '');
  setCheckbox('contactFormEnabled', contact.formEnabled !== false);
  renderSocialsEditor(contact.socials || []);

  // 8. Theme Tokens
  if (data.theme && data.theme.tokens) {
    renderTokenFields('dark', data.theme.tokens.dark || {});
    renderTokenFields('light', data.theme.tokens.light || {});
  }
}

/* ==========================================================================
   HIGHLIGHTS (ABOUT TAB)
   ========================================================================== */
function renderHighlightsEditor(highlights) {
  const container = document.getElementById('highlightsList');
  container.innerHTML = highlights.map((h, i) => `
    <div class="editor-card" data-index="${i}">
      <div class="editor-card-header">
        <span class="card-order-title">Highlight #${i + 1}</span>
        <button type="button" class="remove-btn" onclick="removeHighlight(${i})">Remove</button>
      </div>
      <div class="form-field">
        <label>Title</label>
        <input type="text" class="hl-title-input" value="${escapeAttr(h.title || '')}">
      </div>
      <div class="form-field">
        <label>Description</label>
        <textarea rows="2" class="hl-desc-input">${escapeHtml(h.description || '')}</textarea>
      </div>
    </div>
  `).join('');

  const addBtn = document.getElementById('addHighlightBtn');
  addBtn.onclick = () => {
    if (!appContent.about.highlights) appContent.about.highlights = [];
    appContent.about.highlights.push({ title: 'New Highlight', description: 'Description here...' });
    renderHighlightsEditor(appContent.about.highlights);
  };
}

window.removeHighlight = function(index) {
  if (appContent && appContent.about && appContent.about.highlights) {
    appContent.about.highlights.splice(index, 1);
    renderHighlightsEditor(appContent.about.highlights);
  }
};

/* ==========================================================================
   SKILLS & CATEGORIES TAB
   ========================================================================== */
function renderSkillsCategoriesEditor(categories) {
  const container = document.getElementById('skillsCategoriesList');
  container.innerHTML = categories.map((cat, i) => `
    <div class="editor-card" data-index="${i}">
      <div class="editor-card-header">
        <span class="card-order-title">Category #${i + 1}</span>
        <button type="button" class="remove-btn" onclick="removeSkillCategory(${i})">Remove</button>
      </div>
      <div class="form-field">
        <label>Category Name</label>
        <input type="text" class="skill-cat-name-input" value="${escapeAttr(cat.name || '')}">
      </div>
      <div class="form-field">
        <label>Skills List (comma-separated)</label>
        <input type="text" class="skill-cat-items-input" value="${escapeAttr((cat.skills || []).join(', '))}">
      </div>
    </div>
  `).join('');

  const addBtn = document.getElementById('addSkillCategoryBtn');
  addBtn.onclick = () => {
    if (!appContent.skills.categories) appContent.skills.categories = [];
    appContent.skills.categories.push({ name: 'New Category', skills: ['Skill 1', 'Skill 2'] });
    renderSkillsCategoriesEditor(appContent.skills.categories);
  };
}

window.removeSkillCategory = function(index) {
  if (appContent && appContent.skills && appContent.skills.categories) {
    appContent.skills.categories.splice(index, 1);
    renderSkillsCategoriesEditor(appContent.skills.categories);
  }
};

/* ==========================================================================
   EDUCATION TAB
   ========================================================================== */
function renderEducationEditor(entries) {
  const container = document.getElementById('educationEntriesList');
  container.innerHTML = entries.map((edu, i) => `
    <div class="editor-card" data-index="${i}">
      <div class="editor-card-header">
        <span class="card-order-title">Education #${i + 1}</span>
        <button type="button" class="remove-btn" onclick="removeEducationEntry(${i})">Remove</button>
      </div>
      <div class="field-row-2">
        <div class="form-field">
          <label>Degree / Qualification</label>
          <input type="text" class="edu-degree-input" value="${escapeAttr(edu.degree || '')}">
        </div>
        <div class="form-field">
          <label>Institution / College</label>
          <input type="text" class="edu-institution-input" value="${escapeAttr(edu.institution || '')}">
        </div>
      </div>
      <div class="field-row-2">
        <div class="form-field">
          <label>Period / Year (e.g. 2026 - Present)</label>
          <input type="text" class="edu-period-input" value="${escapeAttr(edu.period || '')}">
        </div>
        <div class="form-field">
          <label>Grade / Score</label>
          <input type="text" class="edu-score-input" value="${escapeAttr(edu.score || '')}">
        </div>
      </div>
      <div class="form-field">
        <label>Description / Notes</label>
        <textarea rows="2" class="edu-desc-input">${escapeHtml(edu.description || '')}</textarea>
      </div>
    </div>
  `).join('');

  const addBtn = document.getElementById('addEducationBtn');
  addBtn.onclick = () => {
    if (!appContent.education.entries) appContent.education.entries = [];
    appContent.education.entries.push({
      id: 'edu-' + Date.now(),
      degree: 'Course / Degree',
      institution: 'Institution',
      period: 'Year',
      score: '',
      description: ''
    });
    renderEducationEditor(appContent.education.entries);
  };
}

window.removeEducationEntry = function(index) {
  if (appContent && appContent.education && appContent.education.entries) {
    appContent.education.entries.splice(index, 1);
    renderEducationEditor(appContent.education.entries);
  }
};

/* ==========================================================================
   PROJECTS TAB
   ========================================================================== */
function renderProjectsEditor(projects) {
  const container = document.getElementById('projectsEditorList');
  container.innerHTML = projects.map((proj, i) => `
    <div class="editor-card" data-index="${i}">
      <div class="editor-card-header">
        <span class="card-order-title">Project #${i + 1}: ${escapeHtml(proj.title || 'Untitled')}</span>
        <button type="button" class="remove-btn" onclick="removeProjectEntry(${i})">Remove Project</button>
      </div>

      <div class="field-row-2">
        <div class="form-field">
          <label>Project Title</label>
          <input type="text" class="proj-title-input" value="${escapeAttr(proj.title || '')}">
        </div>
        <div class="form-field">
          <label>Tech Stack Tags (comma-separated)</label>
          <input type="text" class="proj-tags-input" value="${escapeAttr((proj.tags || []).join(', '))}">
        </div>
      </div>

      <div class="form-field">
        <label>Description</label>
        <textarea rows="3" class="proj-desc-input">${escapeHtml(proj.description || '')}</textarea>
      </div>

      <div class="field-row-2">
        <div class="form-field">
          <label>Live Demo URL</label>
          <input type="text" class="proj-live-input" value="${escapeAttr(proj.liveUrl || '')}">
        </div>
        <div class="form-field">
          <label>Source Code / GitHub URL</label>
          <input type="text" class="proj-repo-input" value="${escapeAttr(proj.repoUrl || '')}">
        </div>
      </div>

      <div class="form-field">
        <label>Thumbnail / Cover Image</label>
        <div style="display: flex; gap: 1rem; align-items: center;">
          <img class="proj-img-preview" src="${escapeAttr(proj.image || '/uploads/project-portfolio.svg')}" style="width: 120px; height: 70px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
          <input type="file" class="proj-file-input" accept="image/*" style="display: none;" onchange="handleProjectImageUpload(event, ${i})">
          <button type="button" class="btn btn-subtle btn-sm" onclick="this.previousElementSibling.click()">Replace Image</button>
          <input type="hidden" class="proj-img-path" value="${escapeAttr(proj.image || '')}">
        </div>
      </div>
    </div>
  `).join('');

  const addBtn = document.getElementById('addProjectBtn');
  addBtn.onclick = () => {
    if (!appContent.projects.items) appContent.projects.items = [];
    appContent.projects.items.push({
      id: 'proj-' + Date.now(),
      title: 'New Project',
      description: 'Project details and accomplishments.',
      tags: ['React', 'CSS'],
      image: '/uploads/project-portfolio.svg',
      liveUrl: '',
      repoUrl: '',
      featured: true
    });
    renderProjectsEditor(appContent.projects.items);
  };
}

window.removeProjectEntry = function(index) {
  if (appContent && appContent.projects && appContent.projects.items) {
    appContent.projects.items.splice(index, 1);
    renderProjectsEditor(appContent.projects.items);
  }
};

window.handleProjectImageUpload = async function(e, index) {
  const file = e.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('file', file);

  try {
    showToast('Uploading image...', 'info');
    const res = await fetch('/api/admin/upload', {
      method: 'POST',
      body: formData
    });
    const result = await res.json();
    if (!res.ok) throw new Error(result.error || 'Upload failed');

    appContent.projects.items[index].image = result.fileUrl;
    renderProjectsEditor(appContent.projects.items);
    showToast('Project image uploaded!', 'success');
  } catch (err) {
    showToast('Upload error: ' + err.message, 'error');
  }
};

/* ==========================================================================
   SOCIALS EDITOR
   ========================================================================== */
function renderSocialsEditor(socials) {
  const container = document.getElementById('socialsEditorList');
  container.innerHTML = socials.map((soc, i) => `
    <div class="editor-card" data-index="${i}">
      <div class="editor-card-header">
        <span class="card-order-title">Social #${i + 1}</span>
        <button type="button" class="remove-btn" onclick="removeSocialEntry(${i})">Remove</button>
      </div>
      <div class="field-row-2">
        <div class="form-field">
          <label>Platform Name</label>
          <input type="text" class="soc-platform-input" value="${escapeAttr(soc.platform || '')}">
        </div>
        <div class="form-field">
          <label>Display Handle / Label</label>
          <input type="text" class="soc-username-input" value="${escapeAttr(soc.username || '')}">
        </div>
      </div>
      <div class="form-field">
        <label>Profile URL</label>
        <input type="text" class="soc-url-input" value="${escapeAttr(soc.url || '')}">
      </div>
    </div>
  `).join('');

  const addBtn = document.getElementById('addSocialBtn');
  addBtn.onclick = () => {
    if (!appContent.contact.socials) appContent.contact.socials = [];
    appContent.contact.socials.push({ platform: 'GitHub', username: 'github.com/rohith', url: 'https://github.com' });
    renderSocialsEditor(appContent.contact.socials);
  };
}

window.removeSocialEntry = function(index) {
  if (appContent && appContent.contact && appContent.contact.socials) {
    appContent.contact.socials.splice(index, 1);
    renderSocialsEditor(appContent.contact.socials);
  }
};

/* ==========================================================================
   THEME TOKENS EDITOR
   ========================================================================== */
const TOKEN_KEYS = [
  { key: 'bgPrimary', label: 'Primary Background' },
  { key: 'bgSurface', label: 'Surface / Cards' },
  { key: 'bgElevated', label: 'Elevated Surface' },
  { key: 'textPrimary', label: 'Primary Text' },
  { key: 'textMuted', label: 'Muted Text' },
  { key: 'borderColor', label: 'Border Lines' },
  { key: 'accentColor', label: 'Accent / Brand' },
  { key: 'accentHover', label: 'Accent Hover State' }
];

function renderTokenFields(mode, tokens) {
  const container = document.getElementById(mode + 'TokenFields');
  if (!container) return;

  container.innerHTML = TOKEN_KEYS.map(item => {
    const val = tokens[item.key] || '#000000';
    return `
      <div class="token-row">
        <span class="token-label">${item.label}</span>
        <div class="token-input-group">
          <input type="color" class="token-color-picker" data-mode="${mode}" data-key="${item.key}" value="${val}">
          <input type="text" class="token-hex-input" data-mode="${mode}" data-key="${item.key}" value="${val}" maxlength="7">
        </div>
      </div>
    `;
  }).join('');

  // Sync color picker with hex input
  container.querySelectorAll('.token-color-picker').forEach(picker => {
    picker.addEventListener('input', (e) => {
      const mode = e.target.getAttribute('data-mode');
      const key = e.target.getAttribute('data-key');
      const hexInput = container.querySelector(`.token-hex-input[data-mode="${mode}"][data-key="${key}"]`);
      if (hexInput) hexInput.value = e.target.value.toUpperCase();
      if (appContent.theme.tokens[mode]) appContent.theme.tokens[mode][key] = e.target.value;
    });
  });

  container.querySelectorAll('.token-hex-input').forEach(input => {
    input.addEventListener('change', (e) => {
      let val = e.target.value.trim();
      if (!val.startsWith('#')) val = '#' + val;
      const mode = e.target.getAttribute('data-mode');
      const key = e.target.getAttribute('data-key');
      const picker = container.querySelector(`.token-color-picker[data-mode="${mode}"][data-key="${key}"]`);
      if (picker && /^#[0-9A-F]{6}$/i.test(val)) {
        picker.value = val;
        if (appContent.theme.tokens[mode]) appContent.theme.tokens[mode][key] = val;
      }
    });
  });

  // Reset to default button
  document.getElementById('resetThemeDefaultsBtn').onclick = () => {
    applyPreset('default');
  };
}

window.applyPreset = function(presetName) {
  if (!appContent || !appContent.theme || !appContent.theme.tokens) return;

  const presets = {
    default: {
      dark: { bgPrimary: '#0B0F17', bgSurface: '#131D31', bgElevated: '#1A2640', textPrimary: '#F1F5F9', textMuted: '#94A3B8', borderColor: '#1E2B45', accentColor: '#3B82F6', accentHover: '#2563EB' },
      light: { bgPrimary: '#F8FAFC', bgSurface: '#FFFFFF', bgElevated: '#F1F5F9', textPrimary: '#0F172A', textMuted: '#64748B', borderColor: '#E2E8F0', accentColor: '#2563EB', accentHover: '#1D4ED8' }
    },
    electricIndigo: {
      dark: { bgPrimary: '#0B0A1A', bgSurface: '#13112E', bgElevated: '#1D1A45', textPrimary: '#F5F3FF', textMuted: '#A5B4FC', borderColor: '#2E285F', accentColor: '#6366F1', accentHover: '#4F46E5' },
      light: { bgPrimary: '#FAF5FF', bgSurface: '#FFFFFF', bgElevated: '#F3E8FF', textPrimary: '#1E1B4B', textMuted: '#6B7280', borderColor: '#E9D5FF', accentColor: '#4F46E5', accentHover: '#4338CA' }
    },
    emeraldTech: {
      dark: { bgPrimary: '#061412', bgSurface: '#0B2320', bgElevated: '#11332F', textPrimary: '#ECFDF5', textMuted: '#6EE7B7', borderColor: '#164E46', accentColor: '#10B981', accentHover: '#059669' },
      light: { bgPrimary: '#F0FDF4', bgSurface: '#FFFFFF', bgElevated: '#DCFCE7', textPrimary: '#064E3B', textMuted: '#4B5563', borderColor: '#BBF7D0', accentColor: '#059669', accentHover: '#047857' }
    },
    amberWarmth: {
      dark: { bgPrimary: '#171108', bgSurface: '#241B0D', bgElevated: '#332714', textPrimary: '#FFFBEB', textMuted: '#FCD34D', borderColor: '#45351C', accentColor: '#F59E0B', accentHover: '#D97706' },
      light: { bgPrimary: '#FFFBEB', bgSurface: '#FFFFFF', bgElevated: '#FEF3C7', textPrimary: '#78350F', textMuted: '#6B7280', borderColor: '#FDE68A', accentColor: '#D97706', accentHover: '#B45309' }
    },
    monoMinimal: {
      dark: { bgPrimary: '#121212', bgSurface: '#1E1E1E', bgElevated: '#2A2A2A', textPrimary: '#EEEEEE', textMuted: '#9E9E9E', borderColor: '#333333', accentColor: '#FFFFFF', accentHover: '#CCCCCC' },
      light: { bgPrimary: '#FAFAFA', bgSurface: '#FFFFFF', bgElevated: '#F0F0F0', textPrimary: '#111111', textMuted: '#757575', borderColor: '#E0E0E0', accentColor: '#000000', accentHover: '#333333' }
    }
  };

  const selected = presets[presetName] || presets.default;
  appContent.theme.tokens.dark = { ...selected.dark };
  appContent.theme.tokens.light = { ...selected.light };
  renderTokenFields('dark', appContent.theme.tokens.dark);
  renderTokenFields('light', appContent.theme.tokens.light);
  showToast(`Applied preset: ${presetName}`, 'info');
};

/* ==========================================================================
   COLLECT ALL CHANGES & SAVE TO BACKEND
   ========================================================================== */
function setupGlobalActions() {
  document.getElementById('saveAllBtn').addEventListener('click', saveAllContent);
  document.getElementById('discardChangesBtn').addEventListener('click', () => {
    if (confirm('Discard any unsaved edits and reload current live content?')) {
      loadContentData();
    }
  });

  document.getElementById('restoreBackupBtn').addEventListener('click', async () => {
    if (confirm('Restore site content from prior automatic backup?')) {
      try {
        const res = await fetch('/api/admin/restore-backup', { method: 'POST' });
        const result = await res.json();
        if (!res.ok) throw new Error(result.error || 'Failed to restore backup');
        appContent = result.content;
        populateForms(appContent);
        showToast(result.message, 'success');
      } catch (err) {
        showToast('Restore error: ' + err.message, 'error');
      }
    }
  });
}

async function saveAllContent() {
  const saveBtn = document.getElementById('saveAllBtn');
  saveBtn.disabled = true;

  try {
    // 1. Gather Hero
    appContent.hero.enabled = getCheckbox('heroEnabled');
    appContent.hero.name = getInputValue('heroNameInput');
    appContent.hero.title = getInputValue('heroTitleInput');
    appContent.hero.status = getInputValue('heroStatusInput');
    appContent.hero.tagline = getInputValue('heroTaglineInput');
    appContent.hero.ctaText = getInputValue('heroCtaTextInput');
    appContent.hero.ctaLink = getInputValue('heroCtaLinkInput');
    appContent.hero.secondaryCtaText = getInputValue('heroSecondaryCtaTextInput');
    appContent.hero.secondaryCtaLink = getInputValue('heroSecondaryCtaLinkInput');

    // 2. Gather About
    appContent.about.enabled = getCheckbox('aboutEnabled');
    appContent.about.sectionTitle = getInputValue('aboutTitleInput');
    appContent.about.sectionSubtitle = getInputValue('aboutSubtitleInput');
    appContent.about.bio = getInputValue('aboutBioInput');
    appContent.about.location = getInputValue('aboutLocationInput');
    appContent.about.availability = getInputValue('aboutAvailabilityInput');

    const hlCards = document.querySelectorAll('#highlightsList .editor-card');
    appContent.about.highlights = Array.from(hlCards).map(card => ({
      title: card.querySelector('.hl-title-input').value.trim(),
      description: card.querySelector('.hl-desc-input').value.trim()
    }));

    // 3. Gather Skills
    appContent.skills.enabled = getCheckbox('skillsEnabled');
    appContent.skills.sectionTitle = getInputValue('skillsTitleInput');
    appContent.skills.sectionSubtitle = getInputValue('skillsSubtitleInput');

    const catCards = document.querySelectorAll('#skillsCategoriesList .editor-card');
    appContent.skills.categories = Array.from(catCards).map(card => ({
      name: card.querySelector('.skill-cat-name-input').value.trim(),
      skills: card.querySelector('.skill-cat-items-input').value.split(',').map(s => s.trim()).filter(Boolean)
    }));

    // 4. Gather Education
    appContent.education.enabled = getCheckbox('educationEnabled');
    appContent.education.sectionTitle = getInputValue('educationTitleInput');
    appContent.education.sectionSubtitle = getInputValue('educationSubtitleInput');

    const eduCards = document.querySelectorAll('#educationEntriesList .editor-card');
    appContent.education.entries = Array.from(eduCards).map((card, i) => ({
      id: (appContent.education.entries[i] && appContent.education.entries[i].id) || ('edu-' + i),
      degree: card.querySelector('.edu-degree-input').value.trim(),
      institution: card.querySelector('.edu-institution-input').value.trim(),
      period: card.querySelector('.edu-period-input').value.trim(),
      score: card.querySelector('.edu-score-input').value.trim(),
      description: card.querySelector('.edu-desc-input').value.trim()
    }));

    // 5. Gather Projects
    appContent.projects.enabled = getCheckbox('projectsEnabled');
    appContent.projects.sectionTitle = getInputValue('projectsTitleInput');
    appContent.projects.sectionSubtitle = getInputValue('projectsSubtitleInput');

    const projCards = document.querySelectorAll('#projectsEditorList .editor-card');
    appContent.projects.items = Array.from(projCards).map((card, i) => ({
      id: (appContent.projects.items[i] && appContent.projects.items[i].id) || ('proj-' + i),
      title: card.querySelector('.proj-title-input').value.trim(),
      description: card.querySelector('.proj-desc-input').value.trim(),
      tags: card.querySelector('.proj-tags-input').value.split(',').map(t => t.trim()).filter(Boolean),
      liveUrl: card.querySelector('.proj-live-input').value.trim(),
      repoUrl: card.querySelector('.proj-repo-input').value.trim(),
      image: card.querySelector('.proj-img-path').value.trim(),
      featured: true
    }));

    // 6. Gather Resume
    appContent.resume.enabled = getCheckbox('resumeEnabled');
    appContent.resume.sectionTitle = getInputValue('resumeTitleInput');
    appContent.resume.description = getInputValue('resumeDescInput');
    appContent.resume.displayMode = getInputValue('resumeDisplayModeSelect');

    // 7. Gather Contact
    appContent.contact.enabled = getCheckbox('contactEnabled');
    appContent.contact.sectionTitle = getInputValue('contactTitleInput');
    appContent.contact.email = getInputValue('contactEmailInput');
    appContent.contact.formEnabled = getCheckbox('contactFormEnabled');

    const socCards = document.querySelectorAll('#socialsEditorList .editor-card');
    appContent.contact.socials = Array.from(socCards).map(card => ({
      platform: card.querySelector('.soc-platform-input').value.trim(),
      username: card.querySelector('.soc-username-input').value.trim(),
      url: card.querySelector('.soc-url-input').value.trim()
    }));

    // Send PUT request
    const res = await fetch('/api/admin/content', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(appContent)
    });

    const result = await res.json();
    if (!res.ok) throw new Error(result.error || 'Failed to save changes');

    showToast('Changes published! Public site updated immediately.', 'success');
  } catch (err) {
    showToast('Save failed: ' + err.message, 'error');
  } finally {
    saveBtn.disabled = false;
  }
}

/* ==========================================================================
   IMAGE & FILE UPLOADS
   ========================================================================== */
function setupProfileUpload() {
  const input = document.getElementById('profileImageUploadInput');
  input.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
      showToast('Uploading profile image...', 'info');
      const res = await fetch('/api/admin/upload', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (!res.ok) throw new Error(result.error || 'Upload failed');

      appContent.hero.image = result.fileUrl;
      document.getElementById('heroImagePreview').src = result.fileUrl;
      showToast('Profile image updated! Remember to click Save Changes.', 'success');
    } catch (err) {
      showToast('Upload error: ' + err.message, 'error');
    }
  });
}

function setupResumeUpload() {
  const input = document.getElementById('resumeFileUploadInput');
  input.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    try {
      showToast('Uploading resume PDF...', 'info');
      const res = await fetch('/api/admin/upload', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (!res.ok) throw new Error(result.error || 'Upload failed');

      appContent.resume.file = result.fileUrl;
      document.getElementById('resumeCurrentPathText').textContent = result.fileUrl;
      document.getElementById('previewResumeFileLink').href = result.fileUrl;
      showToast('Resume PDF replaced! Remember to click Save Changes.', 'success');
    } catch (err) {
      showToast('Resume upload error: ' + err.message, 'error');
    }
  });
}

/* ==========================================================================
   MESSAGE INBOX
   ========================================================================== */
async function loadInboxMessages() {
  const listContainer = document.getElementById('inboxList');
  const badge = document.getElementById('inboxBadge');

  try {
    const res = await fetch('/api/admin/messages');
    const data = await res.json();
    const messages = data.messages || [];

    if (messages.length > 0) {
      badge.textContent = messages.length;
      badge.style.display = 'inline-block';
    } else {
      badge.style.display = 'none';
    }

    if (messages.length === 0) {
      listContainer.innerHTML = `
        <div class="editor-card" style="text-align: center; padding: 3rem 1rem;">
          <p style="color: var(--admin-muted);">No messages received yet. Inquiries submitted via your contact form will appear here.</p>
        </div>
      `;
      return;
    }

    listContainer.innerHTML = messages.map(msg => `
      <div class="message-card" id="msg-${msg.id}">
        <div class="message-card-header">
          <div>
            <div class="message-sender">${escapeHtml(msg.name)}</div>
            <div class="message-email"><a href="mailto:${escapeAttr(msg.email)}">${escapeHtml(msg.email)}</a></div>
          </div>
          <div style="display: flex; gap: 1rem; align-items: center;">
            <span class="message-date">${new Date(msg.receivedAt).toLocaleString()}</span>
            <button type="button" class="remove-btn" onclick="deleteMessage('${msg.id}')">Delete</button>
          </div>
        </div>
        <div class="message-subject">${escapeHtml(msg.subject || 'No Subject')}</div>
        <div class="message-body">${escapeHtml(msg.message)}</div>
      </div>
    `).join('');
  } catch (err) {
    listContainer.innerHTML = `<p style="color: var(--admin-danger);">Failed to load messages: ${err.message}</p>`;
  }
}

window.deleteMessage = async function(id) {
  if (!confirm('Delete this message permanently?')) return;
  try {
    const res = await fetch('/api/admin/messages/' + id, { method: 'DELETE' });
    if (!res.ok) throw new Error('Failed to delete message');
    const el = document.getElementById('msg-' + id);
    if (el) el.remove();
    showToast('Message deleted', 'info');
    loadInboxMessages();
  } catch (err) {
    showToast(err.message, 'error');
  }
};

document.getElementById('refreshInboxBtn').addEventListener('click', loadInboxMessages);

/* ==========================================================================
   SECURITY & PASSWORD
   ========================================================================== */
function setupPasswordForm() {
  const form = document.getElementById('changePasswordForm');
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const currentPassword = document.getElementById('currentPasswordInput').value;
    const newPassword = document.getElementById('newPasswordInput').value;
    const confirmNew = document.getElementById('confirmNewPasswordInput').value;

    if (newPassword !== confirmNew) {
      showToast('New passwords do not match!', 'error');
      return;
    }

    try {
      const res = await fetch('/api/admin/change-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ currentPassword, newPassword })
      });
      const result = await res.json();
      if (!res.ok) throw new Error(result.error || 'Password change failed');

      showToast(result.message, 'success');
      form.reset();
    } catch (err) {
      showToast(err.message, 'error');
    }
  });
}

/* ==========================================================================
   HELPERS & TOAST
   ========================================================================== */
function showToast(message, type = 'info') {
  const toast = document.getElementById('toastNotification');
  toast.textContent = message;
  toast.className = `toast-notification ${type}`;
  toast.style.display = 'block';
  setTimeout(() => {
    toast.style.display = 'none';
  }, 4000);
}

function getInputValue(id) {
  const el = document.getElementById(id);
  return el ? el.value.trim() : '';
}

function setInputValue(id, val) {
  const el = document.getElementById(id);
  if (el) el.value = val;
}

function getCheckbox(id) {
  const el = document.getElementById(id);
  return el ? el.checked : false;
}

function setCheckbox(id, val) {
  const el = document.getElementById(id);
  if (el) el.checked = Boolean(val);
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function escapeAttr(str) {
  if (!str) return '';
  return String(str)
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
