<?php

namespace App\Livewire;

use App\Models\Workspace;
use App\Models\WorkspaceLayer;
use Livewire\Component;
use Livewire\WithFileUploads;

class WorkspaceViewer extends Component
{
    use WithFileUploads;

    public Workspace $workspace;
    public $layers = [];
    public $showAddVolumeModal = false;
    public $volumeFile;
    public $volumeName = '';

    protected $listeners = [
        'openAddVolume' => 'showAddVolume',
    ];

    protected $rules = [
        'volumeFile' => 'required|file|mimes:nii,gz|max:512000', // 500MB max
        'volumeName' => 'required|string|max:255',
    ];

    public function mount(Workspace $workspace)
    {
        $this->workspace = $workspace;
        $this->loadLayers();
        $workspace->touchLastAccessed();
    }

    public function loadLayers()
    {
        $this->layers = $this->workspace->layers()
            ->orderBy('position')
            ->get()
            ->toArray();
    }

    public function showAddVolume()
    {
        $this->showAddVolumeModal = true;
    }

    public function addVolume()
    {
        $this->validate();

        // Store the uploaded file
        $path = $this->volumeFile->store('volumes/' . $this->workspace->id, 'public');

        // Create a layer record
        $layer = $this->workspace->layers()->create([
            'layer_type' => 'base_volume',
            'name' => $this->volumeName,
            'position' => $this->workspace->getNextLayerPosition(),
            'visible' => true,
            'opacity' => 100,
            'configuration' => [
                'nifti_path' => $path,
                'nifti_url' => asset('storage/' . $path),
                'colormap' => 'gray',
            ],
        ]);

        // Reset form
        $this->reset(['volumeFile', 'volumeName', 'showAddVolumeModal']);

        // Reload layers
        $this->loadLayers();

        // Emit event for JavaScript to load the volume
        $this->dispatch('volumeAdded', [
            'layerId' => $layer->id,
            'url' => asset('storage/' . $path),
            'name' => $layer->name,
        ]);
    }

    public function render()
    {
        return view('livewire.workspace-viewer')
            ->layout('layouts.viewer');
    }
}
