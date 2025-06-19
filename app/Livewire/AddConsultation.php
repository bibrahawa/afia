<?php

namespace App\Livewire;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\Employee;
use App\Models\Examen;
use App\Models\Service;
use App\Models\Package;
use App\Models\Medicament;
use App\Models\Test;
use App\Models\ConsultationExamen;
use Carbon\Carbon;

use Livewire\Component;

class AddConsultation extends Component
{

    public $patient;
    public $medecins;
    public $isFirstConsultation;

    public $medical_history = '';
    public $motif = '';
    public $signes_cliniques = '';
    public $diagnostic = '';
    public $observation = '';
    public $ordonnance = '';
    public $rendez_vous;
    public $medecin_id;

    public $tests = [];
    public $services = [];
    public $packages = [];
    public $medicaments = [];
    public $selected_exam;
    public $packageExamens = [];
    public $patients = [];

    public function mount()
    {

        $department = auth()->user()->department;

        $this->services = Service::where('department_id', $department->id)->get();
        $this->packages = Package::all();
        $this->patients = Patient::all();
        $this->medicaments = Medicament::all();
        $this->medecins = Employee::where('type', 'doctor')->get();
        $this->tests    = Test::all();

        $this->patients= Patient::all();
        // $this->patient = $patient;
        // $this->isFirstConsultation = Consultation::where('patient_id', $patient->id)->count() === 0;

    }

    public function addExamToPackage()
    {
        if ($this->selected_exam) {
            $exam = Examen::find($this->selected_exam);
            if ($exam && !collect($this->packageExamens)->pluck('id')->contains($exam->id)) {
                $this->packageExamens[] = ['id' => $exam->id, 'nom' => $exam->nom];
            }
            $this->selected_exam = null;
        }
    }

    public function removeExam($id)
    {
        $this->packageExamens = collect($this->packageExamens)
            ->reject(fn($e) => $e['id'] == $id)
            ->values()
            ->all();
    }

    public function saveConsultation()
    {
        $this->validate([
            'motif' => 'required',
            'signes_cliniques' => 'required',
            'diagnostic' => 'required',
            'medecin_id' => 'required|exists:medecins,id',
        ]);

        $consultation = Consultation::create([
            'patient_id' => $this->patient->id,
            'medecin_id' => $this->medecin_id,
            'motif' => $this->motif,
            'signes_cliniques' => $this->signes_cliniques,
            'diagnostic' => $this->diagnostic,
            'observation' => $this->observation,
            'ordonnance' => $this->ordonnance,
            'rendez_vous' => $this->rendez_vous,
            'medical_history' => $this->isFirstConsultation ? $this->medical_history : null,
        ]);

        foreach ($this->packageExamens as $exam) {
            ConsultationExamen::create([
                'consultation_id' => $consultation->id,
                'examen_id' => $exam['id']
            ]);
        }

        session()->flash('success', 'Consultation enregistrée avec succès.');
        return redirect()->route('consultations.index');
    }


    public function render()
    {
        return view('livewire.add-consultation');
    }

}
