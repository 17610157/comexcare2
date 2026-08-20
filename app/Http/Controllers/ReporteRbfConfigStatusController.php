<?php

namespace App\Http\Controllers;

use App\Models\RbfConfigStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteRbfConfigStatusController extends Controller
{
    public function index(Request $request)
    {
        $syncedAt = RbfConfigStatus::max('synced_at') ? \Carbon\Carbon::parse(RbfConfigStatus::max('synced_at')) : null;
        $total = RbfConfigStatus::count();

        $columnCounts = [
            'pl' => RbfConfigStatus::distinct('pl')->count('pl'),
            'rs' => RbfConfigStatus::distinct('rs')->count('rs'),
            'ti' => RbfConfigStatus::distinct('ti')->count('ti'),
            'li' => RbfConfigStatus::distinct('li')->count('li'),
            'of' => RbfConfigStatus::distinct('of')->count('of'),
            'pr' => RbfConfigStatus::distinct('pr')->count('pr'),
            'co' => RbfConfigStatus::distinct('co')->count('co'),
            'ex' => RbfConfigStatus::distinct('ex')->count('ex'),
            'db' => RbfConfigStatus::distinct('db')->count('db'),
            'pv' => RbfConfigStatus::distinct('pv')->count('pv'),
            'us' => RbfConfigStatus::distinct('us')->count('us'),
        ];

        $columnLabels = [
            'pl' => 'Plaza',
            'rs' => 'Razón',
            'ti' => 'Tipo',
            'li' => 'Lista',
            'of' => 'Oferta',
            'pr' => 'Promo',
            'co' => 'Combo',
            'ex' => 'Exe',
            'db' => 'Dbf',
            'pv' => 'Pvsi',
            'us' => 'Usuario',
        ];

        return view('reportes.rbf-config-status.index', compact('syncedAt', 'total', 'columnCounts', 'columnLabels'));
    }

    public function data(Request $request)
    {
        $query = RbfConfigStatus::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('pl', 'like', "%{$search}%")
                    ->orWhere('rs', 'like', "%{$search}%")
                    ->orWhere('ti', 'like', "%{$search}%")
                    ->orWhere('ca', 'like', "%{$search}%")
                    ->orWhere('li', 'like', "%{$search}%")
                    ->orWhere('of', 'like', "%{$search}%")
                    ->orWhere('pr', 'like', "%{$search}%")
                    ->orWhere('co', 'like', "%{$search}%")
                    ->orWhere('ex', 'like', "%{$search}%")
                    ->orWhere('db', 'like', "%{$search}%")
                    ->orWhere('pv', 'like', "%{$search}%")
                    ->orWhere('us', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);

        $records = $query->orderBy('pl')
            ->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $records,
        ]);
    }
}
