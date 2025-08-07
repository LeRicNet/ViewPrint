/**
 * LayerManager - Handles layer operations for ViewPrint
 *
 * Manages adding, removing, updating, and organizing layers
 * within the Niivue viewer. Tracks layer metadata and state.
 */

import { EventEmitter } from 'eventemitter3';

export class LayerManager extends EventEmitter {
    /**
     * Create a new LayerManager instance
     * @param {Niivue} niivue - The Niivue instance to manage
     */
    constructor(niivue) {
        super();

        this.nv = niivue;
        this.layers = new Map(); // Map of layerId -> layer metadata
        this.layerOrder = []; // Array of layer IDs in display order
    }

    /**
     * Add a new layer to the viewer
     * @param {Object} layer - Layer configuration
     * @param {number} layer.id - Unique layer ID
     * @param {string} layer.url - URL to NIfTI file
     * @param {string} layer.name - Display name
     * @param {string} layer.colormap - Colormap name (default: 'gray')
     * @param {number} layer.opacity - Opacity 0-1 (default: 1)
     * @param {boolean} layer.visible - Initial visibility (default: true)
     * @returns {Promise<void>}
     */
    async addLayer(layer) {
        try {
            // Validate layer configuration
            if (!layer.id || !layer.url) {
                throw new Error('Layer must have id and url');
            }

            // Check if layer already exists
            if (this.layers.has(layer.id)) {
                throw new Error(`Layer with id ${layer.id} already exists`);
            }

            // Prepare volume configuration for Niivue
            const volumeConfig = {
                url: layer.url,
                colormap: layer.colormap || 'gray',
                opacity: layer.opacity ?? 1,
                visible: layer.visible ?? true,
            };

            // Load the volume
            const volumes = [...this.nv.volumes, volumeConfig];
            await this.nv.loadVolumes(volumes);

            // Get the index of the newly added volume
            const volumeIndex = this.nv.volumes.length - 1;

            // Store layer metadata
            const layerData = {
                id: layer.id,
                name: layer.name || `Layer ${layer.id}`,
                volumeIndex: volumeIndex,
                colormap: volumeConfig.colormap,
                opacity: volumeConfig.opacity,
                visible: volumeConfig.visible,
                url: layer.url,
                addedAt: new Date(),
            };

            this.layers.set(layer.id, layerData);
            this.layerOrder.push(layer.id);

            this.emit('layerAdded', layerData);
            console.log(`Layer ${layer.id} added successfully`);

        } catch (error) {
            console.error(`Failed to add layer ${layer.id}:`, error);
            this.emit('error', {
                type: 'ADD_LAYER_FAILED',
                layerId: layer.id,
                message: error.message,
                error: error
            });
            throw error;
        }
    }

    /**
     * Remove a layer from the viewer
     * @param {number} layerId - ID of layer to remove
     */
    removeLayer(layerId) {
        const layer = this.layers.get(layerId);
        if (!layer) {
            console.warn(`Layer ${layerId} not found`);
            return;
        }

        try {
            // Remove from Niivue
            const newVolumes = this.nv.volumes.filter((_, index) => index !== layer.volumeIndex);
            this.nv.loadVolumes(newVolumes);

            // Update volume indices for remaining layers
            this.updateVolumeIndices(layer.volumeIndex);

            // Remove from our tracking
            this.layers.delete(layerId);
            this.layerOrder = this.layerOrder.filter(id => id !== layerId);

            this.emit('layerRemoved', layerId);
            console.log(`Layer ${layerId} removed`);

        } catch (error) {
            console.error(`Failed to remove layer ${layerId}:`, error);
            this.emit('error', {
                type: 'REMOVE_LAYER_FAILED',
                layerId: layerId,
                message: error.message,
                error: error
            });
        }
    }

    /**
     * Update layer properties
     * @param {number} layerId - ID of layer to update
     * @param {Object} updates - Properties to update
     * @param {number} updates.opacity - New opacity (0-1)
     * @param {string} updates.colormap - New colormap
     * @param {boolean} updates.visible - Visibility state
     */
    updateLayer(layerId, updates) {
        const layer = this.layers.get(layerId);
        if (!layer) {
            console.warn(`Layer ${layerId} not found`);
            return;
        }

        try {
            const volume = this.nv.volumes[layer.volumeIndex];
            if (!volume) {
                throw new Error('Volume not found in Niivue');
            }

            // Update Niivue volume properties
            if (updates.opacity !== undefined) {
                volume.opacity = updates.opacity;
                layer.opacity = updates.opacity;
            }

            if (updates.colormap !== undefined) {
                volume.colormap = updates.colormap;
                layer.colormap = updates.colormap;
            }

            if (updates.visible !== undefined) {
                volume.visible = updates.visible;
                layer.visible = updates.visible;
            }

            // Apply updates
            this.nv.updateGLVolume();

            this.emit('layerUpdated', { ...layer, ...updates });
            console.log(`Layer ${layerId} updated`, updates);

        } catch (error) {
            console.error(`Failed to update layer ${layerId}:`, error);
            this.emit('error', {
                type: 'UPDATE_LAYER_FAILED',
                layerId: layerId,
                message: error.message,
                error: error
            });
        }
    }

    /**
     * Toggle layer visibility by index (1-9 keyboard shortcuts)
     * @param {number} index - Layer index (1-based)
     */
    toggleLayerByIndex(index) {
        // Convert 1-based index to 0-based
        const arrayIndex = index - 1;

        if (arrayIndex < 0 || arrayIndex >= this.layerOrder.length) {
            return;
        }

        const layerId = this.layerOrder[arrayIndex];
        const layer = this.layers.get(layerId);

        if (layer) {
            this.updateLayer(layerId, { visible: !layer.visible });
        }
    }

    /**
     * Get all layers in order
     * @returns {Array} Array of layer data
     */
    getAllLayers() {
        return this.layerOrder.map(id => this.layers.get(id));
    }

    /**
     * Get a specific layer
     * @param {number} layerId - Layer ID
     * @returns {Object|null} Layer data or null
     */
    getLayer(layerId) {
        return this.layers.get(layerId) || null;
    }

    /**
     * Reorder layers
     * @param {Array<number>} newOrder - Array of layer IDs in new order
     */
    reorderLayers(newOrder) {
        // Validate new order contains all layer IDs
        const currentIds = new Set(this.layerOrder);
        const newIds = new Set(newOrder);

        if (currentIds.size !== newIds.size || ![...currentIds].every(id => newIds.has(id))) {
            throw new Error('Invalid layer order: must contain all current layer IDs');
        }

        this.layerOrder = [...newOrder];
        this.emit('layersReordered', this.layerOrder);
    }

    /**
     * Update volume indices after removal
     * @private
     * @param {number} removedIndex - Index of removed volume
     */
    updateVolumeIndices(removedIndex) {
        for (const layer of this.layers.values()) {
            if (layer.volumeIndex > removedIndex) {
                layer.volumeIndex--;
            }
        }
    }

    /**
     * Clear all layers
     */
    clearAllLayers() {
        this.nv.loadVolumes([]);
        this.layers.clear();
        this.layerOrder = [];
        this.emit('allLayersCleared');
    }

    /**
     * Clean up resources
     */
    destroy() {
        this.clearAllLayers();
        this.removeAllListeners();
    }
}
