<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spm_checklists', function (Blueprint $table) {
            $table->string('status')->nullable()->after('is_checked');
        });

        DB::table('spm_checklists')
            ->where('is_checked', true)
            ->update(['status' => 'sesuai']);
    }

    public function down(): void
    {
        Schema::table('spm_checklists', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
