<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalSetting extends Model
{
    use BelongsToIspTenant;

    protected $fillable = [
        'isp_id',
        'welcome_message',
        'logo_url',
        'background_type',
        'background_value',
        'show_packages',
        'show_payment_button',
        'payment_button_text',
        'mpesa_till_number',
        'support_phone',
        'support_whatsapp',
        'theme_color',
        'custom_css',
    ];

    protected $casts = [
        'show_packages' => 'boolean',
        'show_payment_button' => 'boolean',
        'custom_css' => 'array',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public static function getForISP($ispId)
    {
        return static::where('isp_id', $ispId)->first();
    }

    public static function getOrCreateForISP($ispId)
    {
        $setting = static::where('isp_id', $ispId)->first();
        
        if (!$setting) {
            $setting = static::create([
                'isp_id' => $ispId,
                'welcome_message' => 'Welcome to WiFi',
                'background_type' => 'gradient',
                'background_value' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
                'show_packages' => true,
                'show_payment_button' => true,
                'payment_button_text' => 'Buy Now',
                'theme_color' => '#3B82F6',
            ]);
        }
        
        return $setting;
    }
}
