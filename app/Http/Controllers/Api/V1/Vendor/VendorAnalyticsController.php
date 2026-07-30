<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorAnalyticsController extends Controller
{
    /**
     * Retrieve vendor dashboard metrics and sales statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json(['message' => 'Vendor store not found.'], 404);
        }

        $storeId = $store->id;

        // 1. Core Metrics
        $totalRevenue = Order::where('store_id', $storeId)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $totalOrders = Order::where('store_id', $storeId)->count();
        $pendingOrders = Order::where('store_id', $storeId)->where('status', 'pending')->count();
        $totalProducts = Product::where('store_id', $storeId)->count();

        // 2. Monthly Revenue Trend (Current Year)
        $monthlySales = Order::where('store_id', $storeId)
            ->where('payment_status', 'paid')
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // 3. Top-Selling Products
        $topProducts = OrderItem::whereHas('order', function ($query) use ($storeId) {
                $query->where('store_id', $storeId)
                      ->where('payment_status', 'paid');
            })
            ->select('product_id', 'product_name', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(total) as revenue'))
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return response()->json([
            'metrics' => [
                'total_revenue'   => number_format((float) $totalRevenue, 2, '.', ''),
                'total_orders'    => $totalOrders,
                'pending_orders'  => $pendingOrders,
                'total_products'  => $totalProducts,
            ],
            'monthly_sales' => $monthlySales,
            'top_products'  => $topProducts,
        ]);
    }
}