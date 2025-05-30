# Listening to LemonSqueezy Javascript Events

We have to configure webhooks locally every time we want to save
corresponding LemonSqueezy customer ID each time a user makes an order.
Ngrok helps us with it, but it comes with a bit of manual work.
For instance, you need to remember to run Ngrok in the background
before expecting webhooks and update the webhook URL every time you
restart Ngrok agent unless you have a paid Ngrok plan.

In this video let's explore how we can alternatively listen to
some LemonSqueezy JavaScript events. Here's a great alternative: we can
set the customer ID upon a successful checkout this way too. LemonSqueezy
provides a special event for this. Open the docs, go to Guides, find
"Using Lemon.js" link in the left sidebar and in the right click on
"Handling events".

When the checkout process is successful, it fires a `Checkout.Success`
event. They even provide some sample code on how to handle it.
The event returns some useful data, including the customer ID we're
looking for.

## Listening to the LemonSqueezy `Checkout.Success` Event

Let's get our hands dirty. Open the `assets/controllers/lemon-squeezy_controller.js`.
Look for `connect()` and at the bottom start with `window.LemonSqueezy.Setup()`.
Pass `{ eventHandler: (data) => {} }` inside it. Here, write
`if (data.event === 'Checkout.Success')`, then we need to get the 
customer ID as `data.data.customer_id` and put it on a `lsCustomerId`
variable. Pass this ID to a method that I will create in a second
`this.#handleCheckout(lsCustomerId)`.

Now, create the `#handleCheckout()` function with `lsCustomerId` and
leave it empty for now. The next step is to create an endpoint
in our app that will handle and save this customer ID for the user.

## Adding a new Endpoint for Creating Checkout URL 

To do this, open `src/Controller/OrderController.php` and create a new
method, `public function handleCheckout()`. Register this route with `/checkout/handle` path, and name
it `app_order_checkout_handle`. We want this method, this action,
to work only for `POST` requests.

We'll need a request and the current user inside, so inject a request, and
the current user PHP attribute. Assume that the ID will be
passed via POST request as `lsCustomerId`, get it from the request as
`$request->request->get('lsCustomerId')`, and below set it on the User.
To actually save it to the database, we'll also need to inject
`EntityManagerInterface $entityManager`, and then at the end call
`$entityManager->flush()`.

Finish with `return $this->json([])`. We don't need to return any actual
data, just a successful response will be enough.

## Updating the Stimulus Controller

For the Stimulus controller, let's add a new value called
`checkoutHandleUrl: String`,, and pass the URL from the template.

For this, in `templates/order/cart.html.twig`, add
`data-lemon-squeezy-checkout-handle-url-value=""` and pass the URL as
`{{ path('app_order_checkout_handle') }}`.

Let's head back to the Stimulus controller. With the value set, let's
start making an AJAX call in the `#handleCheckout()` using the `fetch()`
method. It should be to `this.checkoutHandleUrlValue`. For options, use
`method: 'POST'` as we configured in that endpoint, and headers:
`'Content-Type': 'application/x-www-form-urlencoded'`. This will allow
us to get passed values with simple `$request->request->get()`, no
need to `json_decode()` the request.

For the `body`, pass a `new URLSearchParams()` and pass to it
`{ lsCustomerId: lsCustomerId, }`.

Now chain this `fetch()` call with `.then()`. Inside we expect `response => {}`.
If response is not OK - then throw a new `Error` saying "Network response was not OK "
and concatenate `response.statusText`.

Below, `return response.json()` that will give us the decoded json object
in the next `.then()`. Take it as `data => {}`. Inside I will just leave 
a comment that we don't need to do anything here, because we don't return
any data from that endpoint.

But let's chain a `.catch()` just in case where do
`console.log('Fetch error:', error)`.

## Testing and Fixing Errors

Looks good! Go to the website, add product to the cart, I will also
open the Console tab in the Chrome Dev Tools - oh, an error!

> Uncaught TypeError: Cannot read properties of undefined (reading 'Setup')

Looks like we start using LemonSqueezy faster than its script is downloaded.
Let's do a little trick and wrap with code with `script.addEventListener()`.
We want to listen `load`, pass a function as the 2nd argument and 
insert our code there.

Now, refresh the page! Ah, still the same error.

Ok, looks like we should instantiate the LemonSqueezy manually first.
Before the problem line, write `window.createLemonSqueezy()`. I will
also add a little comment above.

Refresh again - no errors! Perfect, I will also add a `console.log(data)`
in order to know that we hit that if on the `Checkout.Success`.

One more refresh to load the changes, and click on the "Checkout with LemonSqueezy".
If I quickly fill in payment credentials and billing address and click Pay,
we see the success message and in the console we see the data, so our code
was hit. Did it work?

I will go to the console check the DB with:

```terminal
bin/console doctrine:query:sql "SELECT * FROM user"
```

It didn't! It says that lsCustomerId value is "undefined" - hmmm,
sounds like we were using the bad path for the customer ID. Let's
double-check our dump. Yes, indeed, the path docs gave us is incorrect.

Fix the path to: `data.data.order.data.attributes.customer_id`.
OK, last page refresh, go thought the whole checkout process again.
I will do it as fast as I can. Success! Now return to the terminal,
rerun the query:

```terminal-silent
bin/console doctrine:query:sql "SELECT * FROM user"
```

Yes, the customer ID was set correctly. And we don't need that `console.log()`
anymore, I will delete it. And also one more we missed in the `#openOverlay`.

So even if we don't have Ngrok running, we still were able to sync
the LemonSqueezy customer ID with User via JavaScript events.
This way simplifies local development a bit, so both ways are good
and valid.

In the next chapter, we'll tackle some potential security issues by
preventing customer ID hijacking.
