# Implementing the Webhook Request Parser

In the last chapter, we saw LemonSqueezy deliver its first webhook to our local
website via the Ngrok tunnel. *But* it didn't *quite* hit the mark because we
still need to set up the actual webhook handling. So, let's roll up our sleeves
and get to work!

When we ran the `make:webhook` command, it generated two files - a request
parser and a consumer. Let's start with the parser. Open up
`src/Webhook/LemonSqueezyRequestParser.php`.

It's important to remember that *all* LemonSqueezy webhooks are sent as a POST
request with a JSON payload to the `/webhook/lemon-squeezy` path we set up in
the dashboard. With *that* in mind, we need to tweak the `getRequestMatcher()`
method.

Inside `getRequestMatcher()`, we have `ChainRequestMatcher`. This basically
includes the other three matchers. Set the `PathRequestMatcher` to
`/webhook/lemon-squeezy`. Then we need to confirm that it's a `POST` request. To
make it cooler, use the `Request::METHOD_POST` constant, which is just a fancy
way of saying "POST". This `IsJsonRequestMatcher` is fine as is and doesn't need
any arguments.

[[[ code('e66723714b') ]]]

Okay, we're ready to move on to the next method: `doParse()`. The first order of
business here is to verify the webhook signature. To keep things neat, we'll
create a separate method for this. Add `private function verifySignature()`...
and this function will take *two* arguments: `Request $request` and
`string $secret`. The return type is `void`.

[[[ code('e490c9c895') ]]]

Next, we have to calculate the request payload's hash using LemonSqueezy's
algorithm: `$payload = $request->getContent()`. We'll start with a hash
variable: `$hash = hash_hmac('sha256', $payload, $secret)`. Then we need to
fetch the signature from the request header with
`$signature = $request->headers->get('X-Signature', '')`.

[[[ code('fd27cc281e') ]]]

Now it's time to see if the hash matches the signature. If it *does*
(`if (hash_equals($hash, $signature))`), then we're good to go. If *not*, we'll
`throw new RejectWebhookException()` with a `Response::HTTP_UNAUTHORIZED`
status code - a 401. We'll also add a message - `Invalid LemonSqueezy signature!` - so
we know what happened.

Once that's done, we can call this from the `doParse()` method at the beginning.
I'll de-clutter and remove this placeholder code, and *then* we'll validate the
payload: `$payload = $request->toArray()`. Below, write
`$eventName = $payload['meta']['event_name']` and
`$webhookId = $payload['meta']['webhook_id']`.

[[[ code('82fa32dd43') ]]]

We'll also add a sanity check to confirm the presence of the `$eventName` and
`webhookId` with `if (!$eventName || !$webhookId) {`. Inside,
`throw new RejectWebhookException()`, give it a `Response::HTTP_BAD_REQUEST`
status code, and error message:
`Request payload does not contain required fields.`.

Okay, let's check to see if it's a supported event. At the moment, we're only
tracking `order_created`. If it's *not*, we'll throw another exception -
`RejectWebhookException()` - with `Response::HTTP_BAD_REQUEST` status, and use
`sprintf('Unsupported event type: %s', $eventName)`.

To pull it all together, return a
`new RemoteEvent($eventName, $webhookId, $payload)`. *That's it*! Our parser
should be good to go!

[[[ code('7e45194fc1') ]]]

If we've done this correctly, LemonSqueezy *should* receive a 200 successful
status code. We can test this by either *resending* the webhook from
LemonSqueezy's dashboard *or* replaying the webhook from the Ngrok web
interface. I'll click "Replay" and... it's a 202 status code! Still counts!
Success!

Our parser's working and it's *time to celebrate*! Up next, let's implement the
*webhook consumer*.
