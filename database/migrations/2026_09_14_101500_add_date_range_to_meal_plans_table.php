<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->date('starts_on')->nullable()->after('name');
            $table->date('ends_on')->nullable()->after('starts_on');
        });

        $this->backfillExistingPlans();

        Schema::table('meal_plans', function (Blueprint $table) {
            $table->date('starts_on')->nullable(false)->change();
            $table->date('ends_on')->nullable(false)->change();
        });

        Schema::table('meal_plans', function (Blueprint $table) {
            $table->index(['user_id', 'starts_on']);
        });
    }

    public function down(): void
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'starts_on']);
            $table->dropColumn(['starts_on', 'ends_on']);
        });
    }

    /**
     * Plans written before the calendar existed become the week that started
     * the day they were made, so nothing lands outside its own range.
     */
    private function backfillExistingPlans(): void
    {
        DB::table('meal_plans')
            ->select(['id', 'created_at'])
            ->whereNull('starts_on')
            ->orderBy('id')
            ->chunk(200, function ($plans) {
                foreach ($plans as $plan) {
                    $startsOn = $plan->created_at !== null
                        ? CarbonImmutable::parse((string) $plan->created_at)->startOfDay()
                        : CarbonImmutable::today();

                    DB::table('meal_plans')->where('id', $plan->id)->update([
                        'starts_on' => $startsOn->toDateString(),
                        'ends_on' => $startsOn->addDays(6)->toDateString(),
                    ]);
                }
            });
    }
};
