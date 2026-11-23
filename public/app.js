/**
 * Portfolio Client Script — Standard Clean Portfolio
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
  setupScrollspy();
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
      const btnSpan = ctaPrimary.querySelector('span');
      if (btnSpan) btnSpan.textContent = data.hero.ctaText || 'Explore Projects';
      else ctaPrimary.textContent = data.hero.ctaText || 'Explore Projects';
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
            <div>
              <h3 class="edu-degree">${escapeHtml(entry.degree)}</h3>
              <p class="edu-institution">${escapeHtml(entry.institution)}</p>
            </div>
            <span class="edu-period">${escapeHtml(entry.period)}</span>
          </div>
          ${entry.score ? `<p class="edu-score">Grade / Result: <strong>${escapeHtml(entry.score)}</strong></p>` : ''}
          ${entry.description ? `<p class="edu-description">${escapeHtml(entry.description)}</p>` : ''}
        </article>
      `).join('');
    }
  } else {
    eduSec.style.display = 'none';
  }

  // 6. Projects Section (Clean Standard Cards)
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
            <div class="project-category-row">
              <span class="project-category-badge">Website Design</span>
            </div>
            <h3 class="project-title">${escapeHtml(proj.title)}</h3>
            <p class="project-desc">${escapeHtml(proj.description)}</p>
            
            <ul class="project-tags">
              ${(proj.tags || []).map(t => `<li class="project-tag">${escapeHtml(t)}</li>`).join('')}
            </ul>

            <div class="project-footer-row">
              <div class="project-links">
                ${proj.liveUrl && proj.liveUrl !== '#' ? `
                  <a href="${escapeHtml(proj.liveUrl)}" target="_blank" rel="noopener noreferrer" class="project-link">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                      <polyline points="15 3 21 3 21 9"></polyline>
                      <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    <span>Repository</span>
                  </a>
                ` : ''}
                ${proj.repoUrl && proj.repoUrl !== '#' && proj.repoUrl !== proj.liveUrl ? `
                  <a href="${escapeHtml(proj.repoUrl)}" target="_blank" rel="noopener noreferrer" class="project-link">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                      <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                    </svg>
                    <span>Source</span>
                  </a>
                ` : ''}
              </div>
              <a href="${escapeHtml(proj.liveUrl || proj.repoUrl || '#')}" target="_blank" rel="noopener noreferrer" class="project-circle-btn" aria-label="Open ${escapeHtml(proj.title)}">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5">
                  <line x1="7" y1="17" x2="17" y2="7"></line>
                  <polyline points="7 7 17 7 17 17"></polyline>
                </svg>
              </a>
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
   NAVIGATION & SCROLLSPY
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

function setupScrollspy() {
  const sections = document.querySelectorAll('section[id]');
  const navLinks = document.querySelectorAll('.nav-link');

  window.addEventListener('scroll', () => {
    let currentId = '';
    const scrollPos = window.scrollY + 120;

    sections.forEach(sec => {
      const top = sec.offsetTop;
      const height = sec.offsetHeight;
      if (scrollPos >= top && scrollPos < top + height) {
        currentId = sec.getAttribute('id');
      }
    });

    navLinks.forEach(link => {
      link.classList.remove('active');
      if (currentId && link.getAttribute('href') === `#${currentId}`) {
        link.classList.add('active');
      }
    });
  }, { passive: true });
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
