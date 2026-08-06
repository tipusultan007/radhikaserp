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
            $model->logActivity('created', "Created {$model->getModelName()} record");
        });

        static::updated(function ($model) {
            $model->logActivity('updated', "Updated {$model->getModelName()} record");
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted', "Deleted {$model->getModelName()} record");
        });
    }

    /**
     * Get the human-readable model name for logging.
     */
    protected function getModelName()
    {
        // Extract the class basename (e.g., "Sale", "Product")
        $className = class_basename(static::class);
        // Convert to a space-separated readable string, e.g., "StockAdjustment" -> "Stock Adjustment"
        return trim(preg_replace('/(?<!\ )[A-Z]/', ' $0', $className));
    }

    /**
     * Log the activity to the database.
     */
    protected function logActivity($action, $description)
    {
        $userId = auth()->id() ?? 1;
        if (auth()->check() && !(auth()->user() instanceof \App\Models\User)) {
            $userId = 1; // Fallback to admin ID 1 if a customer triggers this
        }

        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'reference_type' => static::class,
            'reference_id' => $this->id ?? 0,
            'description' => $description,
        ]);
    }
}
