<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! $this->connectionAvailable()) {
            return;
        }

        // The create migration already declares HAZIRA, so this must be guarded or a
        // fresh run fails with a duplicate column error.
        if (Schema::connection('sqlsrv')->hasColumn('tblAccountInfo', 'HAZIRA')) {
            return;
        }

        Schema::connection('sqlsrv')->table('tblAccountInfo', function (Blueprint $table) {
            $table->string('HAZIRA', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! $this->connectionAvailable()) {
            return;
        }

        if (! Schema::connection('sqlsrv')->hasColumn('tblAccountInfo', 'HAZIRA')) {
            return;
        }

        Schema::connection('sqlsrv')->table('tblAccountInfo', function (Blueprint $table) {
            $table->dropColumn('HAZIRA');
        });
    }

    /**
     * `tblAccountInfo` lives in an optional external SQL Server reporting database.
     * When that server is unreachable the migration must skip cleanly instead of
     * aborting the whole migration chain for every other database.
     */
    protected function connectionAvailable(): bool
    {
        try {
            DB::connection('sqlsrv')->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
};
