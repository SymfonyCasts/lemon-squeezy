# Writing an Integrational Test for Webhooks

Previously, we set up a webhook that saves the customer ID of the user who
placed an order. Webhooks are both incredibly useful and *slightly* terrifying.
They are a key component in many contemporary web applications, *particularly*
in e-commerce. If they fail, it *could* mean lost sales or broken features - bad
news for businesses big and small. That's why testing them is *super* important.

As developers, we're not big fans of *manual* testing — we prefer automation. If
you're new to testing in Symfony or PHP, fear not! We have a *ton* of courses
related to testing that will guide you through everything from basic unit
testing to full browser testing. For more on that, take a look at
our [Testing track](https://symfonycasts.com/tracks/testing). Now back to our webhook test!

First, we need to install the Symfony testing tools. At your terminal, run:

```terminal
composer require test --dev
```

This gives us access to PHPUnit *and* a bunch of handy tools right off the bat.
Ideally, we'd *unit test* the request parser *and* the consumer, but
since we're not focusing on testing in this course, just think of that as a fun
homework assignment.

***SEEALSO
If you're new to PHPUnit, check out our [intro to PHPUnit course](https://symfonycasts.com/screencast/phpunit).
***

For now, let's dive into something a bit more complex and test the webhook
integration. *Our* task is to write a full integration test for the
`order_created` webhook we implemented earlier.

## Generating a New Test

We'll start by generating a new test. This is where the MakerBundle shines. At
your terminal, run:

```terminal
bin/console make:test
```

Select `WebTestCase`... and we'll name our test
`Webhook\LemonSqueezyRequestParser`. MakerBundle will give us a file with some
boilerplate code. We can find it in
`tests/Webhook/LemonSqueezyRequestParserTest.php`. 

[[[ code('9531ba0023') ]]]

Rename the default test method to something more descriptive: `testOrderCreatedWebhook()`.

[[[ code('b03f76e573') ]]]

We'll keep the line that verifies the response is successful *for now*, but tweak the
error message to read `Webhook failed!`. Let's try it! Over in the terminal,
run:

```terminal
bin/phpunit
```

We got an error, but that's expected.

> No such table in the test environment.

## Fixing the Error

Our test is failing because we need to set up a test database. We
*could* do this manually using Doctrine console commands, but let's take
advantage of [Zenstruck Foundry](https://github.com/zenstruck/foundry), which we already have installed.

In the test class, add `use ResetDatabase`. This creates the test database
before the first test and resets it between tests. Without this, we'd get
lots of duplicate data errors. If we run the test again... it passes! Great!
Now, let's write the *actual* test.

[[[ code('8db07e97d6') ]]]

## Creating Dummy Data

To do that, we need some dummy data. Foundry can help with that too! This
`static::createClient()` call boots the Symfony kernel, so it's safe to use
`UserFactory` right below it. Create a new user with
`UserFactory::new()->create()` passing an array:
`['email' => 'test@example.com', 'plainPassword' => 'testpass', 'firstName' => 'Test']`.

Now that we have a user, we need to simulate an actual `POST` request to the
webhook endpoint. We can do that with
`$client->request('POST', '/webhook/lemon-squeezy', [], [], [], $json)`.

[[[ code('17743cbcc1') ]]]

We can copy the JSON payload from the Ngrok web interface (if you still have
that running), *or* we can copy it from the LemonSqueezy dashboard under
"Webhooks". Copy the whole request body and, back in our code, in `tests/`,
create a new directory called `fixtures`. Inside, create a new
file. Call it `order_created.json`, and... *paste*.

[[[ code('5a25ffac8c') ]]]

In our test, above the request, write
`$json = file_get_contents(__DIR__.'/../fixtures/order_created.json')`. At the
end, add `$this->assertNotNull()` - and... whoops! I forgot to
create a `$user` variable above, so let's fix that. Now, pass
`$user->getLsCustomerId()` as the `assertNotNull()` argument, and for the
error message, write `LemonSqueezy customer ID not set!`. Finally, add
`$this->assertEquals(1000001, $user->getLsCustomerId(), 'LemonSqueezy customer ID mismatch!')`.
Phew! Testing time!

[[[ code('a756c613fb') ]]]

At your terminal, run the tests again:

```terminal-silent
bin/phpunit
```

An error! If you scroll up a little, you'll see the exception message:

> Invalid LemonSqueezy signature!

Whoops! Well, that's expected. We're sending a valid payload but not the
`X-Signature` header - so our request parser is telling us it's invalid.

If we take a look at the request parser... *yep*. We added this
`verifySignature()` method to *prevent* unauthorized requests to the endpoint.
But now *we're* the ones trying to send a fake request there! How the tables have
turned!

We could skip the signature check here *completely* by injecting the app
environment, adding `if ($this->env === 'test')`, and simply returning. But... I
don't like this workaround - it skips valuable logic I want to be tested.
A *better* solution would be to *sign* the requests in your test so they appear
to be legitimate.

Copy this hash line, and paste before the `request()` call, for the payload,
use our `$json` variable. For the secret, use
`$_ENV['LEMON_SQUEEZY_SIGNING_SECRET']` (we have access to all `.env` variables
via `$_ENV` in our test environment). For the 5th `request()` method
argument, expand this array to include `'HTTP_X-Signature' => $hash`.
Symfony will convert that into the `X-Signature` header needed by our parser.

[[[ code('d62a5ea60e') ]]]

Run the test again, and... *another* error, but this one's different. That's a
good sign! The payload we used contains a user ID and customer ID that should be
dynamic and *match* our dynamic test data. Let's update our `order_created.json`
file with some placeholders. For the `user_id`, use `%user_id%`, and for
the `customer_id`, use `%customer_id%`. 

[[[ code('0611073c72') ]]]

Finally, replace the placeholders
in our test by processing the `$json` variable again with
`$json = strtr($json)`, with the array
`['%user_id%' => $user->getId(), '%customer_id%' => 1000001]`.

[[[ code('b040dc6336') ]]]

Run the test again... this time... it *passes*!

Congratulations! You've just completed a full integration test for a webhook.
You created a user, simulated a real webhook request, and verified that your
application handles it all correctly. Nice work!

Up next, we'll utilize the new `lsCustomerId` on the user entity to display
their past orders.
