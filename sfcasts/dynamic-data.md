# Chapter 5: Dynamic Data

Okay! We made our first API request! This generates a *unique* checkout URL for our customer and opens the LemonSqueezy checkout page so they can buy the product. *But* when we set this up, we hard-coded a lot of things. It’s time to make it *dynamic*!

## Use Dynamic Data in the Checkout Object

Let's start with the Store ID. That's unique for both our test and live environments, so it makes sense to set it as an environment variable. Open the `.env` file and, below the `LEMON_SQUEEZY_API_KEY`, say `LEMON_SQUEEZY_STORE_ID=` and set that to the `id` value. You can find that in `OrderController.php`.

Now, in `config/services.yaml`, under `parameters`, add a new one - `env(LEMON_SQUEEZY_STORE_ID)` - and set that to `%env(LEMON_SQUEEZY_STORE_ID)%’`. Back in our controller, replace the `id` value with `$this->getParameter('env(LEMON_SQUEEZY_STORE_ID)')`.

## Store Variant IDs in the Database

For the variant, let's create a new field on the `Product` entity. At your terminal, run:

```terminal
bin/console make:entity
```

update the `Product` entity, and call the new field `lsVariantId`. This will be a `string`, with a default field length, and let's make it "nullable". Press "enter" to finish and then head over to `Entity/Product.php` to see our changes. If we scroll down... ah, there it is! We should probably make this `unique` as well. Looking good! Down here, we can see that it also created a getter and setter. *Convenient*!

Now, back in our terminal, create a migration with:

```terminal
bin/console make:migration
```

Let's check it out! Back in `migrations/`... down here... it looks like a new column was added. *Nice*! Up here, we can add a description:

`Add a column to store the LS variant ID on Product`

Back in our terminal, *run* the migration:

```terminal
bin/console doctrine:migrations:migrate
```

Now, in `src/DataFixtures/AppFixtures.php`, set our new field to the variant ID from the LemonSqueezy dashboard. You can find that by clicking on "Store", "Products", the three dots at the end of our product here, and selecting "Copy variant ID". Paste that and... tada! The first one is *done*! But what kind of designer lemonade stand would we be if we only had one kind of lemonade to choose from? Let's add more based on our fixtures!

Copy this description and, back in our dashboard, create a new product. We'll call this one "Watermelon E-Lemonade" and *paste*. Set the price to "$1.99", add an image, and "Publish product". Copy our *new* product's variant ID and add that to our product in `AppFixtures.php`. Awesome! Let's do the same thing for the next four products. I'll speed this up a bit.


Done! Now let’s *reload* the fixtures with:

```terminal
bin/console doctrine:fixtures:load
```

Back in the controller, inside `createLsCheckoutUrl()`, retrieve products in the shopping cart with `$products = $cart->getProducts()`. And we'll set `$variantId` to `$products[0]->getLsVariantId()` for now. Finally, below `response`, set the variant ID to `$variantId`.

## Set the Correct Quantity

Okay, let's try to check out! Go back to the homepage and choose a new product this time. Set the quantity to "2", add that to the cart, then click the checkout button. *Nice*! We're on the checkout page, and this is the correct product but... *not* the correct quantity. 

To fix that, back in our code, under `type`, add `attributes`... `checkout_data`... `variant_quantities`... another empty array, and inside *that*, say `variant_id => $variantId` and `quantity => $quantity`. Above, under the `variantId`, add `$quantity = $cart->getProductQuantity()` with `$prodcuts[0]` as the argument. If we go to the cart page and click the "checkout" button again... *yes*! We have the correct product *and* the correct quantity!

## Pre-fill User Data
If I scroll up... hm... where did this email come from? I'm an authenticated LemonSqueezy store owner, so this is pre-filled for me. But what if I try to check out as a customer in incognito mode? I'll open a new tab in incognito mode, log in again with `lemon@example.com` as the email and `lemonpass` as the password, choose a product, quantity, and add it to the cart, click "checkout", and... aha! The user data is empty! The user can just fill this in themselves, so it's not a *big* deal, but I bet we can save them some time and pre-fill this from our app, since they already shared their name and email when they authenticated. Let's do it!

Over in `OrderController::checkout()`, let’s add a new argument - `#[CurrentUser] ?User $user` - and below, pass `$user` to `createLsCheckoutUrl()`. Down here in our function, add a *third* argument: `?User $user`. Below `$quantity`, add `$attributes =` and set it to the array of attributes from the request options. We can copy and paste this to make it easy.

We'll set this to `$attributes`, and up here, add `if ($user)`. Inside, say `$attributes['checkout_data']['email'] = $user->getEmail()`. And below, `$attributes['checkout_data']['name'] = $user->getFirstName()`. Perfect! Let's try checking out in incognito mode again. Go back to the homepage, click on the cart where we already have two items waiting, click the checkout button, and... the user data is pre-filled! *Awesome*!

So far, we've only tried purchasing one flavor of lemonade at a time. Let's try to buy *more* than one type *next*.
