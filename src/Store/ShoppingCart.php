<?php

namespace App\Store;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class ShoppingCart
{
    private const SESSION_KEY = '_shopping_cart';

    private array $cart = [
        'products' => [],
    ];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }
    public function addProduct(Product $product, int $quantity = 1): void
    {
        $this->cart = $this->getSession()->get(self::SESSION_KEY, $this->cart);

        if (isset($this->cart['products'][$product->getId()])) {
            $this->cart['products'][$product->getId()] += $quantity;
        } else {
            $this->cart['products'][$product->getId()] = $quantity;
        }

        $this->getSession()->set(self::SESSION_KEY, $this->cart);
    }

    public function deleteProduct(Product $product): void
    {
        $this->cart = $this->getSession()->get(self::SESSION_KEY, $this->cart);

        unset($this->cart['products'][$product->getId()]);

        $this->getSession()->set(self::SESSION_KEY, $this->cart);
    }

    /**
     * @return array|Product[]
     */
    public function getProducts(): array
    {
        $this->cart = $this->getSession()->get(self::SESSION_KEY, $this->cart);
        if (empty($this->cart['products'])) {
            return [];
        }

        return $this->entityManager
            ->getRepository(Product::class)
            ->findBy(['id' => array_keys($this->cart['products'])]);
    }

    public function getProductQuantity(Product $product): int
    {
        $this->cart = $this->getSession()->get(self::SESSION_KEY, $this->cart);

        return $this->cart['products'][$product->getId()] ?? 0;
    }

    public function getTotal(): int
    {
        $total = 0;
        foreach ($this->getProducts() as $product) {
            $total += $product->getPrice() * $this->getProductQuantity($product);
        }

        return $total;
    }

    public function isEmpty(): bool
    {
        return empty($this->getProducts());
    }

    public function clear(): void
    {
        $this->getSession()->remove(self::SESSION_KEY);
    }

    private function getSession(): SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
