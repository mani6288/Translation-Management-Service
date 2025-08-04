<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TranslationService
{
    private const CACHE_TTL = 300; // for 5 minutes
    private const EXPORT_CACHE_TTL = 600; // for 10 minutes


    /**
     * @param array $filters
     * @return array
     */
    public function getTranslationsByParams(array $filters): array
    {
        $cacheKey = 'translations_filtered_' . md5(serialize($filters));

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($filters) {
            $query = Translation::select('id', 'key', 'locale', 'value', 'tag');

            if (!empty($filters['locale'])) {
                $query->where('locale', $filters['locale']);
            }

            if (!empty($filters['tag'])) {
                $query->where('tag', $filters['tag']);
            }

            if (!empty($filters['key'])) {
                $query->where('key', 'like', '%' . $filters['key'] . '%');
            }

            if (!empty($filters['value'])) {
                $query->where('value', 'like', '%' . $filters['value'] . '%');
            }

            $translations = $query->limit(1000)->get();

            return [
                'count' => $translations->count(),
                'data'  => $translations,
            ];
        });
    }

    /**
     * @param int $id
     * @return Translation|null
     */
    public function getTranslationById(int $id): ?Translation
    {
        $cacheKey = "translation_{$id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return Translation::find($id);
        });
    }


    /**
     * @param array $data
     * @return Translation
     */
    public function storeTranslation(array $data): Translation
    {
        return DB::transaction(function () use ($data) {
            $exists = Translation::where('key', $data['key'])
                ->where('locale', $data['locale'])
                ->exists();

            if ($exists) {
                throw new \Exception('Key already exists for this locale.');
            }

            $translation = Translation::create($data);
            $this->clearTranslationCaches();

            return $translation;
        });
    }

    /**
     * @param int $id
     * @param array $data
     * @return Translation
     */
    public function updateTranslation(int $id, array $data): Translation
    {
        return DB::transaction(function () use ($id, $data) {
            $translation = Translation::findOrFail($id);
            $translation->update($data);
            $this->clearTranslationCaches();

            return $translation;
        });
    }

    /**
     * @return array
     */
    public function exportTranslations(): array
    {
        return Cache::remember('translations_export', self::EXPORT_CACHE_TTL, function () {
            $data = [];

            Translation::select('locale', 'key', 'value')
                ->chunk(1000, function ($translations) use (&$data) {
                    foreach ($translations as $translation) {
                        if (!isset($data[$translation->locale])) {
                            $data[$translation->locale] = [];
                        }
                        $data[$translation->locale][$translation->key] = $translation->value;
                    }
                });

            return $data;
        });
    }

    /**
     * @return void
     */
    private function clearTranslationCaches(): void
    {
        // Clear specific caches
        Cache::forget('translations_export');

        // Clear filtered caches using pattern matching
        $keys = Cache::get('translation_cache_keys', []);
        foreach ($keys as $key) {
            if (str_starts_with($key, 'translations_filtered_') || str_starts_with($key, 'translation_')) {
                Cache::forget($key);
            }
        }
    }
}
