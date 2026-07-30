<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handleWebhook(Request $request): Response
    {
        // 1. Verify Paystack Signature
        $secretKey = config('services.paystack.secret_key');
        $paystackHeader = $request->header('x-paystack-signature');

        if (! $paystackHeader || $paystackHeader !== hash_hmac('sha512', $request->getContent(), $secretKey)) {
            Log::warning('Paystack Webhook Signature Verification Failed.');
            return response('Invalid Signature', 400);
        }

        $event = $request->json()->all();

        // 2. Process Successful Charge Event
        if (isset($event['event']) && $event['event'] === 'charge.success') {
            $data = $event['data'];
            $orderNumber = $data['reference'];

            $parentOrder = Order::where('order_number', $orderNumber)
                ->whereNull('parent_id')
                ->first();

            if ($parentOrder && $parentOrder->payment_status !== 'paid') {
                // Update parent order
                $parentOrder->update([
                    'payment_status' => 'paid',
                    'status'         => 'processing',
                ]);

                // Update child vendor sub-orders
                Order::where('parent_id', $parentOrder->id)->update([
                    'payment_status' => 'paid',
                    'status'         => 'processing',
                ]);

                Log::info("Order {$orderNumber} successfully marked as PAID via Paystack webhook.");
            }
        }

        // Return a 200 OK fast so Paystack does not retry
        return response('Webhook Handled', 200);
    }
}