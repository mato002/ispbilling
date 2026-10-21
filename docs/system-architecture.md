# ISP Billing & Hotspot System Architecture

This diagram follows the current Laravel application structure. The web console, public hotspot portal, payment callbacks, background jobs, database, and router integration are shown as distinct parts of one application.

## System context and components

```mermaid
flowchart LR
    subgraph People[People and entry points]
        ISP[ISP admin or staff]
        Buyer[Hotspot customer on phone]
        PPPoE[PPPoE customer]
        RouterClient[Wi-Fi client device]
    end

    subgraph App[PrimeBilling Laravel application]
        Web[Web routes and middleware\nTenant scope, roles, sessions]
        AdminUI[ISP console\nCustomers, plans, routers, payments, reports]
        HotspotUI[Public hotspot portal\nSplash, packages, voucher, checkout, status]
        PayAPI[Payment initiation and callback endpoints]
        Controllers[Controllers\nISP, Hotspot, Payment, Customer]
        Services[Domain services\nPayment completion and posting\nM-Pesa, reports, vouchers, usage]
        Jobs[Queue jobs and scheduler\nRouter provisioning retries, expiry, sync, backups]
    end

    DB[(MySQL\nISPs and tenant settings\nCustomers, plans, subscriptions\nInvoices, payments, vouchers\nSessions, usage, audit records)]
    Daraja[Safaricom Daraja / M-Pesa\nSTK push and callbacks]
    OtherPay[Other payment gateways\nSelcom / Stripe callbacks]
    SMS[SMS provider]
    MT[MikroTik routers\nHotspot and PPPoE, RADIUS/session state]

    ISP --> AdminUI --> Web --> Controllers
    Buyer --> HotspotUI --> Web
    RouterClient --> MT
    MT -->|Captive portal redirect with router/client context| HotspotUI
    PPPoE -->|Payment or account activity| AdminUI
    Controllers --> Services
    Services <--> DB
    Jobs <--> DB
    Jobs <--> MT
    Services -->|STK request| Daraja
    Daraja -->|Authenticated callback| PayAPI
    OtherPay -->|Webhook| PayAPI
    PayAPI --> Controllers
    Services --> SMS
    AdminUI -->|Reads tenant-scoped records| DB
    AdminUI -->|CSV/PDF/XLSX/report views| ISP
```

## Hotspot purchase and activation

```mermaid
sequenceDiagram
    autonumber
    participant Phone as Customer phone
    participant Router as MikroTik hotspot router
    participant Portal as Hotspot portal
    participant DB as Application database
    participant Pay as M-Pesa Daraja
    participant Callback as Payment callback
    participant Complete as Payment completion service

    Phone->>Router: Join Wi-Fi without a valid session
    Router-->>Phone: Redirect to portal with router/client context
    Phone->>Portal: Open splash/packages page
    Portal->>DB: Resolve router, ISP, visible hotspot plans and portal settings
    DB-->>Portal: Plans, prices, Till/support details
    Portal-->>Phone: Compact package list; voucher and buy actions
    alt Redeem existing voucher
        Phone->>Portal: Submit voucher code
        Portal->>DB: Validate voucher and its tenant/router/plan
        Portal->>Router: Create/activate hotspot access
        Portal-->>Phone: Access/status result
    else Buy a package
        Phone->>Portal: Choose plan and enter phone number
        Portal->>DB: Create hotspot customer, pending subscription and invoice
        Phone->>Pay: Request STK push through application
        Pay-->>Phone: Show M-Pesa PIN prompt
        Pay->>Callback: Send payment result callback
        Callback->>DB: Match and record transaction/payment status
        Callback->>Complete: Complete confirmed payment (idempotently)
        Complete->>DB: Mark payment/invoice paid; renew subscription; activate customer
        Complete->>Router: Provision/enable hotspot user and apply limits
        Complete->>DB: Record activation outcome and payment side-effect marker
        Phone->>Portal: Poll signed payment status while modal verifies
        Portal->>DB: Read payment and activation state
        Portal-->>Phone: Success/access details, pending, cancelled, or failure state
        opt Router unavailable
            Complete-->>DB: Queue provisioning retry
        end
    end
```

## ISP admin and PPPoE billing flow

```mermaid
flowchart TD
    Admin[ISP staff in authenticated tenant workspace] --> Setup[Configure plans, customers, routers,
payment methods and portal settings]
    Setup --> Service[PPPoE subscription or hotspot service]
    Service --> Invoice[Invoice and payment record]
    Invoice -->|STK push| Gateway[M-Pesa / supported gateway]
    Gateway -->|Callback| Match[Validate callback and match transaction]
    Match --> Paid{Payment confirmed?}
    Paid -->|Yes| Complete[PaymentCompletionService]
    Paid -->|No / timeout| Failed[Keep failed or pending state;
show result to customer/admin]
    Complete --> Branch{Customer connection type}
    Branch -->|PPPoE| PPP[Renew subscription and enable PPPoE user]
    Branch -->|Hotspot| Hotspot[Renew prepaid plan and activate hotspot user]
    PPP --> Router[MikroTikService provisions router]
    Hotspot --> Router
    Router --> Retry[Retry job if router operation fails]
    Match --> Ledger[Payment, invoice and transaction records]
    Ledger --> AdminViews[ISP payments, failed/unposted, reconciliation,
receipts and reports/exports]
    Complete --> Notify[Customer and ISP notifications]
```

## Key data and separation

- `isp_id` scopes plans, customers, routers, invoices, payments, and portal configuration to an ISP workspace.
- Hotspot purchases create a customer with `connection_type = hotspot`; this lets payment processing and ISP reporting distinguish them from PPPoE customers.
- A payment is linked to its customer and invoice; the invoice links to the purchased subscription/plan. Confirmed payments trigger subscription renewal and router provisioning.
- Voucher redemption follows its own validation path and grants access without an STK transaction.
- Router commands and live connection state are external to the database; the app stores account, session, usage, and action records and synchronizes with MikroTik through `MikroTikService` and background jobs.
- Payment success is callback-driven. The browser's verification view polls application payment status; it does not itself confirm money received.
- ISP reports and exports read tenant-scoped payment, invoice, subscription, voucher, and session records. Reconciliation can compare gateway transaction records with service activation records.

## Source anchors

- Public portal routes: `routes/web.php` (`/hotspot/splash`, `/hotspot/packages`, `/hotspot/buy/{plan}`, voucher, status, and checkout access).
- Payment callbacks: `routes/api.php` (M-Pesa, Selcom, and Stripe webhook routes).
- Hotspot behavior: `app/Http/Controllers/Hotspot/PortalController.php`.
- Payment side effects: `app/Services/PaymentCompletionService.php` and `app/Services/PaymentPostingService.php`.
- Router integration: `app/Services/MikroTikService.php`.
- ISP reporting: `app/Http/Controllers/ISP/Web/ReportController.php` and `app/Services/IspReportBuilder.php`.
```
