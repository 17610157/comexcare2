<?php

namespace App\Http\Controllers;

use App\Models\FileList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileListsController extends Controller
{
    public function index()
    {
        $fileLists = FileList::with('creator')->orderBy('id', 'desc')->get();

        return view('admin.file-lists.index', compact('fileLists'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:whitelist,blacklist',
            'file_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $exists = FileList::where('type', $request->type)
            ->where('file_name', $request->file_name)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Este archivo ya existe en la lista '.($request->type === 'whitelist' ? 'blanca' : 'negra').'.',
            ], 422);
        }

        FileList::create([
            'type' => $request->type,
            'file_name' => $request->file_name,
            'description' => $request->description,
            'created_by' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Archivo agregado a la lista exitosamente.',
        ]);
    }

    public function update(Request $request, FileList $fileList)
    {
        $request->validate([
            'type' => 'required|in:whitelist,blacklist',
            'file_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $exists = FileList::where('type', $request->type)
            ->where('file_name', $request->file_name)
            ->where('id', '!=', $fileList->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Este archivo ya existe en la lista '.($request->type === 'whitelist' ? 'blanca' : 'negra').'.',
            ], 422);
        }

        $fileList->update([
            'type' => $request->type,
            'file_name' => $request->file_name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Archivo actualizado en la lista exitosamente.',
        ]);
    }

    public function destroy(FileList $fileList)
    {
        $fileList->delete();

        return response()->json([
            'message' => 'Archivo eliminado de la lista exitosamente.',
        ]);
    }

    public function validateFiles(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'string|max:255',
        ]);

        $fileNames = $request->files;

        $blacklistRules = FileList::where('type', 'blacklist')->pluck('file_name')->toArray();
        $whitelistRules = FileList::where('type', 'whitelist')->pluck('file_name')->toArray();

        $blacklisted = [];
        $notWhitelisted = [];

        foreach ($fileNames as $fileName) {
            if ($this->matchesList($fileName, $blacklistRules)) {
                $blacklisted[] = $fileName;
            } elseif (! empty($whitelistRules) && ! $this->matchesList($fileName, $whitelistRules)) {
                $notWhitelisted[] = $fileName;
            }
        }

        return response()->json([
            'blacklisted' => $blacklisted,
            'not_whitelisted' => $notWhitelisted,
            'has_whitelist' => ! empty($whitelistRules),
        ]);
    }

    private function matchesList(string $fileName, array $rules): bool
    {
        foreach ($rules as $rule) {
            if (str_starts_with($rule, '.')) {
                if (str_ends_with($fileName, $rule)) {
                    return true;
                }
            } elseif ($fileName === $rule) {
                return true;
            }
        }

        return false;
    }
}
