# Rendering LemonSqueezy Orders on the Account Page

In this chapter, we're going to show users their latest orders right on our
website. With our `User` entity and LemonSqueezy customer relationship
established, we're well-placed to make this happen. So let's get started by
displaying these orders on the account page.

## LemonSqueezy API for fetching Orders

First, navigate to the `src/Store/LemonSqueezyApi.php` file.
Here, we'll add a new method, `public function listOrders()`,
which will return an array. This function will fetch the
orders from the LemonSqueezy API. 

To do this, find "List all Orders" in the LemonSqueezy docs. We'll use
`GET` request to the `/orders` endpoint. Inside our new method write:
`$response = $this->client->request();`. Inside `Request::METHOD_GET`, to
the `orders` path.

But wait, we don't want to display all orders, just the ones for our store
and for the current user. So we'll need to add some extra query parameters
to filter this list.

## Adding Filter Query Parameters

Let's add an empty array as the third argument to the `request` method.
Inside this array, add `'query' => []`, inside `'filter' => []`, and finally
filter by `'store_id' => $this->storeId` and `'user_email' => $user->getEmail()`.
We'll also need to add the `User $user` argument to the `listOrders()` method above. 

Perfect! Now, in the controller inside the `account()` method, open
`UserController`. We'll
inject `LemonSqueezyApi $lsApi` and we'll also need the current user, for
this inject `#[CurrentUser] User $user` PHP attribute. Inside
create the `$orders` variable. Set it to `$lsApi->listOrders()` and pass orders to the template below

## Rendering Orders and Tailwind CSS Styling

Now, let's render those orders! Open up the `account.html.twig` template.
Somewhere below the `{{ app.user.email }}`, I will paste some boilerplate code with
some Tailwind CSS styling. You can copy it from the code blocks below the video.
But should we show this table to everyone? No, we should only render the order if the `app.user.lsCustomerId` is set.
If it is, we display the order table. Otherwise, we'll just say "No orders yet". 

Go ahead and refresh the account page in your browser. Voila! Our dummy
orders list table appears. We'll need to replace the dummy data with real
ones soon, but first let's go to the `UserController::account()` action and
dump the temporary orders variable. Refresh again, and there is our data
holding an array of orders.

## Using Dynamic Data in Orders Table

In the attributes, we'll see some fields we need for the order table.
First, user `order.attributes.order_number` for the Order number - I will add `#`
in front of it. For the date, we need this `created_at` date. Since this is a
string, we'll use `|date()` Twig filter to format it in a more readable way. For the
amount, we have several options but I'd use a value already pre-formatted by LemonSqueezy,
in specific this `total_formatted`. For the link, we can use this `urls.receipt` one.


## Paginate the Orders

By default, Lemon Squeezy returns 10 orders. Want fewer? Sure!
We can paginate it by adding `’page' => ['size' => 5]` to the query.`
Now refresh - nothing changed, but that's because I only have 5 orders before.
Let me quickly buy one more product behind the scene and if I update again 
It's still five, but here's the new one in the beginning.
So our pagination works and only last 5 are shown!

Ideally, we should add a real pagination below to allow users to navigate over their orders without leaving our website, but for now let’s just go the extra mile and add a link to the full list of orders in LS.

In the template, add: `<a href="https://app.lemonsqueezy.com/my-orders" target="_blank">More Orders</a>`

But wait, should we always show that link? Nope. Only if the user has more orders than we rendered. Let’s dump the $orders again and inspect the dump, specifically the `meta` key. We get `total` and `perPage`.

So wrap the link with: `{% if orders.meta.page.total > orders.meta.page.perPage %}`

Nice! But… click the link… and wait… the page is empty? Yeah, it does not work in test mode, and I hope LemonSqueezy will fix it soon.
That’s because LemonSqueezy only shows production orders here but we’re in test mode. Oof. Thought there’s a workaround.

## Refer to All Orders in LS (Even in Test Mode)

Let’s pass the latest order to the template: `’latestOrder' => $orders['data'][0] ?? null,`. Now update the link:
`<a href="https://app.lemonsqueezy.com/my-orders/{{ (orders.data|first).attributes.identifier|default('') }}" target="_blank">More Orders</a>`

That identifier trick should make the test orders show up! Refresh... Ah an error, seems I mistype the `|default` filter. I will fix it myself and refresh again - aha, it shows us the last order, but still no order list. Hm, the last time I tried before recording this tutorial it worked, but seems LemonSqueezy changed something.
Maybe the key is that I'm buying products as lemon@example.com while logged as store owner. If your emails matches - you may see the orders list on the left. If not, I hope LEmonSqueezy will fix it.

## Avoid Possible Orders List Leaking

OK, there's a small security hole here: right now we filter orders by the email
users have set on their account. But in theory users can change their email to something they don't own. To
mitigate this if we will use the email set on the LemonSqueezy customer, not on our User entity.
Inside `LemonSqueezyApi`, add a new `public function`
method and call it `retrieveCustomer()`.

And inside: `return $this->request(Request::METHOD_GET, 'customers/' . $customerId);`
Then in `listOrders()`, let's put suer email on a separate var: `$userEmail = $user->getEmail();`
And below, add an if statement:

`if ($user->getLsCustomerId()) {
    $lsCustomer = $this->retrieveCustomer($user->getLsCustomerId());
    $userEmail = $lsCustomer['data']['attributes']['email'];
}`

This way, we only load orders for the email stored in LS.
Could someone still game this? Technically, yes  but it’s much harder.

I would also suggest adding email verification for users to make sure they
really own the email. We've also made `lsСustomerId` a unique field on the
`User` entity. So the webhook request will fail and won't be able to update the
customer in the webhook process if someone with the same customer ID exists in the DB,
making hijacking someone's identity nearly impossible.

And that's all there is to it! You've successfully rendered
orders list on the account page using Symfony and the LemonSqueezy API.
Happy coding!