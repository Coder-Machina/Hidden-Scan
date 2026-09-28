<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasAuditLogging
{
    /**
     * Fields to exclude from audit logging (sensitive data).
     */
    protected static function auditExcludedFields(): array
    {
        return ['password', 'remember_token', 'updated_at', 'created_at'];
    }

    /**
     * Boot the trait and register model event listeners.
     */
    public static function bootHasAuditLogging(): void
    {
        static::created(function ($model) {
            static::logAuditEvent($model, 'created');
        });

        static::updated(function ($model) {
            // Only log if there are meaningful changes
            $changes = $model->getDirty();
            $excluded = static::auditExcludedFields();
            $meaningfulChanges = array_diff_key($changes, array_flip($excluded));

            if (!empty($meaningfulChanges)) {
                static::logAuditEvent($model, 'updated');
            }
        });

        static::deleted(function ($model) {
            static::logAuditEvent($model, 'deleted');
        });
    }

    /**
     * Create an audit log entry for a model event.
     */
    protected static function logAuditEvent($model, string $action): void
    {
        $excluded = static::auditExcludedFields();

        $oldValues = null;
        $newValues = null;

        if ($action === 'updated') {
            $dirty = $model->getDirty();
            $original = $model->getOriginal();

            $oldValues = [];
            $newValues = [];

            foreach ($dirty as $key => $value) {
                if (in_array($key, $excluded)) {
                    continue;
                }
                $oldValues[$key] = $original[$key] ?? null;
                $newValues[$key] = $value;
            }
        } elseif ($action === 'created') {
            $attributes = $model->getAttributes();
            $newValues = array_diff_key($attributes, array_flip($excluded));
        } elseif ($action === 'deleted') {
            $attributes = $model->getAttributes();
            $oldValues = array_diff_key($attributes, array_flip($excluded));
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Manually log a custom moderation/admin action on this model.
     */
    public function logCustomAction(string $action, ?array $details = null, ?int $userId = null): void
    {
        AuditLog::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'model_type' => get_class($this),
            'model_id' => $this->getKey(),
            'old_values' => null,
            'new_values' => $details,
            'ip_address' => Request::ip(),
        ]);
    }
}
