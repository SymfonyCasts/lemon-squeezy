# Implementing Webhook Consumer 

In our last chapter, we successfully set up the webhook request parser.
This parser is designed to receive a webhook from LemonSqueezy, verify
its signature, parse the payload, and pass the parsed data to the webhook
consumer. Now, we're ready to move on to the next part – handling the
webhook data in the consumer.

Start by opening the `LemonSqueezyWebhookConsumer.php` file from the
`src/RemoveEvent/` directory. We'll work with the `consume()` method. I'll
get rid of this to-do. Our task here is to find the corresponding user
and set the `customer_id` from the webhook data on it.

We're in a different session here, so we can't access the current user
directly from the inject `Security` service.. Remember, we're handling
a webhook in entirely separate request that doesn't have access to the
user session. Yet, we  still need to find the corresponding user.

Luckily, LemonSqueezy "Create a Checkout" API documentation reveals that
we can add custom data when creating the checkout URL - perfect for passing our
user ID. To do this, firstly, let's head over to the `LemonSqueezyApi` in
`src/Store/` directory, and inside find the `CreateCheckoutURL()` method.

Here, we'll require users to be logged in to check out. This is
crucial because we need to link the corresponding LemonSqueezy customer
to it. So, let's make this a requirement in the method's signature.

With this requirement set, we no longer need the `if (user)` statement below,
so  I'll remove it and tidy up these lines. Then, let's add
`$attributes['checkout_data]['custom']['user_id'] = $user->getId();`. This `custom`
field allows us to pass to LemonSqueezy any custom data we might need later.

Our goal here is to let LemonSqueezy know about the user ID who made
this order. Going back to the `OrderController`, you'll notice PhpStorm
isn't too happy with this `createCheckoutUrl()` call. Since we made the
user a requirement in it, we also need to make it a requirement in that
method's signature.

That should do it! No need to call `denyAccessUnlessGranted()` in
it.

To confirm everything is working as expected, let's open the website. I'll
log out, add a product to the cart, and try to check out. As a
non-authenticated user, I should be redirected to the login page. Perfect! 

## Handling the Webhook Consumer

Now, let's return to the consumer and set `$payload = $event->getPayload();`.
Below `$userId = $payload['meta']['custom_data']['user_id'] ?? null;` . I'll
leave a comment here mentioning that `getUser()` won't work in webhooks as
a non-authenticated user in this process.

Next, let's conduct a sanity check with `if (!$userId)`. If this check
fails, we'll `throw new InvalidArgumentException()` with a `sprintf()`
inside saying `'User ID not found in LemonSqueezy webhook: %s', $userId`.

To get acces to the EntityManger, go to the `public function __construct()`
and inject `private EntityManagerInterface` as `$entityManager` there. 

Back in the `consume()`, let's continue with `$user =
$this->entityManager->getRepository(User::class)->find($userId)`. 

Next, if `$user` doesn't exist, we'll `throw new EntityNotFoundException()`
choose one from Doctrine ORM. We'll add a `sprintf()` as an argument
stating `User "%s" not found for LemonSqueezy webhook "%s"!`, and pass
`$userId` and `$event->getId()`. 

Next, we'll use `match ($event->getName())`, and for `order_created`
call `$this->handleOrderCreatedEvent()` method which we'll
create later. Let's pass `$event` and `$user` to it as arguments. Default -
let's `throw new LogicException()` stating
`sprintf('Unsupported LemonSqueezy event: %s', $event->getName())`. 

## Creating the HandleOrder Event

Perfect! Now let's create the `handleOrderCreatedEvent()` as a `private
function`. PhpStorm seems to have missed the second argument, so we'll add
it manually – `User $user`.

Inside, let's fetch the payload: `$payload = $event->getPayload()`. On the next
line, fetch the customer ID from the payload:
`$customerId = $payload['data']['attributes']['customer_id'];` - you can see
this path from the Ngrok request body.

We now have the `customer_id`, but we'll need a new property on the user to
save it. Open up your terminal, create a new tab and run:

```terminal
bin/console make:entity
```

When prompted for the class name, input `User`. For the
property name, name the field `lsCustomerId`. Set it to string 255 and make
it nullable.

Now, open up the `User.php` file. Here's our new column. Let's also set it to
`unique: true`. We're in good shape here, and it's also created a setter
and getter for this field. Now, let's create a migration with:

```terminal
bin/console make:migration
```

Everything looks good. I'll just add a description writing "Add customer ID
property to User entity" and in the console migrate with:

```terminal
bin/console doctrine:migration:migrate
``` 

Now, let's return to the `handleOrderCreatedEvent()`. Set it on our new
column with `$user->setLsCustomerId()`. Be sure to pass the `$customerId`
variable as an argument. To save it, we'll call `$this->entityManager->flush()`.

## Testing the Webhook

Now, it's time to test the webhook again. I'm a fan of Ngrok inspector,
so I'll use that. Hmm, an error. The webhook doesn't have a `user_id`
set on custom data for that specific case. 

With Ngrok, we can modify the original webhook content and replay it with
modifications. But to be extra confident, I'll go through the checkout
again to let LemonSqueezy set the `user_id` correctly. I'll need to login,
add a product to the cart, and proceed to checkout. Oops,
another error. 

If we debug the response content to check the actual error in `LemonSqueezyApi`,
we'll see that the field must be a string and it points to our custom
`user_id` we added. Note that you can't pass just *anything*, so passing the
whole user object definitely won't work. 

Alright, let's explicitly typecast user ID to a string and try again.
Success! We're on the checkout page. I'll quickly fill in some data, make
the payment, and wait for the webhook. And yes, this one was accepted now
with the 202 status code.

Looking at the request data, we see our custom data with `user_id=1`.
Checking the database, we can quickly type an SQL command in my console
`bin/console doctrine:query:sql` and in double quotes input
`SELECT * FROM user WHERE id = 1` as we have the ID = 1 for the current user:

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user WHERE id = 1"
```

Hit enter and yes, `lsCustomerId` is set to this ID. We can
now leverage this in our application, for instance, to show the link to
order lists made by this customer. But before we do that, let's see how we
can test our webhook. That's coming up next!
