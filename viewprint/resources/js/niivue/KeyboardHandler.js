/**
 * KeyboardHandler - Manages keyboard shortcuts for ViewPrint
 *
 * Handles all keyboard interactions including:
 * - Number keys (1-9) for layer visibility
 * - R for reset view
 * - Space for play/pause animations
 * - Other viewer controls
 */

import hotkeys from 'hotkeys-js';

export class KeyboardHandler {
    /**
     * Create a new KeyboardHandler instance
     * @param {WorkspaceNiivueManager} manager - The main manager instance
     */
    constructor(manager) {
        this.manager = manager;
        this.enabled = true;

        // Configure hotkeys
        hotkeys.filter = function(event) {
            // Allow shortcuts when not in input fields
            const target = event.target || event.srcElement;
            const tagName = target.tagName;

            // Don't trigger shortcuts in input fields unless explicitly allowed
            return !(tagName === 'INPUT' ||
                tagName === 'SELECT' ||
                tagName === 'TEXTAREA' ||
                target.contentEditable === 'true');
        };

        this.registerShortcuts();
    }

    /**
     * Register all keyboard shortcuts
     * @private
     */
    registerShortcuts() {
        // Number keys 1-9 for layer visibility
        for (let i = 1; i <= 9; i++) {
            hotkeys(i.toString(), (event, handler) => {
                event.preventDefault();
                this.handleLayerToggle(i);
            });
        }

        // View controls
        hotkeys('r', (event, handler) => {
            event.preventDefault();
            this.handleResetView();
        });

        // Space for future animation controls
        hotkeys('space', (event, handler) => {
            event.preventDefault();
            this.handlePlayPause();
        });

        // Arrow keys for slice navigation
        hotkeys('up,down,left,right', (event, handler) => {
            event.preventDefault();
            this.handleSliceNavigation(handler.key);
        });

        // View mode shortcuts
        hotkeys('a', (event) => {
            event.preventDefault();
            this.setViewMode('axial');
        });

        hotkeys('c', (event) => {
            event.preventDefault();
            this.setViewMode('coronal');
        });

        hotkeys('s', (event) => {
            event.preventDefault();
            this.setViewMode('sagittal');
        });

        // Zoom controls
        hotkeys('=,+', (event) => {
            event.preventDefault();
            this.handleZoom(1.1);
        });

        hotkeys('-,_', (event) => {
            event.preventDefault();
            this.handleZoom(0.9);
        });

        // Opacity adjustment for active layer
        hotkeys('[', (event) => {
            event.preventDefault();
            this.adjustActiveLayerOpacity(-0.1);
        });

        hotkeys(']', (event) => {
            event.preventDefault();
            this.adjustActiveLayerOpacity(0.1);
        });

        // Help shortcut
        hotkeys('?', (event) => {
            event.preventDefault();
            this.showShortcutHelp();
        });

        console.log('Keyboard shortcuts registered');
    }

    /**
     * Handle layer visibility toggle
     * @private
     * @param {number} layerNumber - Layer number (1-9)
     */
    handleLayerToggle(layerNumber) {
        if (!this.enabled) return;

        console.log(`Toggle layer ${layerNumber}`);
        this.manager.toggleLayerByIndex(layerNumber);

        // Emit event for UI feedback
        this.manager.emit('shortcutUsed', {
            shortcut: layerNumber.toString(),
            action: 'toggleLayer',
            layerNumber: layerNumber
        });
    }

    /**
     * Handle view reset
     * @private
     */
    handleResetView() {
        if (!this.enabled) return;

        console.log('Reset view');
        this.manager.resetView();

        this.manager.emit('shortcutUsed', {
            shortcut: 'r',
            action: 'resetView'
        });
    }

    /**
     * Handle play/pause for animations (future feature)
     * @private
     */
    handlePlayPause() {
        if (!this.enabled) return;

        console.log('Play/pause animation');
        this.manager.emit('shortcutUsed', {
            shortcut: 'space',
            action: 'playPause'
        });
    }

    /**
     * Handle slice navigation with arrow keys
     * @private
     * @param {string} direction - Arrow key pressed
     */
    handleSliceNavigation(direction) {
        if (!this.enabled || !this.manager.nv) return;

        const step = 1;
        const pos = this.manager.nv.crosshairPos;

        switch(direction) {
            case 'up':
                // Move slice up
                this.manager.nv.moveCrossairInVox(0, step, 0);
                break;
            case 'down':
                // Move slice down
                this.manager.nv.moveCrossairInVox(0, -step, 0);
                break;
            case 'left':
                // Move slice left
                this.manager.nv.moveCrossairInVox(-step, 0, 0);
                break;
            case 'right':
                // Move slice right
                this.manager.nv.moveCrossairInVox(step, 0, 0);
                break;
        }

        this.manager.emit('shortcutUsed', {
            shortcut: direction,
            action: 'navigateSlice',
            direction: direction
        });
    }

    /**
     * Set view mode (axial, coronal, sagittal)
     * @private
     * @param {string} mode - View mode
     */
    setViewMode(mode) {
        if (!this.enabled || !this.manager.nv) return;

        const sliceTypes = {
            'axial': this.manager.nv.sliceTypeAxial,
            'coronal': this.manager.nv.sliceTypeCoronal,
            'sagittal': this.manager.nv.sliceTypeSagittal
        };

        if (sliceTypes[mode] !== undefined) {
            this.manager.nv.setSliceType(sliceTypes[mode]);

            this.manager.emit('shortcutUsed', {
                shortcut: mode[0],
                action: 'setViewMode',
                mode: mode
            });
        }
    }

    /**
     * Handle zoom in/out
     * @private
     * @param {number} factor - Zoom factor (>1 for zoom in, <1 for zoom out)
     */
    handleZoom(factor) {
        if (!this.enabled || !this.manager.nv) return;

        const currentScale = this.manager.nv.scene.scale;
        this.manager.nv.scene.scale = currentScale * factor;
        this.manager.nv.updateGLVolume();

        this.manager.emit('shortcutUsed', {
            shortcut: factor > 1 ? '+' : '-',
            action: 'zoom',
            factor: factor
        });
    }

    /**
     * Adjust opacity of the active/top layer
     * @private
     * @param {number} delta - Opacity change (-0.1 or 0.1)
     */
    adjustActiveLayerOpacity(delta) {
        if (!this.enabled) return;

        const layers = this.manager.layerManager.getAllLayers();
        if (layers.length === 0) return;

        // Get the top visible layer
        const topLayer = layers.reverse().find(l => l.visible);
        if (!topLayer) return;

        const newOpacity = Math.max(0, Math.min(1, topLayer.opacity + delta));
        this.manager.updateLayer(topLayer.id, { opacity: newOpacity });

        this.manager.emit('shortcutUsed', {
            shortcut: delta > 0 ? ']' : '[',
            action: 'adjustOpacity',
            layerId: topLayer.id,
            opacity: newOpacity
        });
    }

    /**
     * Show keyboard shortcut help
     * @private
     */
    showShortcutHelp() {
        const shortcuts = [
            { key: '1-9', action: 'Toggle layer visibility' },
            { key: 'R', action: 'Reset view' },
            { key: 'A/C/S', action: 'Axial/Coronal/Sagittal view' },
            { key: '↑↓←→', action: 'Navigate slices' },
            { key: '+/-', action: 'Zoom in/out' },
            { key: '[/]', action: 'Decrease/increase opacity' },
            { key: 'Space', action: 'Play/pause animation' },
            { key: '?', action: 'Show this help' }
        ];

        this.manager.emit('showHelp', { shortcuts });
    }

    /**
     * Enable keyboard shortcuts
     */
    enable() {
        this.enabled = true;
        console.log('Keyboard shortcuts enabled');
    }

    /**
     * Disable keyboard shortcuts
     */
    disable() {
        this.enabled = false;
        console.log('Keyboard shortcuts disabled');
    }

    /**
     * Clean up and remove all shortcuts
     */
    destroy() {
        // Unbind all shortcuts
        hotkeys.unbind();
        this.enabled = false;
        console.log('Keyboard shortcuts destroyed');
    }
}
