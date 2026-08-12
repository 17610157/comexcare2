<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitored_files', function (Blueprint $table) {
            $table->json('file_names')->nullable()->after('file_name');
        });

        // Mover cada file_name a file_names (array de 1 elemento).
        // file_name null => [] (listar todos los archivos de la ruta).
        DB::table('monitored_files')->select('id', 'file_name')->orderBy('id')->get()
            ->each(function ($row) {
                DB::table('monitored_files')->where('id', $row->id)->update([
                    'file_names' => json_encode($row->file_name === null ? [] : [$row->file_name]),
                ]);
            });

        Schema::table('monitored_files', function (Blueprint $table) {
            $table->dropColumn('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('monitored_files', function (Blueprint $table) {
            $table->string('file_name', 255)->nullable()->after('path');
        });

        DB::table('monitored_files')->select('id', 'file_names')->orderBy('id')->get()
            ->each(function ($row) {
                $names = json_decode($row->file_names ?? '[]', true) ?: [];
                DB::table('monitored_files')->where('id', $row->id)->update([
                    'file_name' => $names[0] ?? null,
                ]);
            });

        Schema::table('monitored_files', function (Blueprint $table) {
            $table->dropColumn('file_names');
        });
    }
};
