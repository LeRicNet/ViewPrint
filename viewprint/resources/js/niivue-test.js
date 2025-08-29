import { Niivue } from '@niivue/niivue';
import Alpine from 'alpinejs';

// Make Alpine available globally
window.Alpine = Alpine;

// Define the Niivue test component
Alpine.data('niivueTest', () => ({
    // State
    nv: null,
    status: { type: 'info', message: 'Initializing Niivue...' },
    loading: false,
    hasVolume: false,

    // Settings
    showCrosshair: true,
    colormap: 'gray',
    opacity: 100,

    // Info
    niivueVersion: 'Unknown',
    webglSupported: false,
    volumeCount: 0,
    slicePosition: { x: 0, y: 0, z: 0 },

    // Initialize Niivue
    init() {
        try {
            // Check WebGL support
            // const canvas = document.createElement('canvas');
            // const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            const canvas = document.getElementById('gl-canvas');
            const gl = canvas.getContext('webgl2');
            this.webglSupported = !!gl;

            if (!this.webglSupported) {
                this.status = { type: 'error', message: 'WebGL is not supported in this browser' };
                return;
            }

            // Create Niivue instance
            var nv = new Niivue({
                backColor: [0, 0, 0, 1],
                show3Dcrosshair: this.showCrosshair,
                onLocationChange: (data) => {
                    this.updateSlicePosition();
                }
            });
            nv.attachTo('gl-canvas');
            this.nv = nv;
            console.log(this.nv);

            // Get version - Niivue might expose this differently
            this.niivueVersion = this.nv.version || '1.x';

            this.status = { type: 'success', message: 'Niivue initialized successfully! Ready to load volumes.' };

            // Make nv available globally for debugging
            window.nv = nv;
        } catch (error) {
            console.error('Niivue initialization error:', error);
            this.status = { type: 'error', message: 'Failed to initialize Niivue: ' + error.message };
        }
    },

    // Load sample volume
    async loadSampleVolume() {
        this.loading = true;
        this.status = { type: 'info', message: 'Loading sample volume...' };

        try {
            const volumeList = [
                {
                    url: 'https://niivue.github.io/niivue-demo-images/mni152.nii.gz',
                    colormap: this.colormap,
                    opacity: this.opacity / 100
                }
            ];

            await this.nv.loadVolumes(volumeList);
            this.hasVolume = true;
            this.volumeCount = this.nv.volumes.length;
            this.updateSlicePosition();
            this.status = { type: 'success', message: 'Sample volume loaded successfully!' };
            window.nv = this.nv;
        } catch (error) {
            console.error('Error loading sample volume:', error);
            this.status = { type: 'error', message: 'Failed to load sample volume: ' + error.message };
        } finally {
            this.loading = false;
        }
    },

    // Load local file
    async loadLocalFile(event) {
        const file = event.target.files[0];
        if (!file) return;

        this.loading = true;
        this.status = { type: 'info', message: `Loading ${file.name}...` };

        try {
            // For local files, we need to create a File/Blob
            const volumeList = [
                {
                    url: file,
                    name: file.name,
                    colormap: this.colormap,
                    opacity: this.opacity / 100
                }
            ];

            await this.nv.loadVolumes(volumeList);

            this.hasVolume = true;
            this.volumeCount = this.nv.volumes.length;
            this.updateSlicePosition();
            this.status = { type: 'success', message: `${file.name} loaded successfully!` };
        } catch (error) {
            console.error('Error loading file:', error);
            this.status = { type: 'error', message: 'Failed to load file: ' + error.message };
        } finally {
            this.loading = false;
        }
    },

    // Test multiple volumes
    async testMultipleVolumes() {
        this.loading = true;
        this.status = { type: 'info', message: 'Loading multiple volumes for overlay test...' };

        try {
            const volumeList = [
                {
                    url: 'https://niivue.github.io/niivue-demo-images/mni152.nii.gz',
                    colormap: 'gray',
                    opacity: 1
                },
                {
                    url: 'https://niivue.github.io/niivue-demo-images/hippo.nii.gz',
                    colormap: 'hot',
                    opacity: 0.5
                }
            ];

            await this.nv.loadVolumes(volumeList);

            this.hasVolume = true;
            this.volumeCount = this.nv.volumes.length;
            this.status = { type: 'success', message: 'Multiple volumes loaded! Showing overlay test.' };
        } catch (error) {
            console.error('Error loading multiple volumes:', error);
            this.status = { type: 'error', message: 'Failed to load multiple volumes: ' + error.message };
        } finally {
            this.loading = false;
        }
    },

    // Test drawing
    testDrawing() {
        if (!this.hasVolume) {
            this.status = { type: 'error', message: 'Please load a volume first' };
            return;
        }

        const isEnabled = this.nv.opts.drawingEnabled;
        this.nv.setDrawingEnabled(!isEnabled);

        if (!isEnabled) {
            this.status = { type: 'info', message: 'Drawing mode enabled. Click and drag to draw.' };
            this.nv.setPenValue(1, true); // Set pen value for drawing
        } else {
            this.status = { type: 'info', message: 'Drawing mode disabled.' };
        }
    },

    // Test screenshot
    testScreenshot() {
        if (!this.hasVolume) {
            this.status = { type: 'error', message: 'Please load a volume first' };
            return;
        }

        this.nv.saveScene('niivue-test-screenshot.png');
        this.status = { type: 'success', message: 'Screenshot saved!' };
    },

    // Update functions
    updateCrosshair() {
        this.nv.opts.show3Dcrosshair = this.showCrosshair;
        this.nv.updateGLVolume();
    },

    updateColormap() {
        if (this.hasVolume && this.nv.volumes.length > 0) {
            this.nv.setColormap(this.nv.volumes[0].id, this.colormap);
        }
    },

    updateOpacity() {
        if (this.hasVolume && this.nv.volumes.length > 0) {
            this.nv.setOpacity(0, this.opacity / 100);
        }
    },

    updateSlicePosition() {
        if (this.hasVolume && this.nv.scene) {
            this.slicePosition = {
                x: this.nv.scene.crosshairPos[0],
                y: this.nv.scene.crosshairPos[1],
                z: this.nv.scene.crosshairPos[2]
            };
        }
    },

    resetView() {
        if (this.hasVolume) {
            // Set to 3D render view
            this.nv.setSliceType(this.nv.sliceTypeRender);
            this.status = { type: 'info', message: 'View reset to 3D' };
        }
    }
}));

// Start Alpine
Alpine.start();
