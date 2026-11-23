const express = require('express');
const path = require('path');
const fs = require('fs');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const cookieParser = require('cookie-parser');
const multer = require('multer');
const rateLimit = require('express-rate-limit');
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;
const JWT_SECRET = process.env.JWT_SECRET || 'fallback_secret_rohith_portfolio_2026';

const ROOT_DIR = path.join(__dirname, '..');
const DATA_DIR = path.join(ROOT_DIR, 'data');
const UPLOADS_DIR = path.join(ROOT_DIR, 'uploads');
const CONTENT_FILE = path.join(DATA_DIR, 'content.json');
const CONTENT_BACKUP = path.join(DATA_DIR, 'content.backup.json');
const MESSAGES_FILE = path.join(DATA_DIR, 'messages.json');
const ADMIN_FILE = path.join(DATA_DIR, 'admin.json');

// Ensure directories exist
if (!fs.existsSync(DATA_DIR)) fs.mkdirSync(DATA_DIR, { recursive: true });
if (!fs.existsSync(UPLOADS_DIR)) fs.mkdirSync(UPLOADS_DIR, { recursive: true });
if (!fs.existsSync(MESSAGES_FILE)) fs.writeFileSync(MESSAGES_FILE, '[]', 'utf8');

// Helper to get admin credentials
function getAdminCredentials() {
  if (fs.existsSync(ADMIN_FILE)) {
    try {
      const data = JSON.parse(fs.readFileSync(ADMIN_FILE, 'utf8'));
      if (data.username && data.passwordHash) return data;
    } catch (e) {
      console.error('Error reading admin.json:', e);
    }
  }
  return {
    username: process.env.ADMIN_USERNAME || 'admin',
    passwordHash: process.env.ADMIN_PASSWORD_HASH || '$2a$10$8Gn8vDjhI6abM0bopB0.WO64IPPvXqZS7Cl4PLG.rT0UgHCLenW66'
  };
}

// Middlewares
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true }));
app.use(cookieParser());

// Serve uploaded files statically
app.use('/uploads', express.static(UPLOADS_DIR));

// Serve Admin UI statically at /admin
app.use('/admin', express.static(path.join(ROOT_DIR, 'admin')));

// Serve Public Site statically at root
app.use(express.static(path.join(ROOT_DIR, 'public')));

// Rate limiter for login
const loginLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 15, // limit each IP to 15 login requests per windowMs
  message: { error: 'Too many login attempts from this IP, please try again in 15 minutes.' }
});

// Authentication middleware
function authenticateToken(req, res, next) {
  let token = req.cookies && req.cookies.admin_token;
  if (!token && req.headers['authorization']) {
    const authHeader = req.headers['authorization'];
    if (authHeader.startsWith('Bearer ')) {
      token = authHeader.substring(7);
    }
  }

  if (!token) {
    return res.status(401).json({ error: 'Authentication required' });
  }

  jwt.verify(token, JWT_SECRET, (err, user) => {
    if (err) {
      return res.status(403).json({ error: 'Session expired or invalid token' });
    }
    req.user = user;
    next();
  });
}

// Multer Storage Configuration
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, UPLOADS_DIR);
  },
  filename: (req, file, cb) => {
    const safeExt = path.extname(file.originalname).toLowerCase();
    const cleanBase = path.basename(file.originalname, safeExt)
      .toLowerCase()
      .replace(/[^a-z0-9_-]/g, '_')
      .slice(0, 30);
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1e6);
    cb(null, `${cleanBase}-${uniqueSuffix}${safeExt}`);
  }
});

const upload = multer({
  storage,
  limits: { fileSize: 15 * 1024 * 1024 }, // 15MB limit
  fileFilter: (req, file, cb) => {
    const allowedMimes = [
      'image/jpeg',
      'image/png',
      'image/webp',
      'image/svg+xml',
      'image/gif',
      'application/pdf'
    ];
    if (allowedMimes.includes(file.mimetype)) {
      cb(null, true);
    } else {
      cb(new Error('Invalid file type. Only images (JPG, PNG, WebP, SVG, GIF) and PDF documents are allowed.'));
    }
  }
});

/* ==========================================================================
   PUBLIC API ENDPOINTS
   ========================================================================== */

// 1. Get Public Content
app.get('/api/content', (req, res) => {
  try {
    if (!fs.existsSync(CONTENT_FILE)) {
      return res.status(404).json({ error: 'Content file not found' });
    }
    const content = JSON.parse(fs.readFileSync(CONTENT_FILE, 'utf8'));
    res.json(content);
  } catch (err) {
    console.error('Error fetching content:', err);
    res.status(500).json({ error: 'Failed to load content' });
  }
});

// 2. Submit Contact Form
app.post('/api/contact', (req, res) => {
  try {
    const { name, email, subject, message } = req.body;
    if (!name || !email || !message) {
      return res.status(400).json({ error: 'Please provide name, email, and message.' });
    }

    // Basic email check
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return res.status(400).json({ error: 'Please provide a valid email address.' });
    }

    let messages = [];
    if (fs.existsSync(MESSAGES_FILE)) {
      try {
        messages = JSON.parse(fs.readFileSync(MESSAGES_FILE, 'utf8'));
      } catch (e) {
        messages = [];
      }
    }

    const newMessage = {
      id: 'msg-' + Date.now(),
      name: String(name).trim().slice(0, 100),
      email: String(email).trim().slice(0, 100),
      subject: String(subject || 'General Inquiry').trim().slice(0, 150),
      message: String(message).trim().slice(0, 2000),
      receivedAt: new Date().toISOString(),
      read: false
    };

    messages.unshift(newMessage);
    fs.writeFileSync(MESSAGES_FILE, JSON.stringify(messages, null, 2), 'utf8');

    res.json({ success: true, message: 'Message sent successfully! Thank you for reaching out.' });
  } catch (err) {
    console.error('Error saving contact message:', err);
    res.status(500).json({ error: 'Unable to send message at this time.' });
  }
});

/* ==========================================================================
   ADMIN AUTH & SESSION ENDPOINTS
   ========================================================================== */

// Admin Login
app.post('/api/admin/login', loginLimiter, (req, res) => {
  const { username, password } = req.body;
  if (!username || !password) {
    return res.status(400).json({ error: 'Username and password are required' });
  }

  const creds = getAdminCredentials();
  if (username !== creds.username) {
    return res.status(401).json({ error: 'Invalid username or password' });
  }

  const passwordMatches = bcrypt.compareSync(password, creds.passwordHash);
  if (!passwordMatches) {
    return res.status(401).json({ error: 'Invalid username or password' });
  }

  const token = jwt.sign({ username: creds.username }, JWT_SECRET, { expiresIn: '7d' });

  res.cookie('admin_token', token, {
    httpOnly: true,
    sameSite: 'lax',
    maxAge: 7 * 24 * 60 * 60 * 1000
  });

  res.json({ success: true, token, username: creds.username });
});

// Admin Logout
app.post('/api/admin/logout', (req, res) => {
  res.clearCookie('admin_token');
  res.json({ success: true, message: 'Logged out successfully' });
});

// Check Admin Auth Status
app.get('/api/admin/status', (req, res) => {
  let token = req.cookies && req.cookies.admin_token;
  if (!token && req.headers['authorization']) {
    const authHeader = req.headers['authorization'];
    if (authHeader.startsWith('Bearer ')) {
      token = authHeader.substring(7);
    }
  }

  if (!token) {
    return res.json({ authenticated: false });
  }

  jwt.verify(token, JWT_SECRET, (err, decoded) => {
    if (err) {
      return res.json({ authenticated: false });
    }
    res.json({ authenticated: true, username: decoded.username });
  });
});

// Change Admin Password
app.post('/api/admin/change-password', authenticateToken, (req, res) => {
  const { currentPassword, newPassword } = req.body;
  if (!currentPassword || !newPassword) {
    return res.status(400).json({ error: 'Current and new passwords are required' });
  }

  if (newPassword.length < 6) {
    return res.status(400).json({ error: 'New password must be at least 6 characters long' });
  }

  const creds = getAdminCredentials();
  const validCurrent = bcrypt.compareSync(currentPassword, creds.passwordHash);
  if (!validCurrent) {
    return res.status(400).json({ error: 'Current password is incorrect' });
  }

  const newHash = bcrypt.hashSync(newPassword, 10);
  const updatedCreds = {
    username: creds.username,
    passwordHash: newHash,
    updatedAt: new Date().toISOString()
  };

  fs.writeFileSync(ADMIN_FILE, JSON.stringify(updatedCreds, null, 2), 'utf8');
  res.json({ success: true, message: 'Password updated successfully' });
});

/* ==========================================================================
   ADMIN PROTECTED CONTENT & DATA ENDPOINTS
   ========================================================================== */

// Save / Update Content (With automatic backup creation)
app.put('/api/admin/content', authenticateToken, (req, res) => {
  try {
    const newContent = req.body;
    if (!newContent || typeof newContent !== 'object') {
      return res.status(400).json({ error: 'Invalid content format' });
    }

    // 1. Create backup of current file before overwriting
    if (fs.existsSync(CONTENT_FILE)) {
      const currentData = fs.readFileSync(CONTENT_FILE, 'utf8');
      fs.writeFileSync(CONTENT_BACKUP, currentData, 'utf8');
    }

    // 2. Write new content
    fs.writeFileSync(CONTENT_FILE, JSON.stringify(newContent, null, 2), 'utf8');
    res.json({ success: true, message: 'Site content and design tokens updated successfully!' });
  } catch (err) {
    console.error('Error saving content:', err);
    res.status(500).json({ error: 'Failed to save content' });
  }
});

// Restore Previous Backup
app.post('/api/admin/restore-backup', authenticateToken, (req, res) => {
  try {
    if (!fs.existsSync(CONTENT_BACKUP)) {
      return res.status(404).json({ error: 'No backup content found to restore.' });
    }
    const backupData = fs.readFileSync(CONTENT_BACKUP, 'utf8');
    fs.writeFileSync(CONTENT_FILE, backupData, 'utf8');
    const restored = JSON.parse(backupData);
    res.json({ success: true, message: 'Content restored to previous backup successfully.', content: restored });
  } catch (err) {
    console.error('Error restoring backup:', err);
    res.status(500).json({ error: 'Failed to restore backup' });
  }
});

// Upload Asset (Image or PDF)
app.post('/api/admin/upload', authenticateToken, (req, res) => {
  upload.single('file')(req, res, (err) => {
    if (err) {
      if (err instanceof multer.MulterError) {
        return res.status(400).json({ error: `Upload error: ${err.message}` });
      }
      return res.status(400).json({ error: err.message || 'File upload failed' });
    }

    if (!req.file) {
      return res.status(400).json({ error: 'No file received' });
    }

    const fileUrl = `/uploads/${req.file.filename}`;
    res.json({
      success: true,
      fileUrl,
      filename: req.file.filename,
      size: req.file.size,
      mimetype: req.file.mimetype
    });
  });
});

// Get Messages
app.get('/api/admin/messages', authenticateToken, (req, res) => {
  try {
    let messages = [];
    if (fs.existsSync(MESSAGES_FILE)) {
      messages = JSON.parse(fs.readFileSync(MESSAGES_FILE, 'utf8'));
    }
    res.json({ messages });
  } catch (err) {
    res.status(500).json({ error: 'Failed to read messages' });
  }
});

// Delete Message
app.delete('/api/admin/messages/:id', authenticateToken, (req, res) => {
  try {
    const { id } = req.params;
    let messages = [];
    if (fs.existsSync(MESSAGES_FILE)) {
      messages = JSON.parse(fs.readFileSync(MESSAGES_FILE, 'utf8'));
    }
    messages = messages.filter(m => m.id !== id);
    fs.writeFileSync(MESSAGES_FILE, JSON.stringify(messages, null, 2), 'utf8');
    res.json({ success: true, message: 'Message deleted' });
  } catch (err) {
    res.status(500).json({ error: 'Failed to delete message' });
  }
});

// Fallback for Admin SPA routing
app.get('/admin/*', (req, res) => {
  res.sendFile(path.join(ROOT_DIR, 'admin', 'index.html'));
});

// Fallback for Public SPA routing
app.get('*', (req, res) => {
  res.sendFile(path.join(ROOT_DIR, 'public', 'index.html'));
});

// Start Server
app.listen(PORT, () => {
  console.log(`====================================================`);
  console.log(` Rohith's Portfolio & CMS Server`);
  console.log(` Public Site: http://localhost:${PORT}/`);
  console.log(` Admin Panel: http://localhost:${PORT}/admin`);
  console.log(`====================================================`);
});
