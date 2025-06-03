# Enhancing API Error Handling

Head over to `src/Store/LemonSqueezyApi.php`. You probably remember this
`createCheckoutUrl()` method from earlier. This cast to `string` fixed an error.
Remove it temporarily so we can bring that error back. Back in your browser,
click "Add to cart", then "Checkout with LemonSqueezy", and... we see our expected
`ClientException`.

Previously, we used this `dd($response->getContent(false))` trick to see the
details behind the `ClientException`. Uncomment this line, refresh the page,
and... now we can see the *actual* error.

## Making Error Messages More Informative

This is *okay*, but I bet we could make it even better. Instead of using `dd()`
to debug, let's try wrapping the `client->request()` method in *another* method.
At the bottom of this class, create a `private function` called `request()`
that returns an `array`.

Its *first* argument will be `string $method`, followed by `string $url` and an
array of options. Now *here's* the fun part: Open a `try-catch` block and, in
the `try`, write `$response = $this->client->request()` and pass in all the
variables - `$method`, `$url`, and `$options`. Create a `$data` variable that's
equal to `$response->toArray()`. We'll `catch` `ClientException $e` and inside,
perform some *magic*.

At the bottom, `return $data`, and back in the `catch`, we want the raw response
content, so write `$data = $e->getResponse()->toArray()` and pass `false` as
the first argument. We'll also add `dd($data)` here temporarily so we can see
the API error response.

Next, update the `createCheckoutUrl()` method. Instead of
`$this->client->request()`, use just `$this->request()`,
passing all the same arguments. If we head over and try to check out
again... *boom*! *This* is a proper dump of the *real* API request as an array.

## Crafting Helpful Error Messages

Okay, instead of "dumping and dying", let's craft some error messages that are
more helpful. In our code, find the `request()` method. Comment out this `dd()`
statement and below, add `$mainErrorMessage = 'LS API Error:'`. Now, let's check
if we have an error with `$error = $data['errors'][0] ?? null`. If there *is* an
`error`, do another check with `if (isset($error['status'])`. Inside,
write `$mainErrorMessage .= ' ' . $error['status']`. Do the same for `title`,
`detail`, and `source.pointer`. I'll speed through this part. Finally, `else`,
and inside, append the raw content with
`$mainErrorMessage .= $e->getResponse()->getContent(false)`. Perfect!

At the end, `throw new \Exception()` with `$mainErrorMessage`, `0`
for the second argument, and `$e` as the third argument. This sets the *original*
exception as the previous one, which further helps with debugging.
That's it! This is a fairly common and useful pattern for simplifying complex
exceptions but still providing a reference to the original.

Let's give it a try!

Over on the checkout page, refresh, and... *voila*! The *generic* error message
is now a *custom* message:

> LS API Error: 422 status code: Unprocessable entity. {0} field must be a
> string (at path data.attributes.checkout.data.custom.user_id).

That was *much* easier to understand.

All we need to do now is return the `string` typecasting on the `user_id` line.
We don't need this `$response->toArray()` line anymore, so we can delete that
along with the `dd()`. Also replace the `$response` variable with `$lsCheckout`,
since we already have an array of checkout object data here.

Refresh the page again to see if it works, and... we're good!

The final step is to replace all remaining `$this->client->request()` calls with
`$this->request()`. I'll speed through this for `retrieveStoreUrl()`,
`listOrders()`, and remove the `$response->toArray()` calls while I'm at it.

If we try our site one more time... the account page still works... and so does
the checkout page! Our error handling process is now efficient *and*
informative.

Next: Let's improve our customers' checkout experience by embedding the
LemonSqueezy checkout page in *our* app.
