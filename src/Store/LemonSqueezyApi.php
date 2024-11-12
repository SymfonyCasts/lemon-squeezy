<?php

namespace App\Store;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LemonSqueezyApi
{
    public function __construct(
        #[Target('lemonSqueezyClient')]
        private readonly HttpClientInterface $client,
        private readonly ShoppingCart $cart,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function createCheckout(User $user): array
    {
        if ($this->cart->isEmpty()) {
            throw new \LogicException('Nothing to checkout!');
        }

        $attributes = [];
        $attributes['checkout_data']['email'] = $user->getEmail();
        $attributes['checkout_data']['name'] = $user->getFirstName();
        $attributes['checkout_data']['custom']['user_id'] = (string) $user->getId();

        $products = $this->cart->getProducts();
        if (count($products) === 1) {
            $variantId = $products[0]->getLsVariantId();
            $attributes['checkout_data']['variant_quantities'] = [
                [
                    'variant_id' => (int)$variantId, // Should be int!
                    'quantity' => $this->cart->getProductQuantity($products[0]),
                ],
            ];
        } else {
//            throw new \LogicException('Only one product purchase is supported for now!');
            $variantId = $products[0]->getLsVariantId();

            $description = '';
            foreach ($products as $product) {
                $description .= $product->getName()
                    . ' for $' . number_format($product->getPrice()/100, 2)
                    . ' x ' . $this->cart->getProductQuantity($product)
                    . '<br>';
            }
            $attributes['custom_price'] = $this->cart->getTotal();
            $attributes['product_options'] = [
                'name' => sprintf('E-lemonades'),
                'description' => $description,
            ];
        }

        $attributes['product_options']['redirect_url'] = $this->urlGenerator->generate('app_order_success', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->request(Request::METHOD_POST, 'checkouts', [
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
                                'id' => $this->parameterBag->get('env(LEMON_SQUEEZY_STORE_ID)'),
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
//        return $response->toArray();
    }

    public function createCheckoutUrl(User $user): string
    {
        $lsCheckout = $this->createCheckout($user);

        return $lsCheckout['data']['attributes']['url'];
    }

    private function request(string $method, string $url, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $url, $options);
            $data = $response->toArray();
        } catch (ClientException $e) {
            $data = $e->getResponse()->toArray(false);
//            dd($data);

            $mainErrorMessage = 'LS API Error:';

            $error = $data['errors'][0] ?? null;
            if ($error) {
                if (isset($error['status'])) {
                    $mainErrorMessage .= ' ' . $error['status'];
                }
                if (isset($error['title'])) {
                    $mainErrorMessage .= ' ' . $error['title'];
                }
                if (isset($error['detail'])) {
                    $mainErrorMessage .= ' "' . $error['detail'] . '"';
                }
                if (isset($error['source']['pointer'])) {
                    $mainErrorMessage .= sprintf(' (at path "%s")', $error['source']['pointer']);
                }
            } else {
                $mainErrorMessage .= $e->getResponse()->getContent(false);
            }

            throw new \Exception($mainErrorMessage, 0, $e);
        }

        return $data;
    }
}
