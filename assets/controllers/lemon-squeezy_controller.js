import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
    }

    openOverlay(e) {
        e.preventDefault();

        const linkEl = e.currentTarget;
        this.#disableLink(linkEl);

        fetch(linkEl.dataset.targetUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok " + response.statusText);
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
