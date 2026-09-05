export class MockElement {
    constructor(tagName = 'div') {
        this.tagName = tagName.toUpperCase();
        this.attributes = {};
        this.children = [];
        this.style = {};
        this.listeners = {};
        this._textContent = '';
        this._innerHTML = '';
        this.value = '';
        this.type = '';
        this.required = false;
        this.disabled = false;
    }

    get id() { return this.attributes['id'] || ''; }
    set id(val) { this.attributes['id'] = String(val); }

    get className() { return this.attributes['class'] || ''; }
    set className(val) { this.attributes['class'] = String(val); }

    setAttribute(key, val) { this.attributes[key] = String(val); }
    getAttribute(key) { return this.attributes[key] !== undefined ? this.attributes[key] : null; }
    hasAttribute(key) { return key in this.attributes; }

    get textContent() {
        if (this.children.length > 0) {
            return this.children.map(c => c.textContent).join('');
        }
        return this._textContent;
    }

    set textContent(val) {
        this.children = [];
        this._textContent = String(val);
        this._innerHTML = String(val)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    get innerHTML() {
        if (this.children.length > 0) {
            return this.children.map(c => c.outerHTML || c.textContent || '').join('');
        }
        return this._innerHTML;
    }

    set innerHTML(val) {
        this.children = [];
        this._innerHTML = String(val);
        this._textContent = String(val).replace(/<[^>]*>/g, '');
    }

    get outerHTML() {
        if (this.tagName === '#TEXT') {
            return this._innerHTML || this._textContent;
        }
        const attrs = Object.entries(this.attributes)
            .map(([k, v]) => ` ${k}="${v}"`)
            .join('');
        return `<${this.tagName.toLowerCase()}${attrs}>${this.innerHTML}</${this.tagName.toLowerCase()}>`;
    }

    appendChild(child) {
        if (child instanceof MockElement) {
            this.children.push(child);
        }
        return child;
    }

    addEventListener(event, fn) {
        this.listeners[event] = this.listeners[event] || [];
        this.listeners[event].push(fn);
    }

    dispatchEvent(event) {
        const fns = this.listeners[event.type || event] || [];
        fns.forEach(fn => fn(event));
    }
}

export const mockDocument = {
    createElement(tag) { return new MockElement(tag); },
    createTextNode(text) {
        const el = new MockElement('#text');
        el.textContent = text;
        return el;
    }
};

globalThis.document = mockDocument;
globalThis.window = { silaoFrontendConfig: { restUrl: 'http://example.com/wp-json/silao/v1' } };