# Rendering LemonSqueezy Orders on the Account Page

We ordered a *ton* of digital lemonades lately, but we don't really have a convenient way to view those orders. Wouldn't it be cool if we could see a list of them on the account page of our website? Now that we've estabished a relationship between the `User` entity and LemonSqueezy customer, we *can*!

## LemonSqueezy API for fetching Orders

Start by opening `src/Store/LemonSqueezyApi.php`. Add a new method - `public function listOrders()` - and return an array. This function will fetch the orders from LemonSqueezy's API. If we head over to LemonSqueezy's docs, under "List all orders", we can see that we need to use a `GET` request to the `/orders` endpoint.

Back in our new method, add `$response = $this->client->request()`, and inside, `Request::METHOD_GET` to the `orders` path.

But wait... we don't want to display *all* orders - just the ones for our store and the current user - so we need to add some extra query parameters to filter this list.

## Adding Filter Query Parameters

Let's add an empty array as the third argument to the `request()` method, and inside, say `'query' => []`, `'filter' => []`, `'store_id' => $this->storeId`, and `'user_email' => $user->getEmail()`. We also need to add `User $user` to the `listOrders()` method above. Perfect!

Next, open `UserController.php`. Down here, in `account()`, inject `LemonSqueezyApi $lsApi`. We also need the current user, so add `#[CurrentUser] User $user`. Below, create the `$orders` variable and set it to `$lsApi->listOrders()`. Finally, in the `return`, pass `'orders' => $orders`.

## Rendering Orders and Tailwind CSS Styling

Now we need to *render* those orders! Open the `account.html.twig` template... and somewhere below `{{ app.user.email }}`, paste some boilerplate code with some Tailwind CSS styling. You can copy this from the code blocks below the video.

Since we don't want *everyone* to see our orders, we need to render the list *only* if the `app.user.lsCustomerId` is set. If it *is*, the order table is displayed. If *not*, we'll just display a "No orders yet" message.

Let's see what we've got so far! Back on our site, refresh the page and... *voila*! Our orders list full of dummy data is visible! We'll need to replace this dummy data with real orders soon, but first, go to `UserController::account()` and `dd()` the `$orders` variable. Refresh again, and... there's our "data" with an array of orders.

## Using Dynamic Data in Orders Table

If we click on "attributes", we see a ton of fields we can use for our order table. The first one I'll grab is `order_number`. In our code, replace `Order` with `#{{ order.attributes.order_number }}`. For `Date`, replace it with `{{ order.attributes.created_at|date('d M Y, H:i') }}`. We're using `|date()` here so the date field will be easier to read. We have several options to choose from for the `Amount` field. I'll use `total_formatted` because it's pre-formatted by LemonSqueezy. Finally, for our link, we'll say `{{ order.attributes.urls.receipt }}`. Before we test this out, head back to `UserController.php` and remove the `dd()` we added earlier.

## Paginate the Orders

Lemon Squeezy returns *10* orders by default. If you want to see fewer than ten at a time, we can paginate the list by adding `'page' => ['size' => 5]` to the query. If we head back and refresh... hm... nothing changed. Ah! That's because I only had *five* orders before. I'll buy another lemonade behind the scenes... refresh the page again, and... we *still* only see five, but that's because the sixth order - the oldest - is being paginated. It's working!

*Ideally*, we should add *real* pagination below so users can navigate through all of their orders without leaving our site, but for now, let’s just add a link to the full list of orders in LemonSqueezy.

In the template, add:

`<a href="https://app.lemonsqueezy.com/my-orders" target="_blank">More Orders</a>`.

But we don't need to see this link *all the time* - only if the user has more than five orders. Let's `dd($orders)` again, go refresh, and inspect the data. In `meta` `page`, we see `total` and `perPage`, so head back, remove the `dd()`, and wrap the link with `{% if orders.meta.page.total > orders.meta.page.perPage %}`. I'll fix this spacing and add `{% endif %}` at the end.

Okay, if we refresh again and click the link... wait... the page is *empty*? Yeah... *about that* - this doesn't work in test mode because LemonSqueezy only shows *production* orders here. I'm hopeful LemonSqueezy will fix that soon, but for now, we can only see this in action on production.

## Preventing Leaks

Okay, now let's turn our attention to a small security issue here. At the moment, we're filtering orders by the email users have registered on their account. But, *in theory*, users can change their email to something they *don't* own. To mitigate this, we need to use the email set on the LemonSqueezy customer, *not* on our `User` entity. Inside `LemonSqueezyApi.php`, add a new `public function` and call it `retrieveCustomer()` with `string $customerId`. Inside *that*, add `$response = $this->client->request(Request::METHOD_GET, 'customers/' . $customerId)`. Below, `return $response->toArray()`.

Above, in `listOrders()`, let's put the user email on a separate variable with `$userEmail = $user->getEmail()` and change this to `$userEmail`. Finally, down here, add an `if` statement:

`if ($user->getLsCustomerId()) {
    $lsCustomer = $this->retrieveCustomer($user->getLsCustomerId());
    $userEmail = $lsCustomer['data']['attributes']['email'];
}`

This ensures that we only load orders for the email stored in LemonSqueezy. *Technically* someone could *still* interfere with this, but it’s much harder to do now.

I also suggest adding email verification for users, so we can be absolutely sure they own the email they're using. And hey, you can leverage [SymfonyCasts/verify-email-bundle](https://github.com/SymfonyCasts/verify-email-bundle) for this!

We've also made `lsСustomerId` a unique field on the `User` entity, so the webhook request will fail and won't be able to update the customer in the webhook process if someone with the *same* customer ID exists in the database, making hijacking someone's identity nearly impossible.

And that's all there is to it! You've successfully rendered a list of orders on the account page using the LemonSqueezy API.

Next: Let's make some improvements to our API error handling, because it's getting annoying to manually debug errors.
