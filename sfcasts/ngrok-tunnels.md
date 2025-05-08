# Ngrok Tunnels

In the previous chapter, we got our hands dirty with the Symfony webhook
component. We created a unique endpoint to manage our webhooks. But there's
a catch. We can't just ask LemonSqueezy to hit our local machine directly.
Sounds like a problem, right? Don't worry, we've got a solution. We'll use
a clever tool called Ngrok to create a tunnel to our local machine. This
gives us a public URL that forwards all the requests to our local machine.
It's a breeze to use and even has a free plan with some limits, but that's
perfectly fine to get us rolling.

## Installing Ngrok

First things first, install Ngrok to your operating system from the
official website. I'm using a Mac, so I'll install it using the Brew
package manager. I've already got it installed, so I'll skip this step and
fire it up from my terminal. Here's the command to run:

```terminal
ngrok http 8000
```

Since we're using port 8000, make sure to use the same port here. It's
crucial that the port matches your local website address, which in my case
is 127.0.0.1:8000. If you're using a different port, use that one instead.

## Setting Up Ngrok

When you fire it up, in the output you'll see a line like "Forwarding" and
a random URL that forwards requests to our http://localhost:8000. If you open that
random URL (I know it looks a bit ugly, but bear with me, we can hold `Cmd` and
click), you'll see our website. It's alive and kicking, and now it's under
a public URL, how crazy is that?!

The webhook component added a new `/webhook` route. You can check it out in
the routes. Since we created a LemonSqueezy webhook, it's added to the
route as the webhook "type". The final URL will look something like this:
(open it to see an error). Here it is. Copy that URL and paste it into the
LemonSqueezy webhook callback URL field.

## Generating a Signing Secret

Next, you'll need a signing secret. This is just a random string. You can
create it right in `webhook.yaml`, but to keep things
neat and tidy, let's save it as an environment variable too. I'll use a mix
of letters and numbers in varying cases for my secret. You can use any
password generation tool to get a more random string. Once you've generated
your secret, copy it. I'm okay with committing this to the repository, so
I'll open my `.env` file and set it as the `LEMON_SQUEEZY_SIGNING_SECRET`. But
remember, this is a secret, so pretend you never saw mine! Now, in the
`webhook.yaml` file, set the secret to `%env(LEMON_SQUEEZY_SIGNING_SECRET)%`
and wrap it in a single quotes.

## Selecting Events and Saving the Webhook

Back on the dashboard, select the `order_created` event. Right now, that's
the only one we're interested in. Done? Perfect! Go ahead and save the
webhook. Now your webhook configuration is ready.

## Using the Ngrok Web Interface

If you open the console tab where we ran Ngrok command, you'll notice another
local URL for the so-called web interface. On a Mac, hold `Cmd` and click
on it to open in a new tab. Welcome to the Ngrok Web Interface! Here, you
can see all the requests coming to your public URL. It's incredibly useful
for debugging and tracking what's happening with your webhooks.

## Triggering a Webhook

Now, let's trigger a webhook. I'll go through the checkout process to make
an order. We don't need the public URL anymore, so we won't access it
directly to avoid unnecessary noise. Instead, I'll return to the local URL,
go to the homepage, add a product to the cart, and complete the checkout.
Success! With the order complete, LemonSqueezy should send us the webhook.

## Debugging and Handling Issues

If you return to the Ngrok Web Inspector, you'll notice a few requests.
And if we wait a few minutes, we will see one more failed request. This was the same
request payload that LemonSqueezy tried to deliver a few times but failed. Each
time, we respond with `406 Not Acceptable` status code, so LemonSqueezy thinks it
wasn't processed correctly and tries again. Let's find out what's happening
behind the scenes.

Whenever our server doesn't respond with a successful status code,
LemonSqueezy tries to deliver it again after 5 seconds, then 25 seconds,
and finally after 125 seconds. If it fails three times, LemonSqueezy throws
in the towel because it can't keep trying to deliver it indefinitely. We'll
then have to retry it manually from the LemonSqueezy dashboard.

## Wrapping Up

Now that LemonSqueezy can chat with our local website in real-time, we're
all set to implement the actual webhook handling in the next chapter. Stay
tuned!