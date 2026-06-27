# Configuration Guide for Samangile Energy Solutions

## Environment Variables

Create a `.env` file in the root directory (DO NOT commit to git):

```bash
# Database Configuration
DB_HOST=localhost
DB_USER=root
DB_PASS=your_secure_password
DB_NAME=samangile_energy

# Email Configuration
ADMIN_EMAIL=info@samangileenergy.com
FROM_EMAIL=noreply@samangileenergy.com
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password

# Application Settings
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
SESSION_TIMEOUT=3600

# Security
JWT_SECRET=your_jwt_secret_key_here
ALLOWED_ORIGINS=https://samangileenergysol.co.za

# API Configuration
API_RATE_LIMIT=100
API_TIMEOUT=30
```

## Loading Environment Variables

### PHP Method 1: Using .env file
```php
<?php
// config.php
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Use in Chat.php and Quote.php
$host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME') ?: 'samangile_energy';
```

### PHP Method 2: Using $_ENV superglobal
```php
<?php
// In your PHP files
$db_host = $_ENV['DB_HOST'] ?? 'localhost';
$db_user = $_ENV['DB_USER'] ?? 'root';
```

### In Apache .htaccess
```apache
SetEnv DB_HOST localhost
SetEnv DB_USER root
SetEnv ADMIN_EMAIL info@samangileenergy.com
```

### In Nginx (in php-fpm pool config)
```nginx
env[DB_HOST] = localhost
env[DB_USER] = root
env[ADMIN_EMAIL] = info@samangileenergy.com
```

## Database User Setup

Create a dedicated MySQL user:

```sql
-- Login as root
mysql -u root -p

-- Create new user with password
CREATE USER 'samangile_user'@'localhost' IDENTIFIED BY 'secure_password_123';

-- Grant specific privileges (recommended)
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE ON samangile_energy.* TO 'samangile_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify
SHOW GRANTS FOR 'samangile_user'@'localhost';
```

Then update your configuration:
```php
$db_user = 'samangile_user';
$db_pass = 'secure_password_123';
```

## Logging Configuration

### Error Logging
Enable in `php.ini`:
```ini
error_reporting = E_ALL
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log
```

### Application Logging
Files are auto-created:
```
logs/
├── quote_requests.log      # Text format
├── quote_requests.json     # JSON format
└── error.log              # PHP errors
```

View logs:
```bash
# Real-time monitoring
tail -f logs/quote_requests.log

# Search for specific email
grep "john@example.com" logs/quote_requests.log

# Count quote requests
wc -l logs/quote_requests.log
```

## Performance Optimization

### PHP Settings (.user.ini or php.ini)
```ini
# Increase memory limit for large database operations
memory_limit = 256M

# Increase max execution time
max_execution_time = 60

# Upload file size
upload_max_filesize = 50M
post_max_size = 50M

# Buffer output for better performance
output_buffering = 4096

# Connection pooling
mysql.max_links = 10
mysql.max_persistent = 5
```

### Database Optimization
```sql
-- Add indexes for faster queries
ALTER TABLE chat_messages ADD INDEX idx_email (sender_email);
ALTER TABLE chat_messages ADD INDEX idx_created (created_at);
ALTER TABLE quote_requests ADD INDEX idx_email (client_email);
ALTER TABLE quote_requests ADD INDEX idx_status (status);

-- Analyze table performance
ANALYZE TABLE chat_messages;
ANALYZE TABLE quote_requests;

-- Check table size
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB'
FROM information_schema.tables
WHERE table_schema = 'samangile_energy';
```

## Backup Strategy

### Automated MySQL Backup Script
Create `backup.sh`:
```bash
#!/bin/bash
BACKUP_DIR="/var/backups/mysql"
DB_NAME="samangile_energy"
DB_USER="samangile_user"
DB_PASS="secure_password_123"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/samangile_$DATE.sql

# Compress backup
gzip $BACKUP_DIR/samangile_$DATE.sql

# Remove backups older than 30 days
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $BACKUP_DIR/samangile_$DATE.sql.gz"
```

Make it executable:
```bash
chmod +x backup.sh
```

Schedule with cron (daily at 2 AM):
```bash
crontab -e
# Add: 0 2 * * * /path/to/backup.sh
```

## Monitoring & Alerts

### Check Database Connection
```php
<?php
function checkDatabase() {
    $conn = new mysqli('localhost', 'samangile_user', 'password', 'samangile_energy');
    if ($conn->connect_error) {
        error_log("Database Connection Failed: " . $conn->connect_error);
        return false;
    }
    return true;
}
?>
```

### Monitor Disk Space
```bash
#!/bin/bash
# Check if disk usage is above 80%
USAGE=$(df /var/www/html | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $USAGE -gt 80 ]; then
    echo "Warning: Disk usage is $USAGE%" | mail -s "Server Alert" admin@example.com
fi
```

## Development vs Production

### Development (.env.development)
```bash
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=debug
DB_HOST=localhost
DB_USER=root
DB_PASS=
DISPLAY_ERRORS=true
```

### Production (.env.production)
```bash
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
DB_HOST=192.168.1.10
DB_USER=samangile_user
DB_PASS=secure_random_password
DISPLAY_ERRORS=false
HTTPS=true
```

## SSL/TLS Certificate Setup

### Using Let's Encrypt (Free)
```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache

# Generate certificate
sudo certbot certonly --apache -d samangileenergysol.co.za -d www.samangileenergysol.co.za

# Auto-renewal
sudo certbot renew --dry-run
```

### Configure HTTPS in .htaccess
```apache
# Redirect HTTP to HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Strict-Transport-Security
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

## Troubleshooting

### Database Connection Errors
```bash
# Check if MySQL is running
sudo service mysql status

# Test connection
mysql -u samangile_user -p samangile_energy -e "SELECT VERSION();"

# Check error logs
tail -f /var/log/mysql/error.log
```

### Permission Issues
```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/html
sudo chmod 755 /var/www/html
sudo chmod 644 /var/www/html/*.php
```

### Email Not Sending
```php
<?php
// Test SMTP connection
$mail = new PHPMailer();
$mail->Host = 'smtp.gmail.com';
$mail->Port = 587;
$mail->SMTPSecure = 'tls';
$mail->SMTPAuth = true;
// ... configure and test
?>
```

## Security Best Practices

1. **Never commit .env to git**
   ```bash
   echo ".env" >> .gitignore
   ```

2. **Use strong passwords**
   ```bash
   # Generate secure password
   openssl rand -base64 32
   ```

3. **Regular updates**
   ```bash
   sudo apt-get update && sudo apt-get upgrade
   ```

4. **Monitor access logs**
   ```bash
   tail -f /var/log/apache2/access.log
   tail -f /var/log/nginx/access.log
   ```

5. **Regular backups** (automated via cron)

---

For more details, see `SETUP.md`
