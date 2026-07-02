<?php

use Illuminate\Database\Migrations\Migration;

// این migration با 0001_01_01_000000_create_users_table.php ادغام شده
// و برای جلوگیری از conflict خنثی شده است
return new class extends Migration
{
    public function up(): void
    {
        // merged into base users migration (0001_01_01_000000)
    }

    public function down(): void
    {
        //
    }
};
