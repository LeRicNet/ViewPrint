<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ViewPrint') }} - @yield('title', 'NIfTI Eye-Tracking Analysis')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Page Specific Styles -->
    @stack('styles')
</head>
<body class="h-full bg-gray-900 text-gray-100 font-sans antialiased">
<!-- Command Palette -->
<livewire:command-palette />

<!-- Global Keyboard Shortcuts Handler -->
<div x-data="globalShortcuts" x-init="init" class="hidden"></div>

<!-- Main Application -->
<div class="h-full flex flex-col">
    <!-- Minimal Header -->
    <header class="flex-shrink-0 h-10 bg-gray-950 border-b border-gray-800 flex items-center px-4">
        <div class="flex items-center space-x-4 flex-1">
            <!-- Logo/Brand -->
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span class="text-sm font-semibold">ViewPrint</span>
            </div>

            <!-- Workspace Name (if in workspace) -->
            @if(isset($workspace))
                <div class="text-sm text-gray-400">
                    <span class="text-gray-600">/</span>
                    <span class="text-gray-200">{{ $workspace->name }}</span>
                </div>
            @endif

            <!-- Spacer -->
            <div class="flex-1"></div>

            <!-- Status Indicators -->
            <div class="flex items-center space-x-3 text-xs">
                <!-- Save Status -->
                <div x-data="{ saved: true }"
                     x-on:workspace-changed.window="saved = false"
                     x-on:workspace-saved.window="saved = true"
                     class="flex items-center space-x-1">
                    <div x-show="saved" class="flex items-center text-gray-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Saved</span>
                    </div>
                    <div x-show="!saved" class="flex items-center text-yellow-500">
                        <svg class="w-3 h-3 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Unsaved</span>
                    </div>
                </div>

                <!-- Help -->
                <button class="text-gray-500 hover:text-gray-300 transition-colors"
                        @click="$dispatch('open-command-palette', { search: 'help' })">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                <!-- Command Palette Trigger -->
                <button class="flex items-center space-x-1 px-2 py-1 text-gray-400 hover:text-gray-200 hover:bg-gray-800 rounded transition-colors"
                        @click="$dispatch('open-command-palette')">
                    <span class="text-xs">⌘K</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-hidden">
        @yield('content')
    </main>

    <!-- Global Notifications -->
    <div class="fixed bottom-4 right-4 z-50 space-y-2"
         x-data="{ notifications: [] }"
         x-on:notify.window="
                notifications.push($event.detail);
                setTimeout(() => notifications.shift(), 5000);
             ">
        <template x-for="(notification, index) in notifications" :key="index">
            <div x-transition:enter="transform ease-out duration-300 transition"
                 x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                 x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="max-w-sm w-full bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-gray-700">
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg x-show="notification.type === 'success'" class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <svg x-show="notification.type === 'error'" class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <svg x-show="notification.type === 'info'" class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-100" x-text="notification.title"></p>
                            <p x-show="notification.message" class="mt-1 text-sm text-gray-400" x-text="notification.message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>

<!-- Livewire Scripts -->
@livewireScripts

<!-- Global Shortcuts Script -->
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('globalShortcuts', () => ({
            init() {
                // Command Palette (Cmd+K or Ctrl+K)
                document.addEventListener('keydown', (e) => {
                    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                        e.preventDefault();
                        this.$dispatch('open-command-palette');
                    }

                    // Layer visibility shortcuts (1-9)
                    if (!e.metaKey && !e.ctrlKey && !e.altKey && e.key >= '1' && e.key <= '9') {
                        // Only trigger if not in an input field
                        if (document.activeElement.tagName !== 'INPUT' &&
                            document.activeElement.tagName !== 'TEXTAREA') {
                            e.preventDefault();
                            this.$dispatch('toggle-layer', { index: parseInt(e.key) - 1 });
                        }
                    }

                    // Save (Cmd+S or Ctrl+S)
                    if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                        e.preventDefault();
                        this.$dispatch('save-workspace');
                    }

                    // Reset view (R)
                    if (!e.metaKey && !e.ctrlKey && !e.altKey && e.key === 'r') {
                        if (document.activeElement.tagName !== 'INPUT' &&
                            document.activeElement.tagName !== 'TEXTAREA') {
                            e.preventDefault();
                            this.$dispatch('reset-view');
                        }
                    }

                    // Toggle layer panel (L)
                    if (!e.metaKey && !e.ctrlKey && !e.altKey && e.key === 'l') {
                        if (document.activeElement.tagName !== 'INPUT' &&
                            document.activeElement.tagName !== 'TEXTAREA') {
                            e.preventDefault();
                            this.$dispatch('toggle-layer-panel');
                        }
                    }
                });
            }
        }));
    });
</script>

<!-- Page Specific Scripts -->
@stack('scripts')
</body>
</html>
