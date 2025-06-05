# Embedding the LemonSqueezy Checkout Overlay

In the last chapter, we got our hands dirty and built a custom LemonSqueezy
Stimulus controller. It pulls up the LemonSqueezy checkout page in an iFrame
right on our domain. That's pretty sweet, but what if I told you we can make it
*even better*? We're going to lay the checkout page *on top* of our cart page
and create a *real* overlay - like a modal.

But before we dive in, let's add a couple of key features to the "Checkout with
LemonSqueezy" button:

- Preventing double clicks
- And showing loading progress

If you were curious why we created a custom Stimulus controller for the checkout
link, this is why!

## Preventing Double Clicks and Showing Loading Progress

To kick things off, open `lemon-squeezy_controller.js`. We're going to create
some private methods here. Start with `#disableLink()` and pass in a `link`
argument. Then add `#enableLink()` and pass `link` as the argument again.

For `#disableLink()`, we'll write some code to add the `disabled` CSS class to
the link, which will disable pointer events and dim the link slightly. Write
`link.classList.add('disabled')`, then `link.style.pointerEvents ='none'`, and
finish with `link.style.opacity = '0.5'`.

In `#enableLink()`, do the opposite:
`link.classList.remove('disabled')`, `link.style.pointerEvents = 'auto'`, and
`link.style.opacity = '1'`.

Okay, in the `openOverlay()` method, right after we creat `linkEl`,
call `this.#disableLink(linkEl)`. *Now*,
in the second `.then()` after this `window.LemonSqueezy...` line,
call `this.#enableLink(linkEl)`. Do the same thing in `.catch()` after
`console.log()`.

All right, over on our site, reload the cart page, and if we click on the
"Checkout with LemonSqueezy" button a few times... we can see that it's slightly
dimmed and completely ignores our double clicks. Nice!

## Embedding the Checkout Page

Now, onto the fun part - *embedding*! Open `src/Store/LemonSqueezyApi.php` and,
in the `createCheckoutUrl()`, after setting the custom user ID, add
`$attributes['checkout_options']['embed'] = true`.

Go refresh the cart page, click the checkout button again, and... there it is -
a shiny new LemonSqueezy overlay on our cart page! We can even see our cart page
in the background. When we close this, our
"Checkout with LemonSqueezy" button is ready to go again.

## Improving `createCheckoutUrl()`

At the moment, we're calling `createCheckoutUrl()` in a couple places - in
`OrderController::createCheckout()` and again in `checkout()`.
If we want to use embedding for *just* the JavaScript version, we can add an
`$embed` boolean argument to `LemonSqueezyApi::createCheckoutUrl()` that
defaults to `false`. We'll also replace the hard-coded `true` we used earlier
with the new `$embed` variable. Back in `OrderController`, pass `true` to
`createCheckoutUrl()` in the `createCheckout()` action.

## Automating `lemon.js` Inclusion

To ensure our LemonSqueezy Stimulus controller works, we need to include
`lemon.js` on *every* page we use it. If that sounds tedious, it *is*, so let's
*automate* it.

In the `connect()` method, create a `script` variable equal to
`window.document.querySelector()`, and pass
`'script[src="https://app.lemonsqueezy.com/js/lemon.js"]'` as the argument. If
the `script` tag doesn't exist, we'll create and append it to the DOM. Easy,
right? Inside the `if`, write `script = window.document.createElement()` with a
`script` tag inside. Also set `script.src` to the `lemon.js` URL, and don't
forget to set the defer attribute to `true`. Finally, add it with
`window.document.head.appendChild(script)`.

Now we can celebrate by removing the `javascripts` block from the
template! Reload the cart page, click the
checkout button, we can see that it's loading, and... *yes*! We can still see
the overlay!

## Debugging for Non-authenticated Users

*But* there's a hiccup for non-authenticated users. If we log out, add a product
to the cart, and try to checkout again... *nothing happens*. If you open the
Chrome Dev Tools, you can see that the request is redirected to a login page
first, but our JavaScript logic doesn't follow that redirect. Let's fix that!

In our code, add a `console.log(response)` before the `response.ok` check. Back
on our site, in the "Console" tab, we can see that `response.redirected` is set
to `true` for that request. Let's add another check -
`if (response.redirected === true)` - send the user to the login
page with `window.location.href = response.url`. If the user *isn't*
authenticated, we'll add `Promise.reject()` which will tell us that the
`User is not authenticated!`. I'll also add a quick comment above.

## Redirecting Users Back to the Cart Page

Okay, *now* if we click the checkout button when we're not logged in... we're
redirected to the login page! Nice! And if we enter our credentials and log
in... we're redirected to the homepage instead of the cart page. Let's fix that
too! In `lemon-squeezy_controller.js`, after `response.url`, add
`?_target_path=`, and concatenate `window.location.pathname`.

To *actually* make this work, open `src/Security/LoginFormAuthenticator`. At
the start of the `onAuthenticationSuccess()` method, add
`if ($targetPath = $request->query->get('_target_path'))`. Inside,
`return new RedirectResponse($targetPath)`.

This time, if we log out and try to check out again... we're redirected to the
login page. If we sign in again... boom! We're back on the cart page! Click the
checkout button and... it loads our awesome checkout overlay! I'll fill in some
information so we can complete the checkout... click the "Pay" button, and...
tada! Here's our success message!

Next: Let's learn how to *listen* to LemonSqueezy JavaScript events and *use
those* to sync the customer ID with the current user as an alternative to the
webhook we set up earlier.
