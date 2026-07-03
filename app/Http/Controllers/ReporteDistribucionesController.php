<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Distribution;
use App\Models\DistributionTarget;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteDistribucionesController extends Controller
{
    public function index(Request $request)
    {
        $users = User::whereIn('id', Distribution::withTrashed()->select('created_by')->distinct()->pluck('created_by'))
            ->orWhereIn('id', AuditLog::where('action', 'delete')->select('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reportes.distribuciones.index', compact('users'));
    }

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $startIdx = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 50);

        try {
            $query = Distribution::withTrashed()
                ->with(['creator', 'targets', 'files']);

            if ($request->filled('created_by')) {
                $query->where('created_by', $request->created_by);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $total = $query->count();

            $distributions = $query->orderBy('id', 'desc')
                ->offset($startIdx)
                ->limit($length)
                ->get();

            $data = $distributions->map(function ($distribution) {
                $totalTargets = $distribution->targets->count();
                $failedTargets = $distribution->targets->where('status', 'failed')->count();
                $completedTargets = $distribution->targets->where('status', 'completed')->count();
                $pendingTargets = $distribution->targets->where('status', 'pending')->count();

                $files = $distribution->files->pluck('file_name')->implode(', ');
                $commandDisplay = $distribution->command
                    ? ($distribution->command.($distribution->command_args ? ' '.$distribution->command_args : ''))
                    : '';

                return [
                    'id' => $distribution->id,
                    'name' => $distribution->name,
                    'type' => $distribution->type,
                    'distribution_type' => $distribution->distribution_type,
                    'status' => $distribution->status,
                    'files' => $files,
                    'files_count' => $distribution->files->count(),
                    'command' => $commandDisplay,
                    'deleted_at' => $distribution->deleted_at?->format('Y-m-d H:i:s'),
                    'created_by' => $distribution->creator?->name ?? 'N/A',
                    'created_by_id' => $distribution->creator?->id,
                    'created_at' => $distribution->created_at?->format('Y-m-d H:i:s'),
                    'total_targets' => $totalTargets,
                    'completed_targets' => $completedTargets,
                    'failed_targets' => $failedTargets,
                    'pending_targets' => $pendingTargets,
                    'targets_progress' => $totalTargets > 0
                        ? round(($completedTargets / $totalTargets) * 100)
                        : 0,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Reporte distribuciones data error: '.$e->getMessage());

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function resumen(Request $request)
    {
        try {
            $distributionsQuery = Distribution::withTrashed();
            $auditQuery = AuditLog::where('action', 'delete');

            if ($request->filled('created_by')) {
                $distributionsQuery->where('created_by', $request->created_by);
                $auditQuery->where('user_id', $request->created_by);
            }

            if ($request->filled('fecha_desde')) {
                $distributionsQuery->whereDate('created_at', '>=', $request->fecha_desde);
                $auditQuery->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $distributionsQuery->whereDate('created_at', '<=', $request->fecha_hasta);
                $auditQuery->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $distributions = $distributionsQuery->get();

            $totalCreated = $distributions->count();
            $totalActive = $distributions->whereNull('deleted_at')->count();
            $totalDeleted = $distributions->whereNotNull('deleted_at')->count();
            $totalFailed = DistributionTarget::whereIn('distribution_id', $distributions->pluck('id'))
                ->where('status', 'failed')
                ->count();
            $totalCompleted = DistributionTarget::whereIn('distribution_id', $distributions->pluck('id'))
                ->where('status', 'completed')
                ->count();
            $totalPending = DistributionTarget::whereIn('distribution_id', $distributions->pluck('id'))
                ->where('status', 'pending')
                ->count();

            $immediate = $distributions->where('type', 'immediate')->count();
            $scheduled = $distributions->where('type', 'scheduled')->count();
            $recurring = $distributions->where('type', 'recurring')->count();

            $fileType = $distributions->where('distribution_type', 'file')->count();
            $updateType = $distributions->where('distribution_type', 'update')->count();
            $commandType = $distributions->where('distribution_type', 'command')->count();

            $deletedFromAudit = $auditQuery->count();

            return response()->json([
                'total_created' => $totalCreated,
                'total_active' => $totalActive,
                'total_deleted' => $totalDeleted,
                'total_failed' => $totalFailed,
                'total_completed' => $totalCompleted,
                'total_pending' => $totalPending,
                'immediate' => $immediate,
                'scheduled' => $scheduled,
                'recurring' => $recurring,
                'file_type' => $fileType,
                'update_type' => $updateType,
                'command_type' => $commandType,
                'deleted_from_audit' => $deletedFromAudit,
            ]);
        } catch (\Exception $e) {
            Log::error('Reporte distribuciones resumen error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function porUsuario(Request $request)
    {
        try {
            $query = Distribution::withTrashed()
                ->select('created_by', DB::raw('count(*) as total'), DB::raw('count(*) filter (where deleted_at is null) as activas'), DB::raw('count(*) filter (where deleted_at is not null) as eliminadas'))
                ->groupBy('created_by')
                ->with('creator');

            $auditQuery = AuditLog::where('action', 'delete')
                ->select('user_id', DB::raw('count(*) as total_deleted'))
                ->groupBy('user_id');

            if ($request->filled('fecha_desde')) {
                $query->whereDate('created_at', '>=', $request->fecha_desde);
                $auditQuery->whereDate('created_at', '>=', $request->fecha_desde);
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('created_at', '<=', $request->fecha_hasta);
                $auditQuery->whereDate('created_at', '<=', $request->fecha_hasta);
            }

            $userStats = $query->get();
            $deletedStats = $auditQuery->get()->keyBy('user_id');

            $data = $userStats->map(function ($stat) {
                $userId = $stat->created_by;
                $totalTargets = DistributionTarget::whereIn('distribution_id',
                    Distribution::withTrashed()->where('created_by', $userId)->pluck('id')
                )->count();
                $failedTargets = DistributionTarget::whereIn('distribution_id',
                    Distribution::withTrashed()->where('created_by', $userId)->pluck('id')
                )->where('status', 'failed')->count();

                return [
                    'user_id' => $userId,
                    'user_name' => $stat->creator?->name ?? 'N/A',
                    'total_created' => $stat->total,
                    'activas' => $stat->activas,
                    'eliminadas' => $stat->eliminadas,
                    'total_targets' => $totalTargets,
                    'failed_targets' => $failedTargets,
                ];
            });

            return response()->json(['data' => $data]);
        } catch (\Exception $e) {
            Log::error('Reporte distribuciones por usuario error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
