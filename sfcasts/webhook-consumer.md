# Implementing Webhook Consumer 

In the last chapter, we successfully set up the webhook request parser. This parser is designed to receive a webhook from LemonSqueezy, verify its signature, parse the payload, and pass the parsed data on to the webhook consumer. Now that our parser's ready, we can tackle the *next* part – handling the webhook data in the consumer.

Start by opening the `LemonSqueezyWebhookConsumer.php` file from the `src/RemoveEvent/` directory, and find the `consume()` method. We can get rid of this TODO. Our task here is to find the corresponding user to the `customer_id` we got from the webhook data, and *connect* them.

We're in a different session here, and that means we can't access the current user directly from the `Security` service, so... how do we do this? Lucky for us, LemonSqueezy's "Create a Checkout" API documentation explains how to add custom data when creating the checkout URL. This is *perfect* for passing our user ID, so let's get started! Head over to the `LemonSqueezyApi` in `src/Store/` and find the `CreateCheckoutURL()` method.

Here, we'll require users to be logged in before they can check out. This is *crucial* because *that's* the information we need to link to the corresponding LemonSqueezy customer. We can do that in the method's signature. The login requirement means that we no longer need the `if (user)` statement below, so we can remove that and tidy up these lines. Then, add `$attributes['checkout_data]['custom']['user_id'] = $user->getId()`. This `custom` field allows us to pass any custom data we may need to LemonSqueezy.

The *goal* is to share the user ID with LemonSqueezy when a customer places an order. If we head back to the `OrderController`, you may notice that PhpStorm isn't super happy about this `createCheckoutUrl()` call. That's because we still need to make it a requirement in that method's signature. Remove this `?` and that should do it - no need to call `denyAccessUnlessGranted()`.

To confirm everything's working as expected, over on our site, log out... add a product to the cart, and try to check out. Since we're a non-authenticated user, we *should* be redirected to the login page, and... perfect!

## Handling the Webhook Consumer

Back in the code, in our `consume()` method, set `$payload = $event->getPayload()`. Below that, say `$userId = $payload['meta']['custom_data']['user_id'] ?? null`. We can also leave a comment here mentioning that `getUser()` won't work in webhooks as a non-authenticated user in this process.

Now, let's conduct a sanity check with `if (!$userId)`. If this check fails, we'll `throw new InvalidArgumentException()` with a `sprintf()` inside saying `'User ID not found in LemonSqueezy webhook: %s', $userId`.

To access the `EntityManger`, in our constructor, inject `private EntityManagerInterface` as `$entityManager`.

Back in the `consume()` method, continue with `$user = $this->entityManager->getRepository(User::class)->find($userId)`. 

Next, if `$user` doesn't exist, we'll `throw new EntityNotFoundException()` (choose the one from "Doctrine\ORM"). We'll also add `sprintf()` as an argument, stating `User "%s" not found for LemonSqueezy webhook "%s"!`, and pass `$userId` and `$event->getId()`. 

Below that, add `match ($event->getName())`, and for `order_created`, call `$this->handleOrderCreatedEvent()`. This method doesn't exist yet, but we'll create it later. Also pass `$event` and `$user` as arguments. At this point, we should only have supported events, but on the off chance we're missing something, add a `default` that will `throw new LogicException()`, with `sprintf('Unsupported LemonSqueezy event: %s', $event->getName())`. *Nice*.

## Creating the HandleOrder Event

Before we forget, let's circle back and create the `handleOrderCreatedEvent()`. This will be a `private function`, and it looks like PhpStorm added one argument - `RemoteEvent $event` - but forgot the second, so we'll add `User $user` manually.

Inside, let's fetch the payload with `$payload = $event->getPayload()`. Below that, fetch the customer ID from the payload: `$customerId = $payload['data']['attributes']['customer_id']`. If you're wondering where this came from, you can find this path in the Ngrok request summary.

Okay, we have the `customer_id` now, but we still need a new property on the user to save it. At your terminal, create a new tab and run:

```terminal
bin/console make:entity
```

We'll call this `User`. For the property name, call it `lsCustomerId`. Make it a string, with a length of 255, and make it nullable. Hit "enter" one more time and... done!

Back in our code, open `Entity/User.php`... and if we scroll down... here's our new column! Let's also set this to `unique: true`. This looks great, and if we scroll *way* down here, we can see that it *also* created a getter and setter for this field. *Sweet*! 

Now we need to create a migration. We can do that with:

```terminal
bin/console make:migration
```

If we go check that out... looks good! We'll just add a quick description - `Add customer ID property to User entity` - and back in our terminal, migrate with:

```terminal
bin/console doctrine:migration:migrate
``` 

Once that's finished, return to the `handleOrderCreatedEvent()`, and set it on our new column with `$user->setLsCustomerId()`. Be sure to pass the `$customerId` variable as an argument. To *save* it, call `$this->entityManager->flush()`.

## Testing the Webhook

Time to test the webhook again! I'm a fan of the Ngrok inspector, so I'll use that. Hmm... an *error*. The webhook doesn't have a `user_id` set on custom data for that specific case. That makes sense. With Ngrok, we can modify the original webhook content and *replay* it with modifications. But to be *extra* sure our changes are accounted for, we'll go through the checkout process again so LemonSqueezy can set the `user_id` correctly.

Let's log in again, add a product to the cart, and try to check out. Oops, *another* error. Let's head back to our code and uncomment this `dd()` to get a better picture of what's happening here. If we refresh our site... ah! It looks like the field needs to be a string, and it's pointing to the custom `user_id` we added. You can't pass just *anything*, so passing the whole user object definitely won't work. Head back to our code and comment out that `dd()` again... then, up here... let's specify that this user ID is a `string` and try this again. When we refresh... success! We're on the checkout page!

Let's fill in the card info... address... make the payment, and wait for the webhook. *Yes*! Our transaction was accepted and, over here, we have a 202 status code.

If we look at the request, we can see our `custom_data` where the `user_id` equals `1`. We can also check the database with a handy dandy SQL command. At your terminal, run

```terminal
bin/console doctrine:query:sql
```

and in double quotes, input

```terminal
SELECT * FROM user WHERE id = 1
```

since we have an ID of "1" for the current user. Hit "enter" and... *yes*! The `lsCustomerId` is set to this unique ID. This is what we need to create a list of orders made by this customer.

*But* before we do that, let's see how we can *test* our webhook. That's *next*.
