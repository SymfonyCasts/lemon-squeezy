<?php

namespace App\Webhook;

use Symfony\Component\HttpFoundation\ChainRequestMatcher;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\IsJsonRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\MethodRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RemoteEvent\RemoteEvent;
use Symfony\Component\Webhook\Client\AbstractRequestParser;
use Symfony\Component\Webhook\Exception\RejectWebhookException;

final class LemonSqueezyRequestParser extends AbstractRequestParser
{
    protected function getRequestMatcher(): RequestMatcherInterface
    {
        return new ChainRequestMatcher([
            new PathRequestMatcher('/webhook/lemon-squeezy'),
            new MethodRequestMatcher(Request::METHOD_POST),
            new IsJsonRequestMatcher(),
        ]);
    }

    /**
     * @throws JsonException
     */
    protected function doParse(Request $request, #[\SensitiveParameter] string $secret): ?RemoteEvent
    {
        $this->verifySignature($request, $secret);

        // Validate the request payload.
        $payload = $request->toArray();
        $eventName = $payload['meta']['event_name'];
        $webhookId = $payload['meta']['webhook_id'];
        if (!$eventName || !$webhookId) {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, 'Request payload does not contain required fields.');
        }

        if ($eventName !== 'order_created') {
            throw new RejectWebhookException(Response::HTTP_BAD_REQUEST, sprintf('Unsupported event type: %s', $eventName));
        }

        return new RemoteEvent($eventName, $webhookId, $payload);
    }

    private function verifySignature(Request $request, string $secret): void
    {
        $payload = $request->getContent();
        $hash = hash_hmac('sha256', $payload, $secret);
        $signature = $request->headers->get('X-Signature', '');
        if (hash_equals($hash, $signature)) {
            return;
        }

        throw new RejectWebhookException(Response::HTTP_UNAUTHORIZED, 'Invalid LemonSqueezy signature!');
    }
}
