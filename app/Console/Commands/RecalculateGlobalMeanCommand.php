<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Services\RankingService;
use Illuminate\Console\Command;

class RecalculateGlobalMeanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipes:recalculate-global-mean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate global mean rating and sync all recipe Bayesian scores';

    public function handle(RankingService $rankingService): int
    {
        $this->info('Recalculating global mean rating...');
        $globalMean = $rankingService->calculateGlobalMean();
        $this->info("Global mean is: {$globalMean}");

        $recipes = Recipe::all();
        $this->output->progressStart($recipes->count());

        foreach ($recipes as $recipe) {
            $rankingService->updateRecipeStats($recipe->id, $globalMean);
            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info('Completed recalculating Bayesian scores for all recipes.');

        return Command::SUCCESS;
    }
}
