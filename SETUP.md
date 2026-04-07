# Setup Guide

## Quick Start

1. **Install Dependencies**
```bash
composer install
npm install
```

2. **Configure Environment**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Setup Database**
```bash
# For SQLite (default)
touch database/database.sqlite

# Or configure MySQL/PostgreSQL in .env
```

4. **Run Migrations**
```bash
php artisan migrate
```

5. **Install RouterOS Package**
```bash
# The package evgeny-rodionov/routeros-api-php is already in composer.json
# If you need a different package, update composer.json and run:
composer update
```

## Important Notes

### MikroTik Integration
The `MikroTikService` uses the `evgeny-rodionov/routeros-api-php` package. The API structure in the service may need adjustment based on:
- The actual package version
- Your RouterOS version
- Your preferred connection method (API vs REST)

**Alternative**: For RouterOS v7+, you can use the REST API instead:
- Enable REST API on router: `/ip service enable api-ssl`
- Use HTTP client instead of the binary API

### Payment Gateways

#### MPesa Setup
1. Register at https://developer.safaricom.co.ke/
2. Create an app to get Consumer Key & Secret
3. Get Passkey from your Lipa na MPesa Online account
4. Add credentials to `.env` or ISP settings

#### Selcom Setup
1. Register at https://developer.selcommobile.com/
2. Get API credentials from your dashboard
3. Add credentials to `.env` or ISP settings

### Scheduled Jobs

Add to `app/Console/Kernel.php`:

```php
use App\Jobs\GenerateInvoices;
use App\Jobs\SuspendUnpaidUsers;

protected function schedule(Schedule $schedule)
{
    // Generate invoices daily at 2 AM
    $schedule->job(new GenerateInvoices)->dailyAt('02:00');
    
    // Suspend unpaid users daily at 3 AM
    $schedule->job(new SuspendUnpaidUsers)->dailyAt('03:00');
}
```

Then add to crontab:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

### Authentication

The system uses Laravel Sanctum for API authentication. Set up authentication as needed:

```bash
php artisan install:api
```

Or use Laravel Breeze for web authentication:
```bash
composer require laravel/breeze --dev
php artisan breeze:install
```

## Testing

1. Create a test ISP
2. Add a test router (use a test MikroTik instance)
3. Create a test plan
4. Create a test customer
5. Test payment flow

## Production Checklist

- [ ] Update `.env` with production credentials
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Configure proper database (MySQL/PostgreSQL)
- [ ] Set up SSL certificates
- [ ] Configure queue workers
- [ ] Set up scheduled tasks (cron)
- [ ] Test payment webhooks
- [ ] Test MikroTik integration
- [ ] Configure backup strategy
- [ ] Set up monitoring/logging

