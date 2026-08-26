<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    /**
     * Boot the trait to listen for Eloquent events.
     */
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $attributes = $model->filterSensitiveAttributes($model->getAttributes());
            $identifier = $model->getRecordIdentifier();
            $label = $identifier ? " ({$identifier})" : " #{$model->id}";

            $model->logActivity(
                'created',
                "Created {$model->getModelName()}{$label}",
                ['attributes' => $attributes]
            );
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            if (empty($dirty)) return;

            $oldValues = [];
            $newValues = [];
            $changes = [];

            foreach ($dirty as $key => $newValue) {
                if (in_array($key, ['updated_at', 'created_at'])) continue;

                $oldValue = $model->getOriginal($key);

                // Handle sensitive fields
                if (in_array($key, ['password', 'remember_token'])) {
                    $oldValues[$key] = '********';
                    $newValues[$key] = '********';
                    $changes[] = "{$key} was modified";
                    continue;
                }

                $oldValues[$key] = $oldValue;
                $newValues[$key] = $newValue;

                $oldStr = is_array($oldValue) ? json_encode($oldValue) : (string) ($oldValue ?? 'null');
                $newStr = is_array($newValue) ? json_encode($newValue) : (string) ($newValue ?? 'null');
                
                if (strlen($oldStr) > 40) $oldStr = substr($oldStr, 0, 37) . '...';
                if (strlen($newStr) > 40) $newStr = substr($newStr, 0, 37) . '...';

                $changes[] = "{$key}: '{$oldStr}' → '{$newStr}'";
            }

            if (!empty($newValues)) {
                $identifier = $model->getRecordIdentifier();
                $label = $identifier ? " ({$identifier})" : " #{$model->id}";
                $desc = "Updated {$model->getModelName()}{$label}: " . implode(', ', $changes);

                $model->logActivity(
                    'updated',
                    $desc,
                    [
                        'old' => $oldValues,
                        'attributes' => $newValues
                    ]
                );
            }
        });

        static::deleted(function ($model) {
            $snapshot = $model->filterSensitiveAttributes($model->getAttributes());
            $identifier = $model->getRecordIdentifier();
            $label = $identifier ? " ({$identifier})" : " #{$model->id}";

            $model->logActivity(
                'deleted',
                "Deleted {$model->getModelName()}{$label}",
                ['old' => $snapshot]
            );
        });
    }

    /**
     * Get the human-readable model name for logging.
     */
    protected function getModelName(): string
    {
        $className = class_basename(static::class);
        return trim(preg_replace('/(?<!\ )[A-Z]/', ' $0', $className));
    }

    /**
     * Get a human-readable identifier for the model.
     */
    protected function getRecordIdentifier(): ?string
    {
        foreach (['invoice_no', 'name', 'title', 'code', 'order_no', 'reference_no', 'batch_number', 'email', 'username'] as $field) {
            if (!empty($this->{$field}) && is_scalar($this->{$field})) {
                return (string) $this->{$field};
            }
        }
        return !empty($this->id) ? "#{$this->id}" : null;
    }

    /**
     * Filter out or mask sensitive attributes.
     */
    protected function filterSensitiveAttributes(array $attributes): array
    {
        $sensitive = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];
        foreach ($sensitive as $key) {
            if (isset($attributes[$key])) {
                $attributes[$key] = '********';
            }
        }
        return $attributes;
    }

    /**
     * Log the activity to the database.
     */
    protected function logActivity(string $action, string $description, ?array $properties = null): void
    {
        $userId = auth()->id() ?? 1;
        if (auth()->check() && !(auth()->user() instanceof \App\Models\User)) {
            $userId = 1;
        }

        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'reference_type' => static::class,
            'reference_id' => $this->id ?? 0,
            'description' => $description,
            'properties' => $properties,
        ]);
    }
}

