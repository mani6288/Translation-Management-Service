<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class GenerateTranslationsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test generating translations with default parameters
     */
    public function test_generate_translations_with_default_parameters()
    {
        $this->artisan('translations:generate', ['--count' => 1000])
            ->expectsOutput('Generating 1000 translations...')
            ->expectsOutput('Locales: en, fr, es, de, it')
            ->expectsOutput('Tags: mobile, desktop, web')
            ->assertExitCode(0);

        $this->assertEquals(1000, Translation::count());
    }

    /**
     * Test generating translations with custom parameters
     */
    public function test_generate_translations_with_custom_parameters()
    {
        $this->artisan('translations:generate', [
            '--count' => 500,
            '--locales' => 'en,fr',
            '--tags' => 'mobile,web'
        ])
            ->expectsOutput('Generating 500 translations...')
            ->expectsOutput('Locales: en, fr')
            ->expectsOutput('Tags: mobile, web')
            ->assertExitCode(0);

        $this->assertEquals(500, Translation::count());

        // Verify locales distribution
        $enCount = Translation::where('locale', 'en')->count();
        $frCount = Translation::where('locale', 'fr')->count();

        $this->assertGreaterThan(0, $enCount);
        $this->assertGreaterThan(0, $frCount);
        $this->assertEquals(500, $enCount + $frCount);

        // Verify tags distribution
        $mobileCount = Translation::where('tag', 'mobile')->count();
        $webCount = Translation::where('tag', 'web')->count();

        $this->assertGreaterThan(0, $mobileCount);
        $this->assertGreaterThan(0, $webCount);
        $this->assertEquals(500, $mobileCount + $webCount);
    }

    /**
     * Test generating large number of translations for scalability testing
     */
    public function test_generate_large_number_of_translations()
    {
        $this->artisan('translations:generate', ['--count' => 10000])
            ->assertExitCode(0);

        $this->assertEquals(10000, Translation::count());

        // Verify data integrity
        $uniqueKeys = Translation::distinct('key')->count();
        $this->assertEquals(10000, $uniqueKeys);

        // Verify all translations have required fields
        $translations = Translation::take(100)->get();
        foreach ($translations as $translation) {
            $this->assertNotEmpty($translation->key);
            $this->assertNotEmpty($translation->locale);
            $this->assertNotEmpty($translation->value);
            $this->assertNotEmpty($translation->tag);
        }
    }

    /**
     * Test performance of generating translations
     */
    public function test_performance_of_generating_translations()
    {
        $startTime = microtime(true);

        $this->artisan('translations:generate', ['--count' => 5000])
            ->assertExitCode(0);

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $this->assertEquals(5000, Translation::count());

        // Should complete within reasonable time (adjust based on system performance)
        $this->assertLessThan(30000, $duration, "Should generate 5000 translations in under 30 seconds, took {$duration}ms");
    }

    /**
     * Test that generated translations work with API endpoints
     */
    public function test_generated_translations_with_api_endpoints()
    {
        $user = User::factory()->create();

        // Generate some translations
        $this->artisan('translations:generate', ['--count' => 100])
            ->assertExitCode(0);

        // Test API endpoints with generated data
        $response = $this->actingAs($user)->getJson('/api/translations?locale=en');
        $response->assertStatus(200);
        $this->assertLessThan(200, $this->getResponseTime($response), "API should respond in under 200ms");

        $response = $this->actingAs($user)->getJson('/api/translations/export');
        $response->assertStatus(200);
        $this->assertLessThan(500, $this->getResponseTime($response), "Export should respond in under 500ms");
    }

    /**
     * Test scalability with large dataset
     */
    public function test_scalability_with_large_dataset()
    {
        $user = User::factory()->create();

        // Generate 1000 translations for scalability test
        $this->artisan('translations:generate', ['--count' => 1000])
            ->assertExitCode(0);

        // Test various API operations with large dataset
        $startTime = microtime(true);
        $response = $this->actingAs($user)->getJson('/api/translations?locale=en');
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, "Query with large dataset should be under 200ms");

        // Test export with large dataset
        $startTime = microtime(true);
        $response = $this->actingAs($user)->getJson('/api/translations/export');
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime, "Export with large dataset should be under 500ms");
    }

    /**
     * Test command with different locale and tag combinations
     */
    public function test_command_with_different_combinations()
    {
        $combinations = [
            ['locales' => 'en', 'tags' => 'mobile'],
            ['locales' => 'en,fr', 'tags' => 'mobile,desktop'],
            ['locales' => 'en,fr,es', 'tags' => 'mobile,desktop,web'],
        ];

        foreach ($combinations as $combination) {
            Translation::truncate(); // Clear existing data

            $this->artisan('translations:generate', [
                '--count' => 100,
                '--locales' => $combination['locales'],
                '--tags' => $combination['tags']
            ])->assertExitCode(0);

            $this->assertEquals(100, Translation::count());

            $locales = explode(',', $combination['locales']);
            $tags = explode(',', $combination['tags']);

            // Verify all locales are present
            foreach ($locales as $locale) {
                $count = Translation::where('locale', $locale)->count();
                $this->assertGreaterThan(0, $count, "Should have translations for locale: {$locale}");
            }

            // Verify all tags are present
            foreach ($tags as $tag) {
                $count = Translation::where('tag', $tag)->count();
                $this->assertGreaterThan(0, $count, "Should have translations for tag: {$tag}");
            }
        }
    }

    /**
     * Test command error handling
     */
    public function test_command_error_handling()
    {
        // Test with invalid count
        $this->artisan('translations:generate', ['--count' => -1])
            ->assertExitCode(0); // Command should handle gracefully

        // Test with empty locales
        $this->artisan('translations:generate', ['--locales' => ''])
            ->assertExitCode(0); // Command should handle gracefully

        // Test with empty tags
        $this->artisan('translations:generate', ['--tags' => ''])
            ->assertExitCode(0); // Command should handle gracefully
    }

    /**
     * Helper method to get response time from header
     */
    private function getResponseTime($response)
    {
        $responseTimeHeader = $response->headers->get('X-Response-Time');
        return (float) str_replace('ms', '', $responseTimeHeader);
    }
}
