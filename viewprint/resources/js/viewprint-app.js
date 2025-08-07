/**
 * ViewPrint Application Entry Point
 *
 * This file initializes the ViewPrint Niivue components
 * and makes them available for use in Blade templates
 */

import { WorkspaceNiivueManager } from './niivue/index.js';

// Make the manager available globally for Alpine.js components
window.ViewPrint = {
    WorkspaceNiivueManager,

    // Factory method to create a new manager instance
    createManager(canvas, options = {}) {
        return new WorkspaceNiivueManager(canvas, options);
    },

    // Version info
    version: '1.0.0'
};

// Log successful initialization
console.log('ViewPrint loaded, version:', window.ViewPrint.version);
