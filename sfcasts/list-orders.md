# Rendering LemonSqueezy Orders on the Account Page

We've ordered a *ton* of digital lemonade lately, but we don't really have a
convenient way to view those orders. Wouldn't it be cool if we could see a list
of them on our account page? Now that we've established a
relationship between the `User` entity and LemonSqueezy customer, we *can*!

## LemonSqueezy API for fetching Orders

Start by opening `src/Store/LemonSqueezyApi.php`. Add a new method -
`public function listOrders()` - and return an `array`. This function will fetch
the orders from LemonSqueezy's API. If we head over to the LemonSqueezy docs,
under "List all orders", we can see that we need to use a `GET` request to the
`/orders` endpoint.

Back in our new method, add `$response = $this->client->request()`, and inside,
`Request::METHOD_GET` to the `orders` path.

But wait... we don't want to display *all* orders - just the ones for our store
and the current user - so we need to add some extra query parameters to filter
this list.

## Adding Filter Query Parameters

Let's add an empty array as the third argument to the `request()` method, and
inside, write `'query' => []`, `'filter' => []`, `'store_id' => $this->storeId`,
and `'user_email' => $user->getEmail()`. We also need to add `User $user` to the
`listOrders()` method above. Perfect!

Next, open `UserController.php`. Down here, in `account()`, inject
`LemonSqueezyApi $lsApi`. We also need the current user, so add
`#[CurrentUser] User $user`. Below, create the `$orders` variable and set it to
`$lsApi->listOrders()`. Finally, in the `return`, pass `'orders' => $orders`.

## Rendering Orders and Tailwind CSS Styling

Now we need to *render* those orders! Open the `account.html.twig` template...
and somewhere below `{{ app.user.email }}`, paste this boilerplate code with
some Tailwind CSS styling. You can copy this from the code blocks below the
video.

Since we don't want *everyone* to see our orders, we need to render the list
*only* if the `app.user.lsCustomerId` is set. If it *is*, an order table is
displayed. If *not*, we'll just display a "No orders yet" message.

Let's see what we've got so far! Back on our site, refresh the page and...
*voila*! Our orders list full of dummy data is visible! We'll need to replace
this dummy data with real orders soon, but first, go to
`UserController::account()` and `dd()` the `$orders` variable. Refresh again,
and... there's our "data" with an array of orders.

## Using Dynamic Data in Orders Table

If we click on "attributes", we see a ton of fields we can use for our order
table. The first one I'll grab is `order_number`. In our code, replace `Order`
with `#{{ order.attributes.order_number }}`. For `Date`, replace it with
`{{ order.attributes.created_at|date('d M Y, H:i') }}`. We're using date filter
here so the date will be easier to read. We have several options to choose
from for the `Amount` field. I'll use `total_formatted` because it's
pre-formatted by LemonSqueezy. Finally, for our link, we'll write
`{{ order.attributes.urls.receipt }}`. Before we test this out, head back to
`UserController.php` and remove the `dd()` we added earlier.

## Paginate the Orders

Lemon Squeezy returns *10* orders by default. If you want to see fewer than ten
at a time, we can paginate the list by adding `'page' => ['size' => 5]` to the
query. If we head back and refresh... we now have only the 5 latest orders
displayed! It's working!

*Ideally*, we should add *real* pagination below so users can navigate through
all of their orders without leaving our site, but for now, let’s just add a link
to the full order list on LemonSqueezy.

In the template, add a link, with the href:
`https://app.lemonsqueezy.com/my-orders/{{ (orders.data|first).attributes.identifier|default('') }}`,
target: `_blank`, and text: `More Orders`.

That link will take the user to LemonSqueezy and display all their orders - with
the first order pre-selected.

But we don't need to see this link *all the time* - only if the user has more
than five orders. Let's `dd($orders)` again, refresh, and inspect the data.
In `meta`, `page`, we see `total` and `perPage`, so head back, remove the `dd()`,
and wrap the link with
`{% if orders.meta.page.total > orders.meta.page.perPage %}`. I'll fix this
spacing and add `{% endif %}` at the end.

Okay, if we refresh again and click the link... we see the details
for the latest order, but... where are the others? This seems to currently be
a LemonSqueezy limitation when in test mode. In production, this would also
list all the customer's orders.

## Preventing Leaks

Okay, now let's turn our attention to a small security issue here. At the
moment, we're filtering orders by the email users have registered with our site.
But, *in theory*, users could change their email to something they
*don't* own. To mitigate this, we need to use the email set on the LemonSqueezy
customer, *not* on our `User` entity. Inside `LemonSqueezyApi.php`, add a new
`public function` and call it `retrieveCustomer()` with `string $customerId`,
return `array`. Inside, write
`$response = $this->client->request(Request::METHOD_GET, 'customers/' . $customerId)`.
Below, `return $response->toArray()`.

Above, in `listOrders()`, add `$lsCustomerId = $user->getLsCustomerId()`.
Then, `if (!$lsCustomerId)`, `return []`. This ensures there's no way a user
can list orders if they don't have a LemonSqueezy customer ID.

Next, write `$lsCustomer = $this->retrieveCustomer($lsCustomerId)`. Finally,
change the `user_email` filter to `$lsCustomer['data']['attributes']['email']`.

Security hardened!

And that's all there is to it! You've successfully rendered a list of orders on
the account page using the LemonSqueezy API.

Next: Let's make some improvements to our API error handling, because it's
getting annoying to manually debug errors.
