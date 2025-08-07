# Livewire-Niivue Component Interaction Patterns

## Component Architecture Overview

```
app/Http/Livewire/
├── WorkspaceViewer.php      # Main container component
├── ViewerCanvas.php         # Niivue wrapper component
├── LayerPanel.php          # Layer management sidebar
└── CommandPalette.php      # Command interface

resources/views/livewire/
├── workspace-viewer.blade.php
├── viewer-canvas.blade.php
├── layer-panel.blade.php
└── command-palette.blade.php

resources/js/niivue/
├── WorkspaceNiivueManager.js
├── KeyboardHandler.js
└── utils/
    ├── fileValidation.js
    └── stateSync.js
```

## Component Specifications

### 1. WorkspaceViewer Component

**Purpose**: Main container managing overall workspace state

```php
<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Workspace;

class WorkspaceViewer extends Component
{
    public Workspace $workspace;
    public $layers = [];
    public $selectedLayerId = null;
    public $viewState = [];
    
    protected $listeners = [
        'niivueReady' => 'handleNiivueReady',
        'updateViewState' => 'saveViewState',
        'layerLoaded' => 'confirmLayerLoad',
        'handleNiivueError' => 'processError',
    ];
    
    public function mount(Workspace $workspace)
    {
        $this->workspace = $workspace;
        $this->loadLayers();
        $this->viewState = $workspace->settings['view_state'] ?? [];
    }
    
    public function render()
    {
        return view('livewire.workspace-viewer')
            ->layout('layouts.viewer');
    }
}
```

**Blade Template Structure**:
```blade
<div class="flex h-screen bg-gray-900" x-data="workspaceManager">
    <!-- Command Palette -->
    <div x-show="showCommandPalette" 
         x-transition
         @keydown.escape.window="showCommandPalette = false">
        @livewire('command-palette', ['workspace' => $workspace])
    </div>
    
    <!-- Main Viewer Area -->
    <div class="flex-1 relative">
        @livewire('viewer-canvas', [
            'workspace' => $workspace,
            'layers' => $layers
        ])
    </div>
    
    <!-- Layer Panel -->
    <div x-show="showLayerPanel" 
         x-transition:enter="transition ease-out duration-300"
         class="w-80 bg-gray-800 border-l border-gray-700">
        @livewire('layer-panel', ['workspace' => $workspace])
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('workspaceManager', () => ({
        showCommandPalette: false,
        showLayerPanel: true,
        
        init() {
            // Register keyboard shortcuts
            this.$watch('showCommandPalette', value => {
                if (value) this.$dispatch('command-palette:open');
            });
        },
        
        // Keyboard event handlers
        keyboardShortcuts: {
            'cmd+k': () => this.showCommandPalette = true,
            'l': () => this.showLayerPanel = !this.showLayerPanel,
        }
    }));
});
</script>
@endpush
```

### 2. ViewerCanvas Component

**Purpose**: Manages Niivue instance and bridges Livewire-JavaScript communication

```php
<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class ViewerCanvas extends Component
{
    use WithFileUploads;
    
    public $workspace;
    public $layers = [];
    public $uploadedFile;
    public $isLoading = false;
    
    protected $listeners = [
        'layer:visibility-changed' => 'updateLayerVisibility',
        'layer:opacity-changed' => 'updateLayerOpacity',
        'layer:remove' => 'removeLayer',
    ];
    
    public function mount($workspace, $layers)
    {
        $this->workspace = $workspace;
        $this->layers = $layers;
    }
    
    public function updatedUploadedFile()
    {
        $this->isLoading = true;
        
        try {
            // Validate file
            $this->validate([
                'uploadedFile' => 'required|file|mimes:nii,gz|max:512000',
            ]);
            
            // Process upload
            $volume = $this->processNiftiUpload();
            
            // Create layer
            $layer = $this->createVolumeLayer($volume);
            
            // Emit to JavaScript
            $this->emit('layer:added', [
                'id' => $layer->id,
                'name' => $layer->name,
                'nifti_url' => $this->getSignedUrl($volume),
                'colormap' => 'gray',
                'opacity' => 1.0,
            ]);
            
        } catch (\Exception $e) {
            $this->emit('upload:error', $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }
}
```

**Blade Template with Alpine.js Integration**:
```blade
<div class="relative h-full" 
     x-data="niivueCanvas"
     x-init="initializeNiivue"
     @drop.prevent="handleFileDrop"
     @dragover.prevent>
     
    <!-- Niivue Canvas -->
    <canvas id="niivue-canvas" 
            class="w-full h-full"
            x-ref="canvas">
    </canvas>
    
    <!-- Loading Overlay -->
    <div x-show="loading" 
         class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="text-white">
            <svg class="animate-spin h-8 w-8 mx-auto mb-2" fill="none">
                <!-- Spinner SVG -->
            </svg>
            <p x-text="loadingMessage"></p>
        </div>
    </div>
    
    <!-- Drop Zone Overlay -->
    <div x-show="isDragging"
         class="absolute inset-0 bg-blue-500 bg-opacity-20 border-4 border-dashed border-blue-500 flex items-center justify-center">
        <p class="text-white text-2xl">Drop NIfTI file here</p>
    </div>
    
    <!-- File Input (Hidden) -->
    <input type="file" 
           wire:model="uploadedFile" 
           x-ref="fileInput"
           accept=".nii,.nii.gz" 
           class="hidden">
           
    <!-- Upload Button -->
    <button @click="$refs.fileInput.click()" 
            class="absolute bottom-4 right-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow-lg">
        Add Volume
    </button>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('niivueCanvas', () => ({
        niivueManager: null,
        loading: false,
        loadingMessage: '',
        isDragging: false,
        
        async initializeNiivue() {
            this.loading = true;
            this.loadingMessage = 'Initializing viewer...';
            
            try {
                // Dynamically import Niivue and manager
                const { WorkspaceNiivueManager } = await import('/js/niivue/WorkspaceNiivueManager.js');
                
                // Create manager instance
                this.niivueManager = new WorkspaceNiivueManager(this.$refs.canvas, {
                    backColor: [0, 0, 0, 1],
                    show3Dcrosshair: true,
                });
                
                // Set up event listeners
                this.setupEventListeners();
                
                // Initialize with existing layers
                await this.loadInitialLayers();
                
                // Notify Livewire
                this.$wire.call('niivueReady');
                
            } catch (error) {
                console.error('Failed to initialize Niivue:', error);
                this.$wire.call('handleNiivueError', {
                    type: 'INIT_FAILED',
                    message: error.message
                });
            } finally {
                this.loading = false;
            }
        },
        
        setupEventListeners() {
            // Niivue manager events
            this.niivueManager.on('layer:loaded', (layerId) => {
                this.$wire.call('layerLoaded', layerId);
            });
            
            this.niivueManager.on('view:changed', Alpine.debounce((viewState) => {
                this.$wire.call('updateViewState', viewState);
            }, 1000));
            
            this.niivueManager.on('error', (error) => {
                this.$wire.call('handleNiivueError', error);
            });
            
            // Livewire events
            Livewire.on('layer:added', (layer) => {
                this.addLayer(layer);
            });
            
            Livewire.on('layer:updated', (update) => {
                this.niivueManager.updateLayer(update.id, update.changes);
            });
            
            Livewire.on('layer:removed', (layerId) => {
                this.niivueManager.removeLayer(layerId);
            });
        },
        
        async loadInitialLayers() {
            const layers = @json($layers);
            
            for (const layer of layers) {
                if (layer.configuration.nifti_url) {
                    await this.addLayer(layer);
                }
            }
        },
        
        async addLayer(layer) {
            this.loading = true;
            this.loadingMessage = `Loading ${layer.name}...`;
            
            try {
                await this.niivueManager.addLayer({
                    id: layer.id,
                    url: layer.nifti_url || layer.configuration.nifti_url,
                    colormap: layer.colormap || 'gray',
                    opacity: layer.opacity || 0.75,
                });
            } finally {
                this.loading = false;
            }
        },
        
        handleFileDrop(event) {
            this.isDragging = false;
            const file = event.dataTransfer.files[0];
            
            if (file && (file.name.endsWith('.nii') || file.name.endsWith('.nii.gz'))) {
                // Trigger Livewire upload
                this.$wire.uploadMultiple('uploadedFile', [file]);
            }
        }
    }));
});
</script>
@endpush
```

### 3. JavaScript Integration Pattern

**WorkspaceNiivueManager.js**:
```javascript
/**
 * Manages Niivue instance for ViewPrint workspace
 * @class WorkspaceNiivueManager
 */
export class WorkspaceNiivueManager extends EventTarget {
    /**
     * @param {HTMLCanvasElement} canvas - Target canvas element
     * @param {Object} options - Niivue options
     */
    constructor(canvas, options = {}) {
        super();
        
        this.canvas = canvas;
        this.options = options;
        this.volumes = new Map();
        this.isReady = false;
        
        this.initializeNiivue();
    }
    
    async initializeNiivue() {
        try {
            // Dynamic import of Niivue
            const { Niivue } = await import('@niivue/niivue');
            
            this.nv = new Niivue(this.options);
            this.nv.attachToCanvas(this.canvas);
            
            // Set up Niivue event handlers
            this.nv.onImageLoaded = (volume) => {
                this.handleImageLoaded(volume);
            };
            
            this.nv.onDraw = () => {
                this.handleDraw();
            };
            
            this.isReady = true;
            this.emit('ready');
            
        } catch (error) {
            this.emit('error', {
                type: 'INIT_FAILED',
                message: 'Failed to initialize Niivue',
                error
            });
        }
    }
    
    /**
     * Add a layer to the viewer
     * @param {Object} layer - Layer configuration
     * @returns {Promise<void>}
     */
    async addLayer(layer) {
        if (!this.isReady) {
            throw new Error('Niivue not initialized');
        }
        
        try {
            const volumeList = [{
                url: layer.url,
                colormap: layer.colormap,
                opacity: layer.opacity,
            }];
            
            await this.nv.loadVolumes(volumeList);
            
            const volumeIndex = this.nv.volumes.length - 1;
            this.volumes.set(layer.id, {
                index: volumeIndex,
                layer: layer,
            });
            
            this.emit('layer:loaded', layer.id);
            
        } catch (error) {
            this.emit('error', {
                type: 'LOAD_FAILED',
                layerId: layer.id,
                message: `Failed to load ${layer.name}`,
                error
            });
        }
    }
    
    /**
     * Update layer properties
     * @param {number} layerId - Layer ID
     * @param {Object} changes - Properties to update
     */
    updateLayer(layerId, changes) {
        const volume = this.volumes.get(layerId);
        if (!volume) return;
        
        const nvVolume = this.nv.volumes[volume.index];
        
        if ('opacity' in changes) {
            nvVolume.opacity = changes.opacity;
        }
        
        if ('colormap' in changes) {
            nvVolume.colormap = changes.colormap;
        }
        
        if ('visible' in changes) {
            nvVolume.visible = changes.visible;
        }
        
        this.nv.updateGLVolume();
        this.emit('layer:updated', layerId);
    }
    
    /**
     * Get current view state
     * @returns {Object} View state
     */
    getViewState() {
        return {
            azimuth: this.nv.scene.azimuth,
            elevation: this.nv.scene.elevation,
            scale: this.nv.scene.scale,
            clipPlane: [...this.nv.scene.clipPlane],
            renderMode: this.nv.opts.sliceType,
        };
    }
    
    /**
     * Emit custom event
     * @private
     */
    emit(eventName, data) {
        this.dispatchEvent(new CustomEvent(eventName, { detail: data }));
    }
}
```

### 4. Keyboard Handler Pattern

**KeyboardHandler.js**:
```javascript
/**
 * Handles keyboard shortcuts for ViewPrint
 */
export class KeyboardHandler {
    constructor(niivueManager, livewireComponent) {
        this.niivue = niivueManager;
        this.wire = livewireComponent;
        this.shortcuts = new Map();
        
        this.registerDefaultShortcuts();
        this.attachListeners();
    }
    
    registerDefaultShortcuts() {
        // Number keys for layer visibility
        for (let i = 1; i <= 9; i++) {
            this.shortcuts.set(i.toString(), () => {
                this.wire.call('toggleLayerVisibility', i);
            });
        }
        
        // View shortcuts
        this.shortcuts.set('r', () => {
            this.niivue.nv.scene.azimuth = 0;
            this.niivue.nv.scene.elevation = 0;
            this.niivue.nv.updateGLVolume();
        });
        
        // Space for play/pause (future animation)
        this.shortcuts.set(' ', () => {
            this.wire.call('toggleAnimation');
        });
    }
    
    attachListeners() {
        document.addEventListener('keydown', (e) => {
            // Skip if in input field
            if (e.target.matches('input, textarea')) return;
            
            const key = e.key.toLowerCase();
            const handler = this.shortcuts.get(key);
            
            if (handler) {
                e.preventDefault();
                handler();
            }
        });
    }
}
```

## State Synchronization Patterns

### 1. One-Way Data Flow (Livewire → JavaScript)
```javascript
// Layer configuration changes flow from server to client
Livewire.on('layer:updated', (update) => {
    // JavaScript reacts to server state changes
    niivueManager.updateLayer(update.id, update.changes);
});
```

### 2. Event-Driven Updates (JavaScript → Livewire)
```javascript
// User interactions in JavaScript notify server
niivueManager.on('view:changed', debounce((viewState) => {
    // Debounced to prevent excessive server calls
    Livewire.emit('updateViewState', viewState);
}, 1000));
```

### 3. Optimistic UI Updates
```javascript
// Immediate visual feedback before server confirmation
async function updateLayerOpacity(layerId, opacity) {
    // Update UI immediately
    niivueManager.updateLayer(layerId, { opacity });
    
    // Notify server
    await $wire.updateLayerOpacity(layerId, opacity);
}
```

## Testing Integration Points

### Component Tests
```php
/** @test */
public function it_loads_workspace_with_existing_layers()
{
    $workspace = Workspace::factory()
        ->has(WorkspaceLayer::factory()->count(3))
        ->create();
    
    Livewire::test(WorkspaceViewer::class, ['workspace' => $workspace])
        ->assertSet('layers', fn($layers) => count($layers) === 3)
        ->assertEmitted('niivue:init');
}
```

### JavaScript Tests
```javascript
describe('WorkspaceNiivueManager', () => {
    it('should emit ready event after initialization', async () => {
        const canvas = document.createElement('canvas');
        const manager = new WorkspaceNiivueManager(canvas);
        
        const readyPromise = new Promise(resolve => {
            manager.addEventListener('ready', resolve);
        });
        
        await readyPromise;
        expect(manager.isReady).toBe(true);
    });
});
```

## Performance Optimization Patterns

### 1. Lazy Component Loading
```blade
<!-- Only load heavy components when needed -->
<div wire:init="loadViewer">
    @if($viewerLoaded)
        @livewire('viewer-canvas', ['workspace' => $workspace])
    @else
        <div class="flex items-center justify-center h-full">
            <div class="text-gray-400">Initializing viewer...</div>
        </div>
    @endif
</div>
```

### 2. Debounced Property Updates
```javascript
// Batch rapid changes
const updateQueue = new Map();
const flushUpdates = debounce(() => {
    const updates = Array.from(updateQueue.entries());
    updateQueue.clear();
    
    Livewire.emit('batchUpdate', updates);
}, 100);

function queueUpdate(layerId, changes) {
    updateQueue.set(layerId, changes);
    flushUpdates();
}
```

## Error Boundary Pattern

```blade
<div x-data="{ hasError: false }" 
     @niivue-error.window="hasError = true">
     
    <div x-show="!hasError">
        <!-- Normal viewer content -->
    </div>
    
    <div x-show="hasError" class="flex items-center justify-center h-full">
        <div class="text-center">
            <p class="text-red-500 mb-4">Unable to initialize 3D viewer</p>
            <button @click="location.reload()" 
                    class="bg-blue-600 text-white px-4 py-2 rounded">
                Reload Page
            </button>
        </div>
    </div>
</div>
```
