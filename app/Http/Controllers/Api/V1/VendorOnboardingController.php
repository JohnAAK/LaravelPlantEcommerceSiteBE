<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StoreResource;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorOnboardingController extends Controller
{
    /**
     * Submit a vendor store application.
     */
    public function apply(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user already has a store application
        if ($user->store) {
            return response()->json([
                'message' => 'You have already submitted a store application.',
                'store'   => new StoreResource($user->store),
            ], 422);
        }

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255', 'unique:stores,name'],
            'bio'     => ['nullable', 'string', 'max:1000'],
            'phone'   => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:255'],
            'logo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'banner'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $logoPath = $request->hasFile('logo') 
            ? $request->file('logo')->store('stores/logos', 'public') 
            : null;

        $bannerPath = $request->hasFile('banner') 
            ? $request->file('banner')->store('stores/banners', 'public') 
            : null;

        $store = Store::create([
            'user_id' => $user->id,
            'name'    => $validated['name'],
            'bio'     => $validated['bio'] ?? null,
            'phone'   => $validated['phone'],
            'address' => $validated['address'],
            'logo'    => $logoPath,
            'banner'  => $bannerPath,
            'status'  => 'pending',
        ]);

        return response()->json([
            'message' => 'Vendor application submitted successfully. Pending admin review.',
            'store'   => new StoreResource($store->load('user')),
        ], 201);
    }

    /**
     * Check status of the user's vendor application.
     */
    public function applicationStatus(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json(['message' => 'No store application found.'], 404);
        }

        return response()->json([
            'store' => new StoreResource($store->load('user')),
        ]);
    }
}