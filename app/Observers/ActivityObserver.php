<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class ActivityObserver
{
    private array $excludeKeys = ['updated_at', 'created_at', 'password', 'remember_token'];

    public function created(Model $model): void
    {
        ActivityLogger::log('created', $model);
    }

    public function updated(Model $model): void
    {
        $dirty = collect($model->getChanges())
            ->except($this->excludeKeys)
            ->toArray();

        if (empty($dirty)) {
            return;
        }

        $original = collect($model->getOriginal())->only(array_keys($dirty))->toArray();
        $changes = [];
        foreach ($dirty as $key => $newVal) {
            $changes[$key] = [
                'from' => $original[$key] ?? null,
                'to'   => $newVal,
            ];
        }

        ActivityLogger::log('updated', $model, $changes);
    }

    public function deleted(Model $model): void
    {
        $action = method_exists($model, 'trashed') && $model->trashed() ? 'archived' : 'deleted';
        ActivityLogger::log($action, $model);
    }

    public function restored(Model $model): void
    {
        ActivityLogger::log('restored', $model);
    }

    public function forceDeleted(Model $model): void
    {
        ActivityLogger::log('purged', $model);
    }
}
