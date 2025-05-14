# Ngrok Tunnels

In the previous chapter, we got our hands dirty with the Symfony Webhook component. We created a unique endpoint to manage our webhooks, *but*... there's a catch - LemonSqueezy can't hit our local machine directly. Sounds like a problem, right? Worry not! We have a solution! We're going to use a clever tool called "Ngrok" to create a *tunnel* to our local machine. That'll give us a public URL will forward all the requests to our local machine. It's a *breeze* to use and evne though the free plan has some limitations, it's more than enough to get us started. Let's do it!

## Installing Ngrok

First things first: Install Ngrok to your operating system from the official website. I'm using a Mac, so I'll install it using the Homebrew package manager. I already have this installed, so I'll skip this step and fire it up from my terminal. When you're ready, run:

```terminal
ngrok http 8000
```

Since our local website address uses 127.0.0.1:8000, it's *crucial* that we use port 8000 here. If you're using a different port, use that one instead.

## Setting Up Ngrok

After we run that, down here, you'll see this "Forwarding" line with a random URL. *That's* what's forwarding requests to our http://localhost:8000. I'm on a Mac, so I'll hold "cmd" and click on the new URL to open it, and... tada! We're on our website! It's alive and kicking, and now it's under a public URL. How crazy is that?!

The Webhook component added a new `/webhook` route, which you can see in `/routes`. Since we created a LemonSqueezy webhook, it's added to the route as the webhook "type". The *final* URL will look something like this:

`0787-5-154-3-6.ngrok-free.app/webhook/lemon-squeezy`


This gives us an error, but we can ignore that, copy the URL, and paste it into the LemonSqueezy "Callback URL" field we saw earlier.

## Generating a Signing Secret

Okay, now we need something called a "signing secret", which is basically just a random string. We *could* create that directly in `webhook.yaml`, but to keep things nice and tidy, let's save it as an environment variable too. I'll use a mix of letters and numbers in varying cases for my secret. You can use any password generation tool to get a more random string. Once you've generated your secret, copy it. I'm okay with committing this to the repository, so I'll open my `.env` file and set it as the `LEMON_SQUEEZY_SIGNING_SECRET`. But remember, this is a *secret*, so pretend you never saw mine! Now, in the `webhook.yaml` file, set the secret to `%env(LEMON_SQUEEZY_SIGNING_SECRET)%` and wrap it in single quotes.

## Selecting Events and Saving the Webhook

Back on the dashboard, select the "order_created" event. Right now, that's the only one we're interested in. Click "Save Webhook" and... perfect! Our webhook configuration is ready!

## Using the Ngrok Web Interface

If you take at look at the terminal where we ran the Ngrok command, you'll notice *another* local URL for the "Web Interface". I'll hold "Cmd" and click on the URL to open it in a new tab. Welcome to the Ngrok web interface! Here, you can see all the requests coming to your public URL. This is *incredibly* useful for debugging and tracking what's happening with your webhooks.

## Triggering a Webhook

Now that we have this set up, let's *trigger* a webhook. Head back to our site using the local URL, select a lemonade (I'll choose watermelon this time) and add it to our cart. Click the "Checkout" button... fill in the payment information, billing address, and at the bottom, click "Pay" to complete the checkout. Success! LemonSqueezy *should* have sent us that webhook, so let's check it.

## Debugging and Handling Issues

Back in the Ngrok web inspector, you'll notice a few failed requests. And if we wait just a moment... we'll see *another* failed request. These are all the *same* request that LemonSqueezy tried and failed to deliver. Each time, we get a `406 Not Acceptable` status code, so LemonSqueezy thinks it wasn't processed correctly and tries again. Why's that happening?

Whenever our server doesn't respond with a *successful* status code, LemonSqueezy tries to deliver it again after 5 seconds, *then* 25 seconds, and *finally* after *125* seconds. If it fails *three* times, LemonSqueezy throws in the towel because it can't keep trying to deliver it forever. That means we need to retry it *manually* from the LemonSqueezy dashboard.

## Wrapping Up

Now that LemonSqueezy can chat with our local website in real-time, we're ready to implement some *real* webhook handling. Let's do that *next*.
