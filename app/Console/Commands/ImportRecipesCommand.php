<?php

namespace App\Console\Commands;

use App\Services\MealDbImporter;
use Illuminate\Console\Command;

class ImportRecipesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipes:import {--source=themealdb : The recipe source API} {--limit=300 : Maximum recipes to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import recipes from external recipe APIs like TheMealDB';

    public function handle(MealDbImporter $importer): int
    {
        $source = (string) $this->option('source');
        $limit = (int) $this->option('limit');

        $this->info("Starting recipe import from [{$source}] with limit: {$limit}...");

        if ($source !== 'themealdb') {
            $this->error("Unsupported source: {$source}. Only 'themealdb' is supported.");

            return Command::FAILURE;
        }

        $count = $importer->import($limit);

        $this->info("Successfully imported {$count} recipes.");

        return Command::SUCCESS;
    }
}
