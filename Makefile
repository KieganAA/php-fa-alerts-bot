# Start a local PHP development server
server-local:
	@echo "Starting local PHP server..."
	php -S localhost:8080 -t public

# Start ngrok for tunneling
tunnel:
	@echo "Starting ngrok..."
	ngrok http 8080

# Set the Telegram webhook
webhook:
	@echo "Setting Telegram webhook..."
	curl -F "url= https://db52-95-169-205-160.ngrok-free.app/index.php" \
        https://api.telegram.org/bot7728566035:AAG6_nXFVOUKX5K-wVzYCDTYgFJiQYJmb8A/setWebhook

# Clear Telegram webhook
webhook-clear:
	@echo "Clearing Telegram webhook..."
	curl -F "url=" https://api.telegram.org/bot7728566035:AAG6_nXFVOUKX5K-wVzYCDTYgFJiQYJmb8A/deleteWebhook

# Get Telegram webhook information
webhook-info:
	@echo "Fetching Telegram webhook information..."
	curl https://api.telegram.org/bot7728566035:AAG6_nXFVOUKX5K-wVzYCDTYgFJiQYJmb8A/getWebhookInfo

# Restart essential services
.PHONY: restart
restart:
	@echo "Restarting services..."
	sudo systemctl restart nginx
	sudo systemctl restart mysql
	sudo systemctl restart php-fpm

# Tail Nginx error logs
logs:
	@echo "Tailing Nginx error logs..."
	sudo tail -f /var/log/nginx/error.log

# Tail Nginx error logs
access-logs:
	@echo "Tailing Nginx error logs..."
	sudo tail -f /var/log/nginx/access.log

# Clear Nginx logs
clear-logs:
	@echo "Clearing Nginx logs..."
	sudo truncate -s 0 /var/log/nginx/error.log
	sudo truncate -s 0 /var/log/nginx/access.log
	@echo "Logs cleared."

# Tail PHP-FPM logs
php-logs:
	@echo "Tailing PHP-FPM logs..."
	sudo journalctl -u php-fpm -f

# Clear PHP-FPM logs
clear-php-logs:
	@echo "Clearing PHP-FPM logs..."
	sudo truncate -s 0 /var/log/php-fpm/error.log
	@echo "PHP-FPM logs cleared."

# Install PHP dependencies
install-dependencies:
	@echo "Installing PHP dependencies..."
	composer install

# Update PHP dependencies
update-dependencies:
	@echo "Updating PHP dependencies..."
	composer update

# Check for syntax errors in PHP files
lint:
	@echo "Running PHP linting..."
	find . -type f -name "*.php" -exec php -l {} \;

# Run PHPUnit tests
test:
	@echo "Running PHPUnit tests..."
	vendor/bin/phpunit --testdox

# Check code style with PHP CS Fixer
fix-style:
	@echo "Fixing code style issues..."
	vendor/bin/php-cs-fixer fix

# Check for security vulnerabilities using Composer
security-check:
	@echo "Checking for security vulnerabilities..."
	composer audit

# Export MySQL database
db-export:
	@echo "Exporting database..."
	mysqldump -u root -p conference_bot_db > database_export.sql
	@echo "Database exported to database_export.sql."

# Import MySQL database
db-import:
	@echo "Importing database..."
	mysql -u root -p conference_bot_db < database_import.sql
	@echo "Database imported from database_import.sql."

# Clear cache (example for Laravel projects)
clear-cache:
	@echo "Clearing cache..."
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	@echo "Cache cleared."

# Show disk usage
disk-usage:
	@echo "Checking disk usage..."
	df -h

# Show memory usage
memory-usage:
	@echo "Checking memory usage..."
	free -h

# Check active services
services-status:
	@echo "Checking service statuses..."
	systemctl status nginx mysql php-fpm

# Stop all essential services
stop-services:
	@echo "Stopping services..."
	sudo systemctl stop nginx
	sudo systemctl stop mysql
	sudo systemctl stop php-fpm
	@echo "Services stopped."

# Start all essential services
start-services:
	@echo "Starting services..."
	sudo systemctl start nginx
	sudo systemctl start mysql
	sudo systemctl start php-fpm
	@echo "Services started."
