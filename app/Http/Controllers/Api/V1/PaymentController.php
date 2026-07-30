<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaystackService $paystackService;

    public function __construct(PaystackService $paystackService)
    {
        $this->paystackService = $paystackService;
    }

    /**
     * Initialize Paystack Payment for a Parent Order.
     */
    public function initialize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
        ]);

        $order = Order::where('id', $validated['order_id'])
            ->where('user_id', $request->user()->id)
            ->whereNull('parent_id') // Ensure it is the parent order
            ->firstOrFail();

        if ($order->payment_status === 'paid') {
            return response()->json(['message' => 'Order is already paid.'], 400);
        }

        try {
            $paymentData = $this->paystackService->initializeTransaction(
                email: $request->user()->email,
                amountInNaira: $order->total_amount,
                reference: $order->order_number,
                metadata: [
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'user_id'      => $request->user()->id,
                ]
            );

            return response()->json([
                'message'          => 'Payment initialized successfully.',
                'authorization_url'=> $paymentData['authorization_url'],
                'access_code'      => $paymentData['access_code'],
                'reference'        => $paymentData['reference'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Could not initialize payment.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Frontend Callback Verification (User redirects back after payment).
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => ['required', 'string'],
        ]);

        try {
            $data = $this->paystackService->verifyTransaction($request->reference);

            if ($data['status'] === 'success') {
                $order = Order::where('order_number', $request->reference)->firstOrFail();

                // Mark parent and all child sub-orders as paid
                if ($order->payment_status !== 'paid') {
                    $this->markOrderAsPaid($order);
                }

                return response()->json([
                    'message' => 'Payment verified successfully.',
                    'order'   => $order,
                ]);
            }

            return response()->json(['message' => 'Payment was not successful.'], 400);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Verification error.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper to mark parent order and child vendor sub-orders as paid.
     */
    private function markOrderAsPaid(Order $parentOrder): void
    {
        $parentOrder->update([
            'payment_status' => 'paid',
            'status'         => 'processing',
        ]);

        // Update all associated vendor sub-orders
        Order::where('parent_id', $parentOrder->id)->update([
            'payment_status' => 'paid',
            'status'         => 'processing',
        ]);
    }
}