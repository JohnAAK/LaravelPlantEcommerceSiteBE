<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttributeResource;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    /**
     * Public: List all attributes with their allowed values for product filtering UI.
     */
    public function index(): JsonResponse
    {
        $attributes = Attribute::with('values')->get();

        return response()->json([
            'attributes' => AttributeResource::collection($attributes)
        ]);
    }

    /**
     * Admin: Create an attribute key (e.g., "Light Need").
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:attributes,name'],
        ]);

        $attribute = Attribute::create(['name' => $validated['name']]);

        return response()->json([
            'message'   => 'Attribute created successfully.',
            'attribute' => new AttributeResource($attribute),
        ], 201);
    }

    /**
     * Admin: Add a value option to an attribute (e.g., "Low Light" under "Light Need").
     */
    public function addValue(Request $request, Attribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'value' => ['required', 'string', 'max:255'],
        ]);

        $value = $attribute->values()->create([
            'value' => $validated['value'],
        ]);

        return response()->json([
            'message' => 'Attribute value added successfully.',
            'value'   => $value,
        ], 201);
    }
}