<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_shift_schedules', function (Blueprint $table) {
            $table->integer('company_id')->unsigned()->nullable()->after('user_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade')->onUpdate('cascade');
        });

        // Use Eloquent to update existing records
        \App\Models\EmployeeShiftSchedule::with('user')->chunk(100, function ($schedules) {
            foreach ($schedules as $schedule) {
                if ($schedule->user && $schedule->user->company_id) {
                    $schedule->company_id = $schedule->user->company_id;
                    $schedule->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_shift_schedules', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
