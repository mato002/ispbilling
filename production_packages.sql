-- Production SQL queries to insert sample packages
-- Replace 'YOUR_ISP_ID' with your actual ISP ID from the isps table

-- First, get your ISP ID by running:
-- SELECT id FROM isps LIMIT 1;

-- Then insert packages (replace YOUR_ISP_ID with the actual ID)

INSERT INTO plans (isp_id, name, price, access_type, duration_days, download_speed_kbps, upload_speed_kbps, data_limit_mb, billing_type, description, is_active, created_at, updated_at) VALUES
(1, 'Starter Package', 50.00, 'hotspot', 1, 2048, 1024, 1024, 'data', 'Perfect for light browsing and social media', 1, NOW(), NOW()),
(1, 'Basic Package', 100.00, 'hotspot', 3, 5120, 2560, 3072, 'data', 'Great for regular internet usage', 1, NOW(), NOW()),
(1, 'Standard Package', 200.00, 'hotspot', 7, 10240, 5120, 10240, 'data', 'Ideal for streaming and downloads', 1, NOW(), NOW()),
(1, 'Premium Package', 500.00, 'hotspot', 30, 20480, 10240, 51200, 'data', 'Unlimited entertainment and work', 1, NOW(), NOW()),
(1, 'Business Package', 1000.00, 'hotspot', 30, 51200, 25600, 102400, 'data', 'High-speed connection for business needs', 1, NOW(), NOW()),
(1, 'Ultimate Package', 2000.00, 'hotspot', 30, 102400, 51200, NULL, 'unlimited', 'Unlimited data with maximum speed', 1, NOW(), NOW());

-- Alternative: If you know your ISP ID is 1, use this directly:
INSERT INTO plans (isp_id, name, price, access_type, duration_days, download_speed_kbps, upload_speed_kbps, data_limit_mb, billing_type, description, is_active, created_at, updated_at) VALUES
(1, 'Starter Package', 50.00, 'hotspot', 1, 2048, 1024, 1024, 'data', 'Perfect for light browsing and social media', 1, NOW(), NOW()),
(1, 'Basic Package', 100.00, 'hotspot', 3, 5120, 2560, 3072, 'data', 'Great for regular internet usage', 1, NOW(), NOW()),
(1, 'Standard Package', 200.00, 'hotspot', 7, 10240, 5120, 10240, 'data', 'Ideal for streaming and downloads', 1, NOW(), NOW()),
(1, 'Premium Package', 500.00, 'hotspot', 30, 20480, 10240, 51200, 'data', 'Unlimited entertainment and work', 1, NOW(), NOW()),
(1, 'Business Package', 1000.00, 'hotspot', 30, 51200, 25600, 102400, 'data', 'High-speed connection for business needs', 1, NOW(), NOW()),
(1, 'Ultimate Package', 2000.00, 'hotspot', 30, 102400, 51200, NULL, 'unlimited', 'Unlimited data with maximum speed', 1, NOW(), NOW());
