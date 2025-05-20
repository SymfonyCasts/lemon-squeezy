# Implementing the Webhook Consumer

In the last chapter, we successfully set up the webhook request parser. This
parser is designed to receive a webhook from LemonSqueezy, verify its signature,
parse the payload, and pass the parsed data on to a webhook consumer. Now that
our parser's ready, we can tackle the *next* part – handling the webhook data in
the consumer.

Start by opening the `LemonSqueezyWebhookConsumer.php` file from the
`src/RemoveEvent/` directory, and find the `consume()` method. We can get rid of
this TODO. Our task here is to find the corresponding user to the `customer_id`
we get from the webhook data, and *connect* them.

We're in a different session here, and that means we can't access the current
user directly from the `Security` service, so... how do we get the user? Lucky for
us, LemonSqueezy's "Create a Checkout" API documentation explains how to add
custom data when creating the checkout URL. This is *perfect* for passing our
user ID, so let's get started! Head over to the `LemonSqueezyApi` in
`src/Store/` and find the `createCheckoutUrl()` method.

Here, we'll require users to be logged in before they can check out. This is
*crucial* because *that's* the information we need to link to the corresponding
LemonSqueezy customer. We can do that in the method's signature. The login
requirement means that we no longer need the `if ($user)` statement below, so we
can remove that and tidy up these lines. Then, add
`$attributes['checkout_data']['custom']['user_id'] = $user->getId()`. This
`custom` field allows us to pass any custom data we may need to LemonSqueezy
and will be made available to us in the webhook payload.

The *goal* is to share the user ID with LemonSqueezy when a customer places an
order. If we head back to the `OrderController`, you may notice that PhpStorm
isn't super happy about this `createCheckoutUrl()` call. That's because `User`
is no longer optional. Remove this `?` and that should do it.

To confirm everything's working as expected, over on our site, log out... add a
product to the cart, and try to check out. Since we're a non-authenticated user,
we *should* be redirected to the login page, and... perfect!

## Handling the Webhook Consumer

Back in the code, in our `consume()` method, set
`$payload = $event->getPayload()`. Below that, write
`$userId = $payload['meta']['custom_data']['user_id'] ?? null`.

Now, let's have a sanity check with `if (!$userId)`. If this check fails,
we'll `throw new InvalidArgumentException()` with a `sprintf()` inside saying
`'User ID not found in LemonSqueezy webhook: %s', $userId`.

To access the `EntityManager`, in our constructor, inject
`private EntityManagerInterface $entityManager`.

Back in the `consume()` method, continue with
`$user = $this->entityManager->getRepository(User::class)->find($userId)`.

Next, if `$user` doesn't exist, we'll `throw new EntityNotFoundException()`
(choose the one from `Doctrine\ORM`). We'll also add `sprintf()` as an argument,
stating `User "%s" not found for LemonSqueezy webhook "%s"!`, and pass `$userId`
and `$event->getId()`.

Below that, add `match ($event->getName())`, and for `order_created`, call
`$this->handleOrderCreatedEvent()`. This method doesn't exist yet, but we'll
create it later. Also pass `$event` and `$user` as arguments. At this point, we
should only have supported events, but on the off chance we're missing
something, add a `default` that will `throw new LogicException()`, with
`sprintf('Unsupported LemonSqueezy event: %s', $event->getName())`. *Nice*.

## Creating the HandleOrder Event

Before we forget, let's circle back and create the `handleOrderCreatedEvent()`.
This will be a `private function`, and it looks like PhpStorm added one
argument - `RemoteEvent $event` - but forgot the second, so we'll add
`User $user` manually.

Inside, let's fetch the payload with `$payload = $event->getPayload()`. Below
that, fetch the customer ID with
`$customerId = $payload['data']['attributes']['customer_id']`. If you're
wondering where this came from, you can find this path in the Ngrok request
payload.

Okay, we have the `customer_id` now, but we still need a new property on the
`User` to save it. At your terminal, create a new tab and run:

```terminal
bin/console make:entity
```

For the class name, we'll write `User`. For the property name, call it
`lsCustomerId`. Make it a string with a length of 255, and nullable.
Hit `Enter` one more time and... done!

Back in our code, open `src/Entity/User.php`... if we scroll down... here's
our new column! Let's also set this to `unique: true`. This looks great, and if
we scroll *way* down here, we can see that it *also* created a getter and setter
for the field. *Sweet*!

Now we need to create a migration. We can do that with:

```terminal
bin/console make:migration
```

If we go check that out... looks good! We'll just add a quick description -
`Add customer ID property to User entity` - and back in our terminal, migrate
with:

```terminal
bin/console doctrine:migration:migrate
``` 

Once that's finished, return to `handleOrderCreatedEvent()` and call our new
setter: `$user->setLsCustomerId()` with `$customerId`. To *save* it, call
`$this->entityManager->flush()`.

## Testing the Webhook

Time to test the webhook again! In the Ngrok inspector, replay.
Hmm... an *error*. It's a bit hard to see but here it is:

> User ID not found in LemonSqueezy webhook

That makes sense - when this webhook ran originally, it didn't have the `user_id` set.
With Ngrok, we *can* modify the original webhook content and *replay* it with
modifications, but... to be *extra* sure our
changes are accounted for, we'll go through the checkout process again so
LemonSqueezy can set the `user_id` correctly.

Let's log in again, add a product to the cart, and try to check out. Oops,
*another* error - a 422. Jump over to `LemonSqueezyApi` and uncomment this `dd()` to
see what's going on here. If we refresh our site... ah!

> ...field must be a string...

and it's pointing to the custom `user_id` we added... Head back to our code,
comment out that `dd()` again... then, up here... cast this `$user->getId()` to
a string. Back in our app... refresh... and success! We're on the checkout page!

Let's fill in the card info... address... make the payment, and wait for the
webhook. *Yes*! Our transaction was accepted and, over here, we have a 202
status code.

If we look at the request, we can see our `custom_data` and `user_id`
equals `1`. We can also check the database with a handy dandy SQL command. At
your terminal, run:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user WHERE id = 1"
```

This `lsCustomerId` is the unique ID from LemonSqueezy. Sweet!

Before moving on, let's write some *tests* for our webhook setup. That's *next*!
