<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use Illuminate\Http\Request;

class ReporteApiDemoController extends Controller
{
    public function index()
    {
        return view('reportes.api-demo.index');
    }

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $startIdx = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 50);
        $search = $request->input('search.value', '');

        $query = DemoRequest::query();

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('param1', 'ILIKE', '%'.$search.'%')
                    ->orWhere('param2', 'ILIKE', '%'.$search.'%');
            });
        }

        $total = $query->count();

        $items = $query->orderBy('created_at', 'desc')
            ->offset($startIdx)
            ->limit($length)
            ->get();

        $data = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'param1' => $item->param1,
                'param2' => $item->param2,
                'created_at' => $item->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => (int) $total,
            'recordsFiltered' => (int) $total,
            'data' => $data,
        ]);
    }
}
