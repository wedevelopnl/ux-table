import { Controller } from '@hotwired/stimulus';
import { useDebounce } from "stimulus-use";

export default class extends Controller {
    static debounces = ['search']

    activeElement;

    connect() {
        useDebounce(this);
    }

    search(e) {
        e.target.form.requestSubmit();
    }

    saveFocus() {
        this.activeElement = document.activeElement;
    }

    restoreFocus() {
        if (typeof this.activeElement === 'undefined') {
            return;
        }

        this.activeElement.focus();
        this.activeElement = null;
    }
}
