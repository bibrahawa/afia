<?php

namespace App\Console\Commands\Parcours;

use App\Models\Parcours\NormeCroissance;
use Illuminate\Console\Command;

/**
 * Import des tables de référence de croissance de l'OMS (méthode LMS).
 *
 * Les fichiers se téléchargent sur le site de l'OMS (Child Growth Standards,
 * « expanded tables », format texte séparé par des tabulations) : une table par
 * indicateur et par sexe, avec les colonnes Month, L, M, S.
 *
 *   php artisan hali:importer-normes-oms wfa-boys.txt poids_age Homme
 *   php artisan hali:importer-normes-oms lhfa-girls.txt taille_age Femme
 *   php artisan hali:importer-normes-oms bfa-boys.txt imc_age Homme
 *
 * Rien n'est inventé ici : sans import, l'application affiche les mesures de
 * l'enfant sans z-score.
 */
class ImporterNormesOms extends Command
{
    protected $signature = 'hali:importer-normes-oms {fichier} {indicateur : poids_age|taille_age|imc_age} {sexe : Homme|Femme} {--separateur=auto}';

    /**
     * Ancien nom, conservé : il figure dans les tâches cron déjà installées
     * chez les clients. À retirer quand tous les serveurs seront à jour.
     */
    protected $aliases = ['aprosafe:importer-normes-oms'];

    protected $description = 'Importe une table de croissance OMS (colonnes Month, L, M, S)';

    public function handle(): int
    {
        $fichier = $this->argument('fichier');
        $indicateur = $this->argument('indicateur');
        $sexe = $this->argument('sexe');

        if (! is_readable($fichier)) {
            $this->error("Fichier illisible : {$fichier}");

            return self::FAILURE;
        }

        if (! in_array($indicateur, [NormeCroissance::POIDS_AGE, NormeCroissance::TAILLE_AGE, NormeCroissance::IMC_AGE], true)) {
            $this->error('Indicateur inconnu : poids_age, taille_age ou imc_age.');

            return self::FAILURE;
        }

        if (! in_array($sexe, ['Homme', 'Femme'], true)) {
            $this->error('Sexe attendu : Homme ou Femme (comme dans la fiche patient).');

            return self::FAILURE;
        }

        $lignes = file($fichier, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $entete = null;
        $importees = 0;

        foreach ($lignes as $ligne) {
            $colonnes = $this->decouper($ligne);

            if ($entete === null) {
                $entete = array_map(fn ($c) => mb_strtolower(trim($c)), $colonnes);
                continue;
            }

            $valeurs = $this->valeurs($entete, $colonnes);

            if ($valeurs === null) {
                continue;
            }

            NormeCroissance::updateOrCreate(
                ['indicateur' => $indicateur, 'sexe' => $sexe, 'mois' => $valeurs['mois']],
                ['l' => $valeurs['l'], 'm' => $valeurs['m'], 's' => $valeurs['s']]
            );
            $importees++;
        }

        $this->info("{$importees} ligne(s) importée(s) pour {$indicateur} / {$sexe}.");

        return $importees > 0 ? self::SUCCESS : self::FAILURE;
    }

    private function decouper(string $ligne): array
    {
        $separateur = $this->option('separateur');

        if ($separateur === 'auto') {
            $separateur = str_contains($ligne, "\t") ? "\t" : (str_contains($ligne, ';') ? ';' : ',');
        }

        return explode($separateur, $ligne);
    }

    /** @return array{mois: int, l: float, m: float, s: float}|null */
    private function valeurs(array $entete, array $colonnes): ?array
    {
        $index = fn (array $noms) => collect($noms)->map(fn ($n) => array_search($n, $entete, true))->first(fn ($i) => $i !== false);

        $iMois = $index(['month', 'mois', 'age']);
        $iL = $index(['l']);
        $iM = $index(['m']);
        $iS = $index(['s']);

        if ($iMois === null || $iL === null || $iM === null || $iS === null) {
            return null;
        }

        foreach ([$iMois, $iL, $iM, $iS] as $i) {
            if (! isset($colonnes[$i]) || ! is_numeric(trim($colonnes[$i]))) {
                return null;
            }
        }

        return [
            'mois' => (int) trim($colonnes[$iMois]),
            'l' => (float) trim($colonnes[$iL]),
            'm' => (float) trim($colonnes[$iM]),
            's' => (float) trim($colonnes[$iS]),
        ];
    }
}
