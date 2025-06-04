# LemonSqueezy Checkout Overlay

Customers can purchase products on our *website*, but to *finalize* the checkout, we've been redirecting them to LemonSqueezy's site, which is hosted on a completely different domain. Let's use LemonSqueezy's JavaScript tools to enhance this workflow!

Instead of redirecting customers to *LemonSqueezy's* checkout page, we can present that info in a "checkout overlay" - a sleek iFrame that lives right on our site. So let's get to it and sprinkle our website with some *JavaScript magic*.

## Add LemonSqueezy JavaScript to the Cart Page

First, we need to add LemonSqueezy's JavaScript tool - `lemon.js` - to our cart page. Open `templates/order/cart.html.twig` and add a new block. Call it `javascripts`... and close it with `endblock`. Inside, add a `script` tag, and set `src` to `https://app.lemonsqueezy.com/js/lemon.js`. We'll also add the `defer` HTML attribute.

LemonSqueezy advises *against* self-hosting the `lemon.js` file, since you might miss out on new features and crucial security patches, so we're going to link it *directly*, to keep payment-related matters as safe as possible.

We also need to call the `{{ parent() }}` function inside `javascripts` to avoid overriding this block. *Sweet*.

Below, add a unique CSS class to the checkout link: `lemonsqueezy-button`. When we head over and refresh the cart page, you'll notice that we're now loading the LemonSqueezy checkout page under *our* URL. If you inspected the source code, you'd see that LemonSqueezy is replacing the whole page with its own content. That's *awesome*, but we can make this *even better*.

### Creating a Special Stimulus Controller

Remove the `lemonsqueezy-button` class we added earlier, and exchange it for something a bit more flexible. In `assets/controllers/`, create a new controller. We'll call it `lemon-squeezy_controller.js`. 

Inside, add `import { Controller } from '@hotwired/stimulus'`, and below that, `export default class extends Controller`. Inside the class, add a `connect()` method, which we'll leave empty for now. Finally, add another method - `#openOverlay()` - that will be a Stimulus action. 

Now, let's *connect* this controller in `cart.html.twig`. Add a new line to the checkout link with `data-action`, so when we click this button, it will call the `#openOverlay()` action.

We also need to pass the LemonSqueezy Checkout URL, but instead of generating it every time the cart page loads, let's just generate it when the link is clicked.

### Adding a New Action to the OrderController 

Add a new action to the `OrderController`. Go to
`src/Controller/OrderController.php` and, just before the `success()` action, add another one. We'll call it `public function createCheckout()`. 

This will return a `Response`, and we'll add a `#[Route]` attribute above it with a path - `/checkout/create`. Name it `app_order_checkout_create` and only allow `POST` methods.

For dependencies, we'll need `LemonSqueezyApi $lsApi`, as well as a user that's logged in. For this, add the `#[CurrentUser]` attribute with `User $user`. 

In the method, simply return the JSON with an empty array. Then, in the array, add a `targetUrl` key, call `$lsApi->createCheckoutUrl()` for the value, and pass the user. *Done*!

Back in our `lemon-squeezy` controller, register a new value. Say `static values = {}`, and inside, add `checkoutCreateUrl: String`. 

Over in the cart template, add a new data value attribute - `data-lemon-squeezy-checkout-create-url-value` - and pass `{{ path('app_order_checkout_create') }}`.

You can also replace `href` with a '#' if you want to prevent clicking on this if JavaScript is disabled, but I'll keep it for legacy. *Instead*, in `#openOverlay()`, we'll take the event and call `e.preventDefault()`.

Okay, next, let's *implement* the `#openOverlay()` method. Down here, grab the link element with `const linkEl = e.currentTarget`. Below that, we need to execute an AJAX request to the `checkoutCreateUrl` we passed as a value. For that, use the `fetch()` function. Inside, call `this.checkoutCreateUrlValue`, and add the options as a second argument. This AJAX request should be executed with `method: 'POST'`... and for headers, set `Content-Type` to `application/json`. 

Next, we'll chain this `fetch()` call with `.then()`. Inside, we expect a `response`, and we'll also add a sanity check - `if (!response.ok)`, `throw new Error()` - which will tell us that the `Network response was not OK`, followed by `response.statusText`. 

*Otherwise*, just `return response.json()`. That should pass the JSON data as an object to the next `.then()`, where we expect `data => {}`.

We're going to ask LemonSqueezy to open this URL, so call `window.LemonSqueezy.Url.Open` and pass the `data.targetUrl`, which we'll return from the `createCheckout()` action. 

*Finally*, we can add a `catch()` call, expecting an error. Inside, we'll just say `console.error()` with a `Fetch error:` message, passing `error` as the second argument.

Okay, this looks good, so let's test it out. Open our site, and *also* open the Chrome Developer Tools in the Console tab to see the JavaScript logs. Reload the page, and... here's our `lemon-squeezy` controller! 

If we click the "Checkout with LemonSqueezy" button, it *loads* and... it opens the LemonSqueezy checkout page under our domain! It still works!

Next: Let's make this even cooler by rendering the LemonSqueezy checkout page over our cart page.
