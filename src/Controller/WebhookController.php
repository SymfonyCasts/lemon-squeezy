<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhook')]
class WebhookController extends AbstractController
{
    public const LEMON_SQUEEZY_WEBHOOK_SECRET = 'lEm0n-5qUeEzY';

    #[Route('/lemon-squeezy', name: 'app_webhook_lemon_squeezy', methods: ['POST'])]
    public function lemonSqueezy(Request $request, EntityManagerInterface $entityManager): Response
    {
        $payload = $request->getContent();
        $hash = hash_hmac('sha256', $payload, self::LEMON_SQUEEZY_WEBHOOK_SECRET);
        $signature = $request->headers->get('X-Signature', '');
        if (!hash_equals($hash, $signature)) {
            throw new \Exception('Invalid LemonSqueezy signature!');
        }

        $data = $request->toArray();
        $webhookId = $data['meta']['webhook_id'];

        // $this->getUser() will not work in webhooks
        $userId = $data['meta']['custom_data']['user_id'] ?? null;
        if (!$userId) {
            throw new \Exception(sprintf('User ID not found in LemonSqueezy webhook "%s"!', $webhookId));
        }
        $user = $entityManager->getRepository(User::class)
            ->find($userId);
        if (!$user) {
            throw new \Exception(sprintf('User "%s" not found for LemonSqueezy webhook "%s"!', $userId, $webhookId));
        }

        $eventName = $data['meta']['event_name'];
        switch ($eventName) {
            case 'order_created':
                $customerId = $data['data']['attributes']['customer_id'];
                $user->setLsCustomerId($customerId);

                break;
            default:
                throw new \Exception(sprintf('Unsupported LemonSqueezy event: "%s"', $eventName));
        }

        $entityManager->flush();

        return new Response('Webhook successfully handled!');
    }
}
