<?php

namespace App\Services\Labo;

use App\Models\Labo\LaboDemande;
use App\Models\User;
use App\Services\SmsService;
use App\Support\Labo\ContexteLabo;

/**
 * Retour des résultats à la clinique prescriptrice.
 *
 * La clinique voit le compte rendu dès sa publication, mais elle ne regarde pas
 * son écran en permanence : un SMS part vers le contact du partenariat, et les
 * demandes non ouvertes restent signalées dans sa liste.
 */
class NotificationReseauService
{
    public function __construct(private SmsService $sms)
    {
    }

    /** Appelé à la publication d'un compte rendu d'une demande venue du réseau. */
    public function notifierPublication(LaboDemande $demande): void
    {
        if (! $demande->vientDuReseau() || $demande->resultat_notifie_le) {
            return;
        }

        $demande->loadMissing('partenariat.laboratoire', 'patient');
        $telephone = $demande->partenariat?->contact_telephone;

        $demande->update(['resultat_notifie_le' => now()]);

        if (! $telephone) {
            return;
        }

        // Confidentialité : pas de nom de patient dans un SMS envoyé à un
        // numéro saisi à la main. Le numéro de demande suffit à retrouver le
        // dossier dans l'application.
        $message = sprintf(
            'Resultats disponibles pour la demande %s (%s). Consultez %s.',
            $demande->numero,
            $demande->partenariat?->laboratoire?->nom ?? 'laboratoire partenaire',
            \App\Support\Marque::nom()
        );

        $resultat = $this->sms->sendSms($telephone, $message, ['type' => 'labo_reseau', 'sujet' => $demande]);

        ContexteLabo::journaliser('resultats_notifies', $demande, 'Notification à la clinique partenaire', [
            'telephone' => $telephone,
            'succes' => (bool) ($resultat['success'] ?? false),
        ]);
    }

    /** La clinique a ouvert la demande : elle ne compte plus comme « nouveau résultat ». */
    public function marquerVue(LaboDemande $demande, ?User $auteur = null): void
    {
        if (! $demande->premiere_publication_le || $demande->resultat_vu_le) {
            return;
        }

        $demande->forceFill(['resultat_vu_le' => now(), 'resultat_vu_par' => $auteur?->id])->saveQuietly();
    }
}
