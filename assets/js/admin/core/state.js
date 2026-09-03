export class SilaoState {
    constructor(initialState = {}) {
        this.state = { ...initialState };
        this.listeners = [];
    }

    get() { return this.state; }

    set(newState) {
        this.state = { ...this.state, ...newState };
        this.listeners.forEach(fn => fn(this.state));
    }

    subscribe(fn) {
        this.listeners.push(fn);
        return () => { this.listeners = this.listeners.filter(l => l !== fn); };
    }
}