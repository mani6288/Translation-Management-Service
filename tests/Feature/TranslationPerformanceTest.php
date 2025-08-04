<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TranslationPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /**
     * Test performance requirements for all endpoints (< 200ms)
     */
    public function test_all_endpoints_perform_under_200ms()
    {
        // Create test data
        Translation::factory()->count(100)->create();

        $endpoints = [
            'GET /api/translations' => '/api/translations',
            'GET /api/translations with locale filter' => '/api/translations?locale=en',
            'GET /api/translations with tag filter' => '/api/translations?tag=mobile',
            'GET /api/translations with key search' => '/api/translations?key=welcome',
            'GET /api/translations with value search' => '/api/translations?value=hello',
            'POST /api/translations' => '/api/translations',
            'PUT /api/translations/{id}' => '/api/translations/1',
        ];

        foreach ($endpoints as $description => $endpoint) {
            $method = 'GET';
            $data = null;

            if (str_contains($endpoint, 'POST')) {
                $method = 'POST';
                $data = [
                    'key' => 'test_key',
                    'locale' => 'en',
                    'value' => 'Test Value',
                    'tag' => 'mobile'
                ];
            } elseif (str_contains($endpoint, 'PUT')) {
                $method = 'PUT';
                $data = ['value' => 'Updated Value'];
            }

            $startTime = microtime(true);

            if ($method === 'GET') {
                $response = $this->getJson($endpoint);
            } elseif ($method === 'POST') {
                $response = $this->postJson($endpoint, $data);
            } else {
                $response = $this->putJson($endpoint, $data);
            }

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            $this->assertLessThan(200, $responseTime,
                "{$description} should be under 200ms, got {$responseTime}ms");
        }
    }

    /**
     * Test JSON export performance (< 200ms)
     */
    public function test_json_export_performs_under_200ms()
    {
        // Create translations for multiple locales
        Translation::factory()->count(500)->english()->create();
        Translation::factory()->count(300)->spanish()->create();
        Translation::factory()->count(200)->french()->create();

        $startTime = microtime(true);

        $response = $this->getJson('/api/translations/export');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime,
            "JSON export should be under 200ms, got {$responseTime}ms");
    }

    /**
     * Test scalability with 100k+ records
     */
    public function test_scalability_with_100k_plus_records()
    {
        // Generate 100k+ records using the command
        $this->artisan('translations:generate', ['--count' => 100000])
            ->assertExitCode(0);

        $this->assertGreaterThanOrEqual(100000, Translation::count());

        // Test API performance with large dataset
        $startTime = microtime(true);
        $response = $this->getJson('/api/translations?locale=en');
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime,
            "Query with 100k+ records should be under 200ms, got {$responseTime}ms");

        // Test export performance with large dataset
        $startTime = microtime(true);
        $response = $this->getJson('/api/translations/export');
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime,
            "Export with 100k+ records should be under 200ms, got {$responseTime}ms");
    }

    /**
     * Test search functionality performance
     */
    public function test_search_functionality_performance()
    {
        // Create diverse test data
        Translation::factory()->count(1000)->create();

        $searchScenarios = [
            'Search by locale' => ['locale' => 'en'],
            'Search by tag' => ['tag' => 'mobile'],
            'Search by key' => ['key' => 'welcome'],
            'Search by value' => ['value' => 'hello'],
            'Search by multiple criteria' => ['locale' => 'en', 'tag' => 'mobile'],
        ];

        foreach ($searchScenarios as $description => $filters) {
            $queryString = http_build_query($filters);

            $startTime = microtime(true);
            $response = $this->getJson("/api/translations?{$queryString}");
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
            $this->assertLessThan(200, $responseTime,
                "{$description} should be under 200ms, got {$responseTime}ms");
        }
    }

    /**
     * Test concurrent load performance
     */
    public function test_concurrent_load_performance()
    {
        Translation::factory()->count(1000)->create();

        $responseTimes = [];
        $concurrentRequests = 20;

        // Simulate concurrent requests
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $startTime = microtime(true);
            $response = $this->getJson('/api/translations?locale=en');
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
            $responseTimes[] = $responseTime;
        }

        // All responses should be under 200ms
        foreach ($responseTimes as $index => $time) {
            $this->assertLessThan(200, $time,
                "Concurrent request {$index} should be under 200ms, got {$time}ms");
        }

        // Average response time should be reasonable
        $averageTime = array_sum($responseTimes) / count($responseTimes);
        $this->assertLessThan(150, $averageTime,
            "Average response time should be under 150ms, got {$averageTime}ms");
    }

    /**
     * Test CRUD operations performance
     */
    public function test_crud_operations_performance()
    {
        $translation = Translation::factory()->create();

        // Test CREATE performance
        $startTime = microtime(true);
        $response = $this->postJson('/api/translations', [
            'key' => 'performance_test',
            'locale' => 'en',
            'value' => 'Performance Test Value',
            'tag' => 'mobile'
        ]);
        $endTime = microtime(true);
        $createTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(201);
        $this->assertLessThan(200, $createTime,
            "CREATE operation should be under 200ms, got {$createTime}ms");

        // Test READ performance
        $startTime = microtime(true);
        $response = $this->getJson("/api/translations/{$translation->id}");
        $endTime = microtime(true);
        $readTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $readTime,
            "READ operation should be under 200ms, got {$readTime}ms");

        // Test UPDATE performance
        $startTime = microtime(true);
        $response = $this->putJson("/api/translations/{$translation->id}", [
            'value' => 'Updated Performance Test Value'
        ]);
        $endTime = microtime(true);
        $updateTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $updateTime,
            "UPDATE operation should be under 200ms, got {$updateTime}ms");
    }

    /**
     * Test multiple locales performance
     */
    public function test_multiple_locales_performance()
    {
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 'zh'];

        // Create translations for each locale
        foreach ($locales as $locale) {
            Translation::factory()->count(100)->create(['locale' => $locale]);
        }

        // Test performance for each locale
        foreach ($locales as $locale) {
            $startTime = microtime(true);
            $response = $this->getJson("/api/translations?locale={$locale}");
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
            $this->assertLessThan(200, $responseTime,
                "Query for locale '{$locale}' should be under 200ms, got {$responseTime}ms");
        }

        // Test export with multiple locales
        $startTime = microtime(true);
        $response = $this->getJson('/api/translations/export');
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime,
            "Export with multiple locales should be under 200ms, got {$responseTime}ms");
    }

    /**
     * Test tag-based filtering performance
     */
    public function test_tag_based_filtering_performance()
    {
        $tags = ['mobile', 'desktop', 'web', 'tablet', 'api', 'admin', 'public', 'private'];

        // Create translations for each tag
        foreach ($tags as $tag) {
            Translation::factory()->count(100)->create(['tag' => $tag]);
        }

        // Test performance for each tag
        foreach ($tags as $tag) {
            $startTime = microtime(true);
            $response = $this->getJson("/api/translations?tag={$tag}");
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
            $this->assertLessThan(200, $responseTime,
                "Query for tag '{$tag}' should be under 200ms, got {$responseTime}ms");
        }
    }

    /**
     * Test performance consistency over multiple runs
     */
    public function test_performance_consistency()
    {
        Translation::factory()->count(1000)->create();

        $responseTimes = [];
        $runs = 50;

        for ($i = 0; $i < $runs; $i++) {
            $startTime = microtime(true);
            $response = $this->getJson('/api/translations?locale=en');
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
            $responseTimes[] = $responseTime;
        }

        // All responses should be under 200ms
        foreach ($responseTimes as $index => $time) {
            $this->assertLessThan(200, $time,
                "Run {$index} should be under 200ms, got {$time}ms");
        }

        // Calculate statistics
        $average = array_sum($responseTimes) / count($responseTimes);
        $max = max($responseTimes);
        $min = min($responseTimes);
        $variance = array_sum(array_map(function($x) use ($average) {
            return pow($x - $average, 2);
        }, $responseTimes)) / count($responseTimes);
        $stdDev = sqrt($variance);

        // Assert reasonable performance characteristics
        $this->assertLessThan(150, $average,
            "Average response time should be under 150ms, got {$average}ms");
        $this->assertLessThan(50, $stdDev,
            "Standard deviation should be under 50ms, got {$stdDev}ms");
        $this->assertLessThan(100, $max - $min,
            "Response time range should be under 100ms, got " . ($max - $min) . "ms");
    }

    /**
     * Test memory usage with large datasets
     */
    public function test_memory_usage_with_large_datasets()
    {
        $initialMemory = memory_get_usage(true);

        // Generate large dataset
        $this->artisan('translations:generate', ['--count' => 50000])
            ->assertExitCode(0);

        $memoryAfterGeneration = memory_get_usage(true);

        // Test API operations
        $response = $this->getJson('/api/translations?locale=en');
        $response->assertStatus(200);

        $response = $this->getJson('/api/translations/export');
        $response->assertStatus(200);

        $finalMemory = memory_get_usage(true);

        // Memory usage should be reasonable (less than 512MB increase)
        $memoryIncrease = $finalMemory - $initialMemory;
        $this->assertLessThan(512 * 1024 * 1024, $memoryIncrease,
            "Memory usage increase should be less than 512MB, got " . round($memoryIncrease / 1024 / 1024, 2) . "MB");
    }
}
