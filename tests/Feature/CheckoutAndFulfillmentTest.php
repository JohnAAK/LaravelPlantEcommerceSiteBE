<?php

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // 1. Setup Buyer & Vendors
    $this->buyer = User::factory()->create(['role' => 'buyer']);
    $this->vendor1User = User::factory()->create(['role' => 'vendor']);
    $this->vendor2User = User::factory()->create(['role' => 'vendor']);

    $this->store1 = Store::factory()->create(['user_id' => $this->vendor1User->id, 'status' => 'approved']);
    $this->store2 = Store::factory()->create(['user_id' => $this->vendor2User->id, 'status' => 'approved']);

    $category = Category::factory()->create();

    // 2. Setup Products from separate vendors
    $this->product1 = Product::factory()->create([
        'store_id'    => $this->store1->id,
        'category_id' => $category->id,
        'price'       => 50.00,
        'stock'       => 10,
    ]);

    $this->product2 = Product::factory()->create([
        'store_id'    => $this->store2->id,
        'category_id' => $category->id,
        'price'       => 30.00,
        'stock'       => 10,
    ]);
});

test('buyer can add items from multiple vendors to cart and trigger split checkout', function () {
    // Add product 1 (Store 1)
    Cart::create([
        'user_id'    => $this->buyer->id,
        'product_id' => $this->product1->id,
        'quantity'   => 2, // Total $100
    ]);

    // Add product 2 (Store 2)
    Cart::create([
        'user_id'    => $this->buyer->id,
        'product_id' => $this->product2->id,
        'quantity'   => 1, // Total $30
    ]);

    // Mock Paystack API Response
    Http::fake([
        'https://api.paystack.co/transaction/initialize' => Http::response([
            'status'  => true,
            'message' => 'Authorization URL created',
            'data'    => [
                'authorization_url' => 'https://checkout.paystack.co/test_code',
                'access_code'      => 'test_code',
                'reference'        => 'PST_TEST_REF_12345',
            ],
        ], 200),
    ]);

    // Act: Initiate Checkout
    $response = $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/checkout', [
            'payment_method'   => 'paystack',
            'shipping_name'    => 'Jane Doe',
            'shipping_phone'   => '+1234567890',
            'shipping_address' => '123 Main St',
            'city'             => 'Accra',
        ]);

    // Assert: Order Initialization Success
    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('payment_url', 'https://checkout.paystack.co/test_code');

    // Assert Parent Order and Split Sub-Orders Created
    $this->assertDatabaseHas('orders', [
        'user_id'        => $this->buyer->id,
        'parent_id'      => null,
        'total_amount'   => 130.00,
        'payment_status' => 'pending',
    ]);

    // Assert 2 Sub-Orders Split across Vendor Stores
    $parentOrder = Order::whereNull('parent_id')->first();
    expect($parentOrder->subOrders)->toHaveCount(2);

    // Cart cleared after order placement
    expect(Cart::where('user_id', $this->buyer->id)->count())->toBe(0);
});

test('paystack webhook marks parent order and vendor sub-orders as paid', function () {
    $parentOrder = Order::create([
        'user_id'          => $this->buyer->id,
        'order_number'     => 'ORD-TEST12345',
        'subtotal'         => 130.00,
        'delivery_fee'     => 0.00,
        'total_amount'     => 130.00,
        'payment_method'   => 'paystack',
        'payment_status'   => 'pending',
        'status'           => 'pending',
        'shipping_name'    => 'Jane Doe',
        'shipping_phone'   => '+1234567890',
        'shipping_address' => '123 Main St',
        'city'             => 'Accra',
    ]);

    $subOrder1 = Order::create([
        'parent_id'        => $parentOrder->id,
        'store_id'         => $this->store1->id,
        'user_id'          => $this->buyer->id,
        'order_number'     => 'ORD-TEST12345-S1',
        'subtotal'         => 100.00,
        'total_amount'     => 100.00,
        'payment_method'   => 'paystack',
        'payment_status'   => 'pending',
        'status'           => 'pending',
        'shipping_name'    => 'Jane Doe',
        'shipping_phone'   => '+1234567890',
        'shipping_address' => '123 Main St',
        'city'             => 'Accra',
    ]);

    // Webhook Payload Simulation
    $payload = [
        'event' => 'charge.success',
        'data'  => [
            'reference' => 'ORD-TEST12345',
            'status'    => 'success',
            'amount'    => 13000, // in kobo/pesewas
        ],
    ];

    $secretKey = 'sk_test_mock_secret_key';
    config(['services.paystack.secret_key' => $secretKey]);
    $signature = hash_hmac('sha512', json_encode($payload), $secretKey);

    // Act: Send Webhook Request
    $response = $this->call(
        'POST',
        '/api/v1/payments/paystack/webhook',
        [],
        [],
        [],
        ['HTTP_X-PAYSTACK-SIGNATURE' => $signature],
        json_encode($payload)
    );

    $response->assertStatus(200);

    // Assert parent and sub-orders are updated to paid
    expect($parentOrder->fresh()->payment_status)->toBe('paid');
    expect($subOrder1->fresh()->payment_status)->toBe('paid');
});

test('vendor can update status of their assigned sub-orders only', function () {
    $subOrder = Order::create([
        'store_id'         => $this->store1->id,
        'user_id'          => $this->buyer->id,
        'order_number'     => 'ORD-VENDOR-1',
        'subtotal'         => 50.00,
        'total_amount'     => 50.00,
        'payment_method'   => 'payment_on_delivery',
        'payment_status'   => 'pending',
        'status'           => 'pending',
        'shipping_name'    => 'Jane Doe',
        'shipping_phone'   => '+1234567890',
        'shipping_address' => '123 Main St',
        'city'             => 'Accra',
    ]);

    // Authorized Vendor 1 updates order to shipped
    $response = $this->actingAs($this->vendor1User, 'sanctum')
        ->patchJson("/api/v1/vendor/orders/{$subOrder->id}/status", [
            'status' => 'shipped',
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('order.status', 'shipped');

    // Unauthorized Vendor 2 attempts to update Vendor 1's order
    $forbiddenResponse = $this->actingAs($this->vendor2User, 'sanctum')
        ->patchJson("/api/v1/vendor/orders/{$subOrder->id}/status", [
            'status' => 'delivered',
        ]);

    $forbiddenResponse->assertStatus(403);
});