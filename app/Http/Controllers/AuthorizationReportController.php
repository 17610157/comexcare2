<?php

namespace App\Http\Controllers;

use App\Models\FileListAuthorization;
use App\Models\Module;
use Illuminate\Http\Request;

class AuthorizationReportController extends Controller
{
    public function index()
    {
        $modules = Module::orderBy('name')->get();

        return view('admin.authorization-report.index', compact('modules'));
    }

    public function data(Request $request)
    {
        $query = FileListAuthorization::with(['fileList.creator', 'fileList.module', 'user']);

        if ($request->filled('module_id')) {
            $query->whereHas('fileList', function ($q) use ($request) {
                $q->where('module_id', $request->module_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('authorized_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('authorized_at', '<=', $request->date_to.' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhereHas('fileList', function ($q2) use ($search) {
                        $q2->where('file_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($q3) use ($search) {
                        $q3->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $authorizations = $query->orderBy('authorized_at', 'desc')->get();

        $data = $authorizations->map(function ($auth) {
            $typeName = $auth->fileList->type === 'whitelist' ? 'Whitelist' : 'Blacklist';
            $badgeClass = $auth->fileList->type === 'whitelist' ? 'badge-success' : 'badge-danger';

            return [
                'id' => $auth->id,
                'file_list_info' => "<span class=\"badge {$badgeClass}\">{$typeName}</span> {$auth->fileList->file_name}",
                'module_name' => $auth->fileList->module->name ?? 'Sin módulo',
                'creator_name' => $auth->fileList->creator->name ?? 'N/A',
                'email' => $auth->email,
                'authorizer_name' => $auth->user->name ?? $auth->email,
                'authorized_date' => $auth->authorized_at->format('d/m/Y H:i:s'),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function export(Request $request)
    {
        $query = FileListAuthorization::with(['fileList.creator', 'user', 'fileList.module']);

        if ($request->filled('module_id')) {
            $query->whereHas('fileList', function ($q) use ($request) {
                $q->where('module_id', $request->module_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->where('authorized_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('authorized_at', '<=', $request->date_to.' 23:59:59');
        }

        $authorizations = $query->orderBy('authorized_at', 'desc')->get();

        $csvData = [];
        $csvData[] = [
            'ID Autorización',
            'Archivo',
            'Tipo',
            'Módulo',
            'Correo Autorizador',
            'Nombre Autorizador',
            'Creado por',
            'Fecha Autorización',
            'Dirección IP',
            'Notas',
        ];

        foreach ($authorizations as $auth) {
            $csvData[] = [
                $auth->id,
                $auth->fileList->file_name,
                $auth->fileList->type,
                $auth->fileList->module->name ?? 'Sin módulo',
                $auth->email,
                $auth->user->name ?? 'N/A',
                $auth->fileList->creator->name ?? 'N/A',
                $auth->authorized_at->format('d/m/Y H:i:s'),
                $auth->ip_address,
                $auth->notes,
            ];
        }

        $filename = 'reporte_autorizaciones_'.now()->format('Y-m-d_His').'.csv';

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
