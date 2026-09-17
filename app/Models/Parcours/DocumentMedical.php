<?php

namespace App\Models\Parcours;

use App\Enums\Parcours\TypeDocumentMedical;
use App\Models\Consultation;
use App\Models\Employee;
use App\Models\Patient;
use App\Models\User;
use App\Traits\BelongsToEtablissement;
use App\Traits\HeriteEtablissement;
use Illuminate\Database\Eloquent\Model;

/** Certificat médical, arrêt de travail ou certificat de grossesse, numéroté et conservé. */
class DocumentMedical extends Model
{
    use HeriteEtablissement, BelongsToEtablissement;

    protected static array $etablissementDepuis = ['consultation_id' => Consultation::class];

    protected $table = 'documents_medicaux';

    protected $fillable = [
        'etablissement_id', 'patient_id', 'consultation_id', 'medecin_id', 'numero', 'type',
        'contenu', 'date_debut', 'date_fin', 'jours', 'annule', 'motif_annulation', 'cree_par',
    ];

    protected $casts = [
        'type' => TypeDocumentMedical::class,
        'date_debut' => 'date',
        'date_fin' => 'date',
        'annule' => 'boolean',
    ];

    public function patient() { return $this->belongsTo(Patient::class); }
    public function consultation() { return $this->belongsTo(Consultation::class); }
    public function medecin() { return $this->belongsTo(Employee::class, 'medecin_id'); }
    public function auteur() { return $this->belongsTo(User::class, 'cree_par'); }
}
