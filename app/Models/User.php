<?php

namespace App\Models;

use App\Models\Concerns\BelongsToIspTenant;
use App\Support\IspTenant;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use BelongsToIspTenant;
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'isp_id',
        'role',
        'is_active',
    ];

    /**
     * {@inheritdoc}
     *
     * Super admins manage operators across all ISPs; tenant scope would 404
     * on users outside the current tenant when resolving route model binding.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field ??= $this->getRouteKeyName();

        $query = static::query();

        $auth = IspTenant::resolveAuthenticatedUser();
        if ($auth !== null && $auth->isSuperAdmin()) {
            $query->withoutGlobalScope('isp_tenant');
        }

        return $query->where($field, $value)->firstOrFail();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isp()
    {
        return $this->belongsTo(ISP::class, 'isp_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isIspAdmin(): bool
    {
        return $this->role === 'isp_admin';
    }

    public function isIspStaff(): bool
    {
        return $this->role === 'isp_staff';
    }

    public function isTechnician(): bool
    {
        return $this->role === 'isp_technician';
    }

    public function isIspOperator(): bool
    {
        return $this->isIspAdmin() || $this->isIspStaff();
    }

    public function technicianInventoryAssignments()
    {
        return $this->hasMany(InventoryTechnicianAssignment::class, 'technician_user_id');
    }
}
