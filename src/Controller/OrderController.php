<?php

namespace App\Controller;

use App\Entity\Product;
use App\Store\LemonSqueezyApi;
use App\Store\ShoppingCart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrderController extends AbstractController
{
    #[Route('/cart', name: 'app_order_cart')]
    public function cart(ShoppingCart $cart): Response
    {
        return $this->render('order/cart.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/product/{slug:product}/add', name: 'app_cart_product_add', methods: ['POST'])]
    public function addProductToCart(Request $request, Product $product, ShoppingCart $cart): Response
    {
        $quantity = $request->request->getInt('quantity', 1);
        $cart->addProduct($product, $quantity);

        $this->addFlash('success', 'Product added to yours cart!');

        return $this->redirectToRoute('app_order_cart');
    }

    #[Route('/cart/clear', name: 'app_cart_clear', methods: ['POST'])]
    public function clearCart(ShoppingCart $cart): Response
    {
        $cart->clear();

        $this->addFlash('success', 'Cart cleared!');

        return $this->redirectToRoute('app_order_cart');
    }

    #[Route('/checkout', name: 'app_order_checkout')]
    public function checkout(
        LemonSqueezyApi $lsApi,
    ): Response {
        $lsCheckout = $lsApi->createCheckout();
        $checkoutUrl = $lsCheckout['data']['attributes']['url'];

        return $this->redirect($checkoutUrl);
    }

    #[Route('/checkout/success', name: 'app_order_success')]
    public function success(ShoppingCart $cart): Response
    {
        if ($cart->isEmpty()) {
            return $this->redirectToRoute('app_homepage');
        }

        $cart->clear();
        $this->addFlash('success', 'Thanks for your order!');

        return $this->redirectToRoute('app_homepage');
    }
}
