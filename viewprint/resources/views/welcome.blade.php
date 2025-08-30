@extends('layouts.app')

@section('title', 'Welcome')

@section('content')
    <div class="h-full flex items-center justify-center bg-gray-900"
         x-data="{ showCreateModal: false }">
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
                <button @click="showCreateModal = true"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create New Workspace
                </button>

                <div class="text-gray-500">or</div>

                <button @click="alert('Open workspace feature coming soon')"
                        class="text-gray-400 hover:text-gray-200 transition-colors">
                    Open Existing Workspace
                </button>
            </div>

            <!-- Keyboard Hint -->
            <div class="mt-16 text-sm text-gray-600">
                Press <kbd class="px-2 py-1 bg-gray-800 text-gray-400 rounded font-mono">⌘K</kbd> to open command palette
            </div>
        </div>

        <!-- Create Workspace Modal -->
        <div x-show="showCreateModal"
             x-cloak
             @keydown.escape.window="showCreateModal = false"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">

            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity"
                 @click="showCreateModal = false"></div>

            <!-- Modal Content -->
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-lg bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
                     @click.stop>

                    <!-- Close button -->
                    <div class="absolute right-0 top-0 pr-4 pt-4">
                        <button type="button"
                                @click="showCreateModal = false"
                                class="rounded-md bg-gray-800 text-gray-400 hover:text-gray-200 focus:outline-none">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg font-semibold leading-6 text-gray-100 mb-4">
                                Create New Workspace
                            </h3>

                            <form method="POST" action="{{ route('workspace.store') }}">
                                @csrf

                                <div class="mb-4">
                                    <label for="name" class="block text-sm font-medium text-gray-300 mb-1">
                                        Workspace Name
                                    </label>
                                    <input type="text"
                                           name="name"
                                           id="name"
                                           required
                                           autofocus
                                           placeholder="e.g., Expert vs Novice Study"
                                           class="w-full rounded-md border-gray-600 bg-gray-700 text-gray-100 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                <div class="mb-6">
                                    <label for="description" class="block text-sm font-medium text-gray-300 mb-1">
                                        Description <span class="text-gray-500">(optional)</span>
                                    </label>
                                    <textarea name="description"
                                              id="description"
                                              rows="3"
                                              placeholder="Brief description of your analysis goals"
                                              class="w-full rounded-md border-gray-600 bg-gray-700 text-gray-100 placeholder-gray-400 focus:border-blue-500 focus:ring-blue-500"></textarea>
                                </div>

                                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                                    <button type="submit"
                                            class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 sm:w-auto">
                                        Create Workspace
                                    </button>
                                    <button type="button"
                                            @click="showCreateModal = false"
                                            class="mt-3 inline-flex w-full justify-center rounded-md bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-300 shadow-sm hover:bg-gray-600 sm:mt-0 sm:w-auto">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        [x-cloak] { display: none !important; }
    </style>
@endpush
