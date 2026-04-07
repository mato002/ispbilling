<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Figtree', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .notification-box {
            border-left: 4px solid #3b82f6;
            background: #eff6ff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        .notification-box.success {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        .notification-box.warning {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }
        .notification-box.error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .notification-box.payment {
            border-left-color: #8b5cf6;
            background: #f5f3ff;
        }
        .notification-box.subscription {
            border-left-color: #6366f1;
            background: #eef2ff;
        }
        .notification-box.hotspot {
            border-left-color: #14b8a6;
            background: #f0fdfa;
        }
        .notification-title {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 8px;
            color: #1f2937;
        }
        .notification-message {
            color: #4b5563;
            margin-bottom: 15px;
        }
        .notification-meta {
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .footer {
            background: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            margin-top: 15px;
        }
        .btn:hover {
            background: #2563eb;
        }
        .logo {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">{{ config('app.name') }}</div>
            <h1>{{ $title }}</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p>Hello {{ $userName ?? 'Admin' }},</p>
            
            <div class="notification-box {{ $type ?? 'info' }}">
                <div class="notification-title">{{ $title }}</div>
                <div class="notification-message">{{ $message }}</div>
                @isset($actionUrl)
                    <a href="{{ $actionUrl }}" class="btn">{{ $actionText ?? 'View Details' }}</a>
                @endisset
                <div class="notification-meta">
                    Type: {{ ucfirst($type ?? 'info') }} | 
                    Sent: {{ now()->format('M d, Y H:i') }}
                </div>
            </div>

            @isset($additionalInfo)
                <div style="background: #f3f4f6; padding: 15px; border-radius: 6px; margin-top: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #1f2937;">Additional Information</h3>
                    <p style="margin: 0; color: #4b5563;">{{ $additionalInfo }}</p>
                </div>
            @endisset

            <p style="margin-top: 30px;">
                Best regards,<br>
                The {{ config('app.name') }} Team
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an automated notification from {{ config('app.name') }}.</p>
            <p style="margin-top: 10px; font-size: 12px;">
                If you no longer wish to receive these notifications, 
                please update your notification preferences in your account settings.
            </p>
        </div>
    </div>
</body>
</html>
