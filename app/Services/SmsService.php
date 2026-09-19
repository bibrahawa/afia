<?php

namespace App\Services;

use App\Models\Etablissement;
use App\Models\SmsJournal;
use App\Support\EtablissementContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Envoi de SMS via Nimba.
 *
 * Lot S1 :
 *  - Expéditeur PAR CLINIQUE (etablissements.sms_expediteur), sinon celui de
 *    la plateforme (« HALI »). Le nom doit être enregistré chez Nimba.
 *  - Chaque envoi est inscrit au journal (sms_journal), rattaché à sa clinique,
 *    réussi ou non. Le journal n'empêche jamais un envoi : s'il échoue, le SMS
 *    part quand même.
 *
 * Utilisation (le 3e argument est facultatif, les anciens appels restent valides) :
 *   $sms->sendSms($tel, $message, [
 *       'etablissement' => $clinique,   // modèle ou id ; défaut : clinique courante
 *       'type'          => 'rdv_reminder_24h',
 *       'sujet'         => $rendezVous, // modèle lié (facultatif)
 *       'masquer'       => true,        // codes à usage unique : chiffres masqués au journal
 *   ]);
 */
class SmsService
{
    private $apiKey;
    private $apiUrl;
    private $defaultSender;

    public function __construct()
    {
        $this->apiKey = config('services.nimba_sms.api_key');
        $this->apiUrl = config('services.nimba_sms.api_url');
        $this->defaultSender = config('services.nimba_sms.default_sender', \App\Support\Marque::expediteurSms());
    }

    /**
     * @param  array|string|null  $contexte  tableau d'options (une chaîne, ancien usage, est ignorée)
     */
    public function sendSms(string $phoneNumber, string $message, array|string|null $contexte = []): array
    {
        // Ancien usage (chaîne en 3e argument, ex. « TEST ») : cet argument a toujours
        // été ignoré. On garde ce comportement : un expéditeur non enregistré chez
        // Nimba ferait refuser le SMS.
        $contexte = is_array($contexte) ? $contexte : ['type' => is_string($contexte) && strtoupper($contexte) === 'TEST' ? 'test' : 'autre'];

        $etablissement = $this->etablissement($contexte['etablissement'] ?? null);
        $expediteur = $this->expediteur($contexte['expediteur'] ?? null, $etablissement);

        $resultat = $this->envoyerBrut($phoneNumber, $message, $expediteur);

        $resultat['journal_id'] = $this->journaliser($phoneNumber, $message, $expediteur, $etablissement, $contexte, $resultat);

        return $resultat;
    }

    /**
     * Renvoie un SMS du journal au même numéro, avec le même texte.
     * Les codes à usage unique (masqués) ne sont pas renvoyables.
     */
    public function renvoyer(SmsJournal $original, ?int $utilisateurId = null): array
    {
        if (! $original->renvoyable) {
            return ['success' => false, 'error' => 'Ce SMS contenait un code à usage unique : il ne peut pas être renvoyé.'];
        }

        return $this->sendSms($original->telephone, $original->message, [
            'etablissement' => $original->etablissement_id,
            'type' => $original->type,
            'sujet_type' => $original->sujet_type,
            'sujet_id' => $original->sujet_id,
            'renvoi_de_id' => $original->id,
            'envoye_par' => $utilisateurId,
        ]);
    }

    /** Expéditeur effectif d'une clinique (pour l'affichage dans les paramètres). */
    public function expediteurPour(?Etablissement $etablissement): string
    {
        return $this->expediteur(null, $etablissement);
    }

    // ------------------------------------------------------------------

    private function etablissement($valeur): ?Etablissement
    {
        if ($valeur instanceof Etablissement) {
            return $valeur;
        }
        if (is_numeric($valeur)) {
            return Etablissement::find((int) $valeur);
        }

        try {
            return EtablissementContext::current();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Nimba : 11 caractères au plus, lettres, chiffres, espace, tiret. */
    private function expediteur(?string $force, ?Etablissement $etablissement): string
    {
        foreach ([$force, $etablissement?->sms_expediteur, $this->defaultSender] as $candidat) {
            $candidat = trim((string) $candidat);
            if ($candidat !== '' && preg_match('/^[A-Za-z0-9 \-]{1,11}$/', $candidat)) {
                return $candidat;
            }
        }

        return 'HALI';
    }

    private function envoyerBrut(string $phoneNumber, string $message, string $sender): array
    {
        try {
            $data = [
                'to' => [$phoneNumber],
                'sender_name' => $sender,
                'message' => $message,
            ];

            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", [$this->apiKey, 'Content-Type: application/json']),
                    'content' => json_encode($data),
                    'ignore_errors' => true,
                    // Sans délai maximal, file_get_contents() peut attendre jusqu'à
                    // la limite PHP (erreur fatale impossible à intercepter).
                    'timeout' => 8,
                ],
            ];

            // @ volontaire : un échec réseau émet un avertissement, traité juste en dessous.
            $response = @file_get_contents($this->apiUrl, false, stream_context_create($options));

            if ($response === false) {
                Log::error('Échec envoi SMS — pas de réponse (délai ou connexion)', ['phone' => $phoneNumber]);

                return ['success' => false, 'error' => 'Le service SMS n\'a pas répondu à temps.'];
            }

            $entetes = $http_response_header ?? [];
            preg_match('/HTTP\/\S*\s(\d{3})/', $entetes[0] ?? '', $match);
            $statut = (int) ($match[1] ?? 0);

            if ($statut === 201 || $statut === 200) {
                // CORRIGÉ — l'identifiant était lu sur le code HTTP (un nombre) : toujours perdu.
                $corps = json_decode($response, true);
                $messageId = is_array($corps) ? ($corps['messageid'] ?? $corps['message_id'] ?? $corps['id'] ?? null) : null;

                Log::info('SMS envoyé', ['phone' => $phoneNumber, 'sender' => $sender, 'message_id' => $messageId]);

                return ['success' => true, 'message_id' => $messageId ? (string) $messageId : null, 'data' => $corps];
            }

            $erreur = "Erreur (HTTP {$statut}) : " . mb_substr((string) $response, 0, 500);
            Log::error('Échec envoi SMS', ['phone' => $phoneNumber, 'error' => $erreur, 'status_code' => $statut]);

            return ['success' => false, 'error' => $erreur];
        } catch (\Throwable $e) {
            Log::error('Exception lors de l\'envoi SMS', ['phone' => $phoneNumber, 'error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Erreur technique : ' . $e->getMessage()];
        }
    }

    private function journaliser(string $telephone, string $message, string $expediteur, ?Etablissement $etablissement, array $contexte, array $resultat): ?int
    {
        try {
            $masquer = (bool) ($contexte['masquer'] ?? false);
            $sujet = $contexte['sujet'] ?? null;

            // Écriture hors cloisonnement : un envoi déclenché depuis une clinique
            // peut concerner une autre (laboratoire partenaire). Le journal appartient
            // à la clinique qui envoie.
            $ligne = SmsJournal::withoutEvents(fn () => SmsJournal::create([
                'etablissement_id' => $etablissement?->id,
                'telephone' => mb_substr($telephone, 0, 30),
                // Codes à usage unique : jamais en clair dans le journal.
                'message' => $masquer ? preg_replace('/\b\d{4,8}\b/', '••••••', $message) : $message,
                'expediteur' => $expediteur,
                'type' => mb_substr((string) ($contexte['type'] ?? 'autre'), 0, 40),
                'statut' => ($resultat['success'] ?? false) ? SmsJournal::ENVOYE : SmsJournal::ECHEC,
                'erreur' => ($resultat['success'] ?? false) ? null : mb_substr((string) ($resultat['error'] ?? ''), 0, 1000),
                'message_id' => $resultat['message_id'] ?? null,
                'sujet_type' => $sujet instanceof Model ? $sujet->getMorphClass() : ($contexte['sujet_type'] ?? null),
                'sujet_id' => $sujet instanceof Model ? $sujet->getKey() : ($contexte['sujet_id'] ?? null),
                'renvoyable' => ! $masquer,
                'renvoi_de_id' => $contexte['renvoi_de_id'] ?? null,
                'envoye_par' => $contexte['envoye_par'] ?? null,
            ]));

            return $ligne->id;
        } catch (\Throwable $e) {
            Log::warning('Journal SMS : écriture impossible', ['error' => $e->getMessage()]);

            return null;
        }
    }

    public function sendBulkSms(array $recipients, string $message, string $sender): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $phoneNumber) {
            $result = $this->sendSms($phoneNumber, $message, ['type' => 'autre']); // $sender ignoré, comme avant
            $results[] = ['phone' => $phoneNumber, 'success' => $result['success'], 'error' => $result['error'] ?? null];
            $result['success'] ? $successCount++ : $failureCount++;
            usleep(200000);
        }

        return ['total' => count($recipients), 'success_count' => $successCount, 'failure_count' => $failureCount, 'results' => $results];
    }
}
