# Syncing Customer ID via JavaScript event and Improving Security

Hey folks, so now we've synced our LemonSqueezy customer ID with the user
in our database. We did it both ways — through webhooks for a pretty
solid production setup and via LemonSqueezy JavaScript events which help
us skip the Ngrok and webhook configuration locally. It's perfectly okay
to use both methods simultaneously. 

But, let's take a moment to look at our `handleCheckout()` action. Here, we
might have a potential security issue. Some cunning users might attempt to
send an AJAX request to this endpoint using a different LemonSqueezy
customer ID. This could override their own customer, potentially leading to
a situation where our app generates a signed URL for that customer and
hands it over to the attacker. This would let them view personal
information or even make changes on behalf of the customer.
Or even lead to some possible payment actions by the attacker on behalf
of the actual user.

Don't worry, there are solutions to this problem. You could use the
customer sync via the JavaScript events only in dev mode. This
means it won't work on production, but it will work locally, and real users
will only be synced via webhooks with a signed signature on production.

Alternatively, we can add extra checks to the `handleCheckout()` action.
For instance, we could verify if the current user ID corresponds
to the user ID set in the custom data of the LemonSqueezy event.
Let's explore this option and see how we can prevent the overriding
of customer ID with corrupted data.

## Adding Extra Checks to Prevent Data Overriding

Head over to the `lemons-ssqueezy` Stimulus Controller. In the
`LemonSqueezy.Setup()`, you can uncomment the `console.log(data)` command to
debug the response and find the path structure for the user ID. Or, trust
me and simply write `const userId = data.data.order.meta.custom_data.user_id`.

Next, pass this `userId` variable to the `HandleCheckout` method. I'll pass
it as the first argument. Inside the method, change the signature to
`userId lsCustomerId` and pass the `userId` to the `URLSearchParams()`
object, just like we did with `lsCustomerId`.

Let's now return to the `OrderController`. At the start, let's create a
`$userId` variable that will equal to `$request->request->get('userId')`,
since we're dealing with a POST request. So, mostly the same as we did
for `lsCustomerId` before.

We'll add an if statement, stating if `$userId` is not equal to
`$user->getId()`. Because the `getId()` method returns an integer,
and because I love strict comparison, let's typecast it to string.

If this condition is met, let's throw the
`createAccessDeniedException()`. Inside, we'll write a `sprintf()`
function, stating:

> Current user ID "%s" does not match the user ID "%s" of the order!
 
We'll pass `$user->getId()` and `$userId` as arguments.

So, if there's an ID mismatch, we'll hit this if statement and throw an
exception, so we can see it in our logs. Now we can safely set the
customer, as we're sure it relates to the current user.

## Testing Our Setup

After that, let's head to the terminal. Write:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user"
```

We have the `lsCustomerId` set, as expected.

Now, re-run the same command with another query:

```terminal
bin/console doctrine:query:sql "UPDATE user SET lsCustomerId=NULL WHERE id=1"
```

Remember, always add a WHERE clause to your UPDATE or DELETE queries,
that's a good practice to avoid accidentally updating all records if you
have more than 1 record in the table.

Let's test it out. I'll open the cart, place an order and check out with
some data. If we press pay and wait a bit, we'll see the "Thanks for your
order" message. In my account, I have this new order. 

Back to the console, running the SELECT query again:

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user"
```

Shows that the `lsCustomerId` field is set again. As I'm not running the
Ngrok tunnels right now, I'm  sure it was set via the JavaScript event.
It works!

## Always use HTTPS

So, there you have it. I want to highlight how LemonSqueezy handles
checkouts. The cart credentials are never sent to our server,
but are sent directly to LemonSqueezy server via the iFrame we added.
So we don't save any sensitive cart credentials on our servers at all.
Yay! But remember, always use HTTPS for your checkout. Actually,
you should use HTTPS on your *entire website*. It's already standard
practice and boosts your site's security significantly for your
lovely uses.

Alright, that's it for this course. You're now ready to make some profit
with individual purchases. Stay tuned for more on subscription payments
in the next course!
