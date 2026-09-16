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
        // Check if the legacy department column exists
        if (!Schema::hasColumn('fae_users', 'department')) {
            // Column already removed or never existed; ensure FAE users have valid department_id
            // Create a default General department if it doesn't exist
            $defaultDept = DB::table('departments')
                ->where('department_name', 'General')
                ->first();

            if (!$defaultDept) {
                $defaultDeptId = DB::table('departments')
                    ->insertGetId([
                        'department_name' => 'General',
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            } else {
                $defaultDeptId = $defaultDept->id;
            }

            // Assign any FAE without a department_id to the default
            DB::table('fae_users')
                ->whereNull('department_id')
                ->update(['department_id' => $defaultDeptId]);

            return;
        }

        // 1. Get all unique department text values from fae_users
        $uniqueDepts = DB::table('fae_users')
            ->whereNotNull('department')
            ->where('department', '!=', '')
            ->distinct()
            ->pluck('department')
            ->filter()
            ->values();

        // 2. For each unique department, ensure a record exists in departments table
        foreach ($uniqueDepts as $deptName) {
            $exists = DB::table('departments')
                ->where('department_name', $deptName)
                ->exists();

            if (!$exists) {
                DB::table('departments')->insert([
                    'department_name' => $deptName,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Update fae_users to populate department_id from text value
        foreach ($uniqueDepts as $deptName) {
            $deptId = DB::table('departments')
                ->where('department_name', $deptName)
                ->value('id');

            if ($deptId) {
                DB::table('fae_users')
                    ->where('department', $deptName)
                    ->update(['department_id' => $deptId]);
            }
        }

        // 4. For any FAE still without a department_id, assign a default
        $defaultDept = DB::table('departments')
            ->where('department_name', 'General')
            ->first();

        if (!$defaultDept) {
            $defaultDeptId = DB::table('departments')
                ->insertGetId([
                    'department_name' => 'General',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        } else {
            $defaultDeptId = $defaultDept->id;
        }

        // Update any remaining FAE without department_id
        DB::table('fae_users')
            ->whereNull('department_id')
            ->update(['department_id' => $defaultDeptId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration only moves data; reversal would require restoring old department text values
        // which is not easily reversible. Manual intervention recommended if rollback is needed.
    }
};
