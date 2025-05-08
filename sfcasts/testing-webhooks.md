# Writing an Integrational Test for Webhooks

In our last video, we set up a webhook that saves the customer ID of the
user who placed an order. Webhooks are both incredibly useful and slightly
terrifying! They are a key component in many contemporary web applications,
particularly in e-commerce. If they go awry, it could mean lost sales or
broken features.

That's why we want to make sure they're working right by
testing them. As developers, we're not big fans of manual testing — we
prefer automation. If you're fresh to testing in Symfony or PHP, fear not,
we have ample courses related to testing that guide you through everything
from basic unit testing to full browser testing. Take a look at our [Testing
track](https://symfonycasts.com/tracks/testing) on SymfonyCasts.

To begin, install the Symfony test tools, go in console and run:

```terminal
composer require test --dev
```

This gives you access to PHPUnit and a bunch of handy tools right off the
bat. It would be ideal to unit-test the request parser and consumer with
PHPUnit, but since we're not focusing on testing in this course, consider
that as your homework.

***SEEALSO
If you're new to PHPUnit, do check out our
[intro PHPUnit course](https://symfonycasts.com/screencast/phpunit)
on SymfonyCasts.
***

For now, let's dive into something a bit more complex and test a webhook
integration. Our task is to write a full integration test for the
order-created webhook we implemented previously. 

## Generating a New Test

Start with generating a new test. This is where the MakerBundle proves
useful. In your terminal, run:

```terminal
bin/console make:test
```

Opt for the `WebTestCase` and name your test as `Webhook\LemonSqueezyRequestParser`.
The MakerBundle will churn out a file with some boilerplate code.
Open it up under from `tests/Webhook/LemonSqueezyRequestParserTest.php`.
Rename the default test method to something more descriptive, like
`testOrderCreatedWebhook()`. For now, retain the line that verifies
the response is successful, but tweak the error message to `Webhook failed!`.
Let's try running this in the terminal. Type

```terminal
bin/phpunit
```

And hit `Enter`. You'll come across an error, which is expected. It says

> No such table in the test environment. 

## Fixing the Error

Our test is failing because we need to set up a test database. You could do this
manually using Doctrine console commands, but let's take advantage of the
[Zenstruck Foundry](https://github.com/zenstruck/foundry) we already have
installed to reset and manage the test database automatically.

In your test class, add `use ResetDatabase`. This also
takes care of cleaning up the database between tests, so no worries about
duplicate email errors. Try running the test again, and this time it
passes. Great! Now, let's write an actual test.

## Creating Dummy Data

We need some dummy data for testing. Let's use Foundry again to create a
user. The `static::createClient()` call boots the Symfony kernel. So it's
safe user `UserFactory` right below it:

`$user = UserFactory::new()->create();`, and pass `[ 'email' => 'test@example.com',
'plainPassword' => 'testpass', 'firstName' => 'Test', ]`.

Next, let's simulate an actual `POST` request to the webhook endpoint:

`$client->request('POST', '/webhook/lemon-squeezy', [], [], [], $json);`

For the json payload, we can copy it from the Ngrok Web Interface if you still
have that running, or I will copy it from the LemonSqueezy dashboard > "Webhooks"
page. Back to the PhpUnit. To keep things organized, created a new file in your
`tests/fixtures/` - I will create a new file `order_created.json` and paste there.

Back to your test, above the request, let's create this:
`$json = file_get_contents(__DIR__.'/../fixtures/order_created.json')`.

At the ned, let's add more asserts. Add `$this->assertNotNull()`. Aha, I forgot
to create `$user` var above - let me fix it. And now pass `$user->getCustomerId()`
as the argument to our new assert call. And for the error message set
"LemonSqueezy customer ID not set!". And next, add
`$this->assertEquals(1000001, $user->getCustomerId(), 'LemonSqueezy customer ID mismatch!');`

Testing time! In your terminal, run the test again:

```terminal-silent
bin/phpunit
```

An error... if you scroll a little bit up - you will see the exception message:

> Invalid LemonSqueezy signature!

Whoops! That's expected. We created a payload string that seems a little bit
different from the original and so its signature is invalid anymore.

If you look into the request parser - yep we added that `verifySignature()` method
to prevent from unauthorized requests to that endpoint, but now we're those who
are trying to send a fake request there.

So, we could skip the signature check completely in this spot by injecting
the Symfony environment and add `if ($this-env === 'test')` then just return.

But I don't like this workaround, better solution would be to sign the
requests in your test.

I will copy this hash line, paste before the request call, and for the 
payload - use our `$json` var.  
For the secret, let's use `$_ENV['LEMON_SQUEEZY_SIGNING_SECRET']`. Then pass
this `$hash` to the fifth argument to the `request()` method. Inside the array:
`['HTTP_X-Signature' => $hash]` - Symfony converts that into header.

Run the test again. It may throw another error, but that's a different error
that is a good sign. The payload we used contains some user ID and customer ID that should
be dynamic and match our dynamic test data. Update your
`order_created.json` file with some placeholders. For user ID value, use
`%user_id%`, and for customer ID value - `%customer_id%`. Then, let's replace
the placeholders in the test by processing the `$json` variable again:
`$json = strtr($json, []);` And pass the array as ['%user_id%' => $user->getId(),
'%customer_id%' => 1000001]`

Run your tests again. This time, it should pass... and it passed!

Congratulations! You've just completed a full integration test for a webhook.
You created a real user, simulated a real webhook request, and verified your
application handles it all correctly.

Up next, we'll utilize our new customer relation on the user entity and
display user orders in the user account section.
