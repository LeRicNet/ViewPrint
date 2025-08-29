<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Niivue Test - ViewPrint</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Vite Assets (includes Tailwind CSS, Alpine.js, and Niivue) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-white">
<div x-data="niivueTest()" x-init="init()" class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-gray-800 p-4 border-b border-gray-700">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold">Niivue Test Page</h1>
            <p class="text-gray-400">Testing Niivue integration in ViewPrint</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex">
        <!-- Controls Panel -->
        <aside class="w-80 bg-gray-800 p-4 overflow-y-auto">
            <h2 class="text-lg font-semibold mb-4">Test Controls</h2>

            <!-- Status -->
            <div class="mb-6 p-3 rounded" :class="status.type === 'success' ? 'bg-green-900' : status.type === 'error' ? 'bg-red-900' : 'bg-blue-900'">
                <p class="text-sm" x-text="status.message"></p>
            </div>

            <!-- Load Sample Data -->
            <div class="mb-6">
                <h3 class="font-medium mb-2">Sample Data</h3>
                <button @click="loadSampleVolume()"
                        class="w-full bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded transition">
                    Load Sample Brain MRI
                </button>
                <p class="text-xs text-gray-400 mt-1">Uses Niivue's demo MRI data</p>
            </div>

            <!-- File Upload -->
            <div class="mb-6">
                <h3 class="font-medium mb-2">Upload NIfTI File</h3>
                <input type="file"
                       @change="loadLocalFile($event)"
                       accept=".nii,.nii.gz"
                       class="block w-full text-sm text-gray-400
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-gray-700 file:text-white
                                  hover:file:bg-gray-600">
                <p class="text-xs text-gray-400 mt-1">Supports .nii and .nii.gz files</p>
            </div>

            <!-- View Controls -->
            <div class="mb-6">
                <h3 class="font-medium mb-2">View Options</h3>

                <!-- Crosshair Toggle -->
                <label class="flex items-center mb-2">
                    <input type="checkbox"
                           x-model="showCrosshair"
                           @change="updateCrosshair()"
                           class="mr-2">
                    Show Crosshair
                </label>

                <!-- Colormap Selection -->
                <label class="block mb-2">
                    <span class="text-sm">Colormap</span>
                    <select x-model="colormap"
                            @change="updateColormap()"
                            class="mt-1 block w-full bg-gray-700 rounded px-3 py-2">
                        <option value="gray">Gray</option>
                        <option value="jet">Jet</option>
                        <option value="hot">Hot</option>
                        <option value="cool">Cool</option>
                        <option value="red">Red</option>
                        <option value="green">Green</option>
                        <option value="blue">Blue</option>
                    </select>
                </label>

                <!-- Opacity Control -->
                <label class="block mb-2">
                    <span class="text-sm">Opacity</span>
                    <input type="range"
                           x-model="opacity"
                           @input="updateOpacity()"
                           min="0"
                           max="100"
                           class="mt-1 w-full">
                    <span class="text-xs text-gray-400" x-text="opacity + '%'"></span>
                </label>
            </div>

            <!-- Slice Position -->
            <div class="mb-6" x-show="hasVolume">
                <h3 class="font-medium mb-2">Slice Position</h3>
                <div class="space-y-2 text-sm">
                    <p>X: <span x-text="Math.round(slicePosition.x)" class="font-mono"></span></p>
                    <p>Y: <span x-text="Math.round(slicePosition.y)" class="font-mono"></span></p>
                    <p>Z: <span x-text="Math.round(slicePosition.z)" class="font-mono"></span></p>
                </div>
            </div>

            <!-- Test Functions -->
            <div class="mb-6">
                <h3 class="font-medium mb-2">Test Functions</h3>
                <div class="space-y-2">
                    <button @click="testMultipleVolumes()"
                            class="w-full bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded text-sm transition">
                        Test Multiple Volumes
                    </button>
                    <button @click="testDrawing()"
                            class="w-full bg-green-600 hover:bg-green-700 px-4 py-2 rounded text-sm transition">
                        Test Drawing Mode
                    </button>
                    <button @click="testScreenshot()"
                            class="w-full bg-orange-600 hover:bg-orange-700 px-4 py-2 rounded text-sm transition">
                        Test Screenshot
                    </button>
                    <button @click="resetView()"
                            class="w-full bg-gray-600 hover:bg-gray-700 px-4 py-2 rounded text-sm transition">
                        Reset View
                    </button>
                </div>
            </div>

            <!-- Debug Info -->
            <div class="mb-6">
                <h3 class="font-medium mb-2">Debug Info</h3>
                <div class="text-xs font-mono bg-gray-900 p-2 rounded">
                    <p>Niivue Version: <span x-text="niivueVersion"></span></p>
                    <p>WebGL: <span x-text="webglSupported ? 'Supported' : 'Not Supported'"></span></p>
                    <p>Volumes Loaded: <span x-text="volumeCount"></span></p>
                </div>
            </div>
        </aside>

        <!-- Niivue Viewer -->
        <div class="flex-1 bg-black relative">
            <canvas id="gl-canvas" class="w-full h-full"></canvas>

            <!-- Loading Overlay -->
            <div x-show="loading"
                 class="absolute inset-0 bg-black bg-opacity-75 flex items-center justify-center">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mb-4"></div>
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 p-4 border-t border-gray-700">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <p class="text-sm text-gray-400">
                ViewPrint - NIfTI Eye-Tracking Analysis Platform
            </p>
            <a href="/" class="text-blue-400 hover:text-blue-300 text-sm">
                Back to Main App
            </a>
        </div>
    </footer>
</div>


</body>
</html>
