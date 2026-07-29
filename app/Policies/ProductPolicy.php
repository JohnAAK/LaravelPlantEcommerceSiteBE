<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() || ($user->isVendor() && $product->store_id === $user->store?->id);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}