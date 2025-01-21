<?php

namespace App\RemoteEvent;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\RemoteEvent\Attribute\AsRemoteEventConsumer;
use Symfony\Component\RemoteEvent\Consumer\ConsumerInterface;
use Symfony\Component\RemoteEvent\RemoteEvent;

#[AsRemoteEventConsumer('lemon-squeezy')]
final readonly class LemonSqueezyWebhookConsumer implements ConsumerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ){
    }

    /**
     * @throws \Exception
     */
    public function consume(RemoteEvent $event): void
    {
        $payload = $event->getPayload();

        // $this->getUser() will not work in webhooks as no authenticated user in that process
        $userId = $payload['meta']['custom_data']['user_id'] ?? null;
        if (!$userId) {
            throw new \InvalidArgumentException(sprintf('User ID not found in LemonSqueezy webhook "%s"!', $event->getId()));
        }

        $user = $this->entityManager->getRepository(User::class)
            ->find($userId);
        if (!$user) {
            throw new EntityNotFoundException(sprintf('User "%s" not found for LemonSqueezy webhook "%s"!', $userId, $event->getId()));
        }

        match ($event->getName()) {
            'order_created' => $this->handleOrderCreatedEvent($event, $user),
            default => throw new \LogicException(sprintf('Unsupported LemonSqueezy event: %s', $event->getName())),
        };
    }

    private function handleOrderCreatedEvent(RemoteEvent $event, User $user): void
    {
        $payload = $event->getPayload();
        $customerId = $payload['data']['attributes']['customer_id'];
        $user->setLsCustomerId($customerId);

        $this->entityManager->flush();
    }
}
