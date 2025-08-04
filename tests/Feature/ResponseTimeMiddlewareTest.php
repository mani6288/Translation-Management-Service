<?php

namespace Tests\Feature;

use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponseTimeMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that response time header is added
     */
    public function test_response_time_header_is_added()
    {
        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        $response = $this->getJson('/api/translations?locale=en');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('X-Response-Time'));

        $responseTime = (float) str_replace('ms', '', $response->headers->get('X-Response-Time'));
        $this->assertGreaterThan(0, $responseTime);
        $this->assertLessThan(200, $responseTime, "Response time should be under 200ms");
    }

    /**
     * Test response time for different endpoints
     */
    public function test_response_time_for_different_endpoints()
    {
        $translation = Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        // Test GET endpoint
        $response = $this->getJson('/api/translations?locale=en');
        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('X-Response-Time'));

        $getResponseTime = (float) str_replace('ms', '', $response->headers->get('X-Response-Time'));
        $this->assertLessThan(200, $getResponseTime, "GET response time should be under 200ms");

        // Test POST endpoint
        $data = [
            'key' => 'hello',
            'locale' => 'en',
            'value' => 'Hello'
        ];

        $response = $this->postJson('/api/translations', $data);
        $response->assertStatus(201);
        $this->assertTrue($response->headers->has('X-Response-Time'));

        $postResponseTime = (float) str_replace('ms', '', $response->headers->get('X-Response-Time'));
        $this->assertLessThan(200, $postResponseTime, "POST response time should be under 200ms");

        // Test PUT endpoint
        $updateData = ['value' => 'Updated'];
        $response = $this->putJson("/api/translations/{$translation->id}", $updateData);
        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('X-Response-Time'));

        $putResponseTime = (float) str_replace('ms', '', $response->headers->get('X-Response-Time'));
        $this->assertLessThan(200, $putResponseTime, "PUT response time should be under 200ms");
    }

    /**
     * Test response time for export endpoint
     */
    public function test_response_time_for_export_endpoint()
    {
        Translation::factory()->count(10)->create();

        $response = $this->getJson('/api/translations/export');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('X-Response-Time'));

        $responseTime = (float) str_replace('ms', '', $response->headers->get('X-Response-Time'));
        $this->assertLessThan(200, $responseTime, "Export response time should be under 200ms");
    }

    /**
     * Test response time for not found endpoints
     */
    public function test_response_time_for_not_found_endpoints()
    {
        $response = $this->getJson('/api/translations/999');

        $response->assertStatus(404);
        $this->assertTrue($response->headers->has('X-Response-Time'));

        $responseTime = (float) str_replace('ms', '', $response->headers->get('X-Response-Time'));
        $this->assertLessThan(200, $responseTime, "Not found response time should be under 200ms");
    }

    /**
     * Test response time for validation errors
     */
    public function test_response_time_for_validation_errors()
    {
        $response = $this->postJson('/api/translations', []);

        $response->assertStatus(422);
        $this->assertTrue($response->headers->has('X-Response-Time'));

        $responseTime = (float) str_replace('ms', '', $response->headers->get('X-Response-Time'));
        $this->assertLessThan(200, $responseTime, "Validation error response time should be under 200ms");
    }

    /**
     * Test response time consistency
     */
    public function test_response_time_consistency()
    {
        Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        $responseTimes = [];

        // Make multiple requests to test consistency
        for ($i = 0; $i < 5; $i++) {
            $response = $this->getJson('/api/translations?locale=en');
            $response->assertStatus(200);

            $responseTime = (float) str_replace('ms', '', $response->headers->get('X-Response-Time'));
            $responseTimes[] = $responseTime;

            $this->assertLessThan(200, $responseTime, "Response time should be under 200ms");
        }

        // Check that response times are reasonably consistent (within 50ms variance)
        $average = array_sum($responseTimes) / count($responseTimes);
        foreach ($responseTimes as $time) {
            $this->assertLessThan(50, abs($time - $average), "Response times should be consistent");
        }
    }

    /**
     * Test response time with large dataset
     */
    public function test_response_time_with_large_dataset()
    {
        // Create 100 translations
        Translation::factory()->count(100)->english()->create();

        $response = $this->getJson('/api/translations?locale=en');

        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('X-Response-Time'));

        $responseTime = (float) str_replace('ms', '', $response->headers->get('X-Response-Time'));
        $this->assertLessThan(200, $responseTime, "Response time should be under 200ms even with large dataset");
        $this->assertEquals(100, $response->json('count'));
    }

    /**
     * Test that middleware doesn't affect response content
     */
    public function test_middleware_doesnt_affect_response_content()
    {
        $translation = Translation::factory()->create([
            'key' => 'welcome',
            'locale' => 'en',
            'value' => 'Welcome'
        ]);

        $response = $this->getJson("/api/translations/{$translation->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $translation->id,
                    'key' => 'welcome',
                    'locale' => 'en',
                    'value' => 'Welcome'
                ]);

        // Verify response time header is present but doesn't affect content
        $this->assertTrue($response->headers->has('X-Response-Time'));
        $this->assertArrayHasKey('id', $response->json());
        $this->assertArrayHasKey('key', $response->json());
        $this->assertArrayHasKey('locale', $response->json());
        $this->assertArrayHasKey('value', $response->json());
    }
}
