<?php

namespace App\Models;

use App\Traits\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

/**
 * Un SMS envoyé (ou tenté) par la plateforme, rattaché à sa clinique.
 * Écrit uniquement par SmsService ; lu par l'écran « Journal des SMS ».
 */
class SmsJournal extends Model
{
    use BelongsToEtablissement;

    protected $table = 'sms_journal';

    public const ENVOYE = 'envoye';
    public const ECHEC = 'echec';

    /** Libellés des types d'envoi (clé enregistrée → affichage). */
    public const TYPES = [
        'rdv_confirmation' => 'Confirmation de rendez-vous',
        'rdv_reminder_24h' => 'Rappel la veille',
        'rdv_reminder_2h' => 'Rappel 2 h avant',
        'rdv_rescheduling' => 'Rendez-vous déplacé',
        'rdv_cancellation' => 'Rendez-vous annulé',
        'rdv_no_availability' => 'Pas de créneau',
        'code_rdv' => 'Code de prise de rendez-vous',
        'code_portail' => 'Code de connexion patient',
        'consentement' => 'Demande d\'accès au dossier',
        'resultats_labo' => 'Résultats d\'analyses',
        'labo_reseau' => 'Laboratoire partenaire',
        'cpn' => 'Rappel de consultation prénatale',
        'acces_personnel' => 'Accès du personnel',
        'test' => 'Test',
        'autre' => 'Autre',
    ];

    protected $fillable = [
        'etablissement_id', 'telephone', 'message', 'expediteur', 'type', 'statut', 'erreur',
        'message_id', 'sujet_type', 'sujet_id', 'renvoyable', 'renvoi_de_id', 'envoye_par',
    ];

    protected $casts = [
        'renvoyable' => 'boolean',
    ];

    public function sujet()
    {
        return $this->morphTo();
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'envoye_par');
    }

    public function renvois()
    {
        return $this->hasMany(self::class, 'renvoi_de_id');
    }

    public function getTypeLibelleAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function estEnvoye(): bool
    {
        return $this->statut === self::ENVOYE;
    }
}
