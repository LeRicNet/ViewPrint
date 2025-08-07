@extends('layouts.app')

@section('title', 'Welcome')

@push('styles')
    <!-- Any page-specific styles -->
@endpush

@section('content')
    <div class="h-full flex items-center justify-center bg-gray-900">
        <div class="text-center max-w-2xl mx-auto px-8">
            <!-- Logo -->
            <div class="flex justify-center mb-8">
                <div class="w-24 h-24 bg-gray-800 rounded-2xl flex items-center justify-center">
                    <svg class="w-14 h-14 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                </div>
            </div>

            <!-- Welcome Text -->
            <h1 class="text-4xl font-bold text-gray-100 mb-4">
                Welcome to ViewPrint
            </h1>
            <p class="text-xl text-gray-400 mb-12">
                Analyze eye-tracking patterns on volumetric images
            </p>

            <!-- Quick Actions -->
            <div class="space-y-4">
                <button @click="$dispatch('open-command-palette', { search: 'new workspace' })"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create New Workspace
                </button>

                <div class="text-gray-500">or</div>

                <button @click="$dispatch('open-command-palette', { search: 'open workspace' })"
                        class="text-gray-400 hover:text-gray-200 transition-colors">
                    Open Existing Workspace
                </button>
            </div>

            <!-- Keyboard Hint -->
            <div class="mt-16 text-sm text-gray-600">
                Press <kbd class="px-2 py-1 bg-gray-800 text-gray-400 rounded font-mono">⌘K</kbd> to open command palette
            </div>

            <!-- Recent Workspaces (if any) -->
            @if(isset($recentWorkspaces) && $recentWorkspaces->count() > 0)
                <div class="mt-12 text-left">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Recent Workspaces</h2>
                    <div class="space-y-2">
                        @foreach($recentWorkspaces as $workspace)
                            <a href="{{ route('workspace.show', $workspace) }}"
                               class="block px-4 py-3 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-gray-100 font-medium">{{ $workspace->name }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ $workspace->layers_count ?? 0 }} layers •
                                            Last opened {{ $workspace->last_accessed_at?->diffForHumans() ?? 'never' }}
                                        </div>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Any page-specific scripts -->
@endpush
