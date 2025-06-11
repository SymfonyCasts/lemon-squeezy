# Listening to LemonSqueezy Javascript Events

Right now, every time we want to save a LemonSqueezy customer ID on the corresponding user entity *locally*, we have to configure our webhooks. Ngrok definitely helps, but it's still a bit of a pain. We still need to run Ngrok in the background before we start receiving webhooks, *and* we still need to *update* the webhook URL every time we restart the Ngrok agent if we don't have a paid Ngrok plan. That's... not ideal.

Let's explore an *alternative* way to listen to LemonSqueezy JavaScrip events - setting the customer ID on a successful checkout. LemonSqueezy even has a special event for this! Open the docs, go to "Guides", find "Using Lemon.js" on the left, and on the right, click "Handling events".

Here, we can see that when the checkout's successful, LemonSqueezy fires a `Checkout.Success` event. They even give us some sample code for how to handle it. This returns a bunch of useful data, including the customer ID we're looking for.

## Listening to the LemonSqueezy `Checkout.Success` Event

Time to get to work! Open `assets/controllers/lemon-squeezy_controller.js`. Look for the `connect()` method and, at the bottom, start with `window.LemonSqueezy.Setup()`. Inside, pass `eventHandler: (data) => {}`, and inside *that*, write `if (data.event === 'Checkout.Success')`. Now we need to get the customer ID with `data.data.customer_id` and put it on a `lsCustomerId` variable. We'll pass the ID to `this.#handleCheckout(lsCustomerId)`. This doesn't exist yet, but we'll create it in a moment. Finally, create the `#handleCheckout()` function with `lsCustomerId` and leave it empty for now. 

## Adding a new Endpoint for Creating Checkout URL

Next, we need to create an endpoint in our app that will handle and save the customer ID for the user. To do that, open `src/Controller/OrderController.php` and create a new method: `public function handleCheckout()`. Register this `#[Route]` with a path - `/checkout/handle` - and call it `app_order_checkout_handle`. We want this method to *only* work for `POST` requests.

This needs a request and the current user, so inject `Request $request` and the `#[CurrentUser]` PHP attribute with `User $user`. We'll assume that the ID will be passed via a POST request as `lsCustomerId`, so we'll retrieve it from the request with `$request->request->get('lsCustomerId')`.

Below, set it on the user with `$user->setLsCustomerId($lsCustomerId)`. To actually *save* it to the database, we also need to inject `EntityManagerInterface $entityManager` and, at the end, call `$entityManager->flush()`. Finish with `return $this->json([])`. We don't need to return actual data here - a successful response is enough.

## Updating the Stimulus Controller

For the Stimulus controller, let's add a new value called `checkoutHandleUrl: String` and pass the URL from the template. To do that, in `templates/order/cart.html.twig`, add `data-lemon-squeezy-checkout-handle-url-value=""` and pass the URL with `{{ path('app_order_checkout_handle') }}`.

With the value set, back in the controller, let's make an AJAX call in `#handleCheckout()` using the `fetch()` method. Set it to `this.checkoutHandleUrlValue`. For options, use `method: 'POST'`, like we configured in our endpoint, and for headers, `'Content-Type': 'application/x-www-form-urlencoded'`. This allows us to fetch values with `$request->request->get()` - no need to `json_decode()` the request.

For the `body`, pass `new URLSearchParams()` and pass *that* to `lsCustomerId: lsCustomerId`. We'll also chain this `fetch()` call with `.then()`. Inside, we expect `response => {}`. If response is *not* okay, then throw a new `Error()` with a message:

`"Network response was not ok" + response.statusText`.

Below, `return response.json()`. That will give us the decoded JSON object in the next `.then()`. Say `data => {}`, and inside, I'll just leave a comment reminding us that there's nothing to do here, because we don't return any data from that endpoint. *But*, just in case something goes wrong, we'll chain `.catch()` with `console.log('Fetch error:', error)`.

## Testing and Fixing Errors

This looks good, so let's give it a try! Over on our site, add product to the cart, and open the "Console" tab in the Chrome Dev Tools. *Whoops*... an error.

> Uncaught TypeError: Cannot read properties of undefined (reading 'Setup')

Looks like we've started using LemonSqueezy faster than its script can be downloaded. Let's do a little trick and wrap this code with `script.addEventListener()`. We want to listen for the `load`, pass a function as the second argument, and insert our code there.

If we refresh the page again... *dang*... we get the *same* error.

Okay, it looks like we should try to instantiate LemonSqueezy *manually* first. Before the problem line, write `window.createLemonSqueezy()`. I'll also add a little comment above to remind *future us* what we're doing here.

Refresh again, and... *no errors*! Perfect! Let's quickly add `console.log(data)` to our code so we'll know if we hit that `if` on `Checkout.Success`. Refresh our site one more time to load the changes... and click "Checkout with LemonSqueezy". Fill in payment info and billing address... click "Pay", and... we see the success message! And in the console... we can see the data, so our code was hit. So... did this work?

At your terminal, check the database with:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user"
```

It *didn't*! It says that the `lsCustomerId` value is "undefined". Hmmm, sounds like we're using the bad path for the customer ID. If we double-check our dump... *yep*. The path docs gave us is incorrect.

Change the path to `data.data.order.data.attributes.customer_id`, and let's try this *one more time*. Refresh the page, go through the checkout process again (I'll speed through this to save time), and... *success*! Now, back in our terminal, rerun the query:

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user"
```

*Yes*! The customer ID was set correctly! We don't need that `console.log()` anymore, so we can delete it, along with another one that we missed in `#openOverlay`.

*So* even if we don't have Ngrok running, we're *still* able to sync the LemonSqueezy customer ID with the user via JavaScript events. This approach simplifies local development a bit, but both ways are totally valid.

Next: Let's tackle some potential security issues by preventing customer ID *hijacking*.
