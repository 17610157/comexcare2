<?php

namespace App\Http\Controllers;

use App\Models\DashboardAlertEvent;
use App\Models\DashboardAlertRule;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardAlertController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function state(Request $request): JsonResponse
    {
        app(AlertService::class)->evaluateAndRecord();

        $active = $this->eventsQuery()->whereNull('acknowledged_at')->orderByDesc('triggered_at')->limit(30)->get();
        $history = $this->eventsQuery()->orderByDesc('triggered_at')->limit(60)->get();

        return response()->json([
            'active' => $active,
            'history' => $history,
            'active_count' => $this->eventsQuery()->whereNull('acknowledged_at')->count(),
            'rules' => DashboardAlertRule::orderBy('id')->get(),
            'can_configure' => Auth::user()?->can('alertas.configurar') ?? false,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    protected function eventsQuery()
    {
        return DashboardAlertEvent::query()
            ->join('dashboard_alert_rules as r', 'r.id', '=', 'dashboard_alert_events.rule_id')
            ->selectRaw(
                'dashboard_alert_events.id, dashboard_alert_events.value_pct, dashboard_alert_events.message, dashboard_alert_events.meta, dashboard_alert_events.triggered_at, dashboard_alert_events.acknowledged_at, r.label, r.metric_key, r.severity, r.sound_path, r.threshold_pct'
            );
    }

    public function ack(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['required_without:all', 'integer'],
            'all' => ['required_without:id', 'boolean'],
        ]);

        if (! empty($data['all'])) {
            DashboardAlertEvent::whereNull('acknowledged_at')->update([
                'acknowledged_at' => now(),
                'acknowledged_by' => Auth::id(),
            ]);
        } else {
            DashboardAlertEvent::where('id', $data['id'])->whereNull('acknowledged_at')->update([
                'acknowledged_at' => now(),
                'acknowledged_by' => Auth::id(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function simulate(Request $request): JsonResponse
    {
        $service = app(AlertService::class);
        $ruleId = $request->input('rule_id');

        $rule = $ruleId
            ? DashboardAlertRule::find($ruleId)
            : DashboardAlertRule::whereRaw('enabled')->whereIn('comparator', ['gt', 'lt'])->first();

        if (! $rule) {
            return response()->json(['success' => false], 404);
        }

        $value = $rule->comparator === 'lt'
            ? max(0, ($rule->threshold_pct ?? 100) - 5)
            : min(100, ($rule->threshold_pct ?? 0) + 5);

        $event = $service->recordEvent($rule, (float) $value, '[SIMULACIÓN] '.AlertService::describeBreach($rule, (float) $value), ['simulated' => true]);

        return response()->json(['success' => true, 'event_id' => $event->id]);
    }

    public function updateRule(Request $request, DashboardAlertRule $rule): JsonResponse
    {
        abort_unless(Auth::user()?->can('alertas.configurar'), 403);

        $data = $request->validate([
            'threshold_pct' => ['nullable', 'integer', 'between:0,100'],
            'enabled' => ['boolean'],
            'cooldown_min' => ['integer', 'between:1,240'],
            'sound_path' => ['nullable', 'string', 'max:255'],
            'severity' => ['nullable', 'in:info,warning,critical'],
        ]);

        if (isset($data['sound_path']) && $data['sound_path'] !== null && $data['sound_path'] !== '') {
            abort_unless(str_starts_with($data['sound_path'], 'vendor/sounds/'), 422, 'Ruta de sonido inválida');
        }

        $rule->update($data);

        return response()->json(['success' => true, 'rule' => $rule->fresh()]);
    }

    public function sounds(): JsonResponse
    {
        $dir = public_path('vendor/sounds');
        $files = [];

        if (is_dir($dir)) {
            foreach (scandir($dir) ?: [] as $f) {
                if (preg_match('/\.(mp3|wav|ogg)$/i', $f)) {
                    $files[] = 'vendor/sounds/'.$f;
                }
            }
        }
        sort($files);

        return response()->json(['sounds' => $files]);
    }

    public function uploadSound(Request $request): JsonResponse
    {
        abort_unless(Auth::user()?->can('alertas.configurar'), 403);

        $request->validate([
            'sound' => ['required', 'file', 'mimes:mp3,wav,ogg', 'max:2048'],
        ]);

        $file = $request->file('sound');
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = $name.'-'.time().'.'.strtolower($file->getClientOriginalExtension());

        $dir = public_path('vendor/sounds');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file->move($dir, $filename);

        return response()->json(['success' => true, 'path' => 'vendor/sounds/'.$filename]);
    }
}
