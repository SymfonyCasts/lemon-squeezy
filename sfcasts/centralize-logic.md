# Chapter 8: Centralize LemonSqueezy Business Logic

So far we were coding fast focusing on making our checkout working. And we nailed it. But our code is scattered in the controller. First of all, it would be convenient to have everything related to the LemonSqueezy API in one spot in a separate class. So let's extract LemonSqueezy code from the controller into a separate service for convenience - easier to maintain, easier to reuse, and easier to test!

For this, let’s create a new service: `App\Store\LemonSqueezyApi`.  I will make it final read only from scratch. Now move there our `createLsCheckoutUrl()` method but make it public now. I will rename it to just `createCheckoutUrl()` - anyway all the code in this class is related to LemonSqueezy.

Next, make `$lsClient` and `$cart` as proper constructor dependencies - I will name it just `$client` for simplicity too. But here we will need our trick with `#[Target('lemonSqueezyClient')]` above this argument. And at private before each property. Then change vars to properties in `createCheckoutUrl()`.

We also need a service to generate URLs. Inject it in the constructor as: `UrlGeneratorInterface $urlGenerator`. Replace `$this->generateUrl()` with `$this->urlGenerator->generate()` service.

Also, we need a way to get access to the parameters. We could inject the whole `ParameterBagInterface` service from which we can access any parameters, but since we only need `storeId` one - let's inject it directly.

In the constructor, add: `private readonly string $storeId,`. Above, add PHP attribute: `#[Autowire('%env(LEMON_SQUEEZY_STORE_ID)%')]`. Now replace `$this->getParameter()` with the `$this->storeId` in all places.

Now back to `OrderController::checkout()` - drop unused dependencies and inject `LemonSqueezyApi $lsApi` instead. Below, use the service: `$lsCheckoutUrl = $lsApi->createCheckoutUrl();`.

Make sure you can still checkout… Yes, we’re on the LemonSqueezy checkout page, everything looks good.

Now, can we make that `$lsStoreUrl` in `success()` URL dynamic? We can! LemonSqueezy has an API endpoint to fetch that store URL. In the API docs, find the [Retrieve a store](https://docs.lemonsqueezy.com/api/stores/retrieve-store) endpoint. If you will take a look at the example response - we can read the URL from `attributes`.

In `LemonSqueezyApi`, create a new public method, call it `retrieveStoreUrl()` - it will return `string`. Inside, add `$response = $this->client->request(Request::METHOD_GET, 'stores/' . $this->storeId);`. Below: `$lsStore = $response->toArray();`. And finish with `return $lsStore['data']['attributes']['url'];`.

Back to `success()`. Inject `LemonSqueezyApi $lsApi,`. Replace hardcoded URL with `$lsStoreUrl = $lsApi->retrieveStoreUrl();`. Make sure you can still checkout.

Next, let’s assign LemonSqueezy customer to the corresponding user in our system so that we could know which purchases they made.
