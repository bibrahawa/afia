<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Department;
use App\Models\Test;
use App\Models\Service;
use App\Models\Package;
use Illuminate\Support\Str;

class AddPackage extends Component
{
    public $packageId;
    public $name;
    public $department;
    public $selectedTests = [];
    public $selectedServices = [];
    public $totalAmount = 0;
    public $description;
    public $editMode = false;

    public $allTests = [];
    public $allServicesByDepartment = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'department' => 'required|exists:departments,id',
        'selectedTests' => 'array',
        'selectedTests.*' => 'exists:tests,id',
        'selectedServices' => 'array',
        'selectedServices.*' => 'exists:services,id',
        'description' => 'nullable|string|max:1000',
    ];

    // Supprimez cette ligne :
    // protected $listeners = ['updatePreview' => 'emitPreviewData'];
    // Et supprimez la propriété $updated si elle existe.

    public function mount($packageId = null)
    {
        $this->packageId = $packageId;
        if ($this->packageId) {
            $package = Package::with(['tests', 'services'])->findOrFail($this->packageId);
            $this->name = $package->name;
            $this->department = $package->department_id;
            $this->description = $package->description;
            $this->selectedTests = $package->tests->pluck('id')->toArray();
            $this->selectedServices = $package->services->pluck('id')->toArray();
            $this->editMode = true;
        }

        $this->loadAllTests();
        $this->loadServicesByDepartment();
        $this->calculateTotalAmount();

        // Émettez les données initiales pour la prévisualisation après le mount
        $this->emitPreviewData();
        $this->dispatch('contentChanged');

    }

    // Méthode générique pour émettre les données de prévisualisation
    private function emitPreviewData()
    {
        // Debug pour voir ce qui est émis
        // dump([
        //     'name' => $this->name,
        //     'selectedTests' => $this->selectedTests,
        //     'selectedServices' => $this->selectedServices,
        //     'totalAmount' => $this->totalAmount,
        //     'description' => $this->description,
        // ]);

        $this->dispatch('updatePreview', [
            'name' => $this->name,
            'selectedTests' => $this->selectedTests,
            'selectedServices' => $this->selectedServices,
            'totalAmount' => $this->totalAmount,
            'description' => $this->description,
        ]);
    }

    public function updatedDepartment($value)
    {
        $this->selectedServices = [];
        $this->loadServicesByDepartment();
        $this->calculateTotalAmount();
        $this->emitPreviewData(); // Appel explicite ici
        // $this->dispatch('contentChanged');
        $this->dispatch('contentChanged');

    }

    public function updatedSelectedTests()
    {
        $this->calculateTotalAmount();
        $this->emitPreviewData(); // Appel explicite ici
    }

    public function updatedSelectedServices()
    {
        $this->calculateTotalAmount();
        $this->emitPreviewData(); // Appel explicite ici
    }

    public function updatedName($value)
    {
        $this->emitPreviewData(); // Appel explicite si le nom du package change
    }

    public function updatedDescription($value)
    {
        $this->emitPreviewData(); // Appel explicite si la description change
    }

    private function loadAllTests()
    {
        $this->allTests = Test::orderBy('name')->get();
    }

    private function loadServicesByDepartment()
    {
        if ($this->department) {
            $this->allServicesByDepartment = Service::where('department_id', $this->department)
                                                    ->orderBy('name')
                                                    ->get();
        } else {
            $this->allServicesByDepartment = collect();
        }
    }

    private function calculateTotalAmount()
    {
        $total = 0;
        if (!empty($this->selectedTests)) {
            $total += Test::whereIn('id', $this->selectedTests)->sum('amount');
        }
        if (!empty($this->selectedServices)) {
            $total += Service::whereIn('id', $this->selectedServices)->sum('amount');
        }
        $this->totalAmount = $total;
    }

    public function save()
    {
        $this->validate();

        if ($this->packageId) {
            $package = Package::findOrFail($this->packageId);
            $package->update([
                'name' => $this->name,
                'department_id' => $this->department,
                'description' => $this->description,
                'price' => $this->totalAmount,
            ]);
            $package->tests()->sync($this->selectedTests);
            $package->services()->sync($this->selectedServices);

            session()->flash('success', 'Package mis à jour avec succès !');
        } else {
            $package = Package::create([
                'name' => $this->name,
                'department_id' => $this->department,
                'description' => $this->description,
                'price' => $this->totalAmount,
            ]);
            $package->tests()->attach($this->selectedTests);
            $package->services()->attach($this->selectedServices);

            session()->flash('success', 'Package créé avec succès !');
        }

        // Il n'est généralement pas nécessaire d'émettre après le save si vous redirigez
        // Si vous restez sur la page, alors oui.
        // $this->emitPreviewData();

        return redirect()->route('package.index');
    }

    public function render()
    {
        $departments = Department::all();

        return view('livewire.add-package', [
            'departments' => $departments,
            'allTests' => $this->allTests,
            'allServicesByDepartment' => $this->allServicesByDepartment,
        ]);
    }
}
