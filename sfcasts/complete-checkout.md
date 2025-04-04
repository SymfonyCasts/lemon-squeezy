# Chapter 7: Complete the Checkout

Hey! I just bought another awesome product in our online store, and when I completed the checkout - LemonSqueezy showed me this successful message:

> Thanks for your order!

And you know what? It can be configured, and it's actually a product-specific configuration. Open the dashboard > Store > Products > Choose a product > and in the "Confirmation modal" section you will find the Title & Message fields. You can see the defaults there as a placeholder - the exact text we can see in the modal. I'm pretty happy with the defaults, maybe just add more exclamations because I'm super excited. Feel free to customise it for your need, but keep in mind that all these changes will apply only for this specific product, so you may need to do such changes in every product.

The same relates to the button - you can change its text & link here. Where it leads by default? Let’s check it. Click on it - aha, we’re on the LemonSqueezy order page.

## Clear the Cart after Purchase

But if you return to the website - we still have the product I just bought in the cart - we need to clear the cart after the purchase. For this, let's create a special action in `OrderController`, call it `success()`. Register the route as `#[Route('/checkout/success', name: 'app_order_success')]`. We will redirect customers to this route.

But first, to avoid direct access to this page - let's do a little trick. Inject `Request $request`. Inside, add `$referer = $request->headers->get('referer');`. Then create a variable: `$lsStoreUrl` and set it to the store URL. For this, go to the dashboard, open the storefront, copy the URL and set it `https://squeeze-the-day.lemonsqueezy.com';`.

Below, add: `if (!str_starts_with($referer, $lsStoreUrl)) {`.If true - then someone open this URL directly, and we can just redirect to the homepage:`return $this->redirectToRoute('app_homepage');.

Now inject `ShoppingCart $cart` object. Below, continue with`if ($cart->isEmpty())` - return redirect to homepage again. Otherwise, add `$cart->clear();`.

You can render a separate success page with some details if you want, but I will simplify and just add a flash message: `$this->addFlash('success', 'Thanks for your order!');`. And finally return redirect to homepage.

And now we need to set this URL in "Button link" field for every product - bummer :/ Could we do it simpler? Yes, via the API option. Open the "Create a checkout" API docs, and in `product_options` see `redirect_url`.

Open `createLsCheckoutUrl()` method, and below, add: `$attributes['product_options']['redirect_url'] = $this->generateUrl('app_order_success', [], UrlGeneratorInterface::ABSOLUTE_URL),`.

Now go checkout again, complete the payment card credentials and the billing address, press checkout, wait a bit… and here's the Confirmation modal, press Continue... yes, our success flash message "Thanks for your order!", and the cart is completely empty now.

Next, let’s separate LemonSqueezy business logic from the controller and centralise it in a standalone service before start writing more API requests.
