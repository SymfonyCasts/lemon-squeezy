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
- Btw, you can share the link to specific product with this Share button
  and send to your customer - it will lead them directly to the checkout page
  where they can buy that product.

### LS Storefront
- Or just share the link to your Storefront sp that users can see all your
  available products.
- Let's open the storefront: below "Squeeze the Day" store name > "My Store".
- Here it is: https://squeeze-the-day.lemonsqueezy.com/ - click on the lemonade,
  and you can buy it directly from here!
- Btw you can configure some styles in LS dashboard > Design > General:
  https://app.lemonsqueezy.com/design/general
- I would like to upload my logo for sure - you can even upload a favicon here!
- Maybe want some preconfigured theme? Or go wild and set up your own colors!
- There's even more flexibility on the other tab: Design > Store:
  https://app.lemonsqueezy.com/design/storefront
- The Design > Checkout tab also allows you to override some styles if needed - go
  wild and create our own unique style!
- Fill in the form, we can use test card which is 4242424242424242, insert any
  future expiration date and any CVC code, billing address is required: "Broadway 1",
  then choose from the list to autocomplete everything.
- See LS test card numbers for more supported testing card:
  https://docs.lemonsqueezy.com/help/getting-started/test-mode#test-card-numbers
- Press "Pay".
- "Thanks for your order!", press "View Order" now - here you can generate
  a PDF invoice.
- This all is from the customer's perspective, but what about the store owner?

### Invoice Emails
- Check your inbox to see the invoice email to see how it looks like.
- Seems we received 2 emails: 1 as a customer and 1 as a store owner.
  I don't want to receive that 2nd type of emails, so I will turn them off:
  Go to Settings > General > Account and switch the sales notifications off:
  https://app.lemonsqueezy.com/settings/account
- Don't forget to Save changes.
- Btw you can configure it in the LS dashboard > Design > Email:
  https://app.lemonsqueezy.com/design/email
- We can also control the email when create a checkout, see `receipt_*` fields
  in `product_options`.

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
- Let's take a look at the dev guide first for help:
  https://docs.lemonsqueezy.com/guides/developer-guide/getting-started
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
- Keep it secret! Nobody should see it... oh crap, I've already failed this.
  OK, not a big problem, I can always delete it and generate a new one.
- We usually save things on `.env` file, but for better security I do not want
  to commit it to the repository, so I will save it in `.env.local` instead.
- Create `.env.local` and set it there as `LEMON_SQUEEZY_API_KEY`.
- Open `.env` and add this env var but left empty `LEMON_SQUEEZY_API_KEY=`
- On production, you can set it on a real env var with your cloud hosting.
- Or to make it even more secure - take a look at Symfony's secrets management system.
- Now we can add `auth_bearer: '%env(LEMON_SQUEEZY_API_KEY)%'`

### Create a Checkout
- Next dev guide will help us to charge the user:
  https://docs.lemonsqueezy.com/guides/developer-guide/taking-payments
- If we want our customers to buy something via LS on our website - we need
  to create a Checkout object in LS and LS has an API endpoint for this:
  https://docs.lemonsqueezy.com/api/checkouts/create-checkout
- When we create a Checkout object - it returns us the URL to which we should
  redirect our customers to complete the payment.
- Let's do it! Create `OrderController::checkout()` action.
- Make it route: `#[Route('/checkout', name: 'app_order_checkout')]`.
- Set "Checkout with LemonSqueezy" link URL to `app_order_checkout` in `cart.html.twig`
- Inject `HttpClientInterface $lsClient` and `ShoppingCart $cart`.
- Inside, `$lsCheckoutUrl = $this->createCheckoutUrl($lsClient, $cart);`.
- And `return $this->redirect($checkoutUrl);`.
- Now let's implement that `createCheckoutUrl()`.
- Inject the same dependencies: `HttpClientInterface $lsClient` and `ShoppingCart $cart`.
- Inside, first of all, `if ($cart->isEmpty())` - `throw new \LogicException('Nothing to checkout!');`
- Next `$lsCheckout = $this->client->request(Request::METHOD_POST, 'checkouts', []);`.
- Inside options - let's just it as : `'json' => ['data' => ['type' => 'checkouts']]`.
- LS docs do not make it clear what option is required - let's execute this
  and see if there will be any errors.
- Below, `return $lsCheckout['data']['attributes']['url'];`.
- An error:
  > Invalid URL: scheme is missing in "checkouts". Did you forget to add "http(s)://"?
- Hm, it ignored our base URL from the scoped client, it feels like it inject
  the default empty client.
- Run `bin/console debug:autowiring HttpClientInterface` to see the related services.
- Aha, to inject LS client we need to use named autowiring: `$lemonSqueezyClient`
  while we have shortened it to: `$lsClient` in the code.
- We can rename it to `$lemonSqueezyClient` here, but I would prefer a shorter name.
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
- Inside `createCheckoutUrl()`, get `$products = $cart->getProducts();`.
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
- Hm, but where this email comes from? I'm authenticated in LS as a store owner,
  so they pre-fill it for me. What if I try to checkout in incognito mode?
- Open incognito, log in as `lemon@example.com` with password `lemonpass`, try
- to checkout - aha, user data is empty! Not a big deal, user can manually
  fill that... but we can do better and prefill it from our app as we know
  user email and name when they authenticated.
- First, we will need User object - inject `?User $user = null` to the `createCheckoutUrl()`.
- Next, below `$attributes = [];`, add `if ($user)`.
- Inside add `$attributes['checkout_data']['email'] = $user->getEmail();`.
- And add `$attributes['checkout_data']['name'] = $user->getFirstName();`.
- Now, inside `OrderController::checkout()`, inject `#[CurrentUser] ?User $user`.
- And pass the user to the `createCheckoutUrl($user)`.
// TODO Too early - let's do it later
//- But inside add `$this->denyAccessUnlessGranted(AuthenticatedVoter::IS_AUTHENTICATED);`.
//- PhpStorm does not like it much though it should work. To make PhpStorm
//  happy - above add `if (!$user instanceof User)`.
//- And then `throw $this->createAccessDeniedException('You must be logged in to checkout!');`.
- Perfect, let's try checkout in incognito again - now user data pre-filled!

### Workaround for Multiple Products Purchase
? The problem is that even if we will change the name and description of the
  product in the LS checkout - LS will still use the original name and image
  in the emails and orders. That's why probably better to create a base product,
  e.g. "E-lemonades" and use it as a base variant ID for multiple product purchases?
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

### Complete the Checkout
- Now, let's finally complete the checkout!
- It shows us a successful alert:
  > Thanks for your order!
- It can be configured as well and it's a product-specific config.
- Open LS dashboard > Store > Products > A product > Confirmation modal.
- There it is! Title & Message fields which the defaults I see. I'm pretty
  happy with the defaults, maybe just add more exclamations because I'm super excited!
- You can also change the Button text & link here.
- OK, click the Continue button - and we're to the LS Order page.
- But if you return to the website - we still have products in the cart - we need
  to clear the cart after the purchase.
- Let's create a new action `OrderController::success()`
- Register the route as `#[Route('/checkout/success', name: 'app_order_success')]`
- To avoid direct access to this page - let's do a little trick.
- Inject `Request $request`.
- Inside, add `$referer = $request->headers->get('referer');`.
- Then `$lsStoreUrl = 'https://squeeze-the-day.lemonsqueezy.com';`.
- Let's check now: `if (!str_starts_with($referer, $lsStoreUrl)) {`.
- If true `return $this->redirectToRoute('app_homepage');`.
- Can we make that URL dynamic? We can, LS has an API endpoint to fetch that store URL.
- Creat `LemonSqueezyApi::retrieveStoreUrl()`.
- Here's the API endpoint: https://docs.lemonsqueezy.com/api/stores/retrieve-store
- We will need LS Store ID for that, let's simplify getting it.
- Create `private function getStoreId(): string`.
- Inside `return $this->parameterBag->get('env(LEMON_SQUEEZY_STORE_ID)');`.
- Now use this method in `createCheckoutUrl()`.
- Back to `retrieveStoreUrl()`.
- Add `$lsStore = $this->request(Request::METHOD_GET, 'stores/' . $this->getStoreId());`.
- Finish with `return $lsStore['data']['attributes']['url'];`
- Back to `success()`.
- Replace hardcoded URL with `$lsStoreUrl = $lsApi->retrieveStoreUrl();`.
- Now inject `ShoppingCart $cart` dependency.
- Now add `if ($cart->isEmpty())` - return redirect to homepage again.
- Next add `$cart->clear();`
- You can render a separate success page if you want, but I will simplify and just
  add a flash message: `$this->addFlash('success', 'Thanks for your order!');`,
  and return redirect to homepage.
- Now in the `createCheckoutUrl()` API request, add:
  `$attributes['product_options']['redirect_url'] = $this->generateUrl('app_order_success', [], UrlGeneratorInterface::ABSOLUTE_URL),`
- Go checkout again, complete, here's the Confirmation modal, press Continue... 
  and te success flash message! And the cart is empty now.

### Centralize LS logic
- It would be great centralize all the LS logic into a separate service.
  First of all, we're going to add more requests to the LS API and it would
  be convenient to have everything LS API related in a separate class.
  But also it's the best practice that will allow us to keep our controllers
  thin and help to test that code easier.
- So let's extract LS API into a separate service for convenience - easier to test,
  easier to reuse, and easier to maintain!
- Create a new service: `App\Store\LemonSqueezyApi`.
- Now move there our `createCheckoutUrl()` method but make it public now.
- Make `$lsClient` and `$cart` as proper constructor dependencies - I
  will name it just `$client` for simplicity.
- But here we will need our trick with `#[Target('lemonSqueezyClient')]`.
- Then change vars to properties in `createCheckoutUrl()`.
- We also need a service to generate URLs.
- Inject it as: `UrlGeneratorInterface $urlGenerator`.
- Replace `$this->generateUrl()` with the service.
- Also, we need a service to get access to the parameters.
- Inject it as `ParameterBagInterface $parameterBag` 
- Replace `$this->getParameter()` with the service.
- Now back to `OrderController::checkout()` - drop current dependencies and
  inject `LemonSqueezyApi $lsApi` instead.
- Use the service: `$lsCheckoutUrl = $lsApi->createCheckoutUrl();`
- Make sure you can still checkout.

### Always use HTTPS
- Thanks to the way LS handles checkouts - you can see that card credentials
  are never sent to our server. Instead, they are sent directly to LS server.
  It means we do not save sensitive card credentials on our servers at all. Yay!
  But nevertheless this fact, you should always use HTTPS for your checkout.
  Wait! Actually, no, you should always use HTTPS on your whole website!
  That's a pretty standard lately and brings user security to the next level.

### TODO Sync User with LS Customer
- The problem is that when you create a Checkout - you can't specify the customer
  ID and LS will assign a customer to the order automatically behind the scene.
  On the one hand, it's convenient, but on the other hand - it complicates things
  because I cannot sync my user with LS customer easily.
- To overcome it, we need to listen to the LS webhooks and update our user
  with the LS customer ID. For this, we will need to pass custom metadata to LS
  when creating a Checkout. Ideally, we need to pass the user ID. Later, when
  we receive a webhook - we will take that user ID, find the corresponding
  user and set the LS customer ID on it.
- P.S. We also may want to listen to the `checkout:complete` JS event that will
  also give us a customer, and set the customer ID on the current user. It will
  be especially convenient locally when you don't want to set up webhooks.
  See https://chatgpt.com/c/672bd203-8160-8011-9d36-e17fef609fd1
- Actually, we can do both! The JS sync will simplify things locally as we will
  not have to configure webhooks locally for working customer syncronization.
- When we will have customer - we can render a link to the LS Orders list page.

### Listen to Webhooks
- So let's go with webhooks first. There's a dev guide about it:
  https://docs.lemonsqueezy.com/guides/developer-guide/webhooks
- There're only few events about Orders, and to sync customer with app user
  we need to listen to the `order_created` event.
- Let's create a `WebhookController`, extend `AbstractController`.
- Add base route about it: `#[Route('/webhook')]`.
- Now create `lemonSqueezy(): Response` action.
- Declare route as `#[Route('/lemon-squeezy', name: 'app_webhook_lemon_squeezy')]`.
- Inside, let's just `throw new \Exception('Not implemented yet!');`
- Go to Settings > Webhooks > +:
  https://app.lemonsqueezy.com/settings/webhooks
- For callback we need to set https://127.0.0.1:8000/webhook/lemon-squeezy - but
  it will not work as it's not a public URL!
- Thankfully, there's a solution. We can use a tool like Ngrok to create a tunnel
  to our local server. It will give us a public URL that will redirect to our
  local server. It's super easy to use, just download it, run it, and it will
  give you a public URL that you can use in LS settings.
- I already have it installed. If you don't - go to the https://download.ngrok.com/
  and install it for your OS.
- To start it, in the console, run `ngrok http 8000` - 8000 is the port that
  should match your local website address: https://127.0.0.1:8000/ - if you have
  a different port - use that instead.
- Now it says something like:
  `Forwarding https://fa09-89-64-51-130.ngrok-free.app -> http://localhost:8000`
- If you open that https://fa09-89-64-51-130.ngrok-free.app - you will see our
  website under a public URL!
- Copy that URL and paste it to the LS webhook callback URL, but add:
  https://fa09-89-64-51-130.ngrok-free.app/webhook/lemon-squeezy
- For Signing secret - generate a random string. You can save it as env var,
  but I will simplify it and hardcode in the `WebhookController` as a private const.
- Add `WEBHOOK_SECRETLEMON_SQUEEZY_WEBHOOK_SECRET = 'lEm0n-5qUeEzY';` - I will use
  some numbers and register, but I bet you can have a better randomness with
  any password generation tool.
- And of course you want to keep it secret!
- Now cope that and paste into the Signing secret.
- For events - select `order_created` and save.
- Perfect, if you open the console tab where we ran Ngrok - you will see the
  another local URL for Web Interface http://127.0.0.1:4040 - open it!
- Welcome to the Ngrok Web Interface! Here you can see all the requests that
  are coming to your public URL. It's super useful for debugging and seeing
  what's going on.
- Now let's simulate a webhook. We can definitely go through the checkout again,
  but we can do much simpler! LS can "simulate" webhooks for dev purposes.
- Open the URL to see it works:
  https://fa09-89-64-51-130.ngrok-free.app/webhook/lemon-squeezy
- Now open the Ngrok inspector: http://127.0.0.1:4040/inspect/http
- Here's our request! We can even see the response - yeah, that's not implemented yet.
- But before start implementing that, let's trigger a real webhook. Go checkout
  a new order.
- Success!
- Back to the Ngrok inspector - wow, there are a few new requests! Actually if we
  wait for a few minutes - we will see 3 failed requests. It was the same request
  that LS tried to deliver a few times. Every time our server do not response
  with a successful status code - LS will try to deliver it again:
  in 5 seconds, 25 seconds, then 125 seconds. If it fails 3 times - LS will
  give up and we will have to retry to manually from the LS dashboard:
- Here's one failed: https://app.lemonsqueezy.com/settings/webhooks - and here's
  the Resend button. We can even see the response body here - so convenient.
- Now let's focus on implementing it.
- First of all, all webhooks are sent as JSON with POST request - let's fix it
  in the route:
  `#[Route('/lemon-squeezy', name: 'app_webhook_lemon_squeezy', methods: ['POST'])]`
- Now drop throwing the exception.
- Let's inject `lemonSqueezy(Request $request)`.
- Inside, add: `$payload = $request->getContent();`.
- Compute hash of the payload: `$hash = hash_hmac('sha256', $payload, self::LEMON_SQUEEZY_WEBHOOK_SECRET);`.
- Fetch request signature: `$signature = $request->headers->get('X-Signature', '');`.
- Check for match: `if (!hash_equals($hash, $signature))` and `throw new \Exception('Invalid LemonSqueezy signature!');`.
- Get the response data: `$data = $request->toArray();`
- Get event name: `$eventName = $data['meta']['event_name'];`.
- Add a switch-case: `switch ($eventName)`.
- Inside: `case 'order_created': break;`.
- Let's also add: `default: throw new \Exception(sprintf('Unsupported LemonSqueezy event: "%s"', $eventName));`.
- Inside the case, get customer ID: `$customerId = $data['data']['attributes']['customer_id'];`.
- And we need to set it on the user. But `$this->getUser()` will not work, we're
  handling a webhook, i.e. a completely separate request that does not have access
  to the session of the user who made this order. We need to somehow find the
  corresponding user. Thankfully, LS allow us add custom data on a Checkout creation.
- Go to the `createCheckout()`.
- Add: `$attributes['checkout_data']['custom']['user_id'] = $user->getId();`.
- From now on we also need to require the user to be logged in to checkout
  because from now on we need to link the corresponding LS customer to it.
- Make it required in the method signature: `createCheckout(User $user)`.
- We don't need that `if ($user)` anymore.
- In the `OrderController::checkout()`, right in the beginning, add:
  `$this->denyAccessUnlessGranted(AuthenticatedVoter::IS_AUTHENTICATED);`.
- This should be enough, but PhpStorm is not happy at this line:
  `$lsCheckout = $lsApi->createCheckout($user);`.
- That's why above add: `if (!$user instanceof User)`.
- And inside: `throw $this->createAccessDeniedException('You must be logged in to checkout!');`.
- Just a sanity check, but it will help to suppress that warning.
- Back to the webhook, above `switch`, add:
 `$userId = $data['meta']['custom_data']['user_id'] ?? null;`.
- Then: `if (!$userId)`.
- Inside: `throw new \Exception(sprintf('User ID not found in LemonSqueezy webhook "%s"!', $webhookId));`.
- Above add that var name: `$webhookId = $data['meta']['webhook_id'];`.
- Inject `EntityManagerInterface $entityManager`.
- Fetch the user: `$user = $entityManager->getRepository(User::class)->find($userId);`.
- Next add: `if (!$user)`.
- Inside: `throw new \Exception(sprintf('User "%s" not found for LemonSqueezy webhook "%s"!', $userId, $webhookId));`.
- Now we have the User, but we also need a new property on it.
- Add: `private ?string $lsCustomerId = null;`.
- Map it as: `#[ORM\Column(length: 255, unique: true, nullable: true)]`.
- Add setters and getters.
- Create a migration and migrate.
- Finally, inside the case: `$user->setLsCustomerId($customerId);`.
- After the switch, save everything: `$entityManager->flush();`
- And return 200 response: `return new Response('Webhook successfully handled!');`
- Time to retry the webhook, and we can do it either in LS dashboard, or directly
  in the Ngrok inspector - I love Ngrok!
- Ah, the error! Well, that makes sense, that webhook does not have a user ID set
  on custom data.
- OK, let's checkout again.
- Ah, an error. The custom data should be string.
- Typecast user ID to string: `$attributes['checkout_data']['custom']['user_id'] = (string)$user->getId();`.
- Try again - now success!
- Check the webhook - now we have the user ID.
- Check the DB: `bin/console doctrine:query:sql "SELECT * FROM user WHERE id = 3"`.
- Now the user have customer ID set and we can leverage it in our app to show
  the link to orders list.

### Render Link to LS Orders
- How your customers can see their orders? Let's search for my orders:
  https://docs.lemonsqueezy.com/help/online-store/my-orders
 - So we need to render a link to the LS Orders page which is:
  https://app.lemonsqueezy.com/my-orders
- Open `account.html.twig` and add a link:
  `<a href="https://app.lemonsqueezy.com/my-orders">My Orders</a>`.
- But we want to show it only if customers ever bought something on our website.
  how can we know? The lsCustomerID field! If it's set - then customer made an
  order, then show the link.
- Wrap the link with `{% if app.user.lsCustomerId %}`.
- I will also add `target="_blank"` for this link.
? Well, seems this page is empty, I suppose we need to activate the store first,
  or maybe it will work only in live mode?

### Better API Error Handling
- Let's temporary remove the `(string)` typecasting for user ID in `createCheckout()`
- Try to checkout - an error!
- But when we do a bad request - it throws a `ClientException`. But it hides the
  actual error from us. Let's improve the error so we could see what went wrong
  without needing to uncomment that `dd($response->getContent(false));` line.
- Let's create a request method that will match the signature of client's `request()`:
  `private function request(string $method, string $url, array $options = []): array`.
- Inside add `try-catch (ClientException $e)`.
- Inside try: `$response = $this->client->request($method, $url, $options);`.
- And `$data = $response->toArray();`.
- At the very end: `return $data;`.
- Inside catch, we can get the response from the exception and convert it to an array
  without throwing: `$data = $e->getResponse()->toArray(false);`.
- And just add `dd($errorsData)` for now.
- Now we just need to make request with simple `$this->request()`.
- And we can immediately return the result which is already an array of data.
  `return $this->request(Request::METHOD_POST, 'checkouts', [...])`.
- Now try to checkout - an error! And here's our dump.
- So we have an array of errors, I will simplify things (I think most of the time
  we will have only one error).
- I will start as: `$mainErrorMessage = 'LS API Error:';`.
- Then `$error = $data['errors'][0] ?? null;`.
- So `if ($error)`.
- Next I will be extra safe and add `if (isset($error['status']))`.
- Then `$mainErrorMessage .= ' ' . $error['status'];`.
- Let's do the same for `title` and `detail`.
- And finally add `if (isset($error['source']['pointer']))`.
- Then `$mainErrorMessage .= sprintf(' (at path "%s")', $error['source']['pointer']);`.
- Else, if somehow we have no errors but it still a client error - let's print
  the whole error content at least: `$mainErrorMessage .= $e->getResponse()->getContent(false);`.
- Finally, `throw new \Exception($mainErrorMessage, 0, $e);`
- Go try it now - much better! A lot of context is displayed on the error page.
- Go add that `(string)` typecasting for user ID again.

### LS Checkout Overlay
- In `cart.html.twig`, add `{% block javascripts %}`.
- Inside: `<script src="https://app.lemonsqueezy.com/js/lemon.js" defer></script>`.
- Recommendation from the LS:
  > Please don’t self-host Lemon.js, you may miss out on new features and important security patches.
- So better to link to it directly, the payment stuff is important from the
  security perspective.
- Don't forget to `{{ parent() }}` call.
- Now let's add `lemonsqueezy-button` CSS class to the Checkout link.
- Refresh the cart page and try.
- It's loading, but then 404.
- Yeah, that's because we have a special endpoint that redirects to the actual
  LS checkout URL.
- We can temporarily `dd($lsCheckoutUrl)`, copy it, and use in that link.
- But we can do better.
- Let's create `lemon-squeezy_controller.js`.
- Add `openOverlay()` to it.
- Back to `cart.html.twig`, add `data-controller="lemon-squeezy"` to the LS
  checkout link.
- And: `data-action="lemon-squeezy#openOverlay"`.
- We will also need to pass the LS checkout URL, but I don't want to create it
  every time the cart page is load, I want it to do on the link click.
- For this let's add a new action to the controller.
- I will copy the `checkout()` action and duplicate it as `createCheckout()`.
- Change route to: `#[Route('/checkout/create', name: 'app_order_checkout_create', methods: ['POST'])]`.
- Inside, keep checking that user is logged in.
- But then just `return $this->json(['targetUrl' => $lsApi->createCheckoutUrl($user)]);`.
- Back to the template, add Stimulus value: `data-lemon-squeezy-checkout-create-url-value="{{ path('app_order_checkout_create') }}"`.
- Let's also replace `href="{{ path('app_order_checkout') }}"` with `href="#"` if you
  want, but I will keep it.
- In Stimulus controller - add `static values = { checkoutCreateUrl: String, };`
- Back in `openOverlay()`.
- Inside, add: `e.preventDefault();`.
- Then: `const linkEl = e.currentTarget;`.
- Next: `fetch(this.checkoutCreateUrlValue, {`.
- With `method: 'POST',`.
- And `headers: { 'Content-Type': 'application/json', },`.
- Now add `.then(response => {`.
- And `if (!response.ok)` - `throw new Error("Network response was not ok " + response.statusText);`.
- Next just `return response.json();`.
- One more `.then(data => {`.
- Inside: `window.LemonSqueezy.Url.Open(data.targetUrl);`.
- And let's add `.catch(error => {`.
- With `console.error('Fetch error:', error);` inside.
- We also want to disable the link to avoid double-clicks.
- Create `#disableLink(link) {`.
- With `link.classList.add('disabled');`.
- And `link.style.pointerEvents = 'none';`.
- And `link.style.opacity = '0.5';`.
- Below, create `#enableLink(link) {`.
- With `link.classList.remove('disabled');`.
- And `link.style.pointerEvents = 'auto';`.
- And `link.style.opacity = '1';`.
- Now right after `const linkEl`, add `this.#disableLink(linkEl);`.
- After we open the URL, add `this.#enableLink(linkEl);`.
- And also add it in the `catch` too.
- Now try to checkout
- Go checkout, it loads, and the LS checkout page is opened.
- But if you look closer to the URL in the address bar - you will see that
  it's our https://127.0.0.1:8000/cart
- So it kinda works!
- But to make it better, open the `LSAPI::createCheckout()`.
- Add: `$attributes['checkout_options']['embed'] = true;`
- Go checkout again - now it's a real overlay that we can even close.
- Let's do not hardcode it.
- In `createCheckout()`, add a new argument to the method: `bool $embed = false`.
- Use it inside: `$attributes['checkout_options']['embed'] = $embed;`.
- The same in `createCheckoutUrl()`: `bool $embed = false`.
- Now in `OrderController::createCheckout()`, pass `true` to the `createCheckout()`.
- Go checkout again to make sure everything still works.

- But we still have a problem for non-authed users.
- Log out, to something to the cart, and try to checkout - nothing happen.
- In the WDT we see that the request was redirected to the login page.
- TODO

### Dynamically include Lemon.js script
- So right now if we want our Stimulus controller to work properly - we should
  remember to include that LS `script`. Can we make it automatically included
  when we use our Stimulus controller? Yes, we can!
- Add `connect()`.
- Let's check if there's no LS script tag yet:
  `let script = window.document.querySelector('script[src="https://assets.lemonsqueezy.com/lemon.js"]');`.
- Then `if (!script)`.
- Create script tag: `script = window.document.createElement('script');`.
- Set `script.src = 'https://app.lemonsqueezy.com/js/lemon.js';`.
- Set `script.defer = true;`.
- Set `window.document.head.appendChild(script);`.
- Done! Now celebrate by removing the whole javascript block from the `cart.html.twig`.
- Go checkout again - it still works!

### Listen to LS JS events
- So we have to configure webhooks locally every time we want to save corresponding
  LS customer ID to our user. But we can do it an alternative way  - listen to
  the LS JS events and set the customer ID on the successful checkout.
- And LS has a special event for this: `Checkout.Success`.
- At the end of `connect()`, add `window.LemonSqueezy.Setup({`.
- Then `eventHandler: (data) => {`.
- We can `console.log()` the `data` here, but there's already an example of the
  event data in the docs: https://docs.lemonsqueezy.com/guides/developer-guide/lemonjs.
- And let's filter for the event we needed: `if (data.event === 'Checkout.Success') {`.
- Get customer ID: `const lsCustomerId = data.data.customer_id;`.
- And next `this.#handleCheckout(lsCustomerId);`.
- Now create that method `#handleCheckout(lsCustomerId) {`.
- Now we need to add an endpoint to save the LS customer ID to the current user.
- Open OrderController and add a new action: `handleCheckout()`.
- Route it as: `#[Route('/checkout/handle', name: 'app_order_checkout_handle', methods: ['POST'])]`.
- Inject `#[CurrentUser] ?User $user,`
- Inside let's keep our `if (!$user instanceof User) { throw $this->createAccessDeniedException`.
- Inject `Request $request,`.
- Fetch the ID: `$lsCustomerId = $request->request->get('lsCustomerId');`.
- Set it on the current: `$user->setLsCustomerId($lsCustomerId);`.
- Inject `EntityManagerInterface $entityManager,`.
- Save the changes: `$entityManager->flush();`.
- It's enough to return a successful response: `return $this->json([]);`.
- Back to `#handleCheckout`.
- Let's `fetch(this.checkoutHandleUrlValue, {`.
- Use `method: 'POST',`
- For headers I will use `'Content-Type': 'application/x-www-form-urlencoded',` because
  we use `$request->request->get()`.
- And for body: `new URLSearchParams({ lsCustomerId: lsCustomerId, }),`.
- Below `.then(response => {`.
- Inside `if (!response.ok) { throw new Error("...`.
- And `return response.json();`
- Add one more `.then(data => {`.
- But inside I will just leave a comment: `// Nothing to do`.
- Finally finish with our `.catch(error => { console.error('...`.
- Now go refresh the cart page.
- Ah, an error... We start using LS faster than it's downloaded.
- Let's wrap our code in `script.addEventListener('load', () => {`.
- Refresh cart page again - another error!
- See https://docs.lemonsqueezy.com/help/lemonjs/using-with-frameworks-libraries#re-initialize-button-listeners
- So we need to manually initiate the LS object.
- Before `Setup()` call add `window.createLemonSqueezy();`.
- Try again! Yes, no errors in the console.
- Try to checkout and check the DB - what? Customer has undefined value?
- Hm, this sounds like we're using an undefined property there.
- OK, let's `console.log(data);` in the `Checkout.Success` event.
- Checkout again - aha, seems docs mismatch the returning object.
- OK, let's fix the path to `const lsCustomerId = data.data.order.data.attributes.customer_id;`.
- I will delete the `console.log()`.
- Try one more time - yes, success!
- Check the DB - here's out customer ID.
- So even though we didn't have webhooks configured via Ngrok, we still were able
  to sync the customer with user via JS webhook - that's perfect for local
  development and testing.

### Security vulnerability
- Open `OrderController::handleCheckout()`.
- If you think about this - you may see a possible security problem.
- For example, some tricky users may try to send an AJAX request to this endpoint
  with a different `lsCustomerId` to override their own customer.
- It may lead to a situation where our app will generate a signed URL for that
  customer and give it to the attacker so that they can view personal information
  or even do some changes on behalf of the customer.
- There're a few way to solve this problem.
- For example, you can use this customer sync via JS LS event only in dev mode,
  i.e. real users will be synced only via webhooks with signed signature.
- Or we can add some extra checks to the `handleCheckout()` method.
- For example, we can check if the current user ID matches the user ID set in the
  custom data of the LS event.
- Let's do this way.
- Inside `Checkout.Success`, you can `console.log(data)` again to see the structure.
- Or just believe me and write: `const userId = data.data.order.meta.custom_data.user_id;`.
- Then pass add it to the method: `this.#handleCheckout(userId, lsCustomerId);`.
- Inside `#handleCheckout()`, pass `userId: userId,` in the request too.
- And in `OrderController::handleCheckout()`.
- Add `$userId = $request->request->get('userId');`.
- Then add check: `if ($userId !== (string) $user->getId()) {`.
- Let's throw so we could see it in logs:
  `throw $this->createAccessDeniedException(sprintf('Current user ID "%s" does not match the user ID "%s" of the order!', $user->getId(), $userId));`
- Now it's safe to set the customer as we know for sure it relates to the current user.
- Go to the DB, set customer ID to null, no Ngrok tunnel running.
- Checkout, check the DB - here's our customer ID again, it works!

### Testing webhooks
