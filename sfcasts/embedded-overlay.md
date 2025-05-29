# Embedding the LemonSqueezy Checkout Overlay

In the last chapter, we got our hands dirty and built a custom
LemonSqueezy Stimulus controller. It pulls up the LemonSqueezy Checkout
page in an iFrame right on over domain. But hey, let's make it even
smoother! We're going to lay the checkout page on top of our cart page,
in order to create a *real* overlay
But before we dive in, let's add a couple of features: prevent double
clicks and show loading progress for the "Checkout with LemonSqueezy" button.

## Preventing Double Clicks and Showing Loading Progress

To kick things off, open the `lemon-squeezy_controller`. We're
going to create some private methods here. Start with `#disableLink()` and
pass in a `link` argument. Then, let's add `#enableLink()` and pass `link` as
the argument again. 

For the `#disableLink()`, we'll write some code to add the `disabled` CSS class
to the link, disable pointer events and dim the link slightly:

`link.classList.add('disabled')`, then `link.style.pointerEvents ='none'`,
and finish with `link.style.opacity = '0.5'`.

In the `#enableLink()`, we'll do the opposite. Write `link.classList.remove('disabled')`,
then `link.style.pointerEvents = 'auto'`, and `link.style.opacity = '1'`.

Next, in the `#openOverlay()` action, right after we created `linkEl`, we'll
call `this.#disableLink(linkEl);`. Now, just in case something doesn't go
to plan, we'll call `this.#enableLink(linkEl)` in the second `.then()` after
`window.LemonSqueezy.Url.Open(data.targetUrl);` and also in the `catch()`
after the `console.log()`.

Now open the website, reload the cart page, and if I click a few times on the
"Checkout with LemonSqueezy" button - it's slightly dimmed with the opacity 0.5
and ignored my double clicks.

## Embedding the Checkout Page

Now, onto the fun part - embedding! Open up `LemonSqueezyApi` from the `src/Store/`.
In the `createCheckoutUrl()`, after setting the custom user ID, we'll add
`$attributes['checkout_options']['embed'] = true;`.

Refresh your cart page and click the checkout button - there it is!
A slick LemonSqueezy overlay over our cart page! We can even see our cart page in the
background if we click the close button. Once closed, our "Checkout with LemonSqueezy"
button is ready to go again.

## Improving `createCheckoutUrl()`

At the moment, we're calling `createCheckoutUrl()` in a few places: in
`OrderController::createCheckout()` and also in `OrderController::checkout()`.
If we want to use embedding only for the JavaScript version - we can add an
`$embed` boolean argument to `LemonSqueezyApi::createCheckoutUrl()` that defaults
to `false`. Let's replace the hard-coded `true` we used earlier with this
`$embed`variable. Back in our `OrderController`, we'll pass `true` to the
`createCheckoutUrl()` in the `createCheckout()` action. 

## Automating `lemon.js` Inclusion

To ensure our LemonSqueezy Stimulus controller works, we need to
include `lemon.js` on every page where we use it. A bit tedious? We can
automate it! Inside the `connect()` method, create a `script` variable and
set it equal to `window.document.querySelector()` and pass
`'script[src="https://app.lemonsqueezy.com/js/lemon.js"]'` as the argument.
If the `script` tag doesn't exist, we'll create it and append it to the DOM.
Easy, right? Inside the `if`, write: `script = window.document.createElement()`
and write `script` tag inside. Next, `script.src` set to `lemon.js` URL.
And don't forget to set the defer HTML attr to true.
End with `window.document.head.appendChild(script)`.

Celebrate by removing the whole `javascripts` block completely from the template
and go checkout again. I will reload the cart page, click on the checkout button,
it's loading... and yes, we can still see the overlay.

## Debugging for Non-authenticated Users

But there's a hiccup for non-authenticated users. If we log out, add a product
to the cart, and try to checkout again - nothing happens! Open the Chrome Dev Tools
and you'll see that the request is redirected to a login page first but our
JavaScript logic does not follow that redirect. 

Let's add a `console.log(response)` before the `response.ok` check. In the Console
tab we can seet there's a `response.redirected` set to `true`,
Let's add one more if, and `if (response.redirected === true)`, 
we'll redirect the user to the login page with
`window.location.href = response.url`.

and add the current pathname to the URL. If the user isn't authenticated,
we'll do `Promise.reject()` saying "User is not authenticated!". I will also
add a comment above.

## Redirecting Users Back to the Cart Page

Now if we click on the checkout button - we're redirected
to the login page. And if I login, Now, when users sign in, they're redirected
to the home page instead of the cart page.
Let's fix that too. In the LemonSqueezy Stimulus controller, after
`response.url`, we'll add `?_target_path=` string and concatenate
`window.location.pathname`.

To make this actually work, we'll open `src/Security/LoginFormAuthenticator`,
and at the start of the `onAuthenticationSuccess` method
add `if ($targetPath = $request->query->get())` passing out `_target_path`
from the query parameters. Then `return new RedirectResponse($targetPath)`.

Now, when users sign in after being redirected to the login page, they're
taken straight back to the cart page. If they click the checkout button
again, they'll see our awesome checkout overlay. And after completing the
checkout - success! Press continue - here's our success message. All good!

Next, let's see how we can listen to the LemonSqueezy JavaScript events that
will also allow us to sync customer ID with the current user.
