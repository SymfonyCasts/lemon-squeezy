# Listening to LemonSqueezy Javascript Events

Right now, every time we want to save a LemonSqueezy customer ID on the
corresponding user entity *locally*, we have to configure our webhooks. Ngrok
definitely helps, but it's still a bit of a pain. We need to run Ngrok in
the background before we start receiving webhooks. *And* we still need to
*update* the webhook URL every time we restart the Ngrok agent if we don't have
a paid Ngrok plan. That's... not ideal.

Let's explore an *alternative* way - listening to LemonSqueezy *JavaScript* events
and set the customer ID on a successful checkout. LemonSqueezy has a
special event for this! Open the docs, go to "Guides", find "Using Lemon.js" on
the left, and on the right, click on "Handling events".

Here, we can see that when the checkout's successful, LemonSqueezy fires a
`Checkout.Success` event. They even give us some sample code for how to handle
it. This returns a bunch of useful data, including the customer ID we're looking
for.

## Listening to the LemonSqueezy `Checkout.Success` Event

Time to get to work! Open `assets/controllers/lemon-squeezy_controller.js`. Look
for the `connect()` method and, at the bottom, start with
`window.LemonSqueezy.Setup()`. Inside, pass `eventHandler: (data) => {}`, and
inside *that*, write `if (data.event === 'Checkout.Success')`.
Get the customer ID with `data.data.customer_id` and put it on a `lsCustomerId`
variable.

[[[ code('1a7d3ed79f') ]]]

We'll pass the ID to `this.#handleCheckout()`. This doesn't exist yet,
so create it below, with `lsCustomerId` as a parameter.

[[[ code('93940b6eea') ]]]

## Adding a new Endpoint for Creating Checkout URL

Next, we need to create an endpoint in our app that will handle and save the
customer ID for the user. To do that, open `src/Controller/OrderController.php`
and create a new method: `public function handleCheckout()`. Register this
`#[Route]` with a path - `/checkout/handle` and name it
`app_order_checkout_handle`. We want this method to *only* work for `POST`
requests.

This needs a request and the current user, so inject `Request $request` and
`#[CurrentUser] User $user`.

[[[ code('3bf285b5e7') ]]]

We'll assume that the ID will be passed via a POST request as `lsCustomerId`,
so retrieve it from the request with `$request->request->get('lsCustomerId')`.
Below, set it on the user with `$user->setLsCustomerId($lsCustomerId)`.

[[[ code('97cce85263') ]]] 

To actually *save* it to the database, we also need to inject
`EntityManagerInterface $entityManager` and, at the end, call
`$entityManager->flush()`. Finish with `return $this->json([])`. We don't need
to return actual data here - a successful response is enough.

[[[ code('8326a4e668') ]]]

## Updating the Stimulus Controller

In the Stimulus controller, add a new value called
`checkoutHandleUrl: String` and pass the URL from the template. To do that, in
`templates/order/cart.html.twig`, add
`data-lemon-squeezy-checkout-handle-url-value=""` and pass the URL with
`{{ path('app_order_checkout_handle') }}`.

[[[ code('e79883042c') ]]]

[[[ code('0935de6ea7') ]]]

With the value set, back in the controller,
in `#handleCheckout()`, make an AJAX call with `fetch()`, passing
`this.checkoutHandleUrlValue`. For options, use `method: 'POST'`, and for headers,
`'Content-Type': 'application/x-www-form-urlencoded'`. This allows us to fetch
values with `$request->request->get()` - no need to `json_decode()` the request.
For the `body`, pass `new URLSearchParams()` with
`lsCustomerId: lsCustomerId`.

[[[ code('ce8f98e1d3') ]]]

Chain this `fetch()` call with `.then()`. Inside, expect `response => {}`. If the response is *not* okay,
throw a new `Error()` with the message:

`"Network response was not ok" + response.statusText`.

Below, `return response.json()`. This will give us the decoded JSON object in
the next `.then()`. Accept `data => {}`, and inside, just leave a comment
reminding us that there's nothing to do here, because we don't return any data
from that endpoint. *But*, just in case something goes wrong, chain
`.catch()` with `console.error('Fetch error:', error)`.

[[[ code('a7cbe474bb') ]]]

## Testing and Fixing Errors

This looks good, so let's give it a try! Over on our site, add a product to the
cart, and open the "Console" tab in the Dev Tools. *Whoops*... an error.

> Uncaught TypeError: Cannot read properties of undefined (reading 'Setup')

Looks like we've started using LemonSqueezy faster than its script was
downloaded. Let's do a little trick and wrap this code in
`script.addEventListener()`. Listen for the `load` event, pass a function
as the second argument, and insert our code there.

[[[ code('bdfcae72b8') ]]]

If we refresh the page again... *dang*... we get the *same* error.

Okay, it looks like we should try to instantiate LemonSqueezy *manually* first.
Before the problem line, write `window.createLemonSqueezy()`. Add a
little comment above to remind *future us* what we're doing here.

[[[ code('d3708a189e') ]]]

Refresh again, and... *no errors*! Perfect! Let's quickly add
`console.log(data)` to our code so we'll know if we hit that `if` on
`Checkout.Success`.

[[[ code('2c08a75e1d') ]]]

Refresh our site one more time to load the changes... and click "Checkout with LemonSqueezy".
Fill in payment info, billing address... click "Pay", and... we see the success message!
And in the console... we can see the data, so our code was hit. So... did this work?

At your terminal, check the database with:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user"
```

It *didn't*! It says that the `lsCustomerId` value is "undefined". Hmmm, sounds
like we're using a bad path for the customer ID. If we double-check our
dump... *yep*. The path the docs gave us is incorrect.

Change the path to `data.data.order.data.attributes.customer_id`, and try
this *one more time*.

[[[ code('cff195be84') ]]]

Refresh the page, go through the checkout process again (I'll speed through this to save time), and... *success*!
Now, back in our terminal, rerun the query:

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user"
```

And... *Yes*! The customer ID was set correctly! We don't need that `console.log()`
anymore, so we can delete it, along with another one that we missed in
`#openOverlay`.

*So*, even if we don't have Ngrok running, we're *still* able to sync the
LemonSqueezy customer ID with the user via JavaScript events. This approach
simplifies local development a bit, but both ways are totally valid.

Next: Let's tackle some potential security issues by preventing customer ID
*hijacking*.
