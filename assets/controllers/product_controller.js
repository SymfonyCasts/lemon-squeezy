import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['quantityInput']

    incrementQuantity() {
        const value = parseInt(this.quantityInputTarget.value);
        this.quantityInputTarget.value = value > 0 ? value + 1 : 1;
    }

    decrementQuantity() {
        const value = parseInt(this.quantityInputTarget.value);
        this.quantityInputTarget.value = value > 1 ? value - 1 : 1;
    }
}
