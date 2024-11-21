<?php

namespace App\Tests\Webhook;

use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LemonSqueezyRequestParserTest extends WebTestCase
{
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
        $client->request('POST', '/webhook/lemon-squeezy', [], [], [], $json);

        self::assertResponseIsSuccessful('Webhook failed!');
        self::assertNotNull($user->getLsCustomerId(), 'LemonSqueezy customer ID not set!');
        self::assertEquals(1000001, $user->getLsCustomerId(), 'LemonSqueezy customer ID mismatch!');
    }
}
