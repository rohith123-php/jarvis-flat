/**
 * Portfolio Client Script — Modern Editorial Edition
 * Fetches data from /api/content, applies theme tokens, renders brand SVG icons,
 * browser mockups, and interactive copy/scrollspy micro-interactions.
 */

let siteContent = null;
let currentTheme = 'dark';

// SVG Brand & Tech Icons Dictionary
const SKILL_ICONS = {
  'HTML5': `<svg viewBox="0 0 24 24" width="15" height="15" fill="#E34F26"><path d="M1.5 0h21l-1.9 21.2L12 24l-8.6-2.8L1.5 0zm17 5.4H5.5l.5 5.5h11.9l-.6 6.3-5.3 1.5-5.3-1.5-.3-3.6H3.6l.6 6.5L12 22.3l7.8-2.2 1-11.4.1-3.3h-2.4z"/></svg>`,
  'CSS3': `<svg viewBox="0 0 24 24" width="15" height="15" fill="#1572B6"><path d="M1.5 0h21l-1.9 21.2L12 24l-8.6-2.8L1.5 0zm17 5.4H5.5l.5 5.5h11.9l-.6 6.3-5.3 1.5-5.3-1.5-.3-3.6H3.6l.6 6.5L12 22.3l7.8-2.2 1-11.4.1-3.3h-2.4z"/></svg>`,
  'CSS3 / Modern CSS': `<svg viewBox="0 0 24 24" width="15" height="15" fill="#2965F1"><path d="M1.5 0h21l-1.9 21.2L12 24l-8.6-2.8L1.5 0zm17 5.4H5.5l.5 5.5h11.9l-.6 6.3-5.3 1.5-5.3-1.5-.3-3.6H3.6l.6 6.5L12 22.3l7.8-2.2 1-11.4.1-3.3h-2.4z"/></svg>`,
  'JavaScript (ES6+)': `<svg viewBox="0 0 24 24" width="15" height="15" fill="#F7DF1E"><rect width="24" height="24" rx="4" fill="#F7DF1E"/><path d="M7 17.5c0 1.4.8 2.2 2.2 2.2.9 0 1.5-.4 1.8-1l-1.3-.8c-.2.4-.4.6-.7.6-.4 0-.6-.3-.6-.8v-6h-1.4v5zm7.3-6.3c-1.3 0-2.1.8-2.1 2 0 1.3.8 1.8 1.8 2.2.7.3 1 .5 1 .9 0 .4-.4.6-.9.6-.6 0-1-.3-1.2-.8l-1.3.7c.4.9 1.3 1.4 2.4 1.4 1.4 0 2.3-.8 2.3-2.1 0-1.2-.7-1.8-1.7-2.2-.6-.2-1-.4-1-.8 0-.3.3-.5.7-.5.5 0 .8.2 1 .6l1.2-.8c-.4-.7-1.1-1.2-2.2-1.2z" fill="#000"/></svg>`,
  'JavaScript': `<svg viewBox="0 0 24 24" width="15" height="15" fill="#F7DF1E"><rect width="24" height="24" rx="4" fill="#F7DF1E"/><path d="M7 17.5c0 1.4.8 2.2 2.2 2.2.9 0 1.5-.4 1.8-1l-1.3-.8c-.2.4-.4.6-.7.6-.4 0-.6-.3-.6-.8v-6h-1.4v5zm7.3-6.3c-1.3 0-2.1.8-2.1 2 0 1.3.8 1.8 1.8 2.2.7.3 1 .5 1 .9 0 .4-.4.6-.9.6-.6 0-1-.3-1.2-.8l-1.3.7c.4.9 1.3 1.4 2.4 1.4 1.4 0 2.3-.8 2.3-2.1 0-1.2-.7-1.8-1.7-2.2-.6-.2-1-.4-1-.8 0-.3.3-.5.7-.5.5 0 .8.2 1 .6l1.2-.8c-.4-.7-1.1-1.2-2.2-1.2z" fill="#000"/></svg>`,
  'TypeScript': `<svg viewBox="0 0 24 24" width="15" height="15"><rect width="24" height="24" rx="4" fill="#3178C6"/><path d="M12.5 10.5h-4v2h1.2v6.5h1.6v-6.5h1.2v-2zm4.3 2.8c-.3-.2-.7-.4-1.2-.5-.5-.1-.8-.3-.8-.5 0-.2.2-.4.6-.4.4 0 .8.2 1.1.5l1-1c-.5-.5-1.2-.8-2.1-.8-1.4 0-2.2.7-2.2 1.8 0 .8.5 1.4 1.4 1.7.5.2.9.3 1.1.4.3.1.4.3.4.5 0 .3-.3.5-.8.5-.6 0-1.1-.3-1.4-.7l-1.1 1c.6.8 1.4 1.1 2.5 1.1 1.6 0 2.4-.8 2.4-2 0-.8-.5-1.3-1.4-1.6z" fill="#fff"/></svg>`,
  'React.js': `<svg viewBox="-11.5 -10.23174 23 20.46348" width="16" height="16" fill="none"><circle cx="0" cy="0" r="2.05" fill="#61DAFB"/><g stroke="#61DAFB" stroke-width="1"><ellipse rx="11" ry="4.2"/><ellipse rx="11" ry="4.2" transform="rotate(60)"/><ellipse rx="11" ry="4.2" transform="rotate(120)"/></g></svg>`,
  'React': `<svg viewBox="-11.5 -10.23174 23 20.46348" width="16" height="16" fill="none"><circle cx="0" cy="0" r="2.05" fill="#61DAFB"/><g stroke="#61DAFB" stroke-width="1"><ellipse rx="11" ry="4.2"/><ellipse rx="11" ry="4.2" transform="rotate(60)"/><ellipse rx="11" ry="4.2" transform="rotate(120)"/></g></svg>`,
  'Next.js': `<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12s5.37 12 12 12 12-5.37 12-12S18.63 0 12 0zm5.63 17.39l-6.13-7.85v7.85H10V6.61h1.5l6.13 7.84V6.61h1.5v10.78h-1.5z"/></svg>`,
  'Tailwind CSS': `<svg viewBox="0 0 24 24" width="15" height="15" fill="#38BDF8"><path d="M12 6c-4.4 0-7.2 2.2-8.4 6.6 1.8-2.4 3.9-3.3 6.3-2.7 1.4.4 2.4 1.4 3.5 2.5 1.8 1.8 3.8 3.9 8.2 3.9 4.4 0 7.2-2.2 8.4-6.6-1.8 2.4-3.9 3.3-6.3 2.7-1.4-.4-2.4-1.4-3.5-2.5-1.8-1.8-3.8-3.9-8.2-3.9zm-8.4 6.6c-4.4 0-7.2 2.2-8.4 6.6 1.8-2.4 3.9-3.3 6.3-2.7 1.4.4 2.4 1.4 3.5 2.5 1.8 1.8 3.8 3.9 8.2 3.9 4.4 0 7.2-2.2 8.4-6.6-1.8 2.4-3.9 3.3-6.3 2.7-1.4-.4-2.4-1.4-3.5-2.5-1.8-1.8-3.8-3.9-8.2-3.9z"/></svg>`,
  'Bootstrap': `<svg viewBox="0 0 24 24" width="15" height="15" fill="#7952B3"><rect width="24" height="24" rx="4" fill="#7952B3"/><path d="M7 6h5.3c1.6 0 2.8.9 2.8 2.2 0 .9-.6 1.6-1.5 1.9 1.2.3 1.9 1.1 1.9 2.2 0 1.5-1.3 2.4-3 2.4H7V6zm2.2 3.6h2.8c.6 0 1-.3 1-.8 0-.6-.4-.8-1-.8H9.2v1.6zm0 3.3h3.1c.7 0 1.1-.3 1.1-.9 0-.6-.4-.9-1.1-.9H9.2v1.8z" fill="#fff"/></svg>`,
  'Git': `<svg viewBox="0 0 24 24" width="15" height="15" fill="#F05032"><path d="M23.5 10.9L13.1.5c-.7-.7-1.8-.7-2.5 0L8.2 2.9l3.3 3.3c.7-.2 1.6 0 2.1.6.6.6.7 1.5.4 2.2l3.1 3.1c.7-.3 1.6-.2 2.2.4.8.8.8 2.1 0 2.9-.8.8-2.1.8-2.9 0-.6-.6-.7-1.5-.4-2.2l-3-3v7.3c.3.2.5.6.5 1 0 .8-.7 1.5-1.5 1.5s-1.5-.7-1.5-1.5c0-.4.2-.8.5-1V9.9c-.3-.2-.5-.5-.5-1 0-.6.3-1.1.8-1.3L5.3 4.2.5 9c-.7.7-.7 1.8 0 2.5l10.4 10.4c.7.7 1.8.7 2.5 0l10.1-10.1c.7-.7.7-1.8 0-2.4z"/></svg>`,
  'GitHub': `<svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>`,
  'Vite': `<svg viewBox="0 0 24 24" width="15" height="15"><path d="M23.6 3.2L12.7 23.3c-.3.5-.9.5-1.2 0L.4 3.2c-.3-.6.1-1.3.8-1.2l11 1.8L22.8 2c.7-.1 1.1.6.8 1.2z" fill="#FFC920"/><path d="M17.8 1.4l-7.3 12.6c-.2.4.1.9.6.9h4.3l-2.6 7c-.2.6.6 1 .9.5l8.7-13.4c.3-.5-.1-1.1-.6-1.1h-4.3l3-6c.2-.5-.4-.9-.7-.5z" fill="#BD34FE"/></svg>`,
  'VS Code': `<svg viewBox="0 0 24 24" width="15" height="15" fill="#007ACC"><path d="M23.15 2.587L18.21.21a1.49 1.49 0 00-1.74.347L8.41 8.528 3.73 4.966a1.18 1.18 0 00-1.458.077L.34 6.883a.98.98 0 000 1.488l3.967 3.63-3.967 3.63a.98.98 0 000 1.488l1.932 1.84a1.18 1.18 0 001.458.077l4.68-3.562 8.06 7.971c.49.48 1.24.6 1.84.31l4.84-2.32a1.49 1.49 0 00.89-1.35V3.937c0-.58-.35-1.1-.89-1.35zM18.2 17.6L9.8 12l8.4-5.6v11.2z"/></svg>`,
  'Figma': `<svg viewBox="0 0 24 24" width="15" height="15"><path d="M8 24c2.2 0 4-1.8 4-4v-4H8c-2.2 0-4 1.8-4 4s1.8 4 4 4z" fill="#0ACF83"/><path d="M4 12c0-2.2 1.8-4 4-4h4v8H8c-2.2 0-4-1.8-4-4z" fill="#A259FF"/><path d="M4 4c0-2.2 1.8-4 4-4h4v8H8c-2.2 0-4-1.8-4-4z" fill="#F24E1E"/><path d="M12 0h4c2.2 0 4 1.8 4 4s-1.8 4-4 4h-4V0z" fill="#FF7262"/><path d="M20 12c0 2.2-1.8 4-4 4s-4-1.8-4-4 1.8-4 4-4 4 1.8 4 4z" fill="#1ABCFE"/></svg>`,
  'Chrome DevTools': `<svg viewBox="0 0 24 24" width="15" height="15" fill="#4285F4"><circle cx="12" cy="12" r="10" stroke="#4285F4" stroke-width="2" fill="none"/><path d="M12 6v6l4 2" stroke="#4285F4" stroke-width="2"/></svg>`
};

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  loadContent();
  setupNavigation();
  setupContactForm();
  setupResumeModal();
  setupCopyEmail();
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
          <div class="highlight-icon">✦</div>
          <div class="highlight-body">
            <h3 class="highlight-card-title">${escapeHtml(h.title)}</h3>
            <p class="highlight-card-desc">${escapeHtml(h.description)}</p>
          </div>
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
            ${(cat.skills || []).map(s => {
              const iconSvg = SKILL_ICONS[s] || `<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>`;
              return `
                <li class="skill-tag">
                  <span class="skill-tag-icon">${iconSvg}</span>
                  <span>${escapeHtml(s)}</span>
                </li>
              `;
            }).join('')}
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
      timelineContainer.innerHTML = data.education.entries.map((entry, idx) => `
        <article class="education-entry">
          <div class="edu-node-badge">${idx === 0 ? 'Current' : 'Completed'}</div>
          <div class="edu-header">
            <div>
              <h3 class="edu-degree">${escapeHtml(entry.degree)}</h3>
              <p class="edu-institution">${escapeHtml(entry.institution)}</p>
            </div>
            <span class="edu-period">${escapeHtml(entry.period)}</span>
          </div>
          ${entry.score ? `<div class="edu-score-pill">Grade / Result: <strong>${escapeHtml(entry.score)}</strong></div>` : ''}
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
      projectsGrid.innerHTML = data.projects.items.map((proj, idx) => {
        // Derive clean simulated URL for browser chrome
        let simUrl = 'portfolio.dev/app';
        if (proj.repoUrl && proj.repoUrl.includes('/')) {
          const parts = proj.repoUrl.split('/');
          simUrl = parts[parts.length - 1] + '.app';
        }

        return `
          <article class="project-card">
            <!-- Linear-Style Browser Chrome Header -->
            <div class="project-browser-bar">
              <div class="browser-dots">
                <span class="b-dot red"></span>
                <span class="b-dot yellow"></span>
                <span class="b-dot green"></span>
              </div>
              <div class="browser-address">
                <span class="ssl-lock">🔒</span>
                <span class="fake-url">${escapeHtml(simUrl)}</span>
              </div>
              <span class="project-num">// 0${idx + 1}</span>
            </div>

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
                  <a href="${escapeHtml(proj.liveUrl)}" target="_blank" rel="noopener noreferrer" class="project-link btn-link-live">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                      <polyline points="15 3 21 3 21 9"></polyline>
                      <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    <span>View Repository</span>
                  </a>
                ` : ''}
                ${proj.repoUrl && proj.repoUrl !== '#' ? `
                  <a href="${escapeHtml(proj.repoUrl)}" target="_blank" rel="noopener noreferrer" class="project-link btn-link-code">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
                      <path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/>
                    </svg>
                    <span>Source Code</span>
                  </a>
                ` : ''}
              </div>
            </div>
          </article>
        `;
      }).join('');
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
      socialsContainer.innerHTML = data.contact.socials.map(soc => {
        let iconSvg = `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line></svg>`;
        if (soc.platform.toLowerCase().includes('github')) {
          iconSvg = SKILL_ICONS['GitHub'];
        } else if (soc.platform.toLowerCase().includes('linkedin')) {
          iconSvg = `<svg viewBox="0 0 24 24" width="16" height="16" fill="#0A66C2"><path d="M19 0h-14c-2.76 0-5 2.24-5 5v14c0 2.76 2.24 5 5 5h14c2.76 0 5-2.24 5-5v-14c0-2.76-2.24-5-5-5zm-11 19h-3v-10h3v10zm-1.5-11.27c-.97 0-1.75-.79-1.75-1.76s.78-1.75 1.75-1.75 1.75.78 1.75 1.75-.78 1.76-1.75 1.76zm13.5 11.27h-3v-5.6c0-1.34-.03-3.07-1.87-3.07-1.87 0-2.16 1.46-2.16 2.97v5.7h-3v-10h2.88v1.37h.04c.4-.76 1.38-1.56 2.84-1.56 3.04 0 3.6 2 3.6 4.6v5.59z"/></svg>`;
        } else if (soc.platform.toLowerCase().includes('twitter') || soc.platform.toLowerCase().includes('x')) {
          iconSvg = `<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>`;
        }

        return `
          <a href="${escapeHtml(soc.url)}" target="_blank" rel="noopener noreferrer" class="social-pill">
            <span class="social-icon-wrapper">${iconSvg}</span>
            <div class="social-label-group">
              <span class="social-platform">${escapeHtml(soc.platform)}</span>
              <span class="social-handle">${escapeHtml(soc.username || 'Visit')}</span>
            </div>
            <span class="arrow-indicator">↗</span>
          </a>
        `;
      }).join('');
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
   INTERACTIVE COPY EMAIL
   ========================================================================== */
function setupCopyEmail() {
  const copyBtn = document.getElementById('copyEmailBtn');
  const emailLink = document.getElementById('contactEmail');
  const copyText = document.getElementById('copyBtnText');

  if (!copyBtn || !emailLink) return;

  copyBtn.addEventListener('click', async () => {
    const email = emailLink.textContent.trim();
    try {
      await navigator.clipboard.writeText(email);
      if (copyText) copyText.textContent = 'Copied!';
      copyBtn.classList.add('copied');
      setTimeout(() => {
        if (copyText) copyText.textContent = 'Copy';
        copyBtn.classList.remove('copied');
      }, 2500);
    } catch (e) {
      prompt('Copy email address:', email);
    }
  });
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
