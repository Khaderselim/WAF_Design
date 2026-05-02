# WAF Design - Web Application Firewall Dashboard

A comprehensive Web Application Firewall (WAF) management system built with PHP, providing real-time traffic monitoring, attack detection, and IP blocking capabilities.

## Project Overview

This WAF Dashboard system monitors and analyzes web traffic in real-time, detecting various types of attacks including SQL Injection (SQLi), Cross-Site Scripting (XSS), and Command Injection (CMDi). It provides administrators with tools to manage security alerts, block malicious IPs, and configure WAF rules.

## System Requirements

### PHP Version
This project requires PHP 7.4 or higher. Recommended version is PHP 8.0+

You can check your PHP version by running:
```bash
php -v
```

### Required Extensions
The following PHP extensions must be enabled:

1. **PDO (PHP Data Objects)** - Database abstraction layer
   - For MySQL support: `php_pdo_mysql.dll` (Windows) or `pdo_mysql.so` (Linux)
   - Verify: Open your `php.ini` file and ensure `extension=pdo_mysql` is uncommented

2. **cURL** - For HTTP requests and API communication
   - For Windows: `php_curl.dll`
   - For Linux: Usually installed by default
   - Verify: Open your `php.ini` file and ensure `extension=curl` is uncommented

3. **JSON** - For handling JSON data (usually enabled by default)

4. **OpenSSL** - For secure connections
   - For Windows: `php_openssl.dll`
   - Verify: Open your `php.ini` file and ensure `extension=openssl` is uncommented

5. **Sockets** - For network operations
   - For Windows: Usually enabled by default

6. **FFI (Foreign Function Interface)** - Required for Transformers library
   - For Windows: `php_ffi.dll`
   - For Linux: `ffi.so`
   - Verify: Open your `php.ini` file and ensure `extension=ffi` is uncommented
   - Note: FFI is required for the AI-based attack detection features

### How to Enable Extensions

On Windows with Laragon:
1. Open Laragon and click the "Menu" button
2. Select "Preferences"
3. Click on "PHP" section
4. Look for "php.ini" and click "Edit"
5. Find the following lines and uncomment them (remove the semicolon):
   ```
   extension=pdo_mysql
   extension=curl
   extension=openssl
   extension=ffi
   ```
6. Save the file and restart Laragon

On Linux/Mac:
1. Locate your php.ini file (usually in `/etc/php/8.0/apache2/` or similar)
2. Open it with your text editor
3. Find and uncomment the required extensions:
   ```
   extension=pdo_mysql
   extension=curl
   extension=openssl
   extension=ffi
   ```
4. Restart Apache or your PHP server

## Installation Guide

### Step 1: Download and Setup

1. Ensure the project is in your web server's document root:
   ```
   For Laragon: C:\laragon\www\WAF_Design
   For XAMPP: C:\xampp\htdocs\WAF_Design
   For WAMP: C:\wamp\www\WAF_Design
   ```

2. Navigate to the project directory in your terminal/command prompt:
   ```bash
   cd C:\laragon\www\WAF_Design
   ```

### Step 2: Install Composer

Composer is a dependency manager for PHP. The project uses it to manage the Transformers library.

#### Download Composer

1. Visit https://getcomposer.org/download/
2. Download the installer for your operating system
3. Run the installer and follow the installation wizard

#### Verify Composer Installation

Open your terminal/command prompt and run:
```bash
composer --version
```

You should see output like: `Composer version 2.x.x`

### Step 3: Install Project Dependencies

In your project directory, run:
```bash
composer install
```

This command will:
- Read the `composer.json` file
- Download all required packages listed in dependencies
- Install them in the `vendor/` directory
- Generate the `composer.lock` file for version locking

Wait for the installation to complete. You should see output like:
```
Loading composer repositories with package definitions
Installing dependencies (including require-dev dependencies) from lock file
```

### Step 4: Setup Database

1. Create a new MySQL database named `waf_db`:
   ```bash
   mysql -u root -p
   CREATE DATABASE waf_db;
   ```

2. Import the database schema:
   ```bash
   mysql -u root -p waf_db < WAF_Database.sql
   ```

   Or in phpMyAdmin:
   - Create a new database named `waf_db`
   - Import the `WAF_Database.sql` file

### Step 5: Configure Database Connection

Edit the `db_connection.php` file and update the following if needed:
```php
$servername = "localhost";  // Database host
$username = "root";         // MySQL username
$password = "";             // MySQL password
$dbname = "waf_db";         // Database name
```

### Step 6: Start the Application

Using Laragon:
1. Open Laragon
2. Click "Start All"
3. Open your browser and navigate to: `http://localhost/WAF_Design`

Using XAMPP:
1. Start Apache and MySQL from the XAMPP Control Panel
2. Open your browser and navigate to: `http://localhost/WAF_Design`

Using Built-in PHP Server:
```bash
php -S localhost:8000
```
Then visit: `http://localhost:8000`

## Project Structure

```
WAF_Design/
├── assets/                  # Static files (CSS, JavaScript, Images, Fonts)
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript files
│   ├── img/                # Images and logos
│   └── fonts/              # Custom fonts
├── api/                    # API endpoints for AJAX requests
│   ├── alert_actions.php   # Handle security alert actions
│   └── block_actions.php   # Handle IP blocking/unblocking
├── Class/                  # PHP Classes
│   ├── Alerts.php         # Alert management
│   ├── Block.php          # IP blocking management
│   ├── Dashboard.php      # Dashboard statistics
│   ├── Traffic.php        # Traffic analysis
│   ├── url_check.php      # URL validation
│   └── URLAttackChecker.php # Attack detection
├── vendor/                 # Composer dependencies
├── components/             # HTML components and templates
├── tables/                 # Table templates
├── charts/                 # Chart templates
├── forms/                  # Form templates
├── maps/                   # Map templates
├── dashboard.php           # Main dashboard page
├── security-alerts.php     # Security alerts management
├── blocked-attacks.php     # Blocked attacks management
├── traffic-overview.php    # Traffic analysis page
├── settings.php            # WAF settings and configuration
├── login.php               # User login
├── register.php            # User registration
├── logout.php              # User logout
├── waf_init.php            # WAF initialization
├── waf_middleware.php      # WAF middleware
├── db_connection.php        # Database connection
├── composer.json           # PHP dependency configuration
└── WAF_Database.sql        # Database schema
```

## Key Features

1. Real-Time Attack Detection
   - Detects SQL Injection (SQLi)
   - Detects Cross-Site Scripting (XSS)
   - Detects Command Injection (CMDi)

2. Traffic Monitoring
   - Real-time traffic overview
   - Traffic analysis and statistics
   - Attack frequency tracking

3. Alert Management
   - Security alert dashboard
   - Alert severity levels (Critical, High, Medium)
   - Status tracking (Open, In Review, Resolved)
   - Dynamic alert status updates via AJAX

4. IP Blocking
   - Manual IP blocking
   - Dynamic block/unblock via AJAX
   - Blocked IP management
   - Historical tracking of blocked attacks

5. Settings Management
   - WAF mode configuration (Detect Only or Block)
   - Rule selection (SQLi, XSS, CMDi)
   - Manual IP whitelist/blacklist
   - User management

6. User Management
   - Role-based access control
   - Admin, Analyst, and Viewer roles
   - User registration and login

## Configuration

### WAF Settings

Access the WAF configuration at: `/settings.php`

Options include:
- WAF Mode: Detect Only or Block IP
- Detection Rules: Enable/Disable SQLi, XSS, CMDi detection
- Manual IP Blocking: Add IPs to blacklist
- IP Whitelist: Prevent blocking of trusted IPs
- User Management: Add new users and assign roles

### Database Configuration

Update `db_connection.php` with your database credentials:
```php
$servername = "localhost";
$username = "root";
$password = "your_password";
$dbname = "waf_db";
```

## Usage

### Login

1. Navigate to `http://localhost/WAF_Design/login.php`
2. Enter your credentials
3. Click "Login"

### Default Admin Account

After importing the database, check the `users` table for default credentials (if set).

### Dashboard

The main dashboard provides:
- Traffic statistics
- Alert summary
- Blocked attack count
- Recent threats
- Attack type distribution

### Security Alerts

View and manage security alerts:
- Search alerts by keyword
- Filter by severity and status
- Change alert status (Open -> Resolved)
- Reopen resolved alerts
- Real-time pagination

### Blocked Attacks

Manage blocked attacks:
- View all blocked traffic
- Block/Unblock IPs dynamically
- Search and filter attacks
- Real-time status updates
- URL truncation for better readability

### Traffic Overview

Monitor traffic in real-time:
- Hourly traffic distribution
- Attack type breakdown
- Top threats by frequency
- Traffic statistics

### Settings

Configure WAF behavior:
- Enable/Disable detection rules
- Set WAF mode (Detect or Block)
- Manage IP whitelist/blacklist
- Add and manage users
- Assign user roles

## Development

### Adding New Features

1. Create API endpoints in `/api/` directory
2. Add corresponding PHP classes in `/Class/` directory
3. Create frontend pages in the root directory
4. Update navigation in sidebar includes

### Database Modifications

To modify the database schema:
1. Update `WAF_Database.sql`
2. Drop and recreate the database
3. Re-import the updated schema

## Troubleshooting

### Composer Installation Issues

If `composer install` fails:
1. Ensure PHP CLI is accessible
2. Check internet connectivity
3. Clear composer cache: `composer clear-cache`
4. Retry: `composer install --no-cache`

### Database Connection Error

If you see "Connection failed" error:
1. Verify MySQL is running
2. Check credentials in `db_connection.php`
3. Ensure database `waf_db` exists
4. Verify user has required permissions

### Missing PHP Extensions

If you see "Call to undefined function" errors:
1. Check which extension is missing from error message
2. Enable it in `php.ini`
3. Restart your web server

### AJAX Requests Failing

If dynamic features aren't working:
1. Check browser console (F12) for errors
2. Verify API files exist in `/api/` directory
3. Check server error logs
4. Ensure WAF initialization runs without errors

## Security Notes

1. Change all default passwords after installation
2. Keep PHP and all extensions updated
3. Use HTTPS in production environments
4. Regularly backup your database
5. Implement proper access controls
6. Review and update security rules regularly

## Browser Compatibility

Supported browsers:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## Support and Documentation

For additional help:
1. Check error messages in browser console (F12)
2. Review server error logs
3. Verify all extensions are enabled
4. Ensure database connection is working

## License

See LICENSE file for details.

## Version Information

Current Version: 1.0.0
Last Updated: May 2026
PHP Requirement: 7.4+
MySQL Requirement: 5.7+



