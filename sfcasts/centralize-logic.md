# Chapter 8: Centralize LemonSqueezy Business Logic

So far, we've been focused on getting our checkout up and running, and we *nailed it*! But our code is kind of scattered around the controller. Wouldn't it be *much* more convenient to have everything related to LemonSqueezy's API in a separate class? *Of course* it would! So let's get to organizing!

We need to locate all of LemonSqueezy's code in the controller and move it into a separate service so it's easier to maintain, re-use, *and* test. To do that, in `src\Store`, create a new class called `LemonSqueezyApi`. Make this `final readonly`. Now we can move our `createLsCheckoutUrl()` method - I'll copy this big block and paste it in our new class - and *this* time, make it `public`. Since we *know* this is LemonSqueezy-related because it's in `LemonSqueezyApi`, we can just change the name to `createCheckoutUrl()` to keep it simple.

Next, let's grab `$lsClient` and `$cart`, and above, turn them into consructor dependencies with `public function __construct()`. Paste, and we'll also simplify `$lsClient` and just call it `$client`. Above this argument, add `#[Target('lemonSqueezyClient')]`, add `private` before each property, and finally, change this `$cart` variable to a property with `$this->cart`. We'll do the same thing for the remaining `$cart` variables. And while we're here, we'll also change `$lsClient` to `$this->client`. *Nice*.

Now we need a service to generate URLs. We can inject that into the constructor with `UrlGeneratorInterface $urlGenerator`. Then, replace `$this->generateUrl()` with `$this->urlGenerator->generate()`. We also need access to the parameters. We *could* inject the entire `ParameterBagInterface` service, which lets us access *any* parameter, but since we only need *one* - `storeId` - let's inject that *directly*.

In our constructor, add: `private readonly string $storeId,`. Above, add the PHP attribute with `#[Autowire('%env(LEMON_SQUEEZY_STORE_ID)%')]`. And finally, replace every instance of `$this->getParameter()` with `$this->storeId`. I only see it once here, so that's pretty easy.

Now, back in `OrderController::checkout()`, let's get rid of these unused dependencies and inject `LemonSqueezyApi $lsApi` instead. Below, *use* the service with `$lsCheckoutUrl = $lsApi->createCheckoutUrl();`.

Testing time! Let's make sure we can still checkout. On our site, reload, select "Classic Lemonade", add one to the cart, and click "Checkout with LemonSqueezy". *Yes*! We're on the LemonSqueezy checkout page and everything looks great!

Okay, now that we know the checkout's working, can we make `$lsStoreUrl` and the `success()` method dynamic? *Indeed*! And LemonSqueezy has an API endpoint for *just* such an occasion! In the API docs, find the "Retrieve a store" endpoint... and check out the example response on the right here. It *looks* like we can read the URL from `attributes`, so, back in our code, in `LemonSqueezyApi`, create a new public method. Call it `retrieveStoreUrl()`, and have it return a `string`. Inside, add `$response = $this->client->request(Request::METHOD_GET, 'stores/' . $this->storeId)`. Below, say `$lsStore = $response->toArray()` and finally, `return $lsStore['data']['attributes']['url']`.

Back in the `success()` method, inject `LemonSqueezyApi $lsApi,` and replace this hard-coded URL with `$lsStoreUrl = $lsApi->retrieveStoreUrl()`. Time for another test! Back on our site, pick one of our delicious lemonades - I'll choose apple this time - and add it to the cart. On the cart page, click the "Checkout" button again and... tada! It still works!

Next: Let’s assign a *LemonSqueezy* customer to the corresponding user in *our* system so we know which purchases they made.
