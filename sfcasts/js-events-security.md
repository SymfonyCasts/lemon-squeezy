# Syncing Customer ID via JavaScript event and Improving Security

Welcome back! We just synced our LemonSqueezy customer ID with the user in our
database using two different methods — through *webhooks*, which made for a
pretty solid production setup, and via *LemonSqueezy JavaScript events*, which
help us skip the Ngrok and webhook configuration locally. It's perfectly
acceptable to use both methods simultaneously.

*But*, let's take a moment to look at our `handleCheckout()` action. We may have
a potential security issue on our hands here. Malicious users *might* attempt to
send an AJAX request to this endpoint using a different LemonSqueezy customer
ID. This could override their *own* customer ID, *potentially* leading to a
situation where our app generates a signed URL for that customer and hands it
over to the attacker. This would let them view personal information, make
changes on behalf of the customer, and it could even allow them to make
fraudulent purchases.

But don't worry! We have some solutions! We *could* use the customer sync via
the JavaScript events *only* in dev mode. This means it won't work on
*production*, but it *will* work locally, and real users will only be synced via
webhooks with a signed signature on production.

*Alternatively*, we could add extra checks to the `handleCheckout()` action to,
for example, verify if the current user ID corresponds to the user ID set in the
custom data of the LemonSqueezy event. Let's explore *this* option and see how
we can *prevent* people from overriding customer IDs with corrupted data.

## Adding Extra Checks to Prevent Data Overriding

Open `lemons-squeezy_controller.js`. In `LemonSqueezy.Setup()`, you can
uncomment the `console.log(data)` line to debug the response and find the path
structure for the user ID. *Or*, if you'd like to skip that part, you can just
trust me and write `const userId = data.data.order.meta.custom_data.user_id`.

Next, pass this `userId` variable as the *first* argument to the
`#handleCheckout()` method. In `#handleCheckout()`, change the signature to
`userId lsCustomerId` and, down here, pass the `userId` to the
`URLSearchParams()` object, just like we did with `lsCustomerId`.

Back in `OrderController.php`, at the top of `Response`, create a `$userId`
variable and set it equal to `$request->request->get('userId')`, since we're
dealing with a `POST` request. This is *also* pretty similar to what we did for
`lsCustomerId`.

Below that, add an `if` statement: `if ($userId !== $user->getId())`. Since the
`getId()` method returns an integer, *and* because I love strict comparison,
let's typecast this to `string`.

If this condition is met, `throw $this->createAccessDeniedException()`. Inside,
we'll write an `sprintf()` function, stating:

> Current user ID "%s" does not match the user ID "%s" of the order!

We'll pass `$user->getId()` and `$userId` as arguments.

*So*, if there's an ID mismatch, we'll hit this `if` statement and throw an
exception, so we can see it in our logs. Now we can safely set the customer,
since we're certain it's related to the current user.

## Testing Our Setup

Head over to your terminal and run:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user"
```

We have the `lsCustomerId` set, as expected. Now, let's re-run the same command
with a new query:

```terminal
bin/console doctrine:query:sql "UPDATE user SET lsCustomerId=NULL WHERE id=1"
```

It's good practice to always add a `WHERE` clause to your `UPDATE` or `DELETE`
queries, so you can avoid accidentally updating *all* your records if you have
more than one record in the table.

Let's test it out! On the cart page, click the "Checkout" button, fill out the
billing info... and click "Pay". If we wait a moment... we'll see the "Thanks
for your order" message. So we have a brand new order on our account.

Back at the terminal, run the `SELECT` query again:

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user"
```

We can see here that the `lsCustomerId` field is set again. We're not running
Ngrok tunnels right now, so this was set via the JavaScript event. It *works*!

## Always use HTTPS

So there you have it! We saw how LemonSqueezy handles checkouts. The cart
credentials are *never* sent to our server, but they *are* sent directly to
LemonSqueezy's server via the iFrame we added. That means we're not handling or
storing *any* sensitive card credentials on our servers at all. Yay! And
remember to *always* use HTTPS for your checkout. Honestly, it's best to use it
across your *entire website*. Not only is it standard practice, but it also
*significantly* boosts your site's security and protects the users that keep
your business humming.

All right! That's it for this course! You're ready to start generating profit
with individual purchases! We'll learn more about *subscription* payments in the
*next* course, so stay tuned. And, as always, if you have any questions for us,
we're here for you down in the comments. Happy coding!
