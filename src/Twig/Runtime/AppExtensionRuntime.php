<?php

namespace App\Twig\Runtime;

use App\Store\ShoppingCart;
use Twig\Extension\RuntimeExtensionInterface;

final readonly class AppExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private ShoppingCart $cart
    ) {
        // Inject dependencies if needed
    }

    public function cart(): ShoppingCart
    {
        return $this->cart;
    }

    public function priceFormat(int $value): string
    {
        return '$'.number_format($value / 100, 2, '.', ' ');
    }
}
