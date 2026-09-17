<?php

namespace App\Models\Labo;

use App\Models\Consultation;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Patient;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\Labo\ModeFacturation;
use App\Enums\Labo\OrigineDemande;
use App\Enums\Labo\StatutDemande;
use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class LaboDemande extends Model
{
    use BelongsToEtablissement;

    protected $table = 'labo_demandes';

    protected $fillable = [
        'etablissement_id', 'numero', 'patient_id', 'origine', 'consultation_id',
        'prescripteur_employee_id', 'prescripteur_externe', 'prescripteur_telephone',
        'etablissement_prescripteur_id', 'renseignements_cliniques', 'grossesse',
        'semaines_amenorrhee', 'a_jeun_confirme', 'urgence', 'statut', 'mode_facturation',
        'resultats_retenus_si_impaye', 'enregistre_par', 'annule_le', 'annule_par',
        'motif_annulation', 'premiere_publication_le',
    ];

    protected $casts = [
        'origine' => OrigineDemande::class,
        'statut' => StatutDemande::class,
        'mode_facturation' => ModeFacturation::class,
        'grossesse' => 'boolean',
        'a_jeun_confirme' => 'boolean',
        'urgence' => 'boolean',
        'resultats_retenus_si_impaye' => 'boolean',
        'annule_le' => 'datetime',
        'premiere_publication_le' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'numero';
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function examens()
    {
        return $this->hasMany(LaboDemandeExamen::class, 'demande_id');
    }

    public function echantillons()
    {
        return $this->hasMany(LaboEchantillon::class, 'demande_id');
    }

    public function comptesRendus()
    {
        return $this->hasMany(LaboCompteRendu::class, 'demande_id')->orderByDesc('version');
    }

    public function remises()
    {
        return $this->hasMany(LaboRemise::class, 'demande_id')->latest('remis_le');
    }

    public function consultation()
    {
        return $this->belongsTo(Consultation::class);
    }

    public function prescripteur()
    {
        return $this->belongsTo(Employee::class, 'prescripteur_employee_id');
    }

    public function etablissementPrescripteur()
    {
        return $this->belongsTo(Etablissement::class, 'etablissement_prescripteur_id');
    }

    public function enregistrePar()
    {
        return $this->belongsTo(User::class, 'enregistre_par');
    }

    /** Transaction de la facturation EXISTANTE (Transaction → Invoice → Paiement). */
    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    public function nomPrescripteur(): string
    {
        return $this->prescripteur?->full_name
            ?? $this->prescripteur_externe
            ?? ($this->origine === OrigineDemande::SPONTANEE ? 'Sans prescripteur' : '—');
    }

    public function estAnnulee(): bool
    {
        return $this->statut === StatutDemande::ANNULEE;
    }

    /**
     * La part patient est-elle réglée ? Sert UNIQUEMENT à retenir la remise
     * des résultats au patient (SMS, remise au guichet). Le médecin
     * prescripteur interne voit toujours les résultats : la sécurité du
     * patient ne dépend jamais d'un paiement.
     */
    public function partPatientReglee(): bool
    {
        if ($this->mode_facturation !== ModeFacturation::LABO) {
            return true;
        }

        $transaction = $this->transaction;
        if (! $transaction) {
            return false;
        }

        // « completed » : posé par Invoice quand la part patient est réglée (voir Invoice::markAsPaid).
        return in_array($transaction->status, ['paid', 'approved', 'completed'], true);
    }

    public function peutEtreRemisAuPatient(): bool
    {
        return ! $this->resultats_retenus_si_impaye || $this->partPatientReglee();
    }
}
