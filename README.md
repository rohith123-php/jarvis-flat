# M. Rohith — Personal Portfolio & Dynamic CMS

A professional, high-performance personal portfolio website paired with an integrated administrative content management system. Engineered for **M. Rohith**, Frontend Developer.

---

## 🌟 Features

- **Semantic & Accessible Frontend**: Built strictly following clean web standards, WCAG AA contrast rules, responsive grid/flexbox layouts, and purposeful typography. Free of AI-slop (no gratuitous glows, blobs, or emoji icons).
- **Zero-Build Real-Time Theming**: Colors and styles are driven by dynamic CSS Custom Properties stored in `data/content.json`. Dark and light themes can be switched by the visitor or edited by the administrator in real-time without recompiling assets.
- **Data-Driven Architecture**: Every section (Hero, About, Skills, Education, Projects, Resume, Contact) is rendered dynamically from `data/content.json`.
- **Administrative Control Panel (`/admin`)**:
  - Secure bcrypt password verification and JWT-backed session authentication.
  - Rate-limited login endpoint for brute-force prevention.
  - Visual color pickers for dark and light theme tokens with instant preset themes (*Electric Indigo*, *Emerald Tech*, *Warm Amber*, *Monochrome*).
  - Full CRUD management of skills categories, education history, portfolio projects, and social media links.
  - Image and document uploader with MIME type verification and randomized file storage in `/uploads`.
  - Automatic snapshot backups (`content.backup.json`) with a single-click "Restore Backup" fail-safe.
  - Contact inbox for reviewing and managing client inquiries submitted via the public form.

---

## 🚀 Getting Started

### 1. Prerequisites
- [Node.js](https://nodejs.org/) (v18 or higher recommended; developed on Node v24).
- npm (Node Package Manager).

### 2. Installation

Clone or open the repository folder:
```bash
cd C:\Users\ctc\Documents\portfolio
```

Install dependencies:
```bash
npm install
```

### 3. Environment Setup
Copy the template environment configuration:
```bash
cp .env.example .env
```
*(On Windows PowerShell: `Copy-Item .env.example .env`)*

Configure `.env`:
```env
PORT=3000
JWT_SECRET=super_secret_jwt_key_rohith_portfolio_2026_xyz987
ADMIN_USERNAME=admin
ADMIN_PASSWORD_HASH=$2a$10$8Gn8vDjhI6abM0bopB0.WO64IPPvXqZS7Cl4PLG.rT0UgHCLenW66
```

### 4. Running the Application

Start the production server:
```bash
npm start
```
Or start in development mode with automatic restart on file change:
```bash
npm run dev
```

Once started:
- **Public Site**: [http://localhost:3000/](http://localhost:3000/)
- **Admin Control Panel**: [http://localhost:3000/admin](http://localhost:3000/admin)

---

## 🔐 Admin Panel Access

- **Route**: `/admin`
- **Default Username**: `admin`
- **Default Password**: `Rohith@2026Admin!`

> **Security Note**: You can change your password at any time inside the `/admin` dashboard under the **Security & Passwords** tab. Passwords are never stored in plaintext and are salted and hashed using `bcryptjs`.

---

## 📂 Project Architecture

```
portfolio/
├── server/
│   └── index.js             # Express API server, auth middlewares, uploads, backup handler
├── public/
│   ├── index.html           # Public semantic HTML layout (Hero, About, Skills, Education, Projects, Resume, Contact)
│   ├── style.css            # Design system, CSS custom properties, responsive breakpoints
│   └── app.js               # Client application, API fetcher, theme toggler, contact submit
├── admin/
│   ├── index.html           # Admin dashboard & login interface
│   ├── admin.css            # Admin design stylesheet
│   └── admin.js             # Admin client logic, CRUD handlers, color token sync, uploaders
├── data/
│   ├── content.json         # Master content and active theme tokens
│   ├── content.backup.json  # Automatic backup created before each save
│   ├── messages.json        # Contact form submission storage
│   └── admin.json           # Secure credential override storage
├── uploads/                 # Uploaded profile portraits, project thumbnails, and resume PDF
├── .env.example             # Template environment variables
├── .env                     # Local environment configuration (ignored by git)
├── .gitignore
├── package.json
└── README.md
```

---

## 🚢 Deployment Guidance

This project uses standard Node.js/Express with zero native build dependencies, making it deployable on any platform:

### Deploying to Render / Railway / Fly.io / VPS
1. Set the build command: `npm install`
2. Set the start command: `node server/index.js`
3. Add environment variables:
   - `PORT`: provided automatically by the platform or `3000`
   - `JWT_SECRET`: a random secure 32+ character string
   - `ADMIN_USERNAME`: your chosen username
   - `ADMIN_PASSWORD_HASH`: your bcrypt hashed password
4. If deploying to containerized platforms with ephemeral disks, mount a persistent volume to `/uploads` and `/data` to retain uploaded media and JSON updates across restarts.
