<?php

namespace App\Controller;

use App\Entity\Product;
use App\Store\ShoppingCart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
        #[Target('lemonSqueezyClient')]
        HttpClientInterface $lsClient,
        ShoppingCart $cart
    ): Response {
        $lsCheckout = $this->createCheckout($lsClient, $cart);
        $checkoutUrl = $lsCheckout['data']['attributes']['url'];

        return $this->redirect($checkoutUrl);
    }

    private function createCheckout(HttpClientInterface $lemonSqueezyClient, ShoppingCart $cart): array
    {
        if ($cart->isEmpty()) {
            throw new \LogicException('Nothing to checkout!');
        }

        $products = $cart->getProducts();
        if (count($products) === 1) {
            $variantId = $products[0]->getLsVariantId();
            $attributes = [
                'checkout_data' => [
                    'variant_quantities' => [
                        [
                            'variant_id' => (int)$variantId, // Should be int!
                            'quantity' => $cart->getProductQuantity($products[0]),
                        ],
                    ],
                ],
            ];
        } else {
//            throw new \LogicException('Only one product purchase is supported for now!');
            $variantId = $products[0]->getLsVariantId();

            $description = '';
            foreach ($products as $product) {
                $description .= $product->getName()
                    . ' for $' . number_format($product->getPrice()/100, 2)
                    . ' x ' . $cart->getProductQuantity($product)
                    . '<br>';
            }
            $attributes = [
                'custom_price' => $cart->getTotal(),
                'product_options' => [
                    'name' => sprintf('E-lemonades'),
                    'description' => $description,
                ]
            ];
        }

        $response = $lemonSqueezyClient->request(Request::METHOD_POST, 'checkouts', [
//            'json' => [
//                'data' => [
//                    'type' => 'checkouts',
//                ],
//            ],
            'json' => [
                'data' => [
                    'type' => 'checkouts',
                    'attributes' => $attributes,
//                    [
//                        'checkout_data' => [
//                            'variant_quantities' => [
////                                [
////                                    'variant_id' => 579933,
////                                    // TODO
////                                    'quantity' => 3,
////                                ],
//                                $variantQuantities,
//                            ],
//                        ],
//                    ],
                    'relationships' => [
                        'store' => [
                            'data' => [
                                'type' => 'stores',
//                                'id' => '132127', // TODO Convert to env var
                                'id' => $this->getParameter('env(LEMON_SQUEEZY_STORE_ID)'),
                            ],
                        ],
                        'variant' => [
                            'data' => [
                                'type' => 'variants',

//                                'id' => 579933, // TODO Should be a string!
                                'id' => $variantId, // Should be a string!
                            ],
                        ],
                    ],
                ],
            ],
        ]);

//        dd($response->getContent());
//        dd($response->getContent(false));

        return $response->toArray();
    }
}
