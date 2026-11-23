/**
 * Portfolio Client Script
 * Fetches data from /api/content, applies theme tokens, and dynamically renders sections.
 */

let siteContent = null;
let currentTheme = 'dark';

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  loadContent();
  setupNavigation();
  setupContactForm();
  setupResumeModal();
});

/* ==========================================================================
   THEME HANDLING
   ========================================================================== */
function initTheme() {
  const savedTheme = localStorage.getItem('theme_preference');
  if (savedTheme) {
    currentTheme = savedTheme;
  } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
    currentTheme = 'light';
  } else {
    currentTheme = 'dark';
  }
  document.documentElement.setAttribute('data-theme', currentTheme);

  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', toggleTheme);
  }
}

function toggleTheme() {
  currentTheme = currentTheme === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', currentTheme);
  localStorage.setItem('theme_preference', currentTheme);
  if (siteContent && siteContent.theme && siteContent.theme.tokens) {
    applyThemeTokens(siteContent.theme.tokens[currentTheme]);
  }
}

function applyThemeTokens(tokens) {
  if (!tokens) return;
  const root = document.documentElement;
  if (tokens.bgPrimary) root.style.setProperty('--bg-primary', tokens.bgPrimary);
  if (tokens.bgSurface) root.style.setProperty('--bg-surface', tokens.bgSurface);
  if (tokens.bgElevated) root.style.setProperty('--bg-elevated', tokens.bgElevated);
  if (tokens.textPrimary) root.style.setProperty('--text-primary', tokens.textPrimary);
  if (tokens.textMuted) root.style.setProperty('--text-muted', tokens.textMuted);
  if (tokens.borderColor) root.style.setProperty('--border-color', tokens.borderColor);
  if (tokens.accentColor) root.style.setProperty('--accent-color', tokens.accentColor);
  if (tokens.accentHover) root.style.setProperty('--accent-hover', tokens.accentHover);
}

/* ==========================================================================
   CONTENT LOADING & RENDERING
   ========================================================================== */
async function loadContent() {
  try {
    const res = await fetch('/api/content?t=' + Date.now());
    if (!res.ok) throw new Error('Failed to load portfolio content');
    siteContent = await res.json();
    renderPage(siteContent);
  } catch (err) {
    console.error('Error loading portfolio content:', err);
  }
}

function renderPage(data) {
  // 1. Meta & Tokens
  if (data.meta) {
    if (data.meta.siteTitle) document.title = data.meta.siteTitle;
    const metaDesc = document.querySelector('meta[name="description"]');
    if (metaDesc && data.meta.metaDescription) {
      metaDesc.setAttribute('content', data.meta.metaDescription);
    }
    const footerText = document.getElementById('footerText');
    if (footerText && data.meta.footerText) {
      footerText.textContent = data.meta.footerText;
    }
  }

  // Apply theme tokens
  if (data.theme && data.theme.tokens) {
    applyThemeTokens(data.theme.tokens[currentTheme]);
  }

  // 2. Hero Section
  const heroSec = document.getElementById('hero');
  if (data.hero && data.hero.enabled !== false) {
    heroSec.style.display = '';
    setText('heroName', data.hero.name || 'M. Rohith');
    setText('heroTitle', data.hero.title || 'Frontend Developer');
    setText('heroTagline', data.hero.tagline || '');
    setText('heroStatusText', data.hero.status || 'Available for opportunities');

    const brandLogo = document.getElementById('brandLogo');
    if (brandLogo && data.hero.name) {
      const parts = data.hero.name.split(' ');
      if (parts.length > 1) {
        brandLogo.innerHTML = `<span class="brand-accent">${escapeHtml(parts[0])}</span> ${escapeHtml(parts.slice(1).join(' '))}`;
      } else {
        brandLogo.textContent = data.hero.name;
      }
    }

    const ctaPrimary = document.getElementById('heroCtaPrimary');
    if (ctaPrimary) {
      ctaPrimary.textContent = data.hero.ctaText || 'Explore Projects';
      ctaPrimary.setAttribute('href', data.hero.ctaLink || '#projects');
    }

    const ctaSecondary = document.getElementById('heroCtaSecondary');
    if (ctaSecondary) {
      ctaSecondary.textContent = data.hero.secondaryCtaText || 'Get in Touch';
      ctaSecondary.setAttribute('href', data.hero.secondaryCtaLink || '#contact');
    }

    const heroImg = document.getElementById('heroImage');
    if (heroImg && data.hero.image) {
      heroImg.src = data.hero.image;
      heroImg.alt = `${data.hero.name || 'Profile'} — ${data.hero.title || 'Portrait'}`;
    }
  } else {
    heroSec.style.display = 'none';
  }

  // 3. About Section
  const aboutSec = document.getElementById('about');
  if (data.about && data.about.enabled !== false) {
    aboutSec.style.display = '';
    setText('aboutTitle', data.about.sectionTitle || 'About Me');
    setText('aboutSubtitle', data.about.sectionSubtitle || '');
    setText('aboutBio', data.about.bio || '');
    setText('aboutLocation', data.about.location || 'Chennai, India');
    setText('aboutAvailability', data.about.availability || 'Available');

    const highlightsContainer = document.getElementById('aboutHighlights');
    if (highlightsContainer && Array.isArray(data.about.highlights)) {
      highlightsContainer.innerHTML = data.about.highlights.map(h => `
        <article class="highlight-card">
          <h3 class="highlight-card-title">${escapeHtml(h.title)}</h3>
          <p class="highlight-card-desc">${escapeHtml(h.description)}</p>
        </article>
      `).join('');
    }
  } else {
    aboutSec.style.display = 'none';
  }

  // 4. Skills Section
  const skillsSec = document.getElementById('skills');
  if (data.skills && data.skills.enabled !== false && Array.isArray(data.skills.categories)) {
    skillsSec.style.display = '';
    setText('skillsTitle', data.skills.sectionTitle || 'Skills & Technologies');
    setText('skillsSubtitle', data.skills.sectionSubtitle || '');

    const categoriesContainer = document.getElementById('skillsCategories');
    if (categoriesContainer) {
      categoriesContainer.innerHTML = data.skills.categories.map(cat => `
        <div class="skill-category-card">
          <h3 class="skill-category-name">${escapeHtml(cat.name)}</h3>
          <ul class="skill-tags-list">
            ${(cat.skills || []).map(s => `<li class="skill-tag">${escapeHtml(s)}</li>`).join('')}
          </ul>
        </div>
      `).join('');
    }
  } else {
    skillsSec.style.display = 'none';
  }

  // 5. Education Section
  const eduSec = document.getElementById('education');
  if (data.education && data.education.enabled !== false && Array.isArray(data.education.entries)) {
    eduSec.style.display = '';
    setText('educationTitle', data.education.sectionTitle || 'Education');
    setText('educationSubtitle', data.education.sectionSubtitle || '');

    const timelineContainer = document.getElementById('educationTimeline');
    if (timelineContainer) {
      timelineContainer.innerHTML = data.education.entries.map(entry => `
        <article class="education-entry">
          <div class="edu-header">
            <h3 class="edu-degree">${escapeHtml(entry.degree)}</h3>
            <span class="edu-period">${escapeHtml(entry.period)}</span>
          </div>
          <p class="edu-institution">${escapeHtml(entry.institution)}</p>
          ${entry.score ? `<p class="edu-score">Grade / Result: <strong>${escapeHtml(entry.score)}</strong></p>` : ''}
          ${entry.description ? `<p class="edu-description">${escapeHtml(entry.description)}</p>` : ''}
        </article>
      `).join('');
    }
  } else {
    eduSec.style.display = 'none';
  }

  // 6. Projects Section
  const projSec = document.getElementById('projects');
  if (data.projects && data.projects.enabled !== false && Array.isArray(data.projects.items)) {
    projSec.style.display = '';
    setText('projectsTitle', data.projects.sectionTitle || 'Featured Projects');
    setText('projectsSubtitle', data.projects.sectionSubtitle || '');

    const projectsGrid = document.getElementById('projectsGrid');
    if (projectsGrid) {
      projectsGrid.innerHTML = data.projects.items.map(proj => `
        <article class="project-card">
          <div class="project-thumb">
            <img src="${escapeHtml(proj.image || '/uploads/project-portfolio.svg')}" alt="${escapeHtml(proj.title)}" loading="lazy">
          </div>
          <div class="project-content">
            <h3 class="project-title">${escapeHtml(proj.title)}</h3>
            <p class="project-desc">${escapeHtml(proj.description)}</p>
            <ul class="project-tags">
              ${(proj.tags || []).map(t => `<li class="project-tag">${escapeHtml(t)}</li>`).join('')}
            </ul>
            <div class="project-links">
              ${proj.liveUrl && proj.liveUrl !== '#' ? `
                <a href="${escapeHtml(proj.liveUrl)}" target="_blank" rel="noopener noreferrer" class="project-link">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                    <polyline points="15 3 21 3 21 9"></polyline>
                    <line x1="10" y1="14" x2="21" y2="3"></line>
                  </svg>
                  <span>Live Preview</span>
                </a>
              ` : ''}
              ${proj.repoUrl && proj.repoUrl !== '#' ? `
                <a href="${escapeHtml(proj.repoUrl)}" target="_blank" rel="noopener noreferrer" class="project-link">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"></path>
                  </svg>
                  <span>Source Code</span>
                </a>
              ` : ''}
            </div>
          </div>
        </article>
      `).join('');
    }
  } else {
    projSec.style.display = 'none';
  }

  // 7. Resume Section
  const resumeSec = document.getElementById('resume');
  if (data.resume && data.resume.enabled !== false) {
    resumeSec.style.display = '';
    setText('resumeTitle', data.resume.sectionTitle || 'Resume');
    setText('resumeDescription', data.resume.description || 'Download or inspect my current resume.');

    const downloadBtn = document.getElementById('resumeDownloadBtn');
    if (downloadBtn) {
      downloadBtn.href = data.resume.file || '/uploads/resume.pdf';
    }

    const inlineFrame = document.getElementById('resumeInlineFrame');
    if (inlineFrame) {
      inlineFrame.src = data.resume.file || '/uploads/resume.pdf';
    }

    const previewPanel = document.getElementById('resumePreviewEmbedWrapper');
    if (previewPanel) {
      if (data.resume.displayMode === 'download') {
        previewPanel.style.display = 'none';
      } else {
        previewPanel.style.display = '';
      }
    }
  } else {
    resumeSec.style.display = 'none';
  }

  // 8. Contact Section
  const contactSec = document.getElementById('contact');
  if (data.contact && data.contact.enabled !== false) {
    contactSec.style.display = '';
    setText('contactTitle', data.contact.sectionTitle || "Let's Connect");
    setText('contactSubtitle', data.contact.sectionSubtitle || '');

    const contactEmail = document.getElementById('contactEmail');
    if (contactEmail && data.contact.email) {
      contactEmail.textContent = data.contact.email;
      contactEmail.href = `mailto:${data.contact.email}`;
    }

    const socialsContainer = document.getElementById('socialLinksList');
    if (socialsContainer && Array.isArray(data.contact.socials)) {
      socialsContainer.innerHTML = data.contact.socials.map(soc => `
        <a href="${escapeHtml(soc.url)}" target="_blank" rel="noopener noreferrer" class="social-pill">
          <span>${escapeHtml(soc.platform)}</span>
          <span class="social-handle">${escapeHtml(soc.username || 'Visit')} ↗</span>
        </a>
      `).join('');
    }

    const formWrapper = document.getElementById('contactFormWrapper');
    if (formWrapper) {
      formWrapper.style.display = data.contact.formEnabled !== false ? '' : 'none';
    }
  } else {
    contactSec.style.display = 'none';
  }
}

/* ==========================================================================
   NAVIGATION & MOBILE MENU
   ========================================================================== */
function setupNavigation() {
  const menuToggle = document.getElementById('mobileMenuToggle');
  const siteNav = document.getElementById('siteNav');

  if (menuToggle && siteNav) {
    menuToggle.addEventListener('click', () => {
      const isOpen = siteNav.classList.toggle('open');
      menuToggle.setAttribute('aria-expanded', String(isOpen));
    });

    siteNav.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', () => {
        siteNav.classList.remove('open');
        menuToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }
}

/* ==========================================================================
   CONTACT FORM SUBMISSION
   ========================================================================== */
function setupContactForm() {
  const form = document.getElementById('contactForm');
  const statusNotice = document.getElementById('formStatusNotice');
  const submitBtn = document.getElementById('submitFormBtn');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const name = form.name.value.trim();
    const email = form.email.value.trim();
    const subject = form.subject.value.trim();
    const message = form.message.value.trim();

    if (!name || !email || !message) {
      showStatus('Please fill in your name, email, and message.', 'error');
      return;
    }

    submitBtn.disabled = true;
    submitBtn.querySelector('span').textContent = 'Sending...';

    try {
      const res = await fetch('/api/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, subject, message })
      });

      const result = await res.json();
      if (!res.ok) throw new Error(result.error || 'Failed to send message');

      showStatus(result.message || 'Thank you! Your message has been sent successfully.', 'success');
      form.reset();
    } catch (err) {
      showStatus(err.message || 'Error sending message. Please try again later.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.querySelector('span').textContent = 'Send Message';
    }
  });

  function showStatus(msg, type) {
    statusNotice.textContent = msg;
    statusNotice.className = `form-status ${type}`;
    statusNotice.style.display = 'block';
    setTimeout(() => {
      statusNotice.style.display = 'none';
    }, 6000);
  }
}

/* ==========================================================================
   RESUME PREVIEW MODAL
   ========================================================================== */
function setupResumeModal() {
  const previewBtn = document.getElementById('resumePreviewBtn');
  const modal = document.getElementById('resumeModalBackdrop');
  const closeBtn = document.getElementById('closeResumeModalBtn');
  const modalFrame = document.getElementById('modalResumeFrame');

  if (!previewBtn || !modal || !closeBtn) return;

  previewBtn.addEventListener('click', () => {
    const filePath = (siteContent && siteContent.resume && siteContent.resume.file) || '/uploads/resume.pdf';
    modalFrame.src = filePath;
    modal.style.display = 'flex';
  });

  closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
    modalFrame.src = '';
  });

  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.style.display = 'none';
      modalFrame.src = '';
    }
  });
}

/* Helper Utilities */
function setText(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
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
