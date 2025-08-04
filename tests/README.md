# Translation API Test Suite

This test suite ensures that the TranslationController and related components perform optimally with response times under 200ms.

## Test Files Overview

### 1. TranslationControllerTest.php
**Location**: `tests/Feature/TranslationControllerTest.php`

**Purpose**: Comprehensive feature tests for all TranslationController endpoints

**Test Cases**:
- `test_get_translations_by_params()` - Tests GET /api/translations with filters
- `test_get_translations_with_filters()` - Tests filtering functionality
- `test_get_translation_by_id()` - Tests GET /api/translations/{id}
- `test_get_translation_by_id_not_found()` - Tests 404 responses
- `test_store_translation()` - Tests POST /api/translations
- `test_store_translation_duplicate()` - Tests duplicate key validation
- `test_store_translation_validation()` - Tests input validation
- `test_update_translation()` - Tests PUT /api/translations/{id}
- `test_update_translation_not_found()` - Tests update with non-existent ID
- `test_export_translation_json()` - Tests GET /api/translations/export
- `test_caching_functionality()` - Tests caching behavior
- `test_cache_invalidation_on_create()` - Tests cache invalidation
- `test_large_dataset_performance()` - Tests performance with 1000 records
- `test_response_headers()` - Tests X-Response-Time header

### 2. TranslationServiceTest.php
**Location**: `tests/Unit/TranslationServiceTest.php`

**Purpose**: Unit tests for the TranslationService class

**Test Cases**:
- `test_get_translations_by_params_empty_filters()` - Tests service with no filters
- `test_get_translations_by_params_with_locale_filter()` - Tests locale filtering
- `test_get_translations_by_params_with_multiple_filters()` - Tests multiple filters
- `test_get_translation_by_id_existing()` - Tests getting by ID
- `test_get_translation_by_id_not_found()` - Tests non-existent ID
- `test_store_translation_valid_data()` - Tests storing translations
- `test_store_translation_duplicate()` - Tests duplicate handling
- `test_update_translation_valid_data()` - Tests updating translations
- `test_update_translation_not_found()` - Tests update with non-existent ID
- `test_export_translations()` - Tests export functionality
- `test_caching_functionality()` - Tests service caching
- `test_cache_invalidation_on_store()` - Tests cache invalidation on store
- `test_cache_invalidation_on_update()` - Tests cache invalidation on update
- `test_large_dataset_performance()` - Tests performance with large datasets
- `test_export_large_dataset()` - Tests export with large datasets

### 3. ResponseTimeMiddlewareTest.php
**Location**: `tests/Feature/ResponseTimeMiddlewareTest.php`

**Purpose**: Tests for the ResponseTimeMiddleware

**Test Cases**:
- `test_response_time_header_is_added()` - Tests header presence
- `test_response_time_for_different_endpoints()` - Tests all endpoints
- `test_response_time_for_export_endpoint()` - Tests export endpoint
- `test_response_time_for_not_found_endpoints()` - Tests 404 responses
- `test_response_time_for_validation_errors()` - Tests validation errors
- `test_response_time_consistency()` - Tests response time consistency
- `test_response_time_with_large_dataset()` - Tests with large datasets
- `test_middleware_doesnt_affect_response_content()` - Tests content integrity

### 4. TranslationPerformanceTest.php
**Location**: `tests/Feature/TranslationPerformanceTest.php`

**Purpose**: Dedicated performance tests ensuring <200ms response times

**Test Cases**:
- `test_get_translations_performance()` - Tests various filter scenarios
- `test_get_translation_by_id_performance()` - Tests individual record retrieval
- `test_store_translation_performance()` - Tests creation performance
- `test_update_translation_performance()` - Tests update performance
- `test_export_translation_performance()` - Tests export performance
- `test_concurrent_load_performance()` - Tests concurrent requests
- `test_large_dataset_performance()` - Tests with 1000 records
- `test_complex_filters_performance()` - Tests complex filtering
- `test_performance_consistency()` - Tests consistency over multiple runs
- `test_cache_hit_performance()` - Tests cache performance

## Performance Requirements

All tests ensure that:
- **Response times are under 200ms** for all endpoints
- **Cache hits are faster** than cache misses
- **Large datasets** (1000+ records) perform within limits
- **Concurrent requests** maintain performance
- **Response time consistency** is maintained

## Running Tests

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suite
```bash
# Feature tests only
php artisan test --testsuite=Feature

# Unit tests only
php artisan test --testsuite=Unit
```

### Run Specific Test File
```bash
# Performance tests
php artisan test tests/Feature/TranslationPerformanceTest.php

# Controller tests
php artisan test tests/Feature/TranslationControllerTest.php

# Service tests
php artisan test tests/Unit/TranslationServiceTest.php

# Middleware tests
php artisan test tests/Feature/ResponseTimeMiddlewareTest.php
```

### Run with Coverage
```bash
php artisan test --coverage
```

## Test Data

The tests use `TranslationFactory` to generate test data with the following states:
- `english()` - Sets locale to 'en'
- `spanish()` - Sets locale to 'es'
- `tagged(string $tag)` - Sets specific tag
- `common()` - Sets tag to 'common'
- `greeting()` - Sets tag to 'greeting'

## Performance Monitoring

The `ResponseTimeMiddleware` automatically:
- Adds `X-Response-Time` header to all responses
- Logs warnings for responses over 200ms
- Tracks response times for monitoring

## Cache Testing

Tests verify that:
- Cache keys are properly generated
- Cache invalidation works correctly
- Cache hits are faster than misses
- Cache TTL is respected (5 minutes for queries, 10 minutes for exports)

## Database Testing

Tests use:
- SQLite in-memory database for fast execution
- `RefreshDatabase` trait for clean state
- Factory-generated test data
- Proper indexing for performance

## Expected Results

When all tests pass:
- All endpoints respond in <200ms
- Caching works correctly
- Database queries are optimized
- Error handling works properly
- Response headers are correct
- Performance is consistent 
