<?php

namespace App\Console\Commands;

use App\Services\MealDbImporter;
use App\Services\SettingsRepository;
use Illuminate\Console\Command;

class ImportRecipesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipes:import
        {--source=themealdb : The recipe source API}
        {--limit= : Maximum recipes to import, overriding the admin setting}
        {--area=* : Cuisines to import, overriding the admin setting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import recipes from external recipe APIs like TheMealDB';

    public function handle(MealDbImporter $importer, SettingsRepository $settings): int
    {
        $source = (string) $this->option('source');

        if ($source !== 'themealdb') {
            $this->error("Unsupported source: {$source}. Only 'themealdb' is supported.");

            return Command::FAILURE;
        }

        /**
         * Options win when given, otherwise the values an admin set on the
         * settings screen. Falling back to the settings is what makes that
         * screen mean anything for scheduled or unattended runs.
         */
        $limit = $this->option('limit') !== null
            ? (int) $this->option('limit')
            : $settings->integer('mealdb_import_limit', 300);

        /** @var array<int, string> $areas */
        $areas = $this->option('area') ?: $settings->array('mealdb_import_areas');

        if ($areas === []) {
            $this->error('No cuisines configured. Set some on the admin settings screen or pass --area.');

            return Command::FAILURE;
        }

        $this->info("Importing up to {$limit} recipes from [{$source}] for: ".implode(', ', $areas).'...');

        $count = $importer->import($limit, $areas);

        $this->info("Successfully imported {$count} recipes.");

        return Command::SUCCESS;
    }
}
