<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Test;
use App\Models\Service;

class PackagePreview extends Component
{
    public $packageName = '';
    public $selectedTests = [];
    public $selectedServices = [];
    public $totalAmount = 0;
    public $description = '';
    public $currentDate;

    protected $listeners = [
        'updatePreview' => 'updatePreviewData'
    ];

    public function mount()
    {
        $this->currentDate = now()->format('Y-m-d');
    }

    public function updatePreviewData($data)
    {
        $this->packageName = $data['name'];
        $this->selectedTests = $data['selectedTests'];
        $this->selectedServices = $data['selectedServices'];
        $this->totalAmount = $data['totalAmount'];
        $this->description = $data['description'];
    }

    public function render()
    {
        // Récupérer les détails des tests et services sélectionnés pour l'affichage
        // Ces requêtes sont nécessaires car le composant de prévisualisation ne reçoit que les IDs
        $previewTests = Test::whereIn('id', $this->selectedTests)->get();
        $previewServices = Service::whereIn('id', $this->selectedServices)->get();

        return view('livewire.package-preview', [
            'previewTests' => $previewTests,
            'previewServices' => $previewServices,
        ]);
    }
}
