<div class="min-h-screen bg-gray-900 flex flex-col"
{{--     x-data="workspaceViewer"--}}
{{--     @keydown.b.window="$wire.showAddVolume()"--}}
>

    <!-- Header -->
    <div class="bg-gray-800 border-b border-gray-700 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h1 class="text-lg font-medium text-gray-100">{{ $workspace->name }}</h1>
                <span class="text-sm text-gray-500">
                    {{ count($layers) }} {{ Str::plural('layer', count($layers)) }}
                </span>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-xs text-gray-500">Press B to add volume</span>
                <a href="{{ route('welcome') }}" class="text-gray-400 hover:text-gray-200 text-sm">
                    ← Back
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 relative">



        @if(empty($layers))
            <!-- Empty State -->
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center">
                    <div class="mb-6">
                        <svg class="w-24 h-24 mx-auto text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-medium text-gray-400 mb-2">Empty Workspace</h2>
                    <p class="text-gray-500 mb-6">Add a base volume to get started</p>

                    <button wire:click="showAddVolume"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Base Volume
                    </button>

                    <div class="mt-4 text-sm text-gray-600">
                        or press <kbd class="px-2 py-1 bg-gray-800 rounded text-xs">B</kbd>
                    </div>
                </div>
            </div>
        @else
            <!-- Niivue Canvas -->
            <canvas id="gl" class="w-full h-full"></canvas>

            <!-- Layer Info Overlay -->
            <div class="absolute top-4 right-4 bg-gray-800 bg-opacity-90 rounded-lg p-4 max-w-xs">
                <h3 class="text-sm font-medium text-gray-300 mb-2">Active Layers</h3>
                <div class="space-y-2">
                    @foreach($layers as $layer)
                        <div class="text-xs text-gray-400">
                            {{ $loop->iteration }}. {{ $layer['name'] }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Add Volume Modal -->
    @if($showAddVolumeModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-black bg-opacity-75"
                 wire:click="$set('showAddVolumeModal', false)"></div>

            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative bg-gray-800 rounded-lg max-w-md w-full p-6"
                     @click.stop>
                    <!-- Modal content stays the same -->
                    <h3 class="text-lg font-semibold text-gray-100 mb-4">Add Base Volume</h3>

                    <form wire:submit="addVolume">
                        <!-- Form content stays the same -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                Volume Name
                            </label>
                            <input type="text"
                                   wire:model="volumeName"
                                   class="w-full rounded-md border-gray-600 bg-gray-700 text-gray-100"
                                   placeholder="e.g., Brain MRI T1">
                            @error('volumeName')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                NIfTI File
                            </label>
                            <input type="file"
                                   wire:model="volumeFile"
                                   accept=".nii,.nii.gz"
                                   class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-gray-200 hover:file:bg-gray-600">
                            @error('volumeFile')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror

                            <p class="mt-2 text-xs text-gray-500">
                                Accepts .nii and .nii.gz files up to 500MB
                            </p>
                        </div>

                        <div class="flex gap-3">
                            <button type="submit"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md"
                                    wire:loading.attr="disabled">
                                <span wire:loading.remove>Add Volume</span>
                                <span wire:loading>Uploading...</span>
                            </button>
                            <button type="button"
                                    wire:click="$set('showAddVolumeModal', false)"
                                    class="flex-1 bg-gray-700 hover:bg-gray-600 text-gray-300 py-2 rounded-md">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

@push('styles')
    <style>
        [x-cloak] { display: none !important; }
    </style>
@endpush

@push('scripts')
{{--    <script src="https://unpkg.com/@niivue/niivue@0.42.0/dist/niivue.js"></script>--}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('workspaceViewer', () => ({
                nv: null,

                init() {
                    // Listen for volume added events
                    window.addEventListener('volumeAdded', (event) => {
                        console.log('volumeAdded!', event);
                        this.loadVolume(event.detail);
                    });

                    // Initialize Niivue if we have layers
                    if (@json(count($layers)) > 0) {
                        this.initializeNiivue();
                    }
                },

                async initializeNiivue() {
                    const canvas = document.getElementById('gl');
                    if (!canvas) return;

                    this.nv = new niivue.Niivue({
                        backColor: [0.2, 0.2, 0.2, 1],
                        show3Dcrosshair: true,
                    });

                    this.nv.attachToCanvas(canvas);

                    // Load existing layers
                    const layers = @json($layers);
                    for (const layer of layers) {
                        if (layer.configuration?.nifti_url) {
                            await this.nv.addVolume({
                                url: layer.configuration.nifti_url,
                                colormap: layer.configuration.colormap || 'gray',
                                opacity: (layer.opacity || 100) / 100,
                            });
                        }
                    }
                },

                async loadVolume(detail) {
                    if (!this.nv) {
                        await this.initializeNiivue();
                    }

                    await this.nv.addVolume({
                        url: detail.url,
                        colormap: 'gray',
                        opacity: 1.0,
                    });
                }
            }));
        });
    </script>
@endpush
