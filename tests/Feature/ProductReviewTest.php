<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\Store;
use App\Models\User;

beforeEach(function () {
    $this->buyer = User::factory()->create(['role' => 'buyer']);
    $this->vendor = User::factory()->create(['role' => 'vendor']);
    $this->store = Store::factory()->create(['user_id' => $this->vendor->id, 'status' => 'approved']);
    $category = Category::factory()->create();

    $this->product = Product::factory()->create([
        'store_id'    => $this->store->id,
        'category_id' => $category->id,
        'price'       => 40.00,
    ]);

    // Create a delivered order containing the product
    $this->deliveredOrder = Order::create([
        'user_id'          => $this->buyer->id,
        'store_id'         => $this->store->id,
        'order_number'     => 'ORD-REVIEW-001',
        'subtotal'         => 40.00,
        'total_amount'     => 40.00,
        'payment_method'   => 'paystack',
        'payment_status'   => 'paid',
        'status'           => 'delivered',
        'shipping_name'    => 'Jane Doe',
        'shipping_phone'   => '+1234567890',
        'shipping_address' => '123 Main St',
        'city'             => 'Accra',
    ]);

    OrderItem::create([
        'order_id'   => $this->deliveredOrder->id,
        'product_id' => $this->product->id,
        'quantity'   => 1,
        'price'      => 40.00,
    ]);
});

test('verified buyer with delivered order can submit a review', function () {
    $response = $this->actingAs($this->buyer, 'sanctum')
        ->postJson("/api/v1/products/{$this->product->id}/reviews", [
            'order_id' => $this->deliveredOrder->id,
            'rating'   => 5,
            'comment'  => 'Healthy plant with beautiful fenestrations!',
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('message', 'Review submitted successfully.')
        ->assertJsonPath('review.rating', 5);

    $this->assertDatabaseHas('reviews', [
        'user_id'    => $this->buyer->id,
        'product_id' => $this->product->id,
        'order_id'   => $this->deliveredOrder->id,
        'rating'     => 5,
    ]);
});

test('buyer cannot review a product from a pending or non-delivered order', function () {
    $pendingOrder = Order::create([
        'user_id'          => $this->buyer->id,
        'order_number'     => 'ORD-REVIEW-002',
        'subtotal'         => 40.00,
        'total_amount'     => 40.00,
        'payment_method'   => 'paystack',
        'payment_status'   => 'paid',
        'status'           => 'pending',
        'shipping_name'    => 'Jane Doe',
        'shipping_phone'   => '+1234567890',
        'shipping_address' => '123 Main St',
        'city'             => 'Accra',
    ]);

    $response = $this->actingAs($this->buyer, 'sanctum')
        ->postJson("/api/v1/products/{$this->product->id}/reviews", [
            'order_id' => $pendingOrder->id,
            'rating'   => 4,
            'comment'  => 'Arrived fast',
        ]);

    $response->assertStatus(403)
        ->assertJsonPath('message', 'You can only review products from delivered orders.');
});

test('buyer cannot submit duplicate reviews for the same order', function () {
    Review::create([
        'user_id'    => $this->buyer->id,
        'product_id' => $this->product->id,
        'order_id'   => $this->deliveredOrder->id,
        'rating'     => 5,
        'comment'    => 'First review',
    ]);

    $response = $this->actingAs($this->buyer, 'sanctum')
        ->postJson("/api/v1/products/{$this->product->id}/reviews", [
            'order_id' => $this->deliveredOrder->id,
            'rating'   => 4,
            'comment'  => 'Duplicate attempt',
        ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'You have already reviewed this product for this order.');
});

test('public endpoint returns approved reviews and average rating accurately', function () {
    $anotherBuyer = User::factory()->create();

    Review::create([
        'user_id'    => $this->buyer->id,
        'product_id' => $this->product->id,
        'order_id'   => $this->deliveredOrder->id,
        'rating'     => 5,
        'comment'    => 'Amazing',
        'is_approved'=> true,
    ]);

    Review::create([
        'user_id'    => $anotherBuyer->id,
        'product_id' => $this->product->id,
        'rating'     => 3,
        'comment'    => 'Decent',
        'is_approved'=> true,
    ]);

    $response = $this->getJson("/api/v1/products/{$this->product->id}/reviews");

    $response->assertStatus(200)
        ->assertJsonPath('average_rating', 4.0)
        ->assertJsonPath('total_reviews', 2);
});