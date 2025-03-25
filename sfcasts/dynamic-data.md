# Chapter 5: Dynamic Data

OK, so far we made our first API request that generates a unique checkout URL for the customer and opens the LS checkout page so that the customer could buy the product. But we hardcoded a lot of things. It’s time to make them dynamic now!

## Use Dynamic Data in the Checkout Object

I would like to start with Store ID - it’s unique for test/live env, so it makes sense to set it as an environment variable. Open `.env` file and below the `LEMON_SQUEEZY_API_KEY` set `LEMON_SQUEEZY_STORE_ID=` with the value.

Now in `config/services.yaml` add a new parameter `env(LEMON_SQUEEZY_STORE_ID)` set to `%env(LEMON_SQUEEZY_STORE_ID)%’`. Back to the controller, now we can get that parameter as `$this->getParameter('env(LEMON_SQUEEZY_STORE_ID)')`.

## Store Variant IDs in the Database

For variant ID - let's create a new field on Product entity. Run `bin/console make:entity`, choose Product, name the field as `lsVariantId` string, 255, nullable - we will need to allow nullable temporarily because . Finish with one more enter. Open the Product entity now to check it - I think we need to make it unique. Looks good now. It also created getter and setter.

Now create a migration with `bin/console make:migration` - open it to double check, a new column added, everything looks good. Now migration with `bin/console doctrine:migrations:migrate`

Next, go to the in `src/DataFixtures/AppFixtures.php` and set the new field to variant ID from the LS dashboard. I will create more products base on the fixtures we have. OK, now let’s reload the fixtures with `bin/console doctrine:fixtures:load`.

Back to the controller, inside `createLsCheckoutUrl()`, get products in the cart with with `$products = $cart->getProducts();`. Let's just set `$variantId = $products[0]->getLsVariantId();` for now. And set the variant ID variable on the `relationships.variant.data.id`.

## Set the Correct Quantity

Now let's try to check out. Go to the homepage again, choose a new product this time. I will set 2 as the quantity, add to the cart, press checkout - great! We're on the right product in LemonSqueezy checkout.

So the checkout works, but as you see it's missing the quantity. To fix it, under the type, we need to add `attributes`, inside `checkout_data`, `variant_quantities` - another empty array, and inside set both variant ID and quantity. Above, under the `variantId`, add `$quantity = $cart->getProductQuantity()` and as an arguments add the first product: `$prodcuts[0]`.  Now go to the Checkout again, press Checkout button - yes, we have the correct quantity and amount now!

## Pre-fill User Data
But if you scroll up the checkout page - where this email comes from? I'm authenticated in LS as a store owner, so they pre-fill it for me. What if I try to check out in incognito mode? Go open the incognito, log in again with  `lemon@example.com` and password `lemonpass`. Choose the product, add to the cart, press checkout - aha, user data is empty! Not a big deal, user can manually fill that in… but we can do better and pre-fill it from our app as we know user email and name when they authenticated.

First, inside `OrderController::checkout()`, let’s inject one more dependency - add 3rd argument as`#[CurrentUser] ?User $user`. And pass the user to the `createLsCheckoutUrl($user)` - add below in the function, add 3d argument as`?User $user`. Next, below the quantity, let’s add `$attributes =`, add set it to the array of attributes from the request options. And in the options just use this `$attributes` variable. Next, below `if ($user)`. Inside add `$attributes['checkout_data']['email'] = $user->getEmail();`. And below `$attributes['checkout_data']['name'] = $user->getFirstName();`. Perfect, now let's try checkout in incognito again. I will return to the homepage, cart, press the checkout button - and now user data are pre-filled!

But so far we hardcoded buying only the first product from the cart - you can see it in the code . Let’s implement buying several products at once in the next chapter.
