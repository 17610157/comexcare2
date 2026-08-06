<?php

namespace App\Http\Controllers;

use App\Models\AuthorizableEmail;
use App\Models\AuthorizationToken;
use App\Models\FileList;
use App\Models\Module;
use App\Notifications\FileListAuthorizationRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class FileListsController extends Controller
{
    public function index()
    {
        $fileLists = FileList::with(['creator', 'module'])->orderBy('id', 'desc')->get();

        return view('admin.file-lists.index', compact('fileLists'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:whitelist,blacklist',
            'file_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'module_id' => 'nullable|exists:modules,id',
        ]);

        $exists = FileList::where('type', $request->type)
            ->where('file_name', $request->file_name)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Este archivo ya existe en la lista '.($request->type === 'whitelist' ? 'blanca' : 'negra').'.',
            ], 422);
        }

        $moduleId = $request->module_id ?? Module::where('slug', 'file-lists')->value('id');

        $fileList = FileList::create([
            'type' => $request->type,
            'file_name' => $request->file_name,
            'description' => $request->description,
            'created_by' => Auth::id(),
            'status' => 'pending',
            'module_id' => $moduleId,
        ]);

        $this->sendAuthorizationNotifications($fileList);

        return response()->json([
            'message' => 'Archivo agregado a la lista exitosamente. Se han enviado solicitudes de autorización.',
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

        $fileNames = $request->input('files');

        $blacklistRules = FileList::where('type', 'blacklist')->where('status', 'active')->pluck('file_name')->toArray();
        $whitelistRules = FileList::where('type', 'whitelist')->where('status', 'active')->pluck('file_name')->toArray();

        $blacklisted = [];
        $notWhitelisted = [];

        foreach ($fileNames as $fileName) {
            if ($this->matchesList($fileName, $blacklistRules)) {
                $blacklisted[] = $fileName;
            }

            if (! $this->matchesList($fileName, $whitelistRules)) {
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

    private function sendAuthorizationNotifications(FileList $fileList): void
    {
        $emails = AuthorizableEmail::whereRaw('is_active = true')
            ->where(function ($query) use ($fileList) {
                if ($fileList->module_id) {
                    $query->where('module_id', $fileList->module_id)
                        ->orWhereNull('module_id');
                } else {
                    $query->whereRaw('true');
                }
            })
            ->get();

        foreach ($emails as $emailRecord) {
            $token = AuthorizationToken::create([
                'file_list_id' => $fileList->id,
                'authorizable_email_id' => $emailRecord->id,
                'token' => AuthorizationToken::generate(),
                'expires_at' => now()->addHours(48),
            ]);

            $authorizationUrl = route('authorization.show', $token->token);

            $notifiable = new AnonymousNotifiable;
            $notifiable->route('mail', $emailRecord->email);

            Notification::send($notifiable, new FileListAuthorizationRequestNotification(
                $fileList,
                $authorizationUrl
            ));
        }
    }
}
