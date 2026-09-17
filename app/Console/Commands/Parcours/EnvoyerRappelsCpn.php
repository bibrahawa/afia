<?php

namespace App\Console\Commands\Parcours;

use App\Models\Parcours\Grossesse;
use App\Models\Parcours\GrossesseRappel;
use App\Services\SmsService;
use Illuminate\Console\Command;

/**
 * Rappel SMS des consultations prénatales à programmer.
 *
 * À planifier une fois par jour (scheduler) :
 *   php artisan aprosafe:rappels-cpn --jours=3
 *
 * Un contact du calendrier n'est rappelé qu'une seule fois (table grossesse_rappels).
 */
class EnvoyerRappelsCpn extends Command
{
    protected $signature = 'aprosafe:rappels-cpn {--jours=3 : nombre de jours avant la date cible} {--test : n\'envoie rien, affiche seulement}';

    protected $description = 'Envoie un SMS aux patientes dont la prochaine consultation prénatale approche';

    public function handle(SmsService $sms): int
    {
        $jours = max(0, (int) $this->option('jours'));
        $envoyes = 0;
        $ignores = 0;

        $grossesses = Grossesse::withoutGlobalScopes()
            ->where('statut', Grossesse::EN_COURS)
            ->with(['patient.comptesPatients', 'patient'])
            ->get();

        foreach ($grossesses as $grossesse) {
            $contact = collect($grossesse->calendrier())
                ->first(fn ($c) => $c['statut'] !== 'faite' && $c['date_cible']->betweenIncluded(today(), today()->addDays($jours)));

            if (! $contact) {
                continue;
            }

            $dejaEnvoye = GrossesseRappel::withoutGlobalScopes()
                ->where('grossesse_id', $grossesse->id)
                ->where('semaines', $contact['semaines'])
                ->exists();

            if ($dejaEnvoye) {
                continue;
            }

            $telephone = $grossesse->patient?->comptesPatients->first()?->telephone;

            if (! $telephone) {
                $ignores++;
                continue;
            }

            $message = sprintf(
                'Bonjour %s, votre consultation prenatale (%s SA) est prevue vers le %s. Merci de passer a la clinique.',
                $grossesse->patient->first_name,
                $contact['semaines'],
                $contact['date_cible']->format('d/m/Y')
            );

            if ($this->option('test')) {
                $this->line("[test] {$telephone} : {$message}");
                $envoyes++;
                continue;
            }

            $resultat = $sms->sendSms($telephone, $message);

            GrossesseRappel::withoutGlobalScopes()->create([
                'etablissement_id' => $grossesse->etablissement_id,
                'grossesse_id' => $grossesse->id,
                'semaines' => $contact['semaines'],
                'envoye_le' => now(),
                'telephone' => $telephone,
                'succes' => (bool) ($resultat['success'] ?? false),
            ]);

            $envoyes++;
        }

        $this->info("{$envoyes} rappel(s) traité(s), {$ignores} patiente(s) sans numéro de téléphone.");

        return self::SUCCESS;
    }
}
