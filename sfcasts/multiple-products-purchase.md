# Chapter 6: Multiple Products Purchase

A *single* product purchase looks *awesome*. We can even specify the *quantity* and it will be reflected on the LemonSqueezy checkout page. But what if we add *one more* product to the cart? A *different* product. Yep, there's a *problem*. We clicked the checkout button with *two* flavors of lemonade in our cart, but only *one* flavor - the first one in our cart - is on the checkout page. How can we fix this? Well... there's a *slight* problem.

LemonSqueezy currently limits the number of items we can purchase to *just one*. *Bummer*. And even though their roadmap suggests this may change in the future, that doesn't really help us right now. So what can we do? Get creative, of course!

If we take a look at the API docs, LemonSqueezy allows us to set our own price. So let's try something... *for science*. Over in `OrderController.php` inside `createLsCheckoutUrl()`, add `if (count($products) === 1)`. Up here, we can keep the `$attributes` variable, but set it to an empty array. And in our new `if` statement, say `$attributes['checkout_data']['variant_quantities']`, and set that equal to the remaining array. I'll fix my spacing, close this correctly, and... done!

Okay, now we need to change the `quantity`. Copy `$cart->getProductQuantity()`, remove that line, and paste it down here. Below that, add `else`, and inside, say `$attributes['custom_price'] => $cart->getTotal()` and `$attributes['product_options']` set to an array where `'name' => 'E-lemonades'`, so the name is more universal for our users.

This looks good, but I think we can make this even *better*. Between our `$attributes` here, say `$description = ''`. Below *that*, we'll iterate over our products with `foreach ($products as $product)`. Inside, set `$description .= $product->getName() . 'for $' . number_format($product->getPrice()/100, 2) . ' x ' . $cart->getProductQuantity($product)`, and we'll add a `. '<br>'` at the end. Don't forget to close this with a `;`. *Finally*, in `product_options`, let's *use* this variable with `'description' => $description,`. *Nice*.

All right, let's test this out! Go to the cart page again and reload it, just to be safe. Click "Checkout with LemonSqueezy" and... it *works*! We can see the "E-lemonades" product name for $8.97, and below that, a description with the list of products, quantity, and price. It might not be *ideal*, but it works! We don't want to force our customers to go though the full checkout process for *every* *single* product they want to buy. Just think of all the profit we'd be missing out on!

We could even go a step further and change things like which *image* is displayed on the checkout page - *also* possible via the API. Or better yet, we could create a *variety* "E-lemonade" product in the LemonSqueezy dashboard and use *that* `variantId` to make it even more obvious that we’re buying a mix of various e-lemonade products instead of a single, specific product.

The *problem* is that, even if we changed the name and description of the product on the LemonSqueezy checkout page, LemonSqueezy will *still* use the original name and image in emails and reciepts. That may change one day, but at the moment, that's what we're working with.

If we fill in some fake billing info and other required fields on the checkout page and click the "Pay" button... we get this order confirmation message. If we click "Continue" and check our email...*yep*, we just see info for the *first* product. Our custom name and description are missing. The same goes for the page where we view our order. So this isn't *perfect*, but it *does* keep our customers from going through the checkout process for every single product they want to purchase.

Next: Let’s polish our checkout process even *more* with some post-checkout operations.
