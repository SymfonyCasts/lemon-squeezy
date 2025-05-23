# Enhancing API Error Handling

Let's head over to the `src/Store/LemonSqueezyApi`. Now, you remember the
`createCheckoutUrl()` function, right? Temporarily comment out the type
cast into string for the user ID. This used to throw an error, remember?
So, save and head back to your browser. Click "add to
cart", then hit "Checkout with LemonSqueezy". You'll see an error page pops
up with a "general client exception` but not much else. 

Previously, we'd use the little trick of
`dd($response->getContent(false));` to see what's behind the general
client exception. Uncomment this and refresh the page - you should see the
actual error.

## Making Error Messages More Informative

Now, how about we try to make
this error message a bit friendlier? Instead of needing to use `dd()` to
debug, we can wrap the `client->request()` method in another method. Let's dive back
into our code and, at the bottom of this class, create a `private function`
called `request()`. This function will return an array and we'll need to
use the original client arguments. 

The first element will be `string $method`, then `string $url`, and an array
of options that are optional. Now we get to the fun part! Let's open a
`try-catch` block. In the `try`, write `$response =
$this->client->request();` and pass in all the
variables: method, URL and options. Create a `data` variable that is equal
to `$response->toArray()`. We will catch `ClientException $e` and inside perform our magic.

At the method end let's just add `return $data;`. In the
`catch`, we'll get the raw response content without throwing an exception.
Just say `data = $e->getResponse()->toArray();` and pass false as the
first argument. For now, let's just temporarily `dd($data);`. This will
show us the API error response.

Update the `createCheckoutUrl()` method where we were calling a request.
Instead of `$this->client->request()`, we'll call this internal `request()` passing all the
arguments as they are. Try to check out again, and boom! You should now see
a proper dump of the real API request as an array.

## Crafting Helpful Error Messages

Now, instead of dumping and dying,
let's craft some helpful error messages. Open the `request()` method in the
code. I'll comment out the `dd()` statement and start with a
`$mainErrorMessage = 'LS API Error:';`. First, let's check if we have an
error with `$error = $data['errors'][0] ?? null;`. If `error`, then
inside the `if`, let's do more checks.

Here's the plan: if `isset($error['status'])`, let's append it to
`$mainErrorMessage`. Do the same for `title`, `detail`, and
`source['pointer']`. Else, we'll just append the raw
content to `$mainErrorMessage`.

After this, let's throw a new exception with our crafted
`$mainErrorMessage`. That's it! 

So, we've just built a `$mainErrorMessage` based on the fields that we have or
else we just fall back to the raw content. And finally, we re-throw the
another exception with more context. Now, give it a whirl. 

Head over to the Checkout page, refresh, and voila! Instead of the
generic error, we have our custom error message now displaying:

`LS API Error: 422 status code: Unprocessable entity. {0} field must be a string
(at path data.attributes.checkout.data.custom.user_id)`. 

Much clearer, right? Now, all that's left is to return back the string type
casting on the user ID line. We no longer need the `response-toArray()`
line, so let's delete that along with the dump statement. Replace the
`$response` variable with `$lsCheckout` because we already have the array
of the Checkout object data here.

Now refresh the page to see it works. If there are any errors, our custom
error message will show up. The last step is to replace all remaining `$this->client->request()`
calls with `$this->request()`. I'll quickly do this for
`retrieveStoreUrl()` and `listOrders()`, and remove the `response->toArray()` calls. 

Now let's head over to the website again. The account page still works
and the checkout page too. And there we have it! A
more efficient and informative error handling process.
