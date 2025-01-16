<?php

namespace App\Tests\Webhook;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

class LemonSqueezyRequestParserTest extends WebTestCase
{
    use ResetDatabase;

    public function testOrderCreatedWebhook(): void
    {
        $client = static::createClient();

        $user = UserFactory::new()->create([
            'email' => 'test@example.com',
            'plainPassword' => 'testpass',
            'firstName' => 'Test',
        ]);

        $json = file_get_contents(__DIR__.'/../fixtures/order_created.json');
        $json = strtr($json, [
            '%user_id%' => $user->getId(),
            '%customer_id%' => 1000001,
        ]);
        $hash = hash_hmac('sha256', $json, $_ENV['LEMON_SQUEEZY_SIGNING_SECRET']);
        $client->request('POST', '/webhook/lemon-squeezy', [], [], [
            'HTTP_X-Signature' => $hash,
        ], $json);

        self::assertResponseIsSuccessful('Webhook failed!');
        self::assertNotNull($user->getLsCustomerId(), 'LemonSqueezy customer ID not set!');
        self::assertEquals(1000001, $user->getLsCustomerId(), 'LemonSqueezy customer ID mismatch!');
    }
}
