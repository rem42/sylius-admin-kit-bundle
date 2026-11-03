import { Controller } from '@hotwired/stimulus';

/*
 * Tags input of TagsType (rem42/sylius-admin-kit-bundle, block "rem42_admin_kit_tags_widget").
 *
 * Enter, a separator (see the "split-pattern" value) or leaving the input adds the typed tag; Backspace in an
 * empty input removes the last tag; pasting a list adds a tag per item. Tags that do not match the "tag-pattern"
 * value are refused and stay in the input with an error. The hidden input carries the submitted value: a JSON list.
 *
 * Classes: "hidden" hides the error message, "invalid" (optional) is set on the element while the input is invalid.
 */
export default class extends Controller {
    static targets = ['value', 'list', 'entry', 'template', 'tag', 'error'];
    static classes = ['hidden', 'invalid'];
    static values = {
        splitPattern: { type: String, default: '[,;\\n]+' },
        tagPattern: String,
        unique: { type: Boolean, default: true },
        removeLabel: { type: String, default: 'Remove %tag%' },
        invalidMessage: { type: String, default: '"%tag%" is not valid.' },
    };

    connect() {
        // Tags rendered after a failed submit: flag the ones that do not match.
        this.tagTargets.forEach((tag) => this.flag(tag));
    }

    focus(event) {
        if (event.target === this.element || event.target === this.listTarget) {
            this.entryTarget.focus();
        }
    }

    keydown(event) {
        if (event.isComposing) {
            return;
        }

        if (event.key === 'Enter' || (event.key.length === 1 && this.separator().test(event.key))) {
            event.preventDefault();
            this.commit();
        } else if (event.key === 'Backspace' && this.entryTarget.value === '' && this.tagTargets.length > 0) {
            event.preventDefault();
            this.removeTag(this.tagTargets[this.tagTargets.length - 1]);
        }
    }

    paste(event) {
        const text = event.clipboardData?.getData('text') ?? '';
        if (!this.separator().test(text.trim())) {
            return;
        }

        event.preventDefault();
        const rejected = this.split(text).filter((tag) => !this.add(tag));
        this.entryTarget.value = rejected.join(' ');
        this.showError(rejected);
    }

    commit() {
        const rejected = this.split(this.entryTarget.value).filter((tag) => !this.add(tag));
        this.entryTarget.value = rejected.join(' ');
        this.showError(rejected);
    }

    remove(event) {
        event.preventDefault();
        event.stopPropagation();
        this.removeTag(event.currentTarget.closest('[data-admin-kit-tag]'));
        this.entryTarget.focus();
    }

    clearError() {
        this.showError([]);
    }

    /** Adds a tag; a duplicate is ignored (counted as added), a tag not matching the pattern is refused. */
    add(tag) {
        tag = tag.trim();
        if (tag === '') {
            return true;
        }

        if (!this.matches(tag)) {
            return false;
        }

        if (this.uniqueValue && this.tags().some((existing) => existing.toLowerCase() === tag.toLowerCase())) {
            return true;
        }

        const element = this.templateTarget.content.firstElementChild.cloneNode(true);
        const label = this.removeLabelValue.replace('%tag%', tag);
        element.dataset.adminKitTag = tag;
        element.querySelector('[data-admin-kit-tag-label]').textContent = tag;
        element.querySelector('button').title = label;
        element.querySelector('button').setAttribute('aria-label', label);
        this.listTarget.append(element);
        this.sync();

        return true;
    }

    removeTag(tag) {
        tag?.remove();
        this.sync();
    }

    flag(tag) {
        if (this.matches(tag.dataset.adminKitTag)) {
            tag.removeAttribute('aria-invalid');
        } else {
            tag.setAttribute('aria-invalid', 'true');
        }
    }

    showError(rejected) {
        const invalid = rejected.length > 0;

        if (invalid) {
            this.entryTarget.setAttribute('aria-invalid', 'true');
        } else {
            this.entryTarget.removeAttribute('aria-invalid');
        }

        if (this.hasInvalidClass) {
            this.element.classList.toggle(this.invalidClass, invalid);
        }

        if (this.hasErrorTarget) {
            this.errorTarget.textContent = rejected.map((tag) => this.invalidMessageValue.replace('%tag%', tag)).join(' ');
            if (this.hasHiddenClass) {
                this.errorTarget.classList.toggle(this.hiddenClass, !invalid);
            }
        }
    }

    matches(tag) {
        return !this.hasTagPatternValue || this.tagPatternValue === '' || new RegExp(`^(?:${this.tagPatternValue})$`, 'u').test(tag);
    }

    separator() {
        return new RegExp(this.splitPatternValue, 'u');
    }

    split(text) {
        return text.split(new RegExp(this.splitPatternValue, 'u')).map((tag) => tag.trim()).filter((tag) => tag !== '');
    }

    tags() {
        return this.tagTargets.map((tag) => tag.dataset.adminKitTag);
    }

    sync() {
        this.valueTarget.value = JSON.stringify(this.tags());
        this.valueTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
