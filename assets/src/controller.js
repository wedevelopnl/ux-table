import { Controller } from '@hotwired/stimulus';
import { useDebounce } from "stimulus-use";

export default class extends Controller {
    static debounces = ['search']

    connect() {
        useDebounce(this);
    }

    search(e) {
        e.target.form.requestSubmit();
    }
}
