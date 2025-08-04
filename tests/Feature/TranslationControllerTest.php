<?php

namespace Tests\Feature;

use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TranslationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test authentication requirement for all endpoints
     */
    public function test_authentication_required_for_all_endpoints()
    {
        // Test GET translations without authentication
        $response = $this->getJson('/api/translations');
        $response->assertStatus(401);

        // Test POST translations without authentication
        $response = $this->postJson('/api/translations', []);
        $response->assertStatus(401);

        // Test PUT translations without authentication
        $response = $this->putJson('/api/translations/1', []);
        $response->assertStatus(401);

        // Test GET translation by ID without authentication
        $response = $this->getJson('/api/translations/1');
        $response->assertStatus(401);

        // Test export endpoint without authentication
        $response = $this->getJson('/api/translations/export');
        $response->assertStatus(401);
    }

    /**
     * Test getTranslationsByParams endpoint with authentication
     */
    public function test_get_translations_by_params_with_auth()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create test data for multiple locales
        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome',
            'tag' => 'mobile'
        ]);

        Translation::factory()->create([
            'key' => 'hello',
            'locale' => 'fr',
            'value' => 'Bonjour',
            'tag' => 'desktop'
        ]);

        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'es',
            'value' => 'Bienvenido',
            'tag' => 'web'
        ]);

        $startTime = microtime(true);

        $response = $this->getJson('/api/translations?locale=en');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'count',
                    'data' => [
                        '*' => ['id', 'key', 'locale', 'value', 'tag']
                    ]
                ]);

        // Performance requirement: < 200ms
        $this->assertLessThan(200, $responseTime, "Response time should be under 200ms, got {$responseTime}ms");

        $this->assertEquals(1, $response->json('count'));
    }

    /**
     * Test search translations by tags, keys, and content
     */
    public function test_search_translations_by_various_criteria()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create test data with different tags
        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome to our app',
            'tag' => 'mobile'
        ]);

        Translation::factory()->create([
            'key' => 'hello',
            'locale' => 'en',
            'value' => 'Hello world',
            'tag' => 'desktop'
        ]);

        Translation::factory()->create([
            'key' => 'goodbye',
            'locale' => 'en',
            'value' => 'Goodbye message',
            'tag' => 'web'
        ]);

        // Test search by tag
        $response = $this->getJson('/api/translations?tag=mobile');
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('count'));
        $this->assertLessThan(200, $this->getResponseTime($response), "Search by tag should be under 200ms");

        // Test search by key
        $response = $this->getJson('/api/translations?key=welcome');
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('count'));
        $this->assertLessThan(200, $this->getResponseTime($response), "Search by key should be under 200ms");

        // Test search by content
        $response = $this->getJson('/api/translations?value=world');
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('count'));
        $this->assertLessThan(200, $this->getResponseTime($response), "Search by content should be under 200ms");
    }

    /**
     * Test create translation endpoint
     */
    public function test_create_translation()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = [
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome',
            'tag' => 'mobile'
        ];

        $startTime = microtime(true);

        $response = $this->postJson('/api/translations', $data);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(201)
                ->assertJson([
                    'message' => 'Translation created successfully'
                ])
                ->assertJsonStructure([
                    'message',
                    'data' => ['id', 'key', 'locale', 'value', 'tag']
                ]);

        // Performance requirement: < 200ms
        $this->assertLessThan(200, $responseTime, "Create translation should be under 200ms, got {$responseTime}ms");

        $this->assertDatabaseHas('translations', $data);
    }

    /**
     * Test update translation endpoint
     */
    public function test_update_translation()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $translation = Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome',
            'tag' => 'mobile'
        ]);

        $updateData = [
            'value' => 'Welcome Updated',
            'tag' => 'desktop'
        ];

        $startTime = microtime(true);

        $response = $this->putJson("/api/translations/{$translation->id}", $updateData);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Translation updated successfully'
                ]);

        // Performance requirement: < 200ms
        $this->assertLessThan(200, $responseTime, "Update translation should be under 200ms, got {$responseTime}ms");

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'value' => 'Welcome Updated',
            'tag' => 'desktop'
        ]);
    }

    /**
     * Test JSON export endpoint for frontend applications
     */
    public function test_json_export_endpoint()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create translations for multiple locales
        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome',
            'tag' => 'mobile'
        ]);

        Translation::factory()->create([
            'key' => 'hello',
            'locale' => 'en',
            'value' => 'Hello',
            'tag' => 'desktop'
        ]);

        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'fr',
            'value' => 'Bienvenue',
            'tag' => 'mobile'
        ]);

        $startTime = microtime(true);

        $response = $this->getJson('/api/translations/export');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'en' => ['welcome', 'hello'],
                    'fr' => ['welcome']
                ]);

        // Performance requirement: < 500ms for export
        $this->assertLessThan(500, $responseTime, "Export should be under 500ms, got {$responseTime}ms");

        $this->assertEquals('Welcome', $response->json('en.welcome'));
        $this->assertEquals('Hello', $response->json('en.hello'));
        $this->assertEquals('Bienvenue', $response->json('fr.welcome'));
    }

    /**
     * Test JSON export with large dataset (scalability test)
     */
    public function test_json_export_with_large_dataset()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create 1000 translations for testing scalability
        Translation::factory()->count(1000)->create([
            'locale' => 'en'
        ]);

        $startTime = microtime(true);

        $response = $this->getJson('/api/translations/export');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        // Performance requirement: < 500ms even with large dataset
        $this->assertLessThan(500, $responseTime, "Export with large dataset should be under 500ms, got {$responseTime}ms");

        // Verify structure
        $this->assertArrayHasKey('en', $response->json());
    }

    /**
     * Test that export always returns updated translations
     */
    public function test_export_always_returns_updated_translations()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create initial translation
        $translation = Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome',
            'tag' => 'mobile'
        ]);

        // First export
        $response1 = $this->getJson('/api/translations/export');
        $response1->assertStatus(200);
        $this->assertEquals('Welcome', $response1->json('en.welcome'));

        // Update translation
        $this->putJson("/api/translations/{$translation->id}", [
            'value' => 'Welcome Updated'
        ]);

        // Second export - should return updated value
        $response2 = $this->getJson('/api/translations/export');
        $response2->assertStatus(200);
        $this->assertEquals('Welcome Updated', $response2->json('en.welcome'));

        // Both exports should be under 500ms
        $this->assertLessThan(500, $this->getResponseTime($response1), "First export should be under 500ms");
        $this->assertLessThan(500, $this->getResponseTime($response2), "Second export should be under 500ms");
    }

    /**
     * Test multiple locales support
     */
    public function test_multiple_locales_support()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $locales = ['en', 'fr', 'es', 'de', 'it'];

        foreach ($locales as $locale) {
            Translation::factory()->create([
                'key' => 'welcome',
                'locale' => $locale,
                'value' => "Welcome in {$locale}",
                'tag' => 'mobile'
            ]);
        }

        $response = $this->getJson('/api/translations/export');
        $response->assertStatus(200);

        foreach ($locales as $locale) {
            $this->assertArrayHasKey($locale, $response->json());
            $this->assertEquals("Welcome in {$locale}", $response->json("{$locale}.welcome"));
        }

        $this->assertLessThan(500, $this->getResponseTime($response), "Export with multiple locales should be under 500ms");
    }

    /**
     * Test tag-based filtering
     */
    public function test_tag_based_filtering()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $tags = ['mobile', 'desktop', 'web'];

        foreach ($tags as $tag) {
            Translation::factory()->create([
                'key' => "key_{$tag}",
                'locale' => 'en',
                'value' => "Value for {$tag}",
                'tag' => $tag
            ]);
        }

        foreach ($tags as $tag) {
            $response = $this->getJson("/api/translations?tag={$tag}");
            $response->assertStatus(200);
            $this->assertEquals(1, $response->json('count'));
            $this->assertLessThan(200, $this->getResponseTime($response), "Tag filtering should be under 200ms");
        }
    }

    /**
     * Test validation errors
     */
    public function test_validation_errors()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Test missing required fields
        $response = $this->postJson('/api/translations', []);
        $response->assertStatus(422);
        $this->assertLessThan(200, $this->getResponseTime($response), "Validation error should be under 200ms");

        // Test invalid locale
        $response = $this->postJson('/api/translations', [
            'key' => 'test',
            'locale' => 'invalid',
            'value' => 'test'
        ]);
        $response->assertStatus(422);
        $this->assertLessThan(200, $this->getResponseTime($response), "Validation error should be under 200ms");
    }

    /**
     * Test duplicate key and locale combination
     */
    public function test_duplicate_key_locale_combination()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        $response = $this->postJson('/api/translations', [
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome Again'
        ]);

        $response->assertStatus(409)
                ->assertJson([
                    'message' => 'Key already exists for this locale.'
                ]);

        $this->assertLessThan(200, $this->getResponseTime($response), "Duplicate check should be under 200ms");
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
