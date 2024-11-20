import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        checkoutCreateUrl: String,
        checkoutHandleUrl: String,
    };

    connect() {
        // Load the Lemon Squeezy script dynamically avoiding double loading
        let script = window.document.querySelector('script[src="https://app.lemonsqueezy.com/js/lemon.js"]');
        if (!script) {
            script = window.document.createElement('script');
            script.src = 'https://app.lemonsqueezy.com/js/lemon.js';
            script.defer = true;
            window.document.head.appendChild(script);
        }

        script.addEventListener('load', () => {
            // The script has loaded, now you can safely call createLemonSqueezy()
            window.createLemonSqueezy();

            window.LemonSqueezy.Setup({
                eventHandler: (data) => {
                    if (data.event === 'Checkout.Success') {
                        // console.log(data);
                        const userId = data.data.order.meta.custom_data.user_id;
                        const lsCustomerId = data.data.order.data.attributes.customer_id;
                        this.#handleCheckout(userId, lsCustomerId);
                    }
                },
            });
        });
    }

    openOverlay(e) {
        e.preventDefault();

        const linkEl = e.currentTarget;
        this.#disableLink(linkEl);

        fetch(this.checkoutCreateUrlValue, {
            method: 'POST',
            // redirect: 'manual',
            headers: {
                'Content-Type': 'application/json',
            },
        })
            .then(response => {
                console.log(window.location);
                // console.log(response);
                if (!response.ok) {
                    throw new Error("Network response was not ok " + response.statusText);
                }
                if (response.redirected) {
                    window.location.href = response.url+'?_target_path='+window.location.pathname;

                    // Stop further execution
                    return Promise.reject("User is not authenticated!");
                }

                return response.json();
            })
            .then(data => {
                window.LemonSqueezy.Url.Open(data.targetUrl);

                this.#enableLink(linkEl);
            })
            .catch(error => {
                console.error('Fetch error:', error);

                this.#enableLink(linkEl);
            });
    }

    #handleCheckout(userId, lsCustomerId) {
        fetch(this.checkoutHandleUrlValue, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                userId: userId,
                lsCustomerId: lsCustomerId,
            }),
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok " + response.statusText);
                }

                return response.json();
            })
            .then(data => {
                // Nothing to do
            })
            .catch(error => {
                console.error('Fetch error:', error);
            });
    }

    #disableLink(link) {
        link.classList.add('disabled');
        link.style.pointerEvents = 'none';
        link.style.opacity = '0.5';
    }

    #enableLink(link) {
        link.classList.remove('disabled');
        link.style.pointerEvents = 'auto';
        link.style.opacity = '1';
    }
}
