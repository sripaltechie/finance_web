Chit Fund & Financial ERP System
A robust, full-stack enterprise resource planning (ERP) solution designed specifically for Chit Fund Management, local money lending, and daily collection tracking. This application automates the complex ledger processes of "Daybooks," interest calculations, and collateral tracking for financial agencies.
🚀 Key Modules & Features
🏦 Financial Management
Chit Fund Lifecycle: Full management of Chits (Create, Edit, Complete) with support for commission rates, interest rates, and multi-mode payments.
Automated Daybook: A dynamic daily ledger that auto-calculates opening/closing balances, total credits/debits, and net cash flow.
Interest Tracking: Automated calculations for Principal (Asalu) and accrued interest across customer accounts.
Cash Entries (Dr/Cr): Categorized transaction logging (Expenses, Investments, Medical, P2C, etc.).
👤 Customer & Collateral Management
Detailed Profiling: Tracks customer identities and multiple types of security collateral including Cheques, Promissory Notes, and Greensheets.
History Tracking: View full transaction histories and pending dues per customer.
🛡️ System & Security
Telegram Integration: Real-time business alerts and system logs sent directly to admin Telegram groups via Bot API.
Data Portability: Integrated backup system that exports the entire database to CSVs bundled in a ZIP archive.
Secure API: Powered by Slim Framework with API Key authentication and Bcrypt password hashing.
🛠️ Tech Stack
Frontend:
Framework: AngularJS (v1.x)
Routing: Angular Route (SPA)
UI Components: Bootstrap 3, UI-Bootstrap (Modals, Typeahead, Datepickers)
Notifications: Angular Toaster
Backend:
API Framework: Slim Framework (v2)
Language: PHP 7.x+
Database: MySQL / MariaDB
Communication: cURL for Telegram Bot API
📂 Project Structure
├── api/                    # Slim Framework API
│   └── index.php           # REST Endpoints & Telegram Logic
├── include/                # Core Backend
│   ├── Config.php          # Global constants & DB Settings
│   ├── DbConnect.php       # MySQLi connection wrapper
│   ├── DbHandler.php       # Core Business Logic (CRUD)
│   └── passwordHash.php    # Security utilities
├── partials/               # HTML Views (Templates)
│   ├── daybook.html        # Daily ledger dashboard
│   ├── createchiti.html    # Chit entry form
│   └── ...
├── js/
│   └── app.js              # Angular Config, Routes & Custom Directives
└── backup/                 # Data Export storage


⚙️ Installation & Setup
Prerequisites
Web Server (Apache/Nginx)
PHP 7.0+ with mysqli and curl extensions
MySQL 5.7+
1. Database Setup
Create a MySQL database.
Update include/Config.php with your credentials:
define('DB_USERNAME', 'your_db_user');
define('DB_PASSWORD', 'your_db_password');
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_db_name');


2. API & Routing
Ensure mod_rewrite is enabled on your Apache server to allow Slim's virtual routing.
The API entry point is located at /api/index.php.
3. Telegram Integration (Optional)
To receive automated alerts, update the Bot Token and Chat IDs in the sendTelegram() function within api/index.php.
🔌 Core API Endpoints
Endpoint
Method
Description
/login
POST
Authenticates user & returns API Key
/export
GET
Generates a ZIP backup of the DB
/import
POST
Restores database from a backup ZIP
/daybook
GET
Fetches consolidated daily financial data
/customers
GET/POST
CRUD operations for customer profiles

⌨️ Custom Directives
The frontend includes advanced UX directives in app.js:
focus: Manages keyboard navigation and autofocus on inputs.
typeahead: Integrated with UI-Bootstrap for fast customer searching.
onFinishRender: Utility for triggering logic after list rendering.
Disclaimer: This software is designed for private financial management and auditing.
