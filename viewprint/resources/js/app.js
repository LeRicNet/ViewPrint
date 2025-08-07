import './bootstrap';
import './viewprint-app';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import persist from '@alpinejs/persist';

// Alpine plugins
Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.plugin(persist);

// Make Alpine available globally
window.Alpine = Alpine;

// Development tools
if (import.meta.env.DEV) {
    // Import test utilities in development
    import('./niivue-test.js').then(module => {
        console.log('ViewPrint development mode enabled');
    });
}

// Initialize Alpine
Alpine.start();

// ViewPrint-specific initialization
document.addEventListener('DOMContentLoaded', () => {
    console.log('ViewPrint initialized');

    // Check for WebGL2 support
    const canvas = document.createElement('canvas');
    const gl = canvas.getContext('webgl2');

    if (!gl) {
        console.error('WebGL2 not supported. Niivue requires WebGL2.');
        if (window.Livewire) {
            Livewire.emit('webgl:not-supported');
        }
    }
});
