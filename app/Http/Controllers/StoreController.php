<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\ApproveStoreRequest;
use App\Http\Requests\Store\CreateStoreRequest;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    /**
     * Public list of active/approved stores.
     */
    public function index(): JsonResponse
    {
        $stores = Store::approved()
            ->with('owner')
            ->paginate(15);

        return response()->json(StoreResource::collection($stores)->response()->getData());
    }

    /**
     * Public store profile detail page by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $store = Store::approved()
            ->where('slug', $slug)
            ->with('owner')
            ->firstOrFail();

        return response()->json([
            'store' => new StoreResource($store),
        ]);
    }

    /**
     * Submit store registration.
     */
    public function store(CreateStoreRequest $request): JsonResponse
    {
        $user = $request->user();

        $store = DB::transaction(function () use ($request, $user) {
            $logoPath = $request->hasFile('logo') 
                ? $request->file('logo')->store('stores/logos', 'public') 
                : null;

            $bannerPath = $request->hasFile('banner') 
                ? $request->file('banner')->store('stores/banners', 'public') 
                : null;

            return Store::create([
                'user_id'      => $user->id,
                'name'         => $request->name,
                'description'  => $request->description,
                'location'     => $request->location,
                'logo'         => $logoPath,
                'banner'       => $bannerPath,
                'contact_info' => $request->contact_info,
                'status'       => Store::STATUS_PENDING,
            ]);
        });

        return response()->json([
            'message' => 'Store registration submitted successfully and is pending approval.',
            'store'   => new StoreResource($store),
        ], 201);
    }

    /**
     * Vendor: Get my store details.
     */
    public function myStore(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        if (! $store) {
            return response()->json(['message' => 'No store found for this user.'], 404);
        }

        return response()->json([
            'store' => new StoreResource($store->load('owner')),
        ]);
    }

    /**
     * Admin: List stores by status.
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Store::with('owner');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(StoreResource::collection($query->paginate(20))->response()->getData());
    }

    /**
     * Admin: Approve or Reject a Store.
     */
    public function approveStore(ApproveStoreRequest $request, Store $store): JsonResponse
    {
        DB::transaction(function () use ($request, $store) {
            $store->update([
                'status'           => $request->status,
                'rejection_reason' => $request->status === Store::STATUS_REJECTED ? $request->rejection_reason : null,
            ]);

            // Promote user role to vendor when approved
            if ($request->status === Store::STATUS_APPROVED) {
                $store->owner->update(['role' => User::ROLE_VENDOR]);
            }
        });

        return response()->json([
            'message' => "Store status updated to {$request->status}.",
            'store'   => new StoreResource($store),
        ]);
    }
}