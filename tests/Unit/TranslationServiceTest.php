<?php

namespace Tests\Unit;

use App\Models\Translation;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TranslationService $translationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translationService = new TranslationService();
        Cache::flush();
    }

    /**
     * Test getTranslationsByParams with empty filters
     */
    public function test_get_translations_by_params_empty_filters()
    {
        Translation::factory()->count(3)->create();

        $result = $this->translationService->getTranslationsByParams([]);

        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(3, $result['count']);
        $this->assertCount(3, $result['data']);
    }

    /**
     * Test getTranslationsByParams with locale filter
     */
    public function test_get_translations_by_params_with_locale_filter()
    {
        Translation::factory()->count(2)->english()->create();
        Translation::factory()->count(3)->spanish()->create();

        $result = $this->translationService->getTranslationsByParams(['locale' => 'en']);

        $this->assertEquals(2, $result['count']);
        $this->assertCount(2, $result['data']);

        foreach ($result['data'] as $translation) {
            $this->assertEquals('en', $translation->locale);
        }
    }

    /**
     * Test getTranslationsByParams with multiple filters
     */
    public function test_get_translations_by_params_with_multiple_filters()
    {
        Translation::factory()->count(2)->english()->common()->create();
        Translation::factory()->count(3)->english()->greeting()->create();
        Translation::factory()->count(1)->spanish()->common()->create();

        $result = $this->translationService->getTranslationsByParams([
            'locale' => 'en',
            'tag' => 'common'
        ]);

        $this->assertEquals(2, $result['count']);
        $this->assertCount(2, $result['data']);

        foreach ($result['data'] as $translation) {
            $this->assertEquals('en', $translation->locale);
            $this->assertEquals('common', $translation->tag);
        }
    }

    /**
     * Test getTranslationById with existing ID
     */
    public function test_get_translation_by_id_existing()
    {
        $translation = Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        $result = $this->translationService->getTranslationById($translation->id);

        $this->assertNotNull($result);
        $this->assertEquals('welcome', $result->key);
        $this->assertEquals('en', $result->locale);
        $this->assertEquals('Welcome', $result->value);
    }

    /**
     * Test getTranslationById with non-existing ID
     */
    public function test_get_translation_by_id_not_found()
    {
        $result = $this->translationService->getTranslationById(999);

        $this->assertNull($result);
    }

    /**
     * Test storeTranslation with valid data
     */
    public function test_store_translation_valid_data()
    {
        $data = [
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome',
            'tag' => 'common'
        ];

        $translation = $this->translationService->storeTranslation($data);

        $this->assertInstanceOf(Translation::class, $translation);
        $this->assertEquals('welcome', $translation->key);
        $this->assertEquals('en', $translation->locale);
        $this->assertEquals('Welcome', $translation->value);
        $this->assertEquals('common', $translation->tag);

        $this->assertDatabaseHas('translations', $data);
    }

    /**
     * Test storeTranslation with duplicate key and locale
     */
    public function test_store_translation_duplicate()
    {
        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        $data = [
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome Again'
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Key already exists for this locale.');

        $this->translationService->storeTranslation($data);
    }

    /**
     * Test updateTranslation with valid data
     */
    public function test_update_translation_valid_data()
    {
        $translation = Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        $updateData = [
            'value' => 'Welcome Updated',
            'tag' => 'updated'
        ];

        $updatedTranslation = $this->translationService->updateTranslation($translation->id, $updateData);

        $this->assertInstanceOf(Translation::class, $updatedTranslation);
        $this->assertEquals('Welcome Updated', $updatedTranslation->value);
        $this->assertEquals('updated', $updatedTranslation->tag);

        $this->assertDatabaseHas('translations', [
            'id' => $translation->id,
            'value' => 'Welcome Updated',
            'tag' => 'updated'
        ]);
    }

    /**
     * Test updateTranslation with non-existing ID
     */
    public function test_update_translation_not_found()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->translationService->updateTranslation(999, ['value' => 'Updated']);
    }

    /**
     * Test exportTranslations
     */
    public function test_export_translations()
    {
        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        Translation::factory()->create([
            'key' => 'hello',
            'locale' => 'en',
            'value' => 'Hello'
        ]);

        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'es',
            'value' => 'Bienvenido'
        ]);

        $result = $this->translationService->exportTranslations();

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('es', $result);
        $this->assertEquals('Welcome', $result['en']['welcome']);
        $this->assertEquals('Hello', $result['en']['hello']);
        $this->assertEquals('Bienvenido', $result['es']['welcome']);
    }

    /**
     * Test caching functionality
     */
    public function test_caching_functionality()
    {
        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        // First call - should hit database
        $startTime = microtime(true);
        $result1 = $this->translationService->getTranslationsByParams(['locale' => 'en']);
        $endTime = microtime(true);
        $time1 = ($endTime - $startTime) * 1000;

        // Second call - should hit cache
        $startTime = microtime(true);
        $result2 = $this->translationService->getTranslationsByParams(['locale' => 'en']);
        $endTime = microtime(true);
        $time2 = ($endTime - $startTime) * 1000;

        $this->assertEquals($result1['count'], $result2['count']);
        $this->assertLessThan($time1, $time2, "Cached call should be faster");
    }

    /**
     * Test cache invalidation on store
     */
    public function test_cache_invalidation_on_store()
    {
        // Create initial data and cache it
        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        $this->translationService->getTranslationsByParams(['locale' => 'en']);

        // Store new translation
        $data = [
            'key' => 'hello',
            'locale' => 'en',
            'value' => 'Hello'
        ];

        $this->translationService->storeTranslation($data);

        // Verify cache was invalidated
        $result = $this->translationService->getTranslationsByParams(['locale' => 'en']);
        $this->assertEquals(2, $result['count']);
    }

    /**
     * Test cache invalidation on update
     */
    public function test_cache_invalidation_on_update()
    {
        $translation = Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        // Cache the data
        $this->translationService->getTranslationsByParams(['locale' => 'en']);

        // Update translation
        $this->translationService->updateTranslation($translation->id, [
            'value' => 'Welcome Updated'
        ]);

        // Verify cache was invalidated and data is updated
        $result = $this->translationService->getTranslationsByParams(['locale' => 'en']);
        $this->assertEquals(1, $result['count']);
        $this->assertEquals('Welcome Updated', $result['data'][0]->value);
    }

    /**
     * Test large dataset performance
     */
    public function test_large_dataset_performance()
    {
        // Create 1000 translations
        Translation::factory()->count(1000)->english()->create();

        $startTime = microtime(true);
        $result = $this->translationService->getTranslationsByParams(['locale' => 'en']);
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertEquals(1000, $result['count']);
        $this->assertLessThan(200, $responseTime, "Response time should be under 200ms even with large dataset");
    }

    /**
     * Test export with large dataset
     */
    public function test_export_large_dataset()
    {
        // Create translations for multiple locales
        Translation::factory()->count(500)->english()->create();
        Translation::factory()->count(300)->spanish()->create();

        $startTime = microtime(true);
        $result = $this->translationService->exportTranslations();
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $this->assertArrayHasKey('en', $result);
        $this->assertArrayHasKey('es', $result);
        $this->assertLessThan(200, $responseTime, "Export time should be under 200ms even with large dataset");
    }
}
