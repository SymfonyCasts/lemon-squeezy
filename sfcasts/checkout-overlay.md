# LemonSqueezy Checkout Overlay

Our customers can purchase products on our website but to finalize the
checkout, we redirect them to the LemonSqueezy website, which is hosted on
a completely different domain. However, we can enhance this workflow,
thanks to LemonSqueezy's JavaScript tools. 

Rather than redirecting customers to LemonSqueezy's checkout page, we can
present that page in a sleek iFrame right on our website. LemonSqueezy
charmingly refers to this as a "checkout overlay". It's time to sprinkle our
website with some JavaScript magic.

## Add LemonSqueezy JavaScript to the Cart Page

First, let's include LemonSqueezy's JavaScript tool known as `lemon.js` to
our cart page. I'll navigate to the `templates/order/cart.html.twig` file
and add a new block, naming it `javascripts`. Close it with `endblock`.

Inside, we'll include the script with a `script` tag, setting `src` to
`https://app.lemonsqueezy.com/js/lemon.js`. Let's also add the `defer` HTML
attribute. 

LemonSqueezy advises against self-hosting the `lemon.js` file, since you
might miss out on new features and crucial security patches. It's better to
link it directly, as payment-related matters are important from a security
standpoint. 

Also, remember to call the `{{ parent() }}` function inside `javascripts` to
prevent overriding this block. 

Below, let's add a unique CSS class to the checkout link: `lemonsqueezy-button`.
After refreshing the cart page, you'll notice
that it loads the LemonSqueezy checkout page under *our* URL. If you inspect
the source code, you'll see that LemonSqueezy simply replaces the whole
page with its content. But we can make it even better.

### Creating a Special Stimulus Controller

I'll remove the `lemonsqueezy-button` class, we will need more flexability.
Let's create a new stimulus controller. inside `assets/controllers/`,
create a new `lemon-squeezy_controller.js`. 

Within it, I'll `import { Controller } from '@hotwired/stimulus'` and
underneath, `export default class extents Controller`. Inside, let's add a
`connect()` method, which will remain empty for now. 

Below, let's add another method, `#openOverlay`, that will trigger the
actual action. 

Now, let's connect this controller in `cart.html.twig`. I'll add a new
line to the checkout link with `data-action`. So, when we click this button,
it will call the `#openOverlay` action. 

We'll also need to pass the LemonSqueezy Checkout URL, but I don't want to
generate it every time the cart page loads. I want it to generate on link
click. 

### Adding a New Action to the OrderController 

Let's add a new action to the `OrderController`. I'll go to
`src/Controller/OrderController.php` and just before the `success()` action,
add another one. I'll name it `public function createCheckout()`. 

It will return a `Response`, and we'll add a `#[Route]` attribute above it with
`/checkout/create` path. We'll name it as `app_order_checkout_create` and allow
only POST method. 

For dependencies, we'll need our `LemonSqueezyApi $lsApi`. We'll also
need a logged-in user. For this, we'll add `#[CurrentUser]` PHP attribute with
`User $user`. 

Inside the method, simply return the JSON with an empty array. Inside this
array, add `targetUrl` key and call `$lsApi->createCheckoutUrl()` for the value
and pass the user.
Done.

Back to the `lemon-squeezy` Stimulus controller, let's register a new value,
write `static values = {}`. Inside, add `checkoutCreateUrl: String`. 

Back to the cart template, add a new data value attribute:
`data-lemon-squeezy-checkout-create-url-value` and pass the
`{{ path('app_order_checkout_create') }}`. 

You can also replace `href` with a '#' if you want to prevent clicking if
JavaScript is disabled. But I'll keep it for legacy and instead, inside
the `#openOverlay`, we'll take the event and call `e.preventDefault()`. 

Next, let's implement the `#openOverlay()` method. Below, we'll get the link
element with `const linkEl = e.currentTarget`. Below that, we need to
execute an AJAX request to the `checkoutCreateUrl` we passed as a value. 

For this, use the `fetch()` function. Inside, call `this.checkoutCreateUrlValue`,
and add the options as a second argument. 

This AJAX request should be executed with `method: 'POST'`. For headers, set
`Content-Type` to `application/json`. 

Next, chain this `fetch()` call with `.then()`. Inside, we expect a `response`.
Let's add a sanity check. `if (!response.ok)` - `throw new Error()` saying
that "Network response was not OK". Add `response.statusText`. 

Otherwise, just `return response.json()` that should pass the JSON data
as an object to the next `.then()` where we expect `data => {}`.

We want to ask LemonSqueezy to open this URL. 

Call `window.LemonSqueezy.Url.Open` and pass the `data.targetUrl`,
which we return from the `createCheckout()` action. 

Finally, we can add a `catch()` call, expecting an error. Inside, let's
just do `console.error()` with "Fetch error:" message and pass the error
as the second argument.

Okay, it looks good. 

Let's test if it still works. Open the website. I'll open the Chrome
Developer Tools, the Console tab to see JavaScript logs and reload the page.
Here is our `lemon-squeezy` controller. 

If I click the "Checkout with LemonSqueezy" button, it loads and... opens the
LemonSqueezy checkout page. Still works!

In the next chapter, we'll make it even cooler by rendering this
LemonSqueezy checkout page over our cart page.
