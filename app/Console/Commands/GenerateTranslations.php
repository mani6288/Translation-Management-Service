<?php

namespace App\Console\Commands;

use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateTranslations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translations:generate
                            {--count=100000 : Number of translations to generate}
                            {--locales=en,fr,es,de,it : Comma-separated list of locales}
                            {--tags=mobile,desktop,web : Comma-separated list of tags}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test translations for scalability testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->option('count');
        $locales = explode(',', $this->option('locales'));
        $tags = explode(',', $this->option('tags'));

        $this->info("Generating {$count} translations...");
        $this->info("Locales: " . implode(', ', $locales));
        $this->info("Tags: " . implode(', ', $tags));

        $startTime = microtime(true);

        // Use chunk processing to avoid memory issues
        $chunkSize = 1000;
        $chunks = ceil($count / $chunkSize);

        $progressBar = $this->output->createProgressBar($chunks);
        $progressBar->start();

        for ($chunk = 0; $chunk < $chunks; $chunk++) {
            $remaining = min($chunkSize, $count - ($chunk * $chunkSize));

            $translations = [];

            for ($i = 0; $i < $remaining; $i++) {
                $globalIndex = ($chunk * $chunkSize) + $i;

                $translations[] = [
                    'key' => "key_{$globalIndex}",
                    'locale' => $locales[$globalIndex % count($locales)],
                    'value' => "Translation {$globalIndex}",
                    'tag' => $tags[$globalIndex % count($tags)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Use insert for better performance
            DB::table('translations')->insert($translations);

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        $this->info("Generated {$count} translations in {$duration} seconds");

        // Show statistics
        $this->showStatistics($locales, $tags);
    }

    /**
     * Show statistics about generated translations
     */
    private function showStatistics(array $locales, array $tags)
    {
        $this->newLine();
        $this->info('Translation Statistics:');

        $totalCount = Translation::count();
        $this->line("Total translations: {$totalCount}");

        foreach ($locales as $locale) {
            $count = Translation::where('locale', $locale)->count();
            $this->line("Locale '{$locale}': {$count}");
        }

        foreach ($tags as $tag) {
            $count = Translation::where('tag', $tag)->count();
            $this->line("Tag '{$tag}': {$count}");
        }

        // Show unique keys count
        $uniqueKeys = Translation::distinct('key')->count();
        $this->line("Unique keys: {$uniqueKeys}");

        // Show database size info
        $this->showDatabaseSize();
    }

    /**
     * Show database size information
     */
    private function showDatabaseSize()
    {
        $this->newLine();
        $this->info('Database Size Information:');

        try {
            $result = DB::select("SELECT
                pg_size_pretty(pg_total_relation_size('translations')) as table_size,
                pg_size_pretty(pg_database_size(current_database())) as database_size
            ");

            if (!empty($result)) {
                $this->line("Translations table size: {$result[0]->table_size}");
                $this->line("Database size: {$result[0]->database_size}");
            }
        } catch (\Exception $e) {
            // Fallback for other databases
            $this->line("Database size information not available for this database type");
        }
    }
}
