<?php

namespace Tests\Feature\Parcours;

use App\Enums\Parcours\StatutVisite;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\EnsureModuleActive;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Medicament;
use App\Models\MotifRdv;
use App\Models\Parcours\ModeleConsultation;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Test;
use App\Models\User;
use App\Services\Parcours\AccueilService;
use App\Services\Parcours\ModeleConsultationService;
use App\Services\Parcours\SuggestionsConsultationService;
use Carbon\Carbon;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Lot 3b — écran de consultation rapide, modèles et suggestions. MySQL/MariaDB requis. */
class ConsultationRapideTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $etab;
    private User $userMedecin;
    private Employee $medecin;
    private Department $departement;
    private Service $acte;
    private Test $examen;
    private Medicament $medicament;
    private MotifRdv $motif;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->travelTo(Carbon::parse('2026-09-29 09:00:00'));
        $this->withoutMiddleware([CheckPermission::class, Authorize::class, EnsureModuleActive::class]);

        $this->etab = Etablissement::create(['nom' => 'Clinique A', 'slug' => 'clinique-a', 'type' => 'clinique', 'statut' => 'actif']);
        $this->userMedecin = $this->utilisateur();
        $this->actingAs($this->userMedecin);

        $this->departement = Department::create(['name' => 'Médecine générale']);
        $this->medecin = Employee::create(['first_name' => 'Awa', 'last_name' => 'Diallo', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id, 'user_id' => $this->userMedecin->id]);
        $this->acte = Service::create(['name' => 'Consultation générale', 'amount' => 100000, 'department_id' => $this->departement->id]);
        $this->examen = Test::create(['name' => 'Goutte épaisse', 'report_type' => 'numerique', 'amount' => 30000]);
        $this->medicament = Medicament::create(['nom' => 'Artéméther-luméfantrine', 'forme' => 'comprimé', 'dosage' => '20/120 mg', 'frequence' => '2 fois par jour', 'duree' => '3 jours', 'amount' => 25000]);
        $this->motif = MotifRdv::create(['department_id' => $this->departement->id, 'code' => 'controle', 'nom' => 'Contrôle', 'duree_minutes_defaut' => 15, 'service_id' => $this->acte->id]);
        $this->patient = Patient::create(['first_name' => 'Fanta', 'last_name' => 'Camara', 'gender' => 'Femme', 'birth_date' => '1992-04-04']);
        $this->etab->patients()->syncWithoutDetaching([$this->patient->id]);
    }

    public function test_enregistrement_complet_en_un_seul_envoi(): void
    {
        $visite = $this->visite();

        $this->post(route('parcours.consultation.enregistrer', $visite->consultation), [
            'action' => 'terminer',
            'diagnostic' => 'Paludisme simple',
            'signes' => ['Fièvre', 'Céphalées'],
            'actes' => [
                'services' => [['id' => $this->acte->id]],
                'examens' => [['id' => $this->examen->id]],
                'medicaments' => [['id' => $this->medicament->id, 'quantite' => 2, 'dose' => '1 cp', 'frequence' => '2 fois par jour', 'duree' => '3 jours', 'instructions' => 'Après le repas']],
            ],
            'prochain_rdv_jours' => 7,
            'prochain_rdv_motif_id' => $this->motif->id,
        ])->assertRedirect(route('parcours.file.index'))->assertSessionHasNoErrors();

        $consultation = $visite->consultation->fresh(['medicaments', 'tests', 'transaction']);
        $this->assertSame('Paludisme simple', $consultation->diagnostic);
        $this->assertSame(['Fièvre', 'Céphalées'], $consultation->signes_cliniques);
        $this->assertSame(Consultation::TERMINEE, $consultation->statut);
        $this->assertSame(StatutVisite::Terminee, $visite->fresh()->statut);

        // 100 000 (acte) + 30 000 (examen) + 2 × 25 000 (médicament)
        $this->assertSame('180000.00', (string) $consultation->transaction->getRawOriginal('total'));

        $posologie = $consultation->medicaments->first()->pivot;
        $this->assertSame('1 cp', $posologie->dose);
        $this->assertSame(2, (int) $posologie->quantity);

        $rdv = Appointment::where('patient_id', $this->patient->id)->latest('id')->firstOrFail();
        $this->assertSame($this->motif->id, (int) $rdv->motif_rdv_id);
        $this->assertSame('2026-10-06', $rdv->appointment_date->toDateString());
    }

    public function test_terminer_sans_diagnostic_est_refuse(): void
    {
        $visite = $this->visite();

        $this->post(route('parcours.consultation.enregistrer', $visite->consultation), ['action' => 'terminer', 'diagnostic' => ''])
            ->assertSessionHas('error');

        $this->assertSame(StatutVisite::EnAttente, $visite->fresh()->statut);
    }

    public function test_brouillon_garde_la_visite_ouverte(): void
    {
        $visite = $this->visite();

        $this->post(route('parcours.consultation.enregistrer', $visite->consultation), [
            'action' => 'brouillon', 'diagnostic' => 'À confirmer',
            'actes' => ['services' => [['id' => $this->acte->id]]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(StatutVisite::EnAttente, $visite->fresh()->statut);
        $this->assertSame(Consultation::EN_COURS, $visite->consultation->fresh()->statut);
    }

    public function test_modele_enregistre_puis_applique(): void
    {
        $visite = $this->visite();
        $this->post(route('parcours.consultation.enregistrer', $visite->consultation), [
            'action' => 'terminer', 'diagnostic' => 'Paludisme simple', 'signes' => ['Fièvre'],
            'actes' => [
                'examens' => [['id' => $this->examen->id]],
                'medicaments' => [['id' => $this->medicament->id, 'quantite' => 1, 'dose' => '1 cp', 'frequence' => '2 fois par jour', 'duree' => '3 jours']],
            ],
        ]);

        $modele = app(ModeleConsultationService::class)->enregistrerDepuis(
            $visite->consultation->fresh(), ['libelle' => 'Paludisme simple adulte', 'partager' => true], $this->userMedecin
        );

        $this->assertSame(2, $modele->lignes()->count());
        $this->assertNull($modele->medecin_id, 'partagé avec le département');

        $contenu = app(ModeleConsultationService::class)->contenu($modele->fresh(), $this->medecin);
        $this->assertSame('Paludisme simple', $contenu['diagnostic']);
        $this->assertSame(['Fièvre'], $contenu['signes_cliniques']);
        $this->assertSame('1 cp', collect($contenu['lignes'])->firstWhere('type', 'medicament')['dose']);
        $this->assertSame(1, (int) $modele->fresh()->utilisations);
    }

    public function test_suggestions_et_renouvellement(): void
    {
        $visite = $this->visite();
        $this->post(route('parcours.consultation.enregistrer', $visite->consultation), [
            'action' => 'terminer', 'diagnostic' => 'Paludisme simple',
            'actes' => ['medicaments' => [['id' => $this->medicament->id, 'quantite' => 2, 'dose' => '1 cp', 'frequence' => '2 fois par jour', 'duree' => '3 jours']]],
        ]);

        $suggestions = app(SuggestionsConsultationService::class);

        $this->assertSame(['Paludisme simple'], $suggestions->diagnostics($this->medecin)->all());
        $this->assertSame($this->medicament->id, $suggestions->medicaments($this->medecin)->first()['id']);

        $ordonnance = $suggestions->derniereOrdonnance($this->patient);
        $this->assertSame('1 cp', $ordonnance['lignes'][0]['dose']);
        $this->assertSame(2, $ordonnance['lignes'][0]['quantite']);
    }

    public function test_un_confrere_ne_peut_pas_ouvrir_la_consultation(): void
    {
        $visite = $this->visite();

        $userConfrere = $this->utilisateur();
        Employee::create(['first_name' => 'Sékou', 'last_name' => 'Touré', 'address' => 'Conakry', 'type' => 'Doctor', 'department_id' => $this->departement->id, 'user_id' => $userConfrere->id]);
        $this->actingAs($userConfrere);

        $this->get(route('parcours.consultation.show', $visite->consultation))->assertForbidden();
    }

    // ------------------------------------------------------------------ Outils

    private function visite()
    {
        return app(AccueilService::class)->arriveeSansRendezVous(
            $this->patient, $this->medecin, ['motif_rdv_id' => $this->motif->id, 'service_id' => null], $this->userMedecin
        );
    }

    private function utilisateur(): User
    {
        $u = User::create(['name' => 'U', 'email' => Str::random(8) . '@t.gn', 'phone' => (string) random_int(600000000, 699999999), 'password' => bcrypt('x')]);
        $u->forceFill(['etablissement_id' => $this->etab->id])->save();

        return $u;
    }
}
