# Implementing the Webhook Request Parser

In the last lesson, we had a chance to see LemonSqueezy delivering its
first webhook to our local website via the Ngrok tunnel. A big thanks to
Ngrok for that! However, it didn't quite hit the mark because we still need
to set up the actual webhook handling. So, let's roll up our sleeves and
dive right into that.

When we ran the `make:webhook` command, it did us the favor of generating
two files: a request parser and a consumer. To kick things off, we'll start
by focusing on the parser. Open up `src/Webhook/LemonSqueezyRequestParser.php`.

Now, you should know that all LemonSqueezy webhooks are sent as a POST
request with a JSON payload to the `/webhook/lemon-squeezy` path we set up
in the dashboard. Keeping that in mind, we need to tweak the
`getRequestMatcher()` method.

Inside, we have a `ChainRequestMatcher` which is basically a combo of three
other matchers. As we've mentioned, in the `PathRequestMatcher`, we we should
use `/webhook/lemon-squeezy`. Then we confirm it's a POST request. To keep
things clear, I'll use the `Request::METHOD_POST` constant, which is just a
fancy way of saying "POST". And lastly, `isJsonRequestMatcher` is good as
it is and doesn't need any arguments.

I think we're ready to move on to the next method, `doParse()`. Here, the
first order of business is to verify the webhook signature. For the sake of
neatness, we'll create a separate method for this. Below, I'm adding a:
`private function verifySignature()`. This function will take two arguments:
`Request $request` and `string $secret`.

Next up, we have to calculate the request payload's hash using
LemonSqueezy's unique algorithm. Let's start with a hash variable:
`$hash = hash_hmac('sha256', $payload, $secret);`

We then fetch the signature from the request header:
`$signature = $request->headers->get('X-Signature', '');`

Now it's time to see if the hash matches the signature. If it does, we're
good to go. If not, we'll `throw new RejectWebhookException()` with a status
of 401 `Response::HTTP_UNAUTHORIZED` status code. We'll also add a message
saying "Invalid LemonSqueezy signature" to be more specific.

Now that we've done that, we can call this from the `doParse()` method at
the beginning. Below that, I'll remove this placeholder code to make it
easier to follow what I'm doing. Next, we'll validate the payload:
`$payload = $request->toArray();`, below it: `$eventName = $payload['meta']['event_name'];`
and `$webhookId =$payload['meta']['webhook_id'];`

We'll also add a sanity check to confirm the presence of the `$eventName` and
`webhookId`:

`if (!$eventName || !$webhookId) {` then `throw new RejectWebhookException()`.
With `Response::HTTP_BAD_REQUEST` status code, and "Request payload does not contain required fields."
error message.

Now, let's check if it's a supported event. Currently, we're only tracking
`order_created`. If it's not, we'll throw another exception:
`RejectWebhookException()` with `Response::HTTP_BAD_REQUEST` status,
and let's use `sprintf('Unsupported event type: %s', $eventName)`.

Finally, we'll return a `new RemoteEvent($eventName, $webhookId, $payload);`.
And that's it! Our parser should be good to go.

If we've done everything right, LemonSqueezy should get a 200 successful
status code back. We can test this by either resending the webhook from the
LemonSqueezy dashboard or replaying the webhook from the Ngrok Web
Interface. And we see the successful 202 Accepted status code, now we can
celebrate because it means our request parser is working perfectly.

Next up, we'll implement the webhook consumer.
