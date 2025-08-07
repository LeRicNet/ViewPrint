<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Collection;

class CommandPalette extends Component
{
    public string $search = '';
    public bool $open = false;
    public int $selectedIndex = 0;

    protected $listeners = [
        'open-command-palette' => 'open',
        'close-command-palette' => 'close',
    ];

    /**
     * Get available commands based on current context and search.
     */
    public function getCommandsProperty(): Collection
    {
        $allCommands = collect([
            // Workspace Commands
            [
                'id' => 'new-workspace',
                'name' => 'New Workspace',
                'description' => 'Create a new analysis workspace',
                'icon' => 'plus-circle',
                'shortcut' => null,
                'action' => 'createWorkspace',
                'category' => 'Workspace',
            ],
            [
                'id' => 'open-workspace',
                'name' => 'Open Workspace',
                'description' => 'Open an existing workspace',
                'icon' => 'folder-open',
                'shortcut' => null,
                'action' => 'openWorkspace',
                'category' => 'Workspace',
            ],
            [
                'id' => 'save-workspace',
                'name' => 'Save Workspace',
                'description' => 'Save current workspace',
                'icon' => 'save',
                'shortcut' => '⌘S',
                'action' => 'saveWorkspace',
                'category' => 'Workspace',
                'requiresWorkspace' => true,
            ],

            // Volume Commands
            [
                'id' => 'add-base-volume',
                'name' => 'Add Base Volume',
                'description' => 'Load a NIfTI volume as base layer',
                'icon' => 'cube',
                'shortcut' => 'B',
                'action' => 'addBaseVolume',
                'category' => 'Volumes',
                'requiresWorkspace' => true,
            ],
            [
                'id' => 'add-participant-layer',
                'name' => 'Add Participant Layer',
                'description' => 'Add eye-tracking data overlay',
                'icon' => 'users',
                'shortcut' => 'P',
                'action' => 'addParticipantLayer',
                'category' => 'Volumes',
                'requiresWorkspace' => true,
            ],

            // View Commands
            [
                'id' => 'reset-view',
                'name' => 'Reset View',
                'description' => 'Reset camera to default position',
                'icon' => 'refresh',
                'shortcut' => 'R',
                'action' => 'resetView',
                'category' => 'View',
                'requiresWorkspace' => true,
            ],
            [
                'id' => 'toggle-crosshair',
                'name' => 'Toggle Crosshair',
                'description' => 'Show/hide 3D crosshair',
                'icon' => 'crosshair',
                'shortcut' => 'C',
                'action' => 'toggleCrosshair',
                'category' => 'View',
                'requiresWorkspace' => true,
            ],

            // Panel Commands
            [
                'id' => 'open-layer-panel',
                'name' => 'Open Layer Panel',
                'description' => 'Manage layers and visibility',
                'icon' => 'layers',
                'shortcut' => 'L',
                'action' => 'openLayerPanel',
                'category' => 'Panels',
                'requiresWorkspace' => true,
            ],
            [
                'id' => 'open-filter-panel',
                'name' => 'Open Filter Panel',
                'description' => 'Filter participants and data',
                'icon' => 'filter',
                'shortcut' => 'F',
                'action' => 'openFilterPanel',
                'category' => 'Panels',
                'requiresWorkspace' => true,
            ],

            // Calculation Commands
            [
                'id' => 'create-calculated-layer',
                'name' => 'Create Calculated Layer',
                'description' => 'Generate statistics or comparisons',
                'icon' => 'calculator',
                'shortcut' => null,
                'action' => 'createCalculatedLayer',
                'category' => 'Analysis',
                'requiresWorkspace' => true,
            ],

            // Help Commands
            [
                'id' => 'help-shortcuts',
                'name' => 'Keyboard Shortcuts',
                'description' => 'View all keyboard shortcuts',
                'icon' => 'keyboard',
                'shortcut' => '?',
                'action' => 'showShortcuts',
                'category' => 'Help',
            ],
            [
                'id' => 'help-docs',
                'name' => 'Documentation',
                'description' => 'Open ViewPrint documentation',
                'icon' => 'book-open',
                'shortcut' => null,
                'action' => 'openDocs',
                'category' => 'Help',
            ],
        ]);

        // Filter by search term
        if ($this->search) {
            $searchLower = strtolower($this->search);
            $allCommands = $allCommands->filter(function ($command) use ($searchLower) {
                return str_contains(strtolower($command['name']), $searchLower) ||
                    str_contains(strtolower($command['description']), $searchLower) ||
                    str_contains(strtolower($command['category']), $searchLower);
            });
        }

        // Filter by context (e.g., requiresWorkspace)
        $hasWorkspace = session()->has('current_workspace_id');
        if (!$hasWorkspace) {
            $allCommands = $allCommands->reject(function ($command) {
                return $command['requiresWorkspace'] ?? false;
            });
        }

        return $allCommands->values();
    }

    /**
     * Open the command palette.
     */
    public function open($options = []): void
    {
        $this->open = true;
        $this->search = $options['search'] ?? '';
        $this->selectedIndex = 0;

        $this->dispatch('command-palette-opened');
    }

    /**
     * Close the command palette.
     */
    public function close(): void
    {
        $this->open = false;
        $this->search = '';
        $this->selectedIndex = 0;

        $this->dispatch('command-palette-closed');
    }

    /**
     * Execute the selected command.
     */
    public function executeCommand(string $commandId): void
    {
        $command = $this->commands->firstWhere('id', $commandId);

        if (!$command) {
            return;
        }

        $this->close();

        // Dispatch the command action
        $this->dispatch($command['action'], $command);
    }

    /**
     * Move selection up.
     */
    public function selectPrevious(): void
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        } else {
            $this->selectedIndex = max(0, $this->commands->count() - 1);
        }
    }

    /**
     * Move selection down.
     */
    public function selectNext(): void
    {
        if ($this->selectedIndex < $this->commands->count() - 1) {
            $this->selectedIndex++;
        } else {
            $this->selectedIndex = 0;
        }
    }

    /**
     * Execute the currently selected command.
     */
    public function executeSelected(): void
    {
        if ($this->commands->count() > 0 && isset($this->commands[$this->selectedIndex])) {
            $this->executeCommand($this->commands[$this->selectedIndex]['id']);
        }
    }

    /**
     * Reset selection when search changes.
     */
    public function updatedSearch(): void
    {
        $this->selectedIndex = 0;
    }

    public function render()
    {
        return view('livewire.command-palette');
    }
}
