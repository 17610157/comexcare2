<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\DashboardAlertEvent;
use App\Models\DashboardAlertRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AdminAuditObserver
{
    protected array $labels = [
        User::class => 'usuario',
        Role::class => 'rol',
        Permission::class => 'permiso',
    ];

    public function created(Model $model): void
    {
        $this->handle('create', $model);
    }

    public function updated(Model $model): void
    {
        $this->handle('update', $model);
    }

    public function deleted(Model $model): void
    {
        $this->handle('delete', $model);
    }

    protected function handle(string $action, Model $model): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $type = class_basename($model);
        $name = $model->name ?? ('#'.$model->getKey());
        $user = auth()->user();

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'endpoint' => 'model://'.$type.'/'.($model->getKey() ?? ''),
            'method' => strtoupper($action),
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 500),
            'request_data' => [
                'model' => $type,
                'id' => $model->getKey(),
                'name' => $name,
                'by' => $user?->name,
            ],
            'response_code' => 200,
            'duration_ms' => null,
        ]);

        $rule = DashboardAlertRule::where('metric_key', 'admin_changes')->whereRaw('enabled')->first();
        if (! $rule) {
            return;
        }

        DashboardAlertEvent::create([
            'rule_id' => $rule->id,
            'value_pct' => null,
            'message' => sprintf(
                '%s %s "%s" por %s',
                ucfirst($this->labels[$model::class] ?? $type),
                ['create' => 'creado', 'update' => 'modificado', 'delete' => 'eliminado'][$action] ?? $action,
                $name,
                $user?->name ?? 'sistema'
            ),
            'meta' => ['model' => $type, 'id' => $model->getKey(), 'action' => $action],
            'triggered_at' => now(),
        ]);
    }
}
