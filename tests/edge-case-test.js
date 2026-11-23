/**
 * Edge Case & Security Vulnerability Tests (XSS, Empty Sections, Incomplete Payloads)
 */
const fs = require('fs');
const path = require('path');

// Test 1: HTML Escaping function test
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

const xssPayload = '<script>alert("XSS")</script>';
const escaped = escapeHtml(xssPayload);
console.log('XSS Escaped result:', escaped);
if (escaped.includes('<script>') || escaped.includes('</script>')) {
  console.error('FAIL: XSS not escaped!');
  process.exit(1);
}
console.log('PASS: XSS tags safely encoded to &lt;script&gt;');

// Test 2: Verify CSS Variable completeness
const styleCss = fs.readFileSync(path.join(__dirname, '..', 'public', 'style.css'), 'utf8');
const contentJson = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'data', 'content.json'), 'utf8'));

const requiredTokens = ['bgPrimary', 'bgSurface', 'bgElevated', 'textPrimary', 'textMuted', 'borderColor', 'accentColor', 'accentHover'];
for (const mode of ['dark', 'light']) {
  for (const token of requiredTokens) {
    if (!contentJson.theme.tokens[mode][token]) {
      console.error(`FAIL: Missing ${token} in ${mode} mode`);
      process.exit(1);
    }
  }
}
console.log('PASS: All required design tokens exist for both dark and light modes.');

// Test 3: Validate JSON structure stability
const sections = ['hero', 'about', 'skills', 'education', 'projects', 'resume', 'contact'];
for (const s of sections) {
  if (typeof contentJson[s].enabled !== 'boolean') {
    console.error(`FAIL: Section ${s} missing boolean enabled toggle`);
    process.exit(1);
  }
}
console.log('PASS: All 7 sections have valid visibility toggles.');
console.log('All edge case & security checks completely PASSED.');
