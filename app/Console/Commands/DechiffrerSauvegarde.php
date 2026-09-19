<?php

namespace App\Console\Commands;

use App\Support\Sauvegarde\Chiffrement;
use Illuminate\Console\Command;

/**
 * Déchiffre une sauvegarde pour la restaurer :
 *   php artisan hali:dechiffrer-sauvegarde storage/app/sauvegardes/hali-20261019-023000.sql.gz.chiffre
 *   gunzip < hali-20261019-023000.sql.gz | mysql -u UTILISATEUR -p NOM_BASE
 * La phrase secrète est demandée au clavier (jamais sur la ligne de commande).
 */
class DechiffrerSauvegarde extends Command
{
    protected $signature = 'hali:dechiffrer-sauvegarde {fichier : chemin du fichier .chiffre}';

    protected $description = 'Déchiffre une sauvegarde chiffrée de la base (étape avant restauration)';

    public function handle(): int
    {
        $source = $this->argument('fichier');
        if (! is_file($source)) {
            $this->error("Fichier introuvable : {$source}");

            return self::FAILURE;
        }
        $destination = preg_replace('/\.chiffre$/', '', $source);
        if ($destination === $source || is_file($destination)) {
            $destination = $source . '.dechiffre.sql.gz';
        }

        $phrase = $this->secret('Phrase secrète (HALI_SAUVEGARDE_CLE)') ?: (string) env('HALI_SAUVEGARDE_CLE', '');

        try {
            Chiffrement::dechiffrerFichier($source, $destination, $phrase);
        } catch (\Throwable $e) {
            @unlink($destination);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Déchiffré : {$destination}");
        $this->line('Restauration : gunzip < ' . basename($destination) . ' | mysql -u UTILISATEUR -p NOM_BASE');

        return self::SUCCESS;
    }
}
