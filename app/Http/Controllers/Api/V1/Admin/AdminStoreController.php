<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StoreResource;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminStoreController extends Controller
{
    /**
     * List all store applications with optional status filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Store::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $stores = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json(StoreResource::collection($stores)->response()->getData());
    }

    /**
     * Approve a vendor application.
     */
    public function approve(Store $store): JsonResponse
    {
        if ($store->status === 'approved') {
            return response()->json(['message' => 'Store is already approved.'], 400);
        }

        DB::transaction(function () use ($store) {
            $store->update([
                'status'           => 'approved',
                'rejection_reason' => null,
            ]);

            // Promote user role to vendor if not already set
            $user = $store->user;
            if (!$user->hasRole('vendor')) {
                $user->assignRole('vendor'); // Assuming Spatie Permission or simple role attribute
            }
        });

        return response()->json([
            'message' => "Store '{$store->name}' approved successfully.",
            'store'   => new StoreResource($store->load('user')),
        ]);
    }

    /**
     * Reject a vendor application with feedback.
     */
    public function reject(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $store->update([
            'status'           => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json([
            'message' => "Store '{$store->name}' application rejected.",
            'store'   => new StoreResource($store->load('user')),
        ]);
    }

    /**
     * Suspend or toggle active status of a store.
     */
    public function suspend(Store $store): JsonResponse
    {
        $newStatus = $store->status === 'suspended' ? 'approved' : 'suspended';

        $store->update(['status' => $newStatus]);

        return response()->json([
            'message' => "Store status changed to '{$newStatus}'.",
            'store'   => new StoreResource($store->load('user')),
        ]);
    }
}