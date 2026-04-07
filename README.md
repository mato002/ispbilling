# Smart ISP Billing & Subscriber Management System

A comprehensive multi-tenant ISP billing and subscriber management system with automatic billing, MikroTik router integration, and payment gateway support (MPesa & Selcom).

## Features

### 🏢 Multi-Tenant ISP Management
- Each ISP has isolated data (customers, routers, plans, invoices)
- ISP-specific settings and payment gateway credentials
- Role-based access control (Super Admin, ISP Admin, ISP Staff)

### 🌐 Network Architecture Support
- **Hotspot Users**: Time or data-based billing, perfect for cafes & public Wi-Fi
- **PPPoE Users**: Always-on internet with monthly billing, ideal for homes & estates
- Full MikroTik RouterOS API integration for automated user management

### 👥 Customer Management
- Complete customer profiles with location tracking
- Automatic username generation
- Connection type management (Hotspot/PPPoE)
- Status tracking (Active, Suspended, Expired, Inactive)
- Installation date tracking

### 📦 Plans & Speed Profiles
- Flexible plan creation with pricing and duration
- Speed profile assignment (upload/download limits)
- Support for time-based, data-based, and unlimited plans
- MikroTik profile integration

### 🧾 Automated Billing
- Automatic invoice generation (7 days before subscription expiry)
- Due date tracking with overdue penalties
- PDF invoice generation (ready for implementation)
- Multiple invoice statuses (Pending, Paid, Overdue, Cancelled)

### 💰 Payment Processing (Kenya-First)
1. **MPesa STK Push** - Direct mobile money payments
2. **Selcom** - Wallet & bank transfer support
3. **Manual Payments** - For cash/other payment methods

### 🔄 Router Automation (Core Feature)
Via MikroTik API:
- ✅ Create hotspot/PPPoE users automatically
- ✅ Assign speed profiles
- ✅ Disable users if unpaid (automatic suspension)
- ✅ Enable users after payment (automatic activation)
- ✅ Disconnect active sessions
- ✅ Update user profiles

### 🔔 Notifications
- SMS payment reminders (via Vonage/Nexmo)
- Email invoices and payment confirmations
- WhatsApp support (ready for integration)

### 📊 Reports & Analytics
- Active users count
- Revenue per ISP
- Unpaid invoices tracking
- Plan popularity metrics
- Monthly revenue reports

## Technology Stack

- **Framework**: Laravel 12
- **Database**: SQLite (default, easily switchable to MySQL/PostgreSQL)
- **Payment Gateways**: MPesa API, Selcom API
- **Router Integration**: MikroTik RouterOS API
- **PDF Generation**: DomPDF
- **Notifications**: Laravel Notifications (Email, SMS, WhatsApp ready)

## Installation

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- MikroTik RouterOS (for router integration)

### Setup Steps

1. **Clone and install dependencies**
```bash
composer install
npm install
```

2. **Environment Configuration**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Configure Environment Variables**
Add to your `.env` file:

```env
# Database
DB_CONNECTION=sqlite
# Or use MySQL/PostgreSQL
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=isp_billing
# DB_USERNAME=root
# DB_PASSWORD=

# MPesa Configuration
MPESA_CONSUMER_KEY=your_consumer_key
MPESA_CONSUMER_SECRET=your_consumer_secret
MPESA_PASSKEY=your_passkey
MPESA_SHORTCODE=your_shortcode
MPESA_ENVIRONMENT=sandbox

# Selcom Configuration
SELCOM_API_KEY=your_api_key
SELCOM_API_SECRET=your_api_secret
SELCOM_VENDOR_ID=your_vendor_id
SELCOM_ENVIRONMENT=sandbox

# SMS (Vonage/Nexmo) - Optional
VONAGE_KEY=your_vonage_key
VONAGE_SECRET=your_vonage_secret
```

4. **Run Migrations**
```bash
php artisan migrate
```

5. **Create Database File (if using SQLite)**
```bash
touch database/database.sqlite
```

6. **Install RouterOS PHP Library**
The system uses `evgeny-rodionov/routeros-api-php` for MikroTik integration. Ensure your MikroTik router has API access enabled.

## Usage

### Creating an ISP

```php
use App\Models\ISP;

$isp = ISP::create([
    'name' => 'MyISP',
    'company_name' => 'My ISP Company Ltd',
    'email' => 'admin@myisp.com',
    'phone' => '+255123456789',
    'currency' => 'KES',
    'settings' => [
        'mpesa_consumer_key' => '...',
        'mpesa_consumer_secret' => '...',
        // ... other settings
    ],
]);
```

### Adding a Router

```php
use App\Models\Router;

$router = Router::create([
    'isp_id' => $isp->id,
    'name' => 'Main Router',
    'ip_address' => '192.168.1.1',
    'username' => 'admin',
    'password' => 'router_password',
    'port' => 8728,
    'use_ssl' => false,
]);
```

### Creating a Plan

```php
use App\Models\Plan;

$plan = Plan::create([
    'isp_id' => $isp->id,
    'name' => '5Mbps Home',
    'access_type' => 'pppoe',
    'price' => 5000.00,
    'duration_days' => 30,
    'speed_profile' => '5mbps-profile',
    'download_speed_kbps' => 5120,
    'upload_speed_kbps' => 2560,
    'billing_type' => 'unlimited',
]);
```

### Creating a Customer

The system automatically:
- Generates a unique username
- Creates the user on the MikroTik router
- Creates an active subscription
- Activates the customer

```php
use App\Models\Customer;
use App\Services\MikroTikService;

$customer = Customer::create([
    'isp_id' => $isp->id,
    'router_id' => $router->id,
    'full_name' => 'John Doe',
    'phone_number' => '+255123456789',
    'location' => 'Dar es Salaam',
    'connection_type' => 'pppoe',
    'plan_id' => $plan->id,
    'installation_date' => now(),
]);

// Router integration is handled automatically via the controller
```

### Automated Jobs

Set up cron jobs for automated tasks:

```bash
# In your crontab
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Generate invoices daily
    $schedule->job(new \App\Jobs\GenerateInvoices)->daily();
    
    // Suspend unpaid users daily
    $schedule->job(new \App\Jobs\SuspendUnpaidUsers)->daily();
}
```

## API Endpoints

### Payment Endpoints
- `POST /payments/initiate` - Initiate payment (MPesa/Selcom/Manual)
- `POST /payments/mpesa/callback` - MPesa webhook (public)
- `POST /payments/selcom/callback` - Selcom webhook (public)
- `POST /payments/{payment}/confirm-manual` - Confirm manual payment

### ISP Endpoints (Authenticated)
- `GET /isp/customers` - List customers
- `POST /isp/customers` - Create customer
- `GET /isp/customers/{id}` - Get customer details
- `POST /isp/customers/{id}/suspend` - Suspend customer
- `POST /isp/customers/{id}/activate` - Activate customer
- `DELETE /isp/customers/{id}` - Delete customer

- `GET /isp/plans` - List plans
- `POST /isp/plans` - Create plan
- `PUT /isp/plans/{id}` - Update plan
- `DELETE /isp/plans/{id}` - Delete plan

- `GET /isp/routers` - List routers
- `POST /isp/routers` - Add router
- `PUT /isp/routers/{id}` - Update router
- `DELETE /isp/routers/{id}` - Delete router

- `GET /isp/billing/invoices` - List invoices
- `GET /isp/billing/payments` - List payments
- `POST /isp/billing/generate-invoices` - Generate invoices
- `GET /isp/billing/stats` - Get billing statistics

## MikroTik Router Setup

1. **Enable API on RouterOS**
```
/ip service enable api
/ip service set api port=8728
```

2. **Create API User** (optional, can use admin)
```
/user add name=api-user password=strong-password group=full
```

3. **Configure IP Binding** (for security)
```
/ip service set api address=192.168.1.0/24
```

## Payment Gateway Setup

### MPesa
1. Register at [Safaricom Developer Portal](https://developer.safaricom.co.ke/)
2. Get Consumer Key, Consumer Secret, and Passkey
3. Configure in ISP settings or `.env`

### Selcom
1. Register at [Selcom Developer Portal](https://developer.selcommobile.com/)
2. Get API Key, API Secret, and Vendor ID
3. Configure in ISP settings or `.env`

## Security Considerations

- All payment webhooks should verify signatures
- Router passwords are encrypted in database
- Multi-tenant isolation ensures ISP data separation
- Role-based access control for different user types
- API authentication required for all endpoints (except webhooks)

## Roadmap

- [ ] PDF invoice generation with templates
- [ ] WhatsApp notification integration
- [ ] Advanced analytics dashboard
- [ ] Customer self-service portal
- [ ] Mobile app for customers
- [ ] Automated speed profile management
- [ ] Bandwidth usage tracking
- [ ] Multi-currency support
- [ ] Tax calculation engine
- [ ] Recurring payment setup

## Contributing

This is a production-ready system. Contributions are welcome!

## License

MIT License - feel free to use for commercial projects.

## Support

For issues and questions, please open an issue on the repository.

---

**Built with ❤️ for ISPs in Kenya and East Africa**
