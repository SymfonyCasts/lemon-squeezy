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

One thing to note is the consumer is triggered by a totally separate
request - the webhook from LemonSqueezy. Not only that,
but consumers are handled by Symfony Messenger. It's possible they'll be processed by
a background worker. The point is, we have no access to the original user object like we do
in the checkout controller.

So... how do we get the user in the consumer? Lucky for
us, LemonSqueezy's "Create a Checkout" API documentation explains how to add
custom data when creating the checkout URL. This is *perfect* for passing our
user ID, so let's get started! Head over to the `LemonSqueezyApi` in
`src/Store/` and find the `createCheckoutUrl()` method.

Here, we need to make the user *not* optional. This is
*crucial* because *that's* the information we need to link to the corresponding
LemonSqueezy customer. Remove the `?` from the `User` type-hint and
the now unnecessary `if ($user)` statement below. Now, add
`$attributes['checkout_data']['custom']['user_id'] = $user->getId()`. This
`custom` field allows us to pass any custom data we may need to LemonSqueezy
and will be made available to us in the webhook payload.

The *goal* is to share the user ID with LemonSqueezy when a customer places an
order. To achieve this, the user needs to be logged in. Back in
`OrderController::checkout()`, all that's needed to make this route require
authentication is to remove the `?` from the `User` type-hint.
Pretty neat, huh?

To confirm everything's working as expected, over on our site, log out... add a
product to the cart, and try to check out. Since we're a non-authenticated user,
we *should* be redirected to the login page, and... perfect!

## Handling the Webhook Consumer

Back in the `LemonSqueezyWebhookConsumer::consume()` method, set
`$payload = $event->getPayload()`.

To access the `user_id` we set in `createCheckoutUrl()`, write
`$userId = $payload['meta']['custom_data']['user_id'] ?? null`. I'll add a quick
comment to explain we can't get the user by "traditional means".

Now, write a sanity check with `if (!$userId)`. If this check fails,
we'll `throw new InvalidArgumentException()` with 
`sprintf('User ID not found in LemonSqueezy webhook: %s', $event->getId())`.

We now have the `$userId`, but how do we get the user object? From the database!
In our constructor, inject `private EntityManagerInterface $entityManager`.

Back in the `consume()` method, fetch the user with
`$user = $this->entityManager->getRepository(User::class)->find($userId)`.

Next, if `$user` doesn't exist, we'll `throw new EntityNotFoundException()`
(choose the one from `Doctrine\ORM`). For the message write
`sprintf('User "%s" not found for LemonSqueezy webhook "%s"!', $userId, $event->getId())`.

Below, add `match ($event->getName())`, and for `order_created`, call
`$this->handleOrderCreatedEvent()`. This method doesn't exist yet, but we'll
create it later. Also pass `$event` and `$user` as arguments. At this point, we
should only have supported events, but on the off chance we're missing
something, add a `default` that will `throw new LogicException()`, with
`sprintf('Unsupported LemonSqueezy event: %s', $event->getId())`. *Nice*.

## Creating the HandleOrder Event

Now, create the `handleOrderCreatedEvent()` method with PhpStorm as a
private function. It looks like PhpStorm added one
argument - `RemoteEvent $event` - but forgot the second, so add
`User $user` manually and return type: `void`.

Inside, fetch the payload with `$payload = $event->getPayload()`. Below
that, fetch the customer ID with
`$customerId = $payload['data']['attributes']['customer_id']`. If you're
wondering where this came from, you can find it in the Ngrok inspector.

Okay, we have the `customer_id` now, but we still need a new property on the
`User` to save it. At your terminal, create a new tab and run:

```terminal
bin/console make:entity
```

For the class, write `User` to grab the existing entity. For the property name, call it
`lsCustomerId`. Make it a string with a length of 255, and nullable.
Hit `Enter` one more time and... done!

Back in our code, open `src/Entity/User.php`... if we scroll down... here's
our new column! Set this to `unique: true`. This looks great, and if
we scroll *way* down here, we can see that it *also* created a getter and setter
for the field. *Sweet*!

Now we need to create a migration. Do that with:

```terminal
bin/console make:migration
```

If we go check that out... looks good! We'll just add a quick description -
`Add customer ID property to User entity` - and back in our terminal, migrate
with:

```terminal
bin/console doctrine:migration:migrate
``` 

Once that's finished, return to the `handleOrderCreatedEvent()` and call our new
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

Log in again, add a product to the cart, and try to check out. Oops,
*another* error - a 422. Jump over to `LemonSqueezyApi` and uncomment this `dd()` to
see what's going on here. If we refresh our site... ah!

> ...field must be a string...

and it's pointing to the custom `user_id` we added... Turns out LemonSqueezy is
pretty strict with types. Head back to our code,
comment out that `dd()` again... and, up here... cast this `$user->getId()` to
a string. Back in our app... refresh... and success! We're on the checkout page!

Let's fill in the card info... address... make the payment, and wait for the
webhook. *Yes*! Our transaction was accepted and, over here, we have a 202
status code.

If we look at the request, we can see our `custom_data` -> `user_id`
equals `1`. We can also check the database with a handy dandy SQL command. At
your terminal, run:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user WHERE id = 1"
```

This `lsCustomerId` is the unique ID from LemonSqueezy. Sweet!

Before moving on, let's write some *tests* for our webhook setup. That's *next*!
