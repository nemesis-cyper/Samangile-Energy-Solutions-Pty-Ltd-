# Samangile Energy Solutions - Complete Setup Guide

## 🚀 Quick Start

This is the official website and business management platform for **Samangile Energy Solutions (Pty) Ltd**, featuring engineering services, fabrication, renewable energy, AI-powered customer support, and project management.

### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- Apache/Nginx with mod_rewrite enabled
- cURL enabled in PHP
- Modern web browser

---

## 📋 Step-by-Step Setup

### 1. Database Setup

Create the MySQL database and tables:

```bash
# Connect to MySQL
mysql -u root -p

# Run the database initialization script
source sql/database.sql;
```

Or manually execute:
```sql
CREATE DATABASE IF NOT EXISTS samangile_energy;
USE samangile_energy;
-- Import all tables from sql/database.sql
```

**Database Tables Created:**
- `chat_messages` - Customer chat inquiries
- `quote_requests` - Service quote requests
- `admin_users` - Administrative accounts
- `service_packages` - Available service offerings
- `response_logs` - Chat/quote response tracking

### 2. PHP Configuration

Update database credentials in the root directory:

**Chat.php** (lines 5-8):
```php
$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$database = 'samangile_energy';
```

**Quote.php** (lines 5-8):
```php
$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$database = 'samangile_energy';
```

**Or use environment variables** (recommended for production):
```bash
export DB_HOST=localhost
export DB_USER=root
export DB_PASS=your_password
export DB_NAME=samangile_energy
export ADMIN_EMAIL=info@samangileenergy.com
```

### 3. Directory Permissions

Create necessary directories and set proper permissions:

```bash
# Create logs directory
mkdir -p logs
chmod 755 logs

# Create uploads directory (if needed)
mkdir -p uploads
chmod 755 uploads

# Ensure css and js are accessible
chmod 755 css js sql
```

### 4. Email Configuration (Optional)

To enable email notifications, uncomment and configure in `Quote.php` (line 132):

```php
// Enable mail function
ini_set('SMTP', 'your_smtp_server');
ini_set('smtp_port', '587');

// In sendEmail() function, uncomment:
mail($to, $subject, $body, $headers);
```

For Gmail SMTP:
```php
ini_set('SMTP', 'smtp.gmail.com');
ini_set('smtp_port', '587');
ini_set('sendmail_from', 'your_email@gmail.com');
```

### 5. Web Server Configuration

#### Apache (.htaccess)
Create `.htaccess` in root:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.html [L]
</IfModule>

<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

#### Nginx (server block)
```nginx
server {
    listen 80;
    server_name samangileenergysol.co.za;
    root /var/www/html;
    index index.html index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Security headers
    add_header X-Content-Type-Options "nosniff";
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
}
```

### 6. Start Development Server

```bash
# PHP built-in server (development only)
php -S localhost:8000

# Then access: http://localhost:8000
```

Or use:
```bash
# Apache
sudo service apache2 start

# Nginx
sudo service nginx start
```

---

## 🔧 File Structure

```
.
├── index.html              # Main entry point
├── Chat.php                # Chat API endpoint
├── Quote.php               # Quote request API
├── README.md               # Project documentation
├── LICENSE                 # MIT License
├── css/
│   └── style.css          # Main stylesheet (responsive design)
├── js/
│   └── script.js          # Frontend functionality
├── sql/
│   ├── database.sql       # Full database schema
│   └── create_messages_table.sql
└── logs/
    ├── quote_requests.log # Quote request log
    └── quote_requests.json # JSON backup
```

---

## 🛡️ Security Configuration

### 1. Secure Database Connection
Always use environment variables for sensitive data:
```php
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
```

### 2. Input Validation
All inputs are sanitized using:
```php
htmlspecialchars()     // Prevents XSS
filter_var()           // Email validation
mysqli_real_escape_string() // SQL injection prevention
```

### 3. HTTPS Configuration
For production, enable HTTPS:
```apache
<IfModule mod_ssl.c>
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
</IfModule>
```

### 4. CORS Headers
Already configured in PHP files:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
```

---

## 📱 Features Overview

### Chat System
- **Endpoint:** `/Chat.php`
- **Method:** POST
- **Request:**
```json
{
    "message": "Hello, I need information about solar installation",
    "sessionId": "session_1234567890"
}
```
- **Response:**
```json
{
    "success": true,
    "reply": "Our solar solutions are designed to save you money...",
    "message": "Message received and saved",
    "timestamp": "2026-06-27 19:50:00"
}
```

### Quote Request System
- **Endpoint:** `/Quote.php`
- **Method:** POST
- **Request:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+27123456789",
    "service": "solar",
    "amount": 50000,
    "message": "Looking for residential solar installation"
}
```
- **Response:**
```json
{
    "success": true,
    "quote_id": 1,
    "message": "Quote request submitted successfully!",
    "timestamp": "2026-06-27 19:50:00"
}
```

---

## 🧪 Testing

### Manual Testing
1. Open browser to `http://localhost:8000`
2. Navigate to "Chat with Us" section
3. Send a test message
4. Navigate to "Request a Quote"
5. Fill form and submit
6. Check `logs/quote_requests.log` for entries

### API Testing with cURL

**Test Chat:**
```bash
curl -X POST http://localhost:8000/Chat.php \
  -H "Content-Type: application/json" \
  -d '{"message":"Hello","sessionId":"test_123"}'
```

**Test Quote:**
```bash
curl -X POST http://localhost:8000/Quote.php \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Test User",
    "email":"test@example.com",
    "phone":"+27123456789",
    "service":"solar",
    "amount":25000,
    "message":"Test quote"
  }'
```

---

## 📊 Database Queries

### View All Chat Messages
```sql
SELECT * FROM chat_messages ORDER BY created_at DESC;
```

### View All Quote Requests
```sql
SELECT * FROM quote_requests WHERE status = 'pending' ORDER BY created_at DESC;
```

### View Service Packages
```sql
SELECT * FROM service_packages;
```

### Clear Old Logs (30+ days)
```sql
DELETE FROM chat_messages WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
DELETE FROM quote_requests WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## 🚀 Deployment

### Production Checklist
- [ ] Update database credentials with strong passwords
- [ ] Enable HTTPS/SSL certificate
- [ ] Configure SMTP for email notifications
- [ ] Set proper file permissions (644 for files, 755 for dirs)
- [ ] Enable logging and error reporting
- [ ] Configure backups for database
- [ ] Test all forms and APIs
- [ ] Monitor logs for errors
- [ ] Set up uptime monitoring

### Docker Deployment (Optional)
```dockerfile
FROM php:8.0-apache
RUN docker-php-ext-install mysqli
COPY . /var/www/html/
RUN a2enmod rewrite
EXPOSE 80
```

---

## 📞 Support & Contact

**Phone:** +27 68 732 8637  
**Email:** info@samangileenergy.com  
**Address:** 20067 Sebokeng Unit 14, 1983 South Africa  
**Website:** https://samangileenergysol.co.za

---

## 📄 License

MIT License - See LICENSE file for details

© 2024 Samangile Energy Solutions (Pty) Ltd  
**Powering Innovation. Engineering the Future.**
