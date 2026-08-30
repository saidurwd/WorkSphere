<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('sqlsrv')->hasTable('tblAccountInfo')) {
            Schema::connection('sqlsrv')->create('tblAccountInfo', function (Blueprint $table) {
                $table->id();
                $table->string('COMPANYCODE', 20);
                $table->string('COMPANYNAME', 255);
                $table->string('DIVISIONCODE', 20);
                $table->string('DIVISIONNAME', 255);
                $table->string('FILTERDATE', 20);
                $table->string('ACCGROUPCODE', 50);
                $table->string('ACCGROUPDESC', 500);
                $table->string('CLUSTERID', 50);
                $table->string('CLUSTERCODE', 50);
                $table->string('TAG', 20);
                $table->string('ACCSUBGROUPCODE', 50);
                $table->string('ACCSUBGROUPDESC', 500);
                $table->string('AREACODE', 50);
                $table->string('AREADESC', 255);
                $table->string('TYPECAT', 10);
                $table->string('SEX', 10);
                $table->string('HAZIRA', 255);
                $table->decimal('AMOUNT', 18, 2);
                $table->timestamps();

                $table->index(['FILTERDATE', 'COMPANYCODE', 'DIVISIONCODE'], 'idx_tblaccountinfo_filter');
                $table->index(['COMPANYCODE', 'DIVISIONCODE', 'FILTERDATE'], 'idx_tblaccountinfo_company_division');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('tblAccountInfo');
    }
};
