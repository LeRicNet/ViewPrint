<div>
    <!-- Command Palette Modal -->
    <div x-data="{ open: @entangle('open') }"
         x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="$wire.close()"
         @open-command-palette.window="$wire.open($event.detail || {})"
         style="display: none;">

        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-900 bg-opacity-80 backdrop-blur-sm" @click="$wire.close()"></div>

        <!-- Command Palette -->
        <div class="flex items-start justify-center min-h-screen pt-16 px-4">
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.stop
                 class="relative w-full max-w-2xl bg-gray-800 rounded-lg shadow-2xl ring-1 ring-gray-700">

                <!-- Search Input -->
                <div class="border-b border-gray-700">
                    <div class="flex items-center px-4">
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text"
                               wire:model.live.debounce.150ms="search"
                               @keydown.arrow-up.prevent="$wire.selectPrevious()"
                               @keydown.arrow-down.prevent="$wire.selectNext()"
                               @keydown.enter.prevent="$wire.executeSelected()"
                               class="flex-1 bg-transparent border-0 text-gray-100 placeholder-gray-500 focus:ring-0 focus:outline-none py-4 px-3 text-base"
                               placeholder="Type a command or search..."
                               x-ref="commandInput"
                               x-init="$watch('open', value => { if (value) setTimeout(() => $refs.commandInput.focus(), 50) })"
                               autocomplete="off">
                    </div>
                </div>

                <!-- Commands List -->
                <div class="max-h-96 overflow-y-auto">
                    @if($this->commands->isEmpty())
                        <div class="px-4 py-8 text-center text-gray-500">
                            No commands found for "{{ $search }}"
                        </div>
                    @else
                        <div class="py-2">
                            @php $currentCategory = null; @endphp
                            @foreach($this->commands as $index => $command)
                                @if($currentCategory !== $command['category'])
                                    @php $currentCategory = $command['category']; @endphp
                                    <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ $currentCategory }}
                                    </div>
                                @endif

                                <button wire:click="executeCommand('{{ $command['id'] }}')"
                                        wire:key="command-{{ $command['id'] }}"
                                        class="w-full px-4 py-3 flex items-center hover:bg-gray-700 transition-colors
                                               {{ $selectedIndex === $index ? 'bg-gray-700' : '' }}"
                                        @mouseenter="$wire.set('selectedIndex', {{ $index }})">

                                    <!-- Icon -->
                                    <div class="flex-shrink-0 w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center mr-3">
                                        @switch($command['icon'])
                                            @case('plus-circle')
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                @break
                                            @case('folder-open')
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z" />
                                                </svg>
                                                @break
                                            @case('save')
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                                </svg>
                                                @break
                                            @case('cube')
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                </svg>
                                                @break
                                            @case('users')
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                </svg>
                                                @break
                                            @case('layers')
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                                </svg>
                                                @break
                                            @default
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                        @endswitch
                                    </div>

                                    <!-- Command Info -->
                                    <div class="flex-1 text-left">
                                        <div class="flex items-center">
                                            <span class="text-gray-100 font-medium">{{ $command['name'] }}</span>
                                            @if(($command['shortcut'] ?? null))
                                                <span class="ml-2 text-xs text-gray-500 font-mono bg-gray-900 px-1.5 py-0.5 rounded">
                                                    {{ $command['shortcut'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-400 mt-0.5">{{ $command['description'] }}</div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-700 px-4 py-2 flex items-center justify-between text-xs text-gray-500">
                    <div class="flex items-center space-x-4">
                        <span class="flex items-center space-x-1">
                            <kbd class="px-1.5 py-0.5 bg-gray-900 rounded">↑</kbd>
                            <kbd class="px-1.5 py-0.5 bg-gray-900 rounded">↓</kbd>
                            <span>Navigate</span>
                        </span>
                        <span class="flex items-center space-x-1">
                            <kbd class="px-1.5 py-0.5 bg-gray-900 rounded">↵</kbd>
                            <span>Select</span>
                        </span>
                        <span class="flex items-center space-x-1">
                            <kbd class="px-1.5 py-0.5 bg-gray-900 rounded">esc</kbd>
                            <span>Close</span>
                        </span>
                    </div>
                    <div>
                        {{ $this->commands->count() }} {{ Str::plural('command', $this->commands->count()) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
