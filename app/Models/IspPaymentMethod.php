<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class IspPaymentMethod extends Model
{
    protected $table = 'isp_payment_methods';

    protected $fillable = [
        'isp_id',
        'provider',
        'credentials',
        'callback_url',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $hidden = [
        'credentials',
    ];

    public function isp(): BelongsTo
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    /**
     * Set encrypted credentials
     */
    public function setCredentialsAttribute($value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $this->attributes['credentials'] = Crypt::encryptString($value);
    }

    /**
     * Get decrypted credentials
     */
    public function getCredentialsAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($value);
            return json_decode($decrypted, true) ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to decrypt payment method credentials: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a specific credential value
     */
    public function getCredential(string $key, $default = null)
    {
        $credentials = $this->credentials;
        return $credentials[$key] ?? $default;
    }

    /**
     * Update credentials
     */
    public function updateCredentials(array $credentials): void
    {
        $this->credentials = $credentials;
        $this->save();
    }

    /**
     * Activate payment method
     */
    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    /**
     * Deactivate payment method
     */
    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Scope to filter by ISP
     */
    public function scopeForIsp($query, $ispId)
    {
        return $query->where('isp_id', $ispId);
    }

    /**
     * Scope to filter by provider
     */
    public function scopeByProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to get active methods
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}





