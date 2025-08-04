<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Translation Management API",
 *     version="1.0.0",
 *     description="API for managing translations with support for multiple locales and tags",
 *     @OA\Contact(
 *         email="admin@example.com",
 *         name="API Support"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class TranslationController extends Controller
{
    /**
     * @var TranslationService
     */
    private TranslationService $translationService;

    /**
     * @param TranslationService $translationService
     */
    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    /**
     * Get translations with optional filters
     *
     * @OA\Get(
     *     path="/api/translations",
     *     summary="Get translations with filters",
     *     description="Retrieve translations with optional filtering by locale, tag, key, or value",
     *     tags={"Translations"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         description="Filter by locale (e.g., en, fr, es)",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=5)
     *     ),
     *     @OA\Parameter(
     *         name="tag",
     *         in="query",
     *         description="Filter by tag (e.g., mobile, desktop, web)",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=50)
     *     ),
     *     @OA\Parameter(
     *         name="key",
     *         in="query",
     *         description="Search by translation key (partial match)",
     *         required=false,
     *         @OA\Schema(type="string", maxLength=255)
     *     ),
     *     @OA\Parameter(
     *         name="value",
     *         in="query",
     *         description="Search by translation value (partial match)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="count", type="integer", example=10),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="key", type="string", example="welcome"),
     *                     @OA\Property(property="locale", type="string", example="en"),
     *                     @OA\Property(property="value", type="string", example="Welcome"),
     *                     @OA\Property(property="tag", type="string", example="mobile"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="locale", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function getTranslationsByParams(Request $request): JsonResponse
    {
        $filters = $request->only(['locale', 'tag', 'key', 'value']);
        $result = $this->translationService->getTranslationsByParams($filters);

        return response()->json($result);
    }

    /**
     * Get translation by ID
     *
     * @OA\Get(
     *     path="/api/translations/{id}",
     *     summary="Get translation by ID",
     *     description="Retrieve a specific translation by its ID",
     *     tags={"Translations"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Translation ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="key", type="string", example="welcome"),
     *             @OA\Property(property="locale", type="string", example="en"),
     *             @OA\Property(property="value", type="string", example="Welcome"),
     *             @OA\Property(property="tag", type="string", example="mobile"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Translation not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Translation not found")
     *         )
     *     )
     * )
     */
    public function getTranslationById($id): JsonResponse
    {
        $translation = $this->translationService->getTranslationById($id);

        if (!$translation) {
            return response()->json(['message' => 'Translation not found'], 404);
        }

        return response()->json($translation);
    }

    /**
     * Create a new translation
     *
     * @OA\Post(
     *     path="/api/translations",
     *     summary="Create a new translation",
     *     description="Create a new translation with the specified key, locale, value, and optional tag",
     *     tags={"Translations"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"key", "locale", "value"},
     *             @OA\Property(property="key", type="string", maxLength=255, example="welcome"),
     *             @OA\Property(property="locale", type="string", maxLength=5, example="en"),
     *             @OA\Property(property="value", type="string", example="Welcome"),
     *             @OA\Property(property="tag", type="string", maxLength=50, example="mobile")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Translation created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Translation created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="key", type="string", example="welcome"),
     *                 @OA\Property(property="locale", type="string", example="en"),
     *                 @OA\Property(property="value", type="string", example="Welcome"),
     *                 @OA\Property(property="tag", type="string", example="mobile"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - Key already exists for this locale",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Key already exists for this locale.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="key", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="locale", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="value", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function storeTranslation(Request $request): JsonResponse
    {
        $request->validate([
            'key'    => 'required|string|max:255',
            'locale' => 'required|string|max:5',
            'value'  => 'required|string',
            'tag'    => 'nullable|string|max:50',
        ]);

        try {
            $translation = $this->translationService->storeTranslation($request->only(['key', 'locale', 'value', 'tag']));

            return response()->json([
                'message' => 'Translation created successfully',
                'data'    => $translation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 409);
        }
    }

    /**
     * Update an existing translation
     *
     * @OA\Put(
     *     path="/api/translations/{id}",
     *     summary="Update an existing translation",
     *     description="Update a translation with the specified ID",
     *     tags={"Translations"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Translation ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="key", type="string", maxLength=255, example="welcome"),
     *             @OA\Property(property="locale", type="string", maxLength=5, example="en"),
     *             @OA\Property(property="value", type="string", example="Welcome Updated"),
     *             @OA\Property(property="tag", type="string", maxLength=50, example="desktop")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Translation updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="key", type="string", example="welcome"),
     *                 @OA\Property(property="locale", type="string", example="en"),
     *                 @OA\Property(property="value", type="string", example="Welcome Updated"),
     *                 @OA\Property(property="tag", type="string", example="desktop"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Translation not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Translation not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid"),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="key", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="locale", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="value", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function updateTranslation(Request $request, $id): JsonResponse
    {
        $request->validate([
            'key'    => 'sometimes|string|max:255',
            'locale' => 'sometimes|string|max:5',
            'value'  => 'sometimes|string',
            'tag'    => 'nullable|string|max:50',
        ]);

        try {
            $translation = $this->translationService->updateTranslation($id, $request->all());

            return response()->json([
                'message' => 'Translation updated successfully',
                'data'    => $translation,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Translation not found'
            ], 404);
        }
    }

    /**
     * Export translations as JSON for frontend applications
     *
     * @OA\Get(
     *     path="/api/translations/export",
     *     summary="Export translations as JSON",
     *     description="Export all translations grouped by locale for frontend applications like Vue.js",
     *     tags={"Translations"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="en",
     *                 type="object",
     *                 @OA\Property(property="welcome", type="string", example="Welcome"),
     *                 @OA\Property(property="hello", type="string", example="Hello"),
     *                 @OA\Property(property="goodbye", type="string", example="Goodbye")
     *             ),
     *             @OA\Property(
     *                 property="fr",
     *                 type="object",
     *                 @OA\Property(property="welcome", type="string", example="Bienvenue"),
     *                 @OA\Property(property="hello", type="string", example="Bonjour"),
     *                 @OA\Property(property="goodbye", type="string", example="Au revoir")
     *             ),
     *             @OA\Property(
     *                 property="es",
     *                 type="object",
     *                 @OA\Property(property="welcome", type="string", example="Bienvenido"),
     *                 @OA\Property(property="hello", type="string", example="Hola"),
     *                 @OA\Property(property="goodbye", type="string", example="Adiós")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function exportTranslationJson(): JsonResponse
    {
        $data = $this->translationService->exportTranslations();
        return response()->json($data);
    }
}
