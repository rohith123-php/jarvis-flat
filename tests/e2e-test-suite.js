/**
 * Comprehensive Backend and Frontend Test Suite
 */
const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = 3000;
let adminToken = '';
let adminCookie = '';

function request({ path: reqPath, method = 'GET', headers = {}, body = null }) {
  return new Promise((resolve, reject) => {
    const opts = {
      hostname: 'localhost',
      port: PORT,
      path: reqPath,
      method,
      headers: { ...headers }
    };

    let postData = null;
    if (body) {
      if (typeof body === 'string' || Buffer.isBuffer(body)) {
        postData = body;
      } else {
        postData = JSON.stringify(body);
        opts.headers['Content-Type'] = 'application/json';
      }
      opts.headers['Content-Length'] = Buffer.byteLength(postData);
    }

    const req = http.request(opts, (res) => {
      let data = Buffer.alloc(0);
      res.on('data', chunk => data = Buffer.concat([data, chunk]));
      res.on('end', () => {
        const text = data.toString('utf8');
        let json = null;
        if (res.headers['content-type'] && res.headers['content-type'].includes('application/json')) {
          try { json = JSON.parse(text); } catch (e) {}
        }
        resolve({
          status: res.statusCode,
          headers: res.headers,
          text,
          json
        });
      });
    });

    req.on('error', reject);
    if (postData) req.write(postData);
    req.end();
  });
}

const results = [];
function record(testName, passed, details = '') {
  results.push({ testName, passed, details });
  const icon = passed ? '✅ PASS' : '❌ FAIL';
  console.log(`${icon} | ${testName} ${details ? '(' + details + ')' : ''}`);
}

async function runAllTests() {
  console.log('========================================================');
  console.log('  STARTING COMPREHENSIVE BACKEND & FRONTEND BUG TESTS  ');
  console.log('========================================================\n');

  // --- SECTION 1: PUBLIC STATIC & ASSETS ---
  try {
    const home = await request({ path: '/' });
    record('Public Home Page HTML', home.status === 200 && home.text.includes('<!DOCTYPE html>'), `Status: ${home.status}`);
  } catch (e) { record('Public Home Page HTML', false, e.message); }

  try {
    const css = await request({ path: '/style.css' });
    record('Public Stylesheet (style.css)', css.status === 200 && css.text.includes('--bg-primary'), `Status: ${css.status}`);
  } catch (e) { record('Public Stylesheet (style.css)', false, e.message); }

  try {
    const js = await request({ path: '/app.js' });
    record('Public Client Script (app.js)', js.status === 200 && js.text.includes('initTheme'), `Status: ${js.status}`);
  } catch (e) { record('Public Client Script (app.js)', false, e.message); }

  try {
    const avatar = await request({ path: '/uploads/profile.jpg' });
    record('Profile Image Asset', avatar.status === 200, `Status: ${avatar.status}`);
  } catch (e) { record('Profile Image Asset', false, e.message); }

  try {
    const resume = await request({ path: '/uploads/resume.pdf' });
    record('Resume PDF Asset', resume.status === 200, `Status: ${resume.status}`);
  } catch (e) { record('Resume PDF Asset', false, e.message); }

  // --- SECTION 2: PUBLIC CONTENT API VALIDATION ---
  let originalContent = null;
  try {
    const res = await request({ path: '/api/content' });
    const c = res.json;
    originalContent = c;
    const hasSections = c && c.hero && c.about && c.skills && c.education && c.projects && c.resume && c.contact && c.theme;
    record('Content API Returns Full Schema', res.status === 200 && hasSections, 'All 7 sections & theme present');
    record('Identity Details Verification', c && c.hero && c.hero.name === 'M. Rohith' && c.hero.title === 'Frontend Developer');
    record('Education Details Verification', c && Array.isArray(c.education.entries) && c.education.entries.length >= 2, `${c.education.entries.length} entries`);
  } catch (e) { record('Content API Returns Full Schema', false, e.message); }

  // --- SECTION 3: CONTACT FORM SUBMISSION & VALIDATION ---
  try {
    const emptyRes = await request({ path: '/api/contact', method: 'POST', body: {} });
    record('Contact Form Rejects Empty Submission', emptyRes.status === 400, `Status: ${emptyRes.status}`);

    const badEmailRes = await request({
      path: '/api/contact', method: 'POST',
      body: { name: 'John Doe', email: 'invalid-email', message: 'Hello' }
    });
    record('Contact Form Validates Email Format', badEmailRes.status === 400, `Status: ${badEmailRes.status}`);

    const validRes = await request({
      path: '/api/contact', method: 'POST',
      body: { name: 'Bug Tester', email: 'tester@example.com', subject: 'Automated Test', message: 'Testing system robustness.' }
    });
    record('Contact Form Accepts Valid Submission', validRes.status === 200 && validRes.json && validRes.json.success === true);
  } catch (e) { record('Contact Form Submission Tests', false, e.message); }

  // --- SECTION 4: AUTHENTICATION & SECURITY ---
  try {
    const unauthStatus = await request({ path: '/api/admin/status' });
    record('Admin Status Denies Unauthenticated Request', unauthStatus.json && unauthStatus.json.authenticated === false);

    const badLogin = await request({
      path: '/api/admin/login', method: 'POST',
      body: { username: 'admin', password: 'wrongPassword!123' }
    });
    record('Login Rejects Wrong Password', badLogin.status === 401, `Status: ${badLogin.status}`);

    const goodLogin = await request({
      path: '/api/admin/login', method: 'POST',
      body: { username: 'admin', password: 'Rohith@2026Admin!' }
    });
    const loggedIn = goodLogin.status === 200 && goodLogin.json && goodLogin.json.success && Boolean(goodLogin.json.token);
    record('Login Accepts Verified Password', loggedIn);

    if (loggedIn) {
      adminToken = goodLogin.json.token;
      adminCookie = goodLogin.headers['set-cookie'] ? goodLogin.headers['set-cookie'][0].split(';')[0] : '';
    }

    const authCheck = await request({
      path: '/api/admin/status',
      headers: { 'Authorization': 'Bearer ' + adminToken, 'Cookie': adminCookie }
    });
    record('Admin Status Confirms Authenticated Session', authCheck.json && authCheck.json.authenticated === true && authCheck.json.username === 'admin');
  } catch (e) { record('Authentication & Security', false, e.message); }

  // --- SECTION 5: PROTECTED ROUTE GUARDS ---
  try {
    const guardedContent = await request({ path: '/api/admin/content', method: 'PUT', body: {} });
    record('Protected Route (PUT /api/admin/content) Blocks Unauthenticated User', guardedContent.status === 401, `Status: ${guardedContent.status}`);

    const guardedMessages = await request({ path: '/api/admin/messages', method: 'GET' });
    record('Protected Route (GET /api/admin/messages) Blocks Unauthenticated User', guardedMessages.status === 401, `Status: ${guardedMessages.status}`);
  } catch (e) { record('Protected Route Guards', false, e.message); }

  // --- SECTION 6: ADMIN CONTENT CRUD & BACKUP SYSTEM ---
  try {
    if (adminToken && originalContent) {
      const modifiedContent = JSON.parse(JSON.stringify(originalContent));
      const testTagline = 'Automated Verification Tagline ' + Date.now();
      modifiedContent.hero.tagline = testTagline;

      const saveRes = await request({
        path: '/api/admin/content',
        method: 'PUT',
        headers: { 'Authorization': 'Bearer ' + adminToken, 'Cookie': adminCookie },
        body: modifiedContent
      });
      record('Admin Saves Content Successfully', saveRes.status === 200 && saveRes.json && saveRes.json.success);

      // Verify persistence
      const verifyGet = await request({ path: '/api/content' });
      record('Content Changes Persisted to Public API', verifyGet.json && verifyGet.json.hero.tagline === testTagline);

      // Verify backup file exists
      const backupPath = path.join(__dirname, '..', 'data', 'content.backup.json');
      record('Backup Snapshot File Created', fs.existsSync(backupPath), 'data/content.backup.json');

      // Test Restore Backup
      const restoreRes = await request({
        path: '/api/admin/restore-backup',
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + adminToken, 'Cookie': adminCookie }
      });
      record('Admin Restore Backup Works', restoreRes.status === 200 && restoreRes.json && restoreRes.json.success);

      // Verify restored content matches original
      const verifyRestored = await request({ path: '/api/content' });
      record('Public API Reverted to Previous Backup State', verifyRestored.json && verifyRestored.json.hero.tagline === originalContent.hero.tagline);
    }
  } catch (e) { record('Admin Content CRUD & Backup System', false, e.message); }

  // --- SECTION 7: FILE UPLOAD VALIDATION & FILTERING ---
  try {
    if (adminToken) {
      // 1. Upload valid SVG file
      const boundary = '----WebKitFormBoundary' + Math.random().toString(36).substring(2);
      const validSvgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><circle cx="5" cy="5" r="5" fill="red"/></svg>';
      
      let multipartBody = Buffer.concat([
        Buffer.from(`--${boundary}\r\nContent-Disposition: form-data; name="file"; filename="test-dot.svg"\r\nContent-Type: image/svg+xml\r\n\r\n`),
        Buffer.from(validSvgContent),
        Buffer.from(`\r\n--${boundary}--\r\n`)
      ]);

      const uploadSvgRes = await request({
        path: '/api/admin/upload',
        method: 'POST',
        headers: {
          'Authorization': 'Bearer ' + adminToken,
          'Cookie': adminCookie,
          'Content-Type': 'multipart/form-data; boundary=' + boundary
        },
        body: multipartBody
      });
      record('Image Upload Accepts Valid SVG File', uploadSvgRes.status === 200 && uploadSvgRes.json && uploadSvgRes.json.success, `File: ${uploadSvgRes.json ? uploadSvgRes.json.filename : ''}`);

      // Clean up uploaded test file
      if (uploadSvgRes.json && uploadSvgRes.json.filename) {
        const uploadedPath = path.join(__dirname, '..', 'uploads', uploadSvgRes.json.filename);
        if (fs.existsSync(uploadedPath)) fs.unlinkSync(uploadedPath);
      }

      // 2. Reject disallowed file type (e.g. text/plain or script)
      const badBoundary = '----WebKitFormBoundary' + Math.random().toString(36).substring(2);
      let badMultipartBody = Buffer.concat([
        Buffer.from(`--${badBoundary}\r\nContent-Disposition: form-data; name="file"; filename="malicious.sh"\r\nContent-Type: application/x-sh\r\n\r\n`),
        Buffer.from('echo hack'),
        Buffer.from(`\r\n--${badBoundary}--\r\n`)
      ]);

      const badUploadRes = await request({
        path: '/api/admin/upload',
        method: 'POST',
        headers: {
          'Authorization': 'Bearer ' + adminToken,
          'Cookie': adminCookie,
          'Content-Type': 'multipart/form-data; boundary=' + badBoundary
        },
        body: badMultipartBody
      });
      record('Upload Rejects Dangerous File Types (sh/exe)', badUploadRes.status === 400, `Status: ${badUploadRes.status}`);
    }
  } catch (e) { record('File Upload Validation & Filtering', false, e.message); }

  // --- SECTION 8: MESSAGE INBOX CRUD ---
  try {
    if (adminToken) {
      const getMsgs = await request({
        path: '/api/admin/messages',
        headers: { 'Authorization': 'Bearer ' + adminToken, 'Cookie': adminCookie }
      });
      const msgs = getMsgs.json ? getMsgs.json.messages : [];
      record('Admin Inbox Retrieves Messages', getMsgs.status === 200 && Array.isArray(msgs), `Count: ${msgs.length}`);

      // Clean up the test message we created earlier
      const testMsg = msgs.find(m => m.name === 'Bug Tester');
      if (testMsg) {
        const delRes = await request({
          path: '/api/admin/messages/' + testMsg.id,
          method: 'DELETE',
          headers: { 'Authorization': 'Bearer ' + adminToken, 'Cookie': adminCookie }
        });
        record('Admin Inbox Deletes Message', delRes.status === 200 && delRes.json && delRes.json.success);
      }
    }
  } catch (e) { record('Message Inbox CRUD', false, e.message); }

  // --- SUMMARY REPORT ---
  const passedCount = results.filter(r => r.passed).length;
  const totalCount = results.length;
  console.log('\n========================================================');
  console.log(`  TEST RESULTS: ${passedCount} / ${totalCount} PASSED (${Math.round((passedCount/totalCount)*100)}%)`);
  console.log('========================================================\n');

  if (passedCount !== totalCount) {
    process.exit(1);
  }
}

runAllTests().catch(err => {
  console.error('Fatal Test Suite Error:', err);
  process.exit(1);
});
