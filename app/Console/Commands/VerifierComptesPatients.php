<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\ActivityLog;
use App\Models\Etablissement;
use App\Models\Patient;
use App\Services\PatientAccountService;
use Illuminate\Console\Command;

/**
 * Contrôle des soldes patients : solde enregistré vs Σ factures − Σ paiements.
 *
 *   php artisan aprosafe:comptes                          # rapport, ne modifie rien
 *   php artisan aprosafe:comptes --etablissement=aprosafe
 *   php artisan aprosafe:comptes --corriger               # aligne les soldes, journalisé
 *   php artisan aprosafe:comptes --seuil=500              # n'afficher que les écarts ≥ 500 GNF
 */
class VerifierComptesPatients extends Command
{
    protected $signature = 'aprosafe:comptes
        {--etablissement= : slug d\'un seul établissement}
        {--corriger : remettre chaque solde à sa valeur attendue}
        {--seuil=1 : écart minimal affiché, en GNF}';

    protected $description = 'Vérifie (et corrige) les soldes des comptes patients';

    public function handle(PatientAccountService $comptes): int
    {
        $requete = Account::withoutGlobalScopes()->whereIn('owner_type', \App\Support\Facturation\TypesFacturables::variantes(Patient::class))->orderBy('etablissement_id')->orderBy('id');

        if ($slug = $this->option('etablissement')) {
            $etab = Etablissement::where('slug', $slug)->first();
            if (! $etab) {
                $this->error("Établissement « {$slug} » introuvable.");

                return self::FAILURE;
            }
            $requete->where('etablissement_id', $etab->id);
        }

        $seuil = (float) $this->option('seuil');
        $lignes = [];
        $totalEcart = 0.0;
        $nbComptes = 0;

        $requete->chunkById(500, function ($lot) use ($comptes, $seuil, &$lignes, &$totalEcart, &$nbComptes) {
            foreach ($lot as $compte) {
                $nbComptes++;
                $attendu = $comptes->soldeAttendu($compte);
                $ecart = round($attendu - (float) $compte->balance, 2);

                if (abs($ecart) < max($seuil, 0.01)) {
                    continue;
                }

                $totalEcart += $ecart;
                $patient = Patient::find($compte->owner_id);
                $lignes[] = [
                    $compte->etablissement_id, $compte->id, $patient?->full_name ?? "patient #{$compte->owner_id}",
                    number_format((float) $compte->balance, 0, ',', ' '), number_format($attendu, 0, ',', ' '), number_format($ecart, 0, ',', ' '),
                ];

                if ($this->option('corriger')) {
                    $corrige = $comptes->recalculer($compte);
                    ActivityLog::create([
                        'etablissement_id' => $compte->etablissement_id,
                        'subject_type' => Account::class, 'subject_id' => $compte->id,
                        'action' => 'comptes.solde_recalcule',
                        'description' => 'Solde recalculé par aprosafe:comptes',
                        'proprietes' => ['ancien' => (float) $compte->balance, 'nouveau' => $attendu, 'ecart' => $corrige],
                    ]);
                }
            }
        });

        $this->info("{$nbComptes} compte(s) patient contrôlé(s).");

        if (! $lignes) {
            $this->info('✔ Aucun écart.');

            return self::SUCCESS;
        }

        $this->table(['Étab.', 'Compte', 'Patient', 'Solde enregistré', 'Solde attendu', 'Écart'], $lignes);
        $this->warn(count($lignes) . ' compte(s) en écart, total ' . number_format($totalEcart, 0, ',', ' ') . ' GNF.');

        if ($this->option('corriger')) {
            $this->info('✔ Soldes corrigés et journalisés (activity_logs : comptes.solde_recalcule).');
        } else {
            $this->line('Rien n\'a été modifié. Après vérification : php artisan aprosafe:comptes --corriger');
        }

        return self::SUCCESS;
    }
}
