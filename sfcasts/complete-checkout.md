# Chapter 7: Complete the Checkout

I just bought *another* awesome product in our online store, and when I finished checking out, LemonSqueezy gave me a success message:

> Thanks for your order!

It's short, sweet, and to the point, but is there a way to customize this if we need to? Yep! *And* it's product-specific.

Over in the dashboard, click on "Store"... "Products"... "Choose a product", and in the "Confirmation modal" section down here, find the "Title" and "Message" fields. We have some default text here - the *same* text we saw in the confirmation message earlier.

I'm pretty happy with the default text at the moment, even if it *could* use a few more exclamation points because I'm so excited, so I'll leave it the way it is for now. If you *do* decide to change this text, remember that the changes you make here will only apply to this specific product, so if you want *all* of your products to reflect your changes, you'll have to customize them one by one.

The same goes for this button. We can change *its* text and link here. The default link looks like it goes to a my-orders page. If we click on that... yep! We're on the LemonSqueezy order page.

## Clear the Cart after Purchase

Over on the website... the product we just bought is *still* in the cart. We need to make sure the cart is cleared after we make a purchase. To do that, over in `OrderController`, create a special action. We'll call it `success()`. Then, register the route with `#[Route('/checkout/success', name: 'app_order_success')]`. This will redirect customers to this route.

*Now*, to avoid direct access to this page, we're going to use a little trick. Inject `Request $request` and, inside, add `$referer = $request->headers->get('referer')`. Then, create a variable - `$lsStoreUrl` - and set it to the store URL. For that, go to the dashboard, open the storefront, copy the URL, and paste it in our code `https://squeeze-the-day.lemonsqueezy.com'`.

Below, add `if (!str_starts_with($referer, $lsStoreUrl))`. So if this is *true*, and someone opens this URL *directly*, we can just redirect them to the homepage with `return $this->redirectToRoute('app_homepage')`. Inject `ShoppingCart $cart` and, below, continue with `if ($cart->isEmpty())`. Finally, return to the homepage again with `return $this->redirectToRoute(route: 'app_homepage')`. *Otherwise*, clear the cart with `$cart->clear()`.

We *could* render a separate success page with some details if we wanted to, but for now, we'll keep it simple and just add a flash message - `$this->addFlash('success', 'Thanks for your order!')` - and `return $this->redirectToRoute(route: 'app_homepage')`.

Okay, now we need to add this URL to the "Button link" field for each and every product. *Bummer*. There *has* to be an easier way to do that, right? Thankfully, *yes* - with an *API option*.

In the API docs, search for "Create a checkout". Under "product_options", check out this "redirect_url": "A custom URL to redirect to after a successful purchase". *That's* what we're looking for!

In our code, open the `createLsCheckoutUrl()` method, and below, add: `$attributes['product_options']['redirect_url'] = $this->generateUrl('app_order_success', [], UrlGeneratorInterface::ABSOLUTE_URL)`.

Okay, head over and check out again. Enter the card info and the billing address, click the "Checkout" button, and wait for the confirmation modal to pop up. Here it is! If we click "Continue"... yes! We see the "successful" flash message - "Thanks for your order!" - and the cart is completely empty now. Woohoo!

Next: Before we make *more* API requests, let’s separate LemonSqueezy's business logic from the controller and *centralize* it in a standalone service.
