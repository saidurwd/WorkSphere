<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE assets MODIFY COLUMN current_status ENUM('In Stock', 'Assigned', 'Spare', 'Under Repair', 'Returned', 'Lost', 'Stolen', 'Damaged', 'Disposed', 'Scrapped', 'Donated', 'Awaiting Disposal') DEFAULT 'In Stock'");

        DB::statement("ALTER TABLE assets MODIFY COLUMN condition_status ENUM('New', 'Excellent', 'Good', 'Fair', 'Poor', 'Faulty', 'Under Repair', 'Repaired', 'Damaged', 'Obsolete', 'End of Life (EOL)', 'Beyond Economic Repair (BER)', 'Scrapped', 'Disposed', 'Lost', 'Stolen', 'Retired') DEFAULT 'Good'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE assets MODIFY COLUMN current_status ENUM('Available', 'Assigned', 'In Repair', 'In Transit', 'Disposed', 'Lost', 'Damaged', 'Reserved') DEFAULT 'Available'");

        DB::statement("ALTER TABLE assets MODIFY COLUMN condition_status ENUM('Excellent', 'Good', 'Fair', 'Poor', 'Damaged') DEFAULT 'Good'");
    }
};
