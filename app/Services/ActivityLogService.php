<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    /**
     * Log an activity
     */
    public static function log(
        string $action,
        string $description,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): ActivityLog {
        $user = Auth::user();
        $ispId = $user?->isp_id;

        // Get old values if model exists and is being updated
        if ($model && $model->exists && $oldValues === null) {
            $oldValues = $model->getOriginal();
        }

        // Get new values from model if not provided
        if ($model && $newValues === null) {
            $newValues = $model->getAttributes();
        }

        return ActivityLog::create([
            'isp_id' => $ispId,
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a create action
     */
    public static function logCreate(Model $model, ?string $description = null): ActivityLog
    {
        $description = $description ?? sprintf(
            'Created %s: %s',
            class_basename($model),
            $model->getKey()
        );

        return self::log('create', $description, $model);
    }

    /**
     * Log an update action
     */
    public static function logUpdate(
        Model $model,
        ?string $description = null,
        ?array $oldValues = null
    ): ActivityLog {
        $description = $description ?? sprintf(
            'Updated %s: %s',
            class_basename($model),
            $model->getKey()
        );

        return self::log('update', $description, $model, $oldValues);
    }

    /**
     * Log a delete action
     */
    public static function logDelete(Model $model, ?string $description = null): ActivityLog
    {
        $description = $description ?? sprintf(
            'Deleted %s: %s',
            class_basename($model),
            $model->getKey()
        );

        return self::log('delete', $description, $model, $model->getAttributes());
    }

    /**
     * Log a login action
     */
    public static function logLogin($user, ?string $description = null): ActivityLog
    {
        $description = $description ?? sprintf('User %s logged in', $user->email);

        return ActivityLog::create([
            'isp_id' => $user->isp_id,
            'user_id' => $user->id,
            'action' => 'login',
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a logout action
     */
    public static function logLogout($user, ?string $description = null): ActivityLog
    {
        $description = $description ?? sprintf('User %s logged out', $user->email);

        return ActivityLog::create([
            'isp_id' => $user->isp_id,
            'user_id' => $user->id,
            'action' => 'logout',
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log a custom action
     */
    public static function logAction(
        string $action,
        string $description,
        ?array $metadata = null
    ): ActivityLog {
        return self::log($action, $description, null, null, null, $metadata);
    }
}





