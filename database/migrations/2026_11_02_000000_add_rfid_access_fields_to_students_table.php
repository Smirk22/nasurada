<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('school_year')->nullable();
            $table->boolean('enrollment_confirmed')->default(false);
            $table->string('card_type')->default('student');
            $table->date('valid_until')->nullable();
        });

        Schema::table('entry_logs', function (Blueprint $table) {
            $table->string('card_uid')->nullable();
            $table->string('reason')->nullable();
        });

        $now = Carbon::now();
        $start = $now->month >= 6 ? $now->year : $now->year - 1;

        DB::table('students')->update([
            'school_year' => $start.'-'.($start + 1),
            'enrollment_confirmed' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['school_year', 'enrollment_confirmed', 'card_type', 'valid_until']);
        });

        Schema::table('entry_logs', function (Blueprint $table) {
            $table->dropColumn(['card_uid', 'reason']);
        });
    }
};
