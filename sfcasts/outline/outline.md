# Outline

### Register LS Account
- LemonSqueezy is your Merchant of record! It means that they handle all the
  payment processing, taxes, and compliance for you. You just need to focus on
  your product and marketing.
- Register your store on https://app.lemonsqueezy.com/dashboard - you will
  need an email, password, and your future store name and URL.
- Confirm your email and now you can log in to https://app.lemonsqueezy.com/dashboard
- The https://app.lemonsqueezy.com/setup page contains a lot of steps you
  will need to complete to get your live store up and running... but
  we can postpone those details for later and already start working on our integration.
- This little "Test mode" switch below in the left sidebar tell us that we're
  in a testing mode so that we can use the test card numbers to simulate payments
  without need to actually pay real money which should help us on our way with
  this integration setup. As soon as you finish the setup steps and activate your
  store - you will be able to switch between your live (real) store and test (fake)
  store using this switch.
- First of all, you even don't need any website to start selling with LemonSqueezy,
  isn't that cool? You can just share your LS storefront URL with your customers
  and they will be able to buy your products directly from there.

### Create a Product
- Create a product: Go to Store > Products > New Product.
- Name it "Classic Lemonade".
- Set description as "A classic citrus lemonade".
- Choose the classic "Single payment" pricing model - there's also "Subscription"
  that we will see later in the next course, but also "Pay what you want" that
  allows you to set a suggested and minimum prices and let your customers decide
  how much they want to pay for your product, and more specific so-called "Lead magnet"
  that allows access for free.
- Let's keep the "Standard pricing" model.
- Price: $0.99
- Choose "Digital goods or services (excluding ebooks)" - yeah, didn't you know our
  lemonade is digital?!
- Go change name to "Classic E-lemonade" for clarity.
- Anyway, keep in mind that you can sell only “digital” things with LemonSqueezy.
  As LS is a "merchant of record", they limit products you can sell via their platform,
  see their docs about prohibited products:
  https://docs.lemonsqueezy.com/help/getting-started/prohibited-products
  If you're still not sure about your specific case - just ask LS support team.
- Upload a nice image - oh yes, it definitely looks taste that I already want to buy it!
- If you sell files - you can attach a file to the Product and your customers
  will have access to it after the purchase. The same about Links
- Variants are useful when you have a product with different options like taste,
  size, color, etc. We will see it later in this course.
- If you're selling license keys - "Generate license keys" is what you may need.
- And let's show this product on the LS storefront page - this will be convenient
  if you don't have a web store yet.
- You can customize "Confirmation modal" and "Email receipt" - but I will just
  keep defaults - it works for us!
- Finally, "Publish product"!
- Close "Product Details", here's the product we just created.
- Let's create and publish a few more products - see data from our fixtures.

### LS Storefront
- Let's open the storefront: below "Squeeze the Day" store name > "My Store".
- Here it is: https://squeeze-the-day.lemonsqueezy.com/ - click on the lemonade,
  and you can buy it directly from here!
- Fill in the form, we can use test card which is 4242424242424242, insert any
  future expiration date and any CVC code, billing address is required: "Broadway 1",
  then choose from the list to autocomplete everything.
- Press "Pay".
- "Thanks for your order!", press "View Order" now - here you can generate
  a PDF invoice.
- This all is from the customer's perspective, but what about the store owner?

### LS Dashboard
- Back to https://app.lemonsqueezy.com/dashboard - here we go, our charts already
  go up!
- Open Store > Orders to see the orders list.
- Also, go to Store > Customers - we have a customer already! Oh wait, it's me.
- Anyway, LS handles all emails for us, so you don't need to worry about it.
  You will find the invoice email in your inbox shortly. Sour (🍋)! Um, I mean, sweet!!
- OK OK, that's really great! You can start selling your products without a website,
  just create more products, publish them on the storefront, and share the link
  to your storefront to your friends!
- But since we're developers, and since we already have a super popular
  lemonade stand (our on-site sales clearly confirm this 😎), *and* we already
  have an awesome website about it that I will show you in a second - we would
  like to have an integration of LemonSqueezy directly on *our* website.
  So, let's do it!

### Start the Project Code App
- Well, "payment system integration" term may sound scary at the first sight,
  but don't worry, "with LemonSqueezy - it's super easy"! And you will see it
  at the end of this course.
- Download the course code from this page, unzip it, go to the start directory, 
  and follow the instructions in the README.md file.
- Welcome to the "Squeeze the Day" website - our digital Lemonade Stand!
- Let's look around! We have some products here, you can add it to cart for buying,
  you can even specify the quantity. The actual cart page where you will be able
  to checkout - that's something for what we will need LS integration. We also
  provide weekly lemonade delivery for subscribed users - how convenient! We will
  see subscriptions in the next episode. You can also register and then log in,
  and you will find a basic account info.

### Install HTTP Client
- To send API requests let's leverage the Symfony HttpClient component - it should
  be perfect for executing HTTP requests.
- Install it with: `com req symfony/http-client`.
- Now let's create a scoped client that will help us to send requests to the LS API.
- Create `config/packages/http_client.yaml`.

### Create a Scoped HTTP Client
- In `scoped_clients` add `lemon_squeezy.client`
- Then `base_uri: 'https://api.lemonsqueezy.com/v1/'`
- And headers set to:
  `Accept: 'application/vnd.api+json'`
  `Content-Type: 'application/vnd.api+json'`
- For authorization, we need to add a Bearer token.
- First, let's set up the API key so we could make API requests
- Go to Settings > API > Add API key > name it "API", and copy the key.
- Open .env.local and set it as `LEMON_SQUEEZY_API_KEY`.
- Open .env and add this env var but left empty `LEMON_SQUEEZY_API_KEY=`
- Now we can add `auth_bearer: '%env(LEMON_SQUEEZY_API_KEY)%'`

### Create a Checkout
- If we want our customers to buy something via LS - we need to create a
  Checkout object in LS and LS has an API endpoint for this:
  https://docs.lemonsqueezy.com/api/checkouts/create-checkout
- When we create a Checkout object - it returns us the URL to which we should
  redirect our customers to complete the payment.
- Let's do it! Create `OrderController::checkout()` action.
- Make it route: `#[Route('/checkout', name: 'app_order_checkout')]`.
- Set "Checkout with LemonSqueezy" link URL to `app_order_checkout` in `cart.html.twig`
- Inject `HttpClientInterface $lsClient` and `ShoppingCart $cart`.
- Inside, `$lsCheckout = $this->createCheckout($lsClient, $cart);`.
- From the API docs we see it returns an array of data, find the URL:
  `$checkoutUrl = $lsCheckout['data']['attributes']['url'];`.
- And `return $this->redirect($checkoutUrl);`.
- Now let's implement that `createCheckout()`.
- Inject the same dependencies: `HttpClientInterface $lsClient` and `ShoppingCart $cart`.
- Inside, first of all, `if ($cart->isEmpty())` - `throw new \LogicException('Nothing to checkout!');`
- Next `$response = $lemonSqueezyClient->request(Request::METHOD_POST, 'checkouts', []);`.
- Inside options - let's just it as : `'json' => ['data' => ['type' => 'checkouts']]`.
- LS docs do not make it clear what option is required - let's execute this
  and see if there will be any errors.
- Below, `return $response->toArray();`.
- An error:
  > Invalid URL: scheme is missing in "checkouts". Did you forget to add "http(s)://"?
- Hm, it ignored our base URL from the scoped client, it feels like it inject
  the default empty client.
- Run `bin/console debug:autowiring HttpClientInterface` to see the related services.
- Aha, to inject LS client we need to use named autowiring: `$lemonSqueezyClient`
  while we have shortened it to: `$lsClient` in the code.
- We can rename it to `$lemonSqueezyClient` here but I would prefer a shorter name.
- Instead, let's leverage the new `#[Target]` PHP attr to link it:
  `#[Target('lemonSqueezyClient')]` - above the argument.
- Update again - great, an error! I mean, a *different* error now! As you can see
- Now we see:
  > HTTP/2 400 returned for "https://api.lemonsqueezy.com/v1/checkouts".
- Hm, bad request status code - let's dump the response content.
- Before the return, add: `dd($response->getContent());`
- Update to see the same error - ah, we should to pass false:
  `dd($response->getContent(false));`
- Iterate a few times to see require options and fix it.
- Next error:
  > The store.id field is required
- Check the API - aha, here it is. Let's add it to the request body.
- To find the actual store ID - go to Settings > Stores:
  https://app.lemonsqueezy.com/settings/stores
- Cope it from there and paste.
- For the variant - let's hardcoded one from the LS dashboard.
- Open the dashboard, go to Store > Products > ... > Copy variant ID
- Yes, we're on the LS checkout page and buying the product.
- Now let's make it dynamic

### Use dynamic data in the Checkout object
- Store ID is unique for test/live env, let's set it as an env var.
- Open .env and set `LEMON_SQUEEZY_STORE_ID` with the value.
- Now in `config/services.yaml` add a new parameter:
  `env(LEMON_SQUEEZY_STORE_ID): '%env(LEMON_SQUEEZY_STORE_ID)%'`
- Now use dynamic `$this->getParameter('env(LEMON_SQUEEZY_STORE_ID)')`.
- For variant ID - let's create a new field for Product:
  `private ?string $lsVariantId = null;`
- Map it as: `#[ORM\Column(length: 255, unique: true, nullable: true)]`.
- Create getters & setters.
- Create a migration and migrate.
- Now in AppFixtures - set it to variant ID from the LS dashboard.
- Run `bin/console doctrine:fixtures:load` to update the DB.
- Inside `createCheckout()`, get `$products = $cart->getProducts();`.
- Let's just set `$variantId = $products[0]->getLsVariantId();`
- And set the `variantId` on the `relationships.variant.data.id` 
- Now let's checkout - great! We're on the right product in LS checkout.
- Notice that the variantId in this specific case should be a string, not an int
  according to the docs. It's important to match that type, otherwise you will get
  an error. But since our variant ID is already a string - we're good. Though
  I would still add a comment: `'id' => $variantId, // Should be a string!`
- OK, checkout works, but as you see it's missing quantity.
- To fix it, add `attributes.checkout_data.variant_quantities` - set both
  variant ID and quantity.
- Checkout again - it's still missing! But we did everything right.
- Well, if you take a look at the API docs - variantId in `variant_quantities`
  should be integer, not a string. Yep, a subtle detail that easy to skip,
  it would be nice to have an error about it LS!
- Fix it by casting to int: `'variant_id' => (int)$variantId, // Should be an int!`
- OK, now a single product purchase looks awesome, but if we add one more product
  there's a problem - we purchase only the first product from our shopping cart.
- Actually, there's a bigger problem - LS do not allow you to buy more than
  1 product, and that's a bummer. Though it might change in the future! Watch
  closer for their roadmap: https://www.lemonsqueezy.com/roadmap - there is
  a "Cart" feature that will add support for a traditional cart checkout experience
  and I hope it will solve this issue.
- So we can either twaek our shopping card and allow to add only 1 product to it,
  i.e. overwrite a product if it's already there with a new one.
- Or we can make a custom workaround - if you take a look at the API docs - LS
  allows you to set up your own price.
- Let's do the second option because it's more fun and a good change to see
  more options in action.
- Let's `if (count($products) === 1) {` first, and then do everything we did so far.
- But `else`, we still need to set a variantId - that's required:
  `$variantId = $products[0]->getLsVariantId();`
- Below set `$attributes` to an array where set:
  `'custom_price' => $cart->getTotal(),`
- And `product_options` set to an array where `'name' => sprintf('E-lemonades')`
  to make the name more universal.
- Checkout again to see it works.
- We can do even better, above set `$description = '';`
- Below `foreach ($products as $product)`
- And inside set it:
  `$description .= $product->getName() . ' for $' . number_format($product->getPrice()/100, 2) . ' x ' . $cart->getProductQuantity($product) . '<br>'`
- And finally in `product_options` set `'description' => $description,`
- Checkout again - much better, I like this workaround!
- We can do much more, change the image, etc - but I will leave it to you.
- Now, let's finally complete the checkout!
- Check the email to see how it looks like there - btw you can configure it
  in the LS dashboard > Design > Email: https://app.lemonsqueezy.com/design/email
- ...

