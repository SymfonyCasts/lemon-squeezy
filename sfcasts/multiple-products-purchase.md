# Chapter 6: Multiple Products Purchase

OK, now a single product purchase looks awesome, you can even specify the quantity of the product and it will be reflected on the LemonSqueezy checkout page. But if we add one more product to the cart, a different product, there's a problem - we purchase only the first product from our shopping cart. We hardcoded this on purpose to simplify the implementation in the beginning.

But now when the single product purchase implemented - let’s handle multiple purchases. And actually, there's a problem - LemonSqueezy do not allow you to buy more than 1 product during the checkout… yet… and that's a bummer. Though this may change in the future! I see they may work on a Cart feature soon - they mentioned it on their roadmap: https://www.lemonsqueezy.com/roadmap - there is a "Cart" feature that will add support for a traditional cart checkout experience and I hope it will solve this issue some day.

Soooo, we can let our customers wait for when LemonSqueezy finally add this feature and tweak our shopping card business logic to allow adding only 1 product to to the cart, i.e. overwrite a product if it's already there with a new one.

Or we can make a custom workaround! If you take a look at the API docs - LemonSqueezy allows you to set up your own price. For the sake of science! Let's do the second option because it's more fun and a good chance to see more API options in action.

First of all, in the OrderController, let’s `if (count($products) === 1) {` - then do everything we did so far, I will move around this code a little bit. I will keep the `$attributes` variable set to an empty array. And here in the if write `$attributes[‘checkout_data’][‘variant_quantities’] =` to the remaining array. Then realign and close it correctly.

We still need to set a variantId - that's required. We can take it from the first product that was added to the cart as we did before: `$variantId = $products[0]->getLsVariantId();`.

But for `$quantity` variable - we don’t need that. I will copy the `$cart->getProductQuantity()` call, delete this line completely, and paste it to the “quantity” key directly.

Below add else. And inside set `$attributes['custom_price'] => $cart->getTotal(),`.

And `product_options` set to an array where `'name' => sprintf('E-lemonades')` to make the name more universal for users. Looks good, but we can do even better. Above set `$description = '';`. Below iterate over products, add `foreach ($products as $product)`.

And inside set `$description .= $product->getName() . 'for $' . number_format($product->getPrice()/100, 2) . ' x ' . $cart->getProductQuantity($product) . '<br>'`. Don’t forget about

And finally in `product_options` use this variable, set `'description' => $description,`.

OK, now looks good, go checkout again - I will reload the page just in case, press Checkout with LemonSqueezy, aaaand, it works! I see the "E-lemonades" key for $8.97. And below we have a description with the list of products with its quantity and price. Yeah, I know it’s not ideal, but I like this workaround! I don’t want my users to be force to go though the full checkout process for every product they want to buy, I love bulk purchases!

We can do much more, like changing the image that’s displayed on the checkout page - it’s also possible via the API.

Or probably even better to create a basic "E-lemonade" product in LemonSqueezy dashboard and use THAT variantID to make it even more clearer we’re buying not a specific strawberry lemonade but a mix of lemonades. The problem is that even if we changed the name and description of the product in the LemonSqueezy checkout - LemonSqueezy will still use the original name and image in the emails and orders. Maybe they will fix it someday, but on the moment I record it works this way. That's why probably creating a base product for multiple-purchase purpose would be even better. But I will leave it to you guys.

If you will input test card credentials, fill in the required billing address, press the checkout, and check the email - you will still see the first product info - no our custom name and description. The same for the view order page. that’s displayed only on checkout page. But what is the most important here - with our workaround we avoid our users to go though the full checkout process for every product.

If we return back to our checkout page and press the Continue button in the successful modal - we’re redirected to the LemonSqueezy order page.

Next, let’s polish our checkout process even further with some post-checkout operations and control ourselves what we want to do with the customer after the successful purchase.
