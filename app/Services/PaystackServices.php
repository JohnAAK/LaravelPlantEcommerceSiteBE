<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class PaystackService
{
    protected string $baseUrl;
    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl = config('services.paystack.payment_url');
        $this->secretKey = config('services.paystack.secret_key');
    }

    /**
     * Initialize a Paystack transaction.
     */
    public function initializeTransaction(string $email, float $amountInNaira, string $reference, array $metadata = []): array
    {
        // Paystack expects amount in kobo (multiply Naira/GHS base unit by 100)
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email'        => $email,
                'amount'       => (int) ($amountInNaira * 100),
                'reference'    => $reference,
                'metadata'     => $metadata,
                'callback_url' => route('paystack.callback'),
            ]);

        if ($response->failed()) {
            throw new Exception('Paystack initialization failed: ' . $response->body());
        }

        return $response->json()['data'];
    }

    /**
     * Verify a transaction via reference.
     */
    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->failed()) {
            throw new Exception('Paystack verification failed: ' . $response->body());
        }

        return $response->json()['data'];
    }
}