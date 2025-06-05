# LemonSqueezy Checkout Overlay

Customers can purchase products on our *website*, but to *finalize* the
checkout, we've been redirecting them to LemonSqueezy's site, which is hosted on
a completely different domain. Let's use LemonSqueezy's JavaScript tools to
enhance this workflow!

Instead of redirecting customers to *LemonSqueezy's* checkout page, we can
present that info in a "checkout overlay" - a sleek iFrame that lives right on
our site. So let's get to it and sprinkle our website with some *JavaScript
magic*.

## Add LemonSqueezy JavaScript to the Cart Page

First, we need to add LemonSqueezy's JavaScript tool - `lemon.js` - to our cart
page. Open `templates/order/cart.html.twig` and add a new block. Call it
`javascripts`... and close it with `endblock`. Inside, add a `script` tag, and
set `src` to `https://app.lemonsqueezy.com/js/lemon.js`. Also add the
`defer` attribute.

LemonSqueezy advises *against* self-hosting the `lemon.js` file, since you might
miss out on new features and crucial security patches. Be sure to link it
*directly*, to keep payment-related matters as safe as possible.

We also need to call the `{{ parent() }}` function inside `javascripts` to avoid
completely overriding this block. *Sweet*.

Below, add a unique CSS class to the checkout link: `lemonsqueezy-button`. When
we head over and refresh the cart page, it's subtle, but you'll notice that we're now loading the
LemonSqueezy checkout page under *our* URL. If you inspected the source code,
you'd see that LemonSqueezy is replacing the whole page with its own content.
That's *awesome*, but we can make this *even better*.

### Creating a Special Stimulus Controller

Remove the `lemonsqueezy-button` class we added earlier, and exchange it for
something a bit more flexible. In `assets/controllers/`, create a new
controller called `lemon-squeezy_controller.js`.

Inside, add `import { Controller } from '@hotwired/stimulus'`, and below that,
`export default class extends Controller`. Inside the class, add two methods:
`connect()` and `openOverlay()`.

Now, let's *connect* this controller in `cart.html.twig`. Add a new line to the
checkout link and set `data-controller="lemon-squeezy"`. This connects this link
to our Stimulus controller. Below that, add `data-action="lemon-squeezy#openOverlay"`,
which tells Stimulus to call the `openOverlay()` method when the link is clicked.

We also need to pass the LemonSqueezy Checkout URL, but instead of generating it
every time the cart page loads, let's only generate it when the link is clicked.

### Adding a New Action to the OrderController

We need a new action to on `OrderController`. Go to
`src/Controller/OrderController.php` and, just before the `success()` method,
add another one: `public function createCheckout()`.

This will return a `Response`. Above, add the `#[Route]` attribute
with a path - `/checkout/create`. Name it `app_order_checkout_create` and only
allow `POST` methods.

For dependencies, inject `LemonSqueezyApi $lsApi`, as well as the current
user with `#[CurrentUser] User $user`.

Inside, `return $this->json()` with an array: `['targetUrl' => $lsApi->createCheckoutUrl($user)]`.

Back in our `lemon-squeezy` Stimulus controller, register a new value. Write
`static values = {}`, and inside, `checkoutCreateUrl: String`.

Over in the cart template, add a new data value attribute -
`data-lemon-squeezy-checkout-create-url-value` - and pass
`{{ path('app_order_checkout_create') }}`.

I'll leave the `href` as-is, so, if for some reason a user doesn't have JS
enabled (is that still a thing?), they can still checkout. Back in our
`openOverlay()` method, add `e` as a parameter, then call `e.preventDefault()`
to stop JS-enabled browsers from following the link.

For the rest of this method, grab the
link element with `const linkEl = e.currentTarget`. Below that, we need to
execute an AJAX request to the `checkoutCreateUrl` we passed as a value. For
that, use the `fetch()` function for `this.checkoutCreateUrlValue`.
For the options, add `method: 'POST'`, and `headers: {'Content-Type': 'application/json'}`.

Next, chain this `fetch()` call with `.then()`. Inside, expect a
`response`, and add a sanity check - `if (!response.ok)`,
`throw new Error()` with `Network response was not OK`,
followed by `response.statusText`.

*Otherwise*, `return response.json()`. That should pass the JSON data as an
object to the next `.then()`, where we expect `data`.

We're going to ask LemonSqueezy to open this URL, so call
`window.LemonSqueezy.Url.Open()` and pass `data.targetUrl`, which we returned
from the `createCheckout()` action.

*Finally*, add a `catch()`, expecting an `error`. Inside,
write `console.error()` with a `Fetch error:` message, passing `error` as the
second argument.

Okay, this looks good, so let's test it out. Open our site, and *also* open the
Developer Tools in the Console tab to see the JavaScript logs. Reload the
page, and... here's our `lemon-squeezy` controller!

If we click the "Checkout with LemonSqueezy" button, it *loads* and... it opens
the LemonSqueezy checkout page under our domain! It still works!

Again, this is subtle, so next: let's make this even cooler by rendering the
checkout page *over* our cart page, in a modal.
