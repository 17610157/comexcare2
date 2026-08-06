<?php

namespace App\Http\Controllers;

use App\Models\AuthorizationToken;
use App\Models\FileListAuthorization;
use Illuminate\Http\Request;

class AuthorizationController extends Controller
{
    public function show(Request $request, string $token)
    {
        $authToken = AuthorizationToken::with(['fileList.creator', 'fileList.module', 'authorizableEmail'])
            ->where('token', $token)
            ->first();

        if (! $authToken) {
            return view('authorization.unauthorized', [
                'message' => 'Enlace de autorización no válido.',
            ]);
        }

        if (! $authToken->isValid()) {
            return view('authorization.unauthorized', [
                'message' => 'Este enlace de autorización ha expirado o ya fue utilizado.',
            ]);
        }

        if ($authToken->fileList->isActive()) {
            return view('authorization.unauthorized', [
                'message' => 'Este registro ya ha sido autorizado.',
            ]);
        }

        return view('authorization.authorize', [
            'fileList' => $authToken->fileList,
            'token' => $token,
            'authorizerEmail' => $authToken->authorizableEmail->email,
            'authorizerName' => $authToken->authorizableEmail->user->name ?? '',
        ]);
    }

    public function process(Request $request, string $token)
    {
        $authToken = AuthorizationToken::with(['fileList', 'authorizableEmail'])
            ->where('token', $token)
            ->first();

        if (! $authToken) {
            return response()->json([
                'message' => 'Enlace de autorización no válido.',
            ], 404);
        }

        if (! $authToken->isValid()) {
            return response()->json([
                'message' => 'Este enlace de autorización ha expirado o ya fue utilizado.',
            ], 410);
        }

        if ($authToken->fileList->isActive()) {
            return response()->json([
                'message' => 'Este registro ya ha sido autorizado.',
            ], 422);
        }

        $emailRecord = $authToken->authorizableEmail;

        FileListAuthorization::create([
            'file_list_id' => $authToken->file_list_id,
            'user_id' => $emailRecord->user_id,
            'email' => $emailRecord->email,
            'ip_address' => $request->ip(),
            'notes' => 'Autorizado por: '.($emailRecord->user->name ?? $emailRecord->email),
            'authorized_at' => now(),
        ]);

        $authToken->fileList->update([
            'status' => 'active',
        ]);

        $authToken->update([
            'used_at' => now(),
        ]);

        AuthorizationToken::where('file_list_id', $authToken->file_list_id)
            ->where('id', '!=', $authToken->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        return response()->json([
            'message' => 'Registro autorizado exitosamente.',
        ]);
    }
}
