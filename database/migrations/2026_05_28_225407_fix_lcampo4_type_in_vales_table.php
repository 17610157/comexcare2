<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE vales ALTER COLUMN lcampo4 TYPE integer USING (lcampo4::integer)');
        DB::statement('ALTER TABLE vales ALTER COLUMN lcampo4 SET DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE vales ALTER COLUMN lcampo4 TYPE boolean USING (lcampo4::boolean)');
        DB::statement('ALTER TABLE vales ALTER COLUMN lcampo4 DROP DEFAULT');
    }
};
