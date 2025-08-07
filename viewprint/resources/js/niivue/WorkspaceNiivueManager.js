/**
 * WorkspaceNiivueManager - Main class for managing Niivue instance in ViewPrint
 *
 * This class handles:
 * - Niivue initialization and lifecycle
 * - Communication between Livewire and Niivue
 * - Event emission for state changes
 * - View state management
 */

import { Niivue } from '@niivue/niivue';
import { EventEmitter } from 'eventemitter3';
import { LayerManager } from './LayerManager.js';
import { KeyboardHandler } from './KeyboardHandler.js';

export class WorkspaceNiivueManager extends EventEmitter {
    /**
     * Create a new WorkspaceNiivueManager instance
     * @param {HTMLCanvasElement} canvas - The canvas element for Niivue
     * @param {Object} options - Configuration options
     * @param {Array} options.backColor - RGBA background color array [0-1]
     * @param {boolean} options.show3Dcrosshair - Show 3D crosshair
     * @param {Object} options.livewireComponent - Reference to Livewire component
     */
    constructor(canvas, options = {}) {
        super();

        this.canvas = canvas;
        this.options = {
            backColor: [0, 0, 0, 1],
            show3Dcrosshair: true,
            ...options
        };

        this.nv = null;
        this.isReady = false;
        this.layerManager = null;
        this.keyboardHandler = null;

        // Track current state
        this.state = {
            loading: false,
            error: null,
            viewState: null
        };

        this.initialize();
    }

    /**
     * Initialize Niivue and related components
     * @private
     */
    async initialize() {
        try {
            this.state.loading = true;
            this.emit('loading', { message: 'Initializing Niivue...' });

            // Create Niivue instance
            this.nv = new Niivue(this.options);
            await this.nv.attachToCanvas(this.canvas);

            // Initialize sub-components
            this.layerManager = new LayerManager(this.nv);
            this.keyboardHandler = new KeyboardHandler(this);

            // Set up event handlers
            this.setupEventHandlers();

            this.isReady = true;
            this.state.loading = false;
            this.emit('ready');

            console.log('WorkspaceNiivueManager initialized successfully');

        } catch (error) {
            this.state.error = error;
            this.state.loading = false;
            this.emit('error', {
                type: 'INIT_FAILED',
                message: 'Failed to initialize Niivue',
                error: error
            });
            console.error('WorkspaceNiivueManager initialization failed:', error);
        }
    }

    /**
     * Set up Niivue event handlers
     * @private
     */
    setupEventHandlers() {
        // Niivue events
        this.nv.onImageLoaded = (volume) => {
            console.log('Image loaded:', volume);
            this.emit('imageLoaded', { volume });
        };

        this.nv.onDraw = () => {
            // Debounced view state updates will go here
        };

        // Layer manager events
        this.layerManager.on('layerAdded', (layer) => {
            this.emit('layerAdded', layer);
        });

        this.layerManager.on('layerRemoved', (layerId) => {
            this.emit('layerRemoved', layerId);
        });

        this.layerManager.on('layerUpdated', (layer) => {
            this.emit('layerUpdated', layer);
        });

        this.layerManager.on('error', (error) => {
            this.emit('error', error);
        });
    }

    /**
     * Add a layer to the viewer
     * @param {Object} layer - Layer configuration
     * @param {number} layer.id - Unique layer ID
     * @param {string} layer.url - URL to NIfTI file
     * @param {string} layer.name - Display name
     * @param {string} layer.colormap - Colormap name
     * @param {number} layer.opacity - Opacity 0-1
     * @returns {Promise<void>}
     */
    async addLayer(layer) {
        if (!this.isReady) {
            throw new Error('Manager not initialized');
        }

        this.state.loading = true;
        this.emit('loading', { message: `Loading ${layer.name}...` });

        try {
            await this.layerManager.addLayer(layer);
            this.state.loading = false;
        } catch (error) {
            this.state.loading = false;
            throw error;
        }
    }

    /**
     * Remove a layer from the viewer
     * @param {number} layerId - Layer ID to remove
     */
    removeLayer(layerId) {
        if (!this.isReady) {
            throw new Error('Manager not initialized');
        }

        this.layerManager.removeLayer(layerId);
    }

    /**
     * Update layer properties
     * @param {number} layerId - Layer ID
     * @param {Object} updates - Properties to update
     */
    updateLayer(layerId, updates) {
        if (!this.isReady) {
            throw new Error('Manager not initialized');
        }

        this.layerManager.updateLayer(layerId, updates);
    }

    /**
     * Toggle layer visibility
     * @param {number} layerIndex - Layer index (1-9)
     */
    toggleLayerByIndex(layerIndex) {
        this.layerManager.toggleLayerByIndex(layerIndex);
    }

    /**
     * Get current view state
     * @returns {Object} Current view state
     */
    getViewState() {
        if (!this.nv) return null;

        return {
            azimuth: this.nv.scene.azimuth,
            elevation: this.nv.scene.elevation,
            scale: this.nv.scene.scale,
            clipPlane: [...this.nv.scene.clipPlane],
            sliceType: this.nv.opts.sliceType,
            crosshairPos: this.nv.crosshairPos,
            // Add more view properties as needed
        };
    }

    /**
     * Restore view state
     * @param {Object} viewState - View state to restore
     */
    setViewState(viewState) {
        if (!this.nv || !viewState) return;

        if (viewState.azimuth !== undefined) {
            this.nv.scene.azimuth = viewState.azimuth;
        }
        if (viewState.elevation !== undefined) {
            this.nv.scene.elevation = viewState.elevation;
        }
        if (viewState.scale !== undefined) {
            this.nv.scene.scale = viewState.scale;
        }
        if (viewState.clipPlane) {
            this.nv.scene.clipPlane = [...viewState.clipPlane];
        }
        if (viewState.sliceType !== undefined) {
            this.nv.opts.sliceType = viewState.sliceType;
        }

        this.nv.updateGLVolume();
    }

    /**
     * Reset view to default
     */
    resetView() {
        if (!this.nv) return;

        this.nv.scene.azimuth = 0;
        this.nv.scene.elevation = 0;
        this.nv.scene.scale = 1;
        this.nv.updateGLVolume();

        this.emit('viewReset');
    }

    /**
     * Clean up resources
     */
    destroy() {
        if (this.keyboardHandler) {
            this.keyboardHandler.destroy();
        }

        if (this.layerManager) {
            this.layerManager.destroy();
        }

        if (this.nv) {
            // Niivue cleanup if needed
        }

        this.removeAllListeners();
        this.isReady = false;
    }
}
