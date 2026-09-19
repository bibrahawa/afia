<?php

namespace App\Console\Commands;

use App\Support\Sauvegarde\Chiffrement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Sauvegarde quotidienne de la base (lot R2).
 *
 *   php artisan hali:sauvegarder              export + copie distante (si configurée)
 *   php artisan hali:sauvegarder --locale     export seulement, sans copie distante
 *
 * Étapes : export mysqldump (mot de passe jamais sur la ligne de commande) → compression
 * gzip en flux → chiffrement libsodium si HALI_SAUVEGARDE_CLE est défini → vérification
 * → copie hors du serveur → rotation locale et distante. En cas d'échec : journal +
 * SMS d'alerte si HALI_SAUVEGARDE_ALERTE_TEL est défini.
 *
 * Variables .env (toutes facultatives sauf pour la copie distante) :
 *   HALI_SAUVEGARDE_CLE=phrase-secrete-longue     chiffrement (FORTEMENT recommandé)
 *   HALI_SAUVEGARDE_JOURS_LOCAL=7                 sauvegardes gardées sur le serveur
 *   HALI_SAUVEGARDE_JOURS_DISTANT=30              sauvegardes gardées sur la copie distante
 *   HALI_SAUVEGARDE_ALERTE_TEL=622000000          SMS si la sauvegarde échoue
 *   HALI_SAUVEGARDE_MYSQLDUMP=/usr/bin/mysqldump  si mysqldump n'est pas dans le PATH
 *   HALI_SAUVEGARDE_DRIVER=sftp|ftp|s3            copie distante (voir LISEZMOI-lot-R2.md)
 *   HALI_SAUVEGARDE_HOTE, _PORT, _UTILISATEUR, _MOT_DE_PASSE, _DOSSIER   (sftp / ftp)
 *   HALI_SAUVEGARDE_S3_CLE, _S3_SECRET, _S3_REGION, _S3_BUCKET, _S3_ENDPOINT  (s3)
 */
class SauvegarderBase extends Command
{
    protected $signature = 'hali:sauvegarder {--locale : ne pas envoyer la copie hors du serveur}';

    protected $description = 'Sauvegarde la base de données (compressée, chiffrée) et l\'envoie hors du serveur';

    private const DOSSIER = 'sauvegardes';

    public function handle(): int
    {
        $debut = microtime(true);
        $dossier = storage_path('app/' . self::DOSSIER);
        if (! is_dir($dossier) && ! mkdir($dossier, 0750, true) && ! is_dir($dossier)) {
            return $this->echec("Impossible de créer le dossier {$dossier}.");
        }

        $cle = (string) env('HALI_SAUVEGARDE_CLE', '');
        $nom = 'hali-' . now()->format('Ymd-His') . '.sql.gz' . ($cle !== '' ? '.chiffre' : '');
        $chemin = $dossier . '/' . $nom;

        try {
            $this->exporter($chemin, $cle);
            $taille = filesize($chemin);
            $empreinte = hash_file('sha256', $chemin);
            file_put_contents($chemin . '.sha256', $empreinte . '  ' . $nom . "\n");
            $this->info(sprintf('Sauvegarde locale : %s (%s)', $nom, $this->octets($taille)));

            if (! $this->option('locale') && ($disque = $this->disqueDistant())) {
                $flux = fopen($chemin, 'rb');
                $disque->writeStream($nom, $flux);
                if (is_resource($flux)) {
                    fclose($flux);
                }
                $disque->put($nom . '.sha256', $empreinte . '  ' . $nom . "\n");
                if ($disque->size($nom) !== $taille) {
                    throw new RuntimeException('Copie distante incomplète (taille différente).');
                }
                $this->info('Copie hors du serveur : OK.');
                $this->rotation($disque->files(), fn ($f) => $disque->delete($f), (int) env('HALI_SAUVEGARDE_JOURS_DISTANT', 30));
            } elseif (! $this->option('locale')) {
                $this->warn('Aucune copie hors du serveur : HALI_SAUVEGARDE_DRIVER n\'est pas configuré.');
            }

            $this->rotation(
                array_map('basename', glob($dossier . '/hali-*') ?: []),
                fn ($f) => @unlink($dossier . '/' . $f),
                (int) env('HALI_SAUVEGARDE_JOURS_LOCAL', 7)
            );

            Log::info('Sauvegarde de la base réussie', ['fichier' => $nom, 'octets' => $taille, 'secondes' => round(microtime(true) - $debut, 1), 'chiffree' => $cle !== '']);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            @unlink($chemin);

            return $this->echec($e->getMessage());
        }
    }

    /**
     * mysqldump → gzip (→ chiffrement) en flux : la base n'est jamais chargée en mémoire,
     * et le mot de passe passe par un fichier d'options temporaire (0600) plutôt que par
     * la ligne de commande, visible des autres utilisateurs d'un serveur mutualisé.
     */
    private function exporter(string $destination, string $cle): void
    {
        $connexion = config('database.connections.' . config('database.default'));
        if (! in_array($connexion['driver'] ?? '', ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Seules les bases MySQL / MariaDB sont prises en charge.');
        }

        $options = tempnam(sys_get_temp_dir(), 'hali-my');
        chmod($options, 0600);
        file_put_contents($options, "[client]\nuser=\"" . addcslashes((string) $connexion['username'], "\"\\") . "\"\npassword=\"" . addcslashes((string) $connexion['password'], "\"\\") . "\"\n");

        // Connexion : socket s'il est configuré ; « localhost » sans port (un port précisé fait
        // basculer le client en TCP, refusé par les hébergements qui n'ouvrent que le socket) ;
        // sinon hôte + port.
        $hote = (string) ($connexion['host'] ?? '127.0.0.1');
        $acces = match (true) {
            ! empty($connexion['unix_socket']) => ['--socket=' . $connexion['unix_socket']],
            $hote === 'localhost' => ['--host=localhost'],
            default => ['--host=' . $hote, '--port=' . ($connexion['port'] ?? 3306)],
        };

        $commande = [
            (string) env('HALI_SAUVEGARDE_MYSQLDUMP', 'mysqldump'),
            '--defaults-extra-file=' . $options,
            ...$acces,
            '--single-transaction', '--quick', '--routines', '--triggers', '--no-tablespaces',
            '--default-character-set=utf8mb4',
            (string) $connexion['database'],
        ];

        $processus = proc_open($commande, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tubes);
        if (! is_resource($processus)) {
            @unlink($options);
            throw new RuntimeException('mysqldump introuvable : définissez HALI_SAUVEGARDE_MYSQLDUMP.');
        }

        $temporaire = $destination . '.part';
        $gz = gzopen($temporaire, 'wb6');
        $fin = '';
        while (! feof($tubes[1])) {
            $morceau = fread($tubes[1], 1 << 20);
            if ($morceau === false || $morceau === '') {
                continue;
            }
            gzwrite($gz, $morceau);
            $fin = substr($fin . $morceau, -200);
        }
        gzclose($gz);
        $erreurs = stream_get_contents($tubes[2]);
        fclose($tubes[1]);
        fclose($tubes[2]);
        $code = proc_close($processus);
        @unlink($options);

        // Un export interrompu (disque plein, connexion coupée) n'a pas cette ligne finale.
        if ($code !== 0 || ! str_contains($fin, 'Dump completed')) {
            @unlink($temporaire);
            throw new RuntimeException('Export incomplet (code ' . $code . ') : ' . trim(mb_substr((string) $erreurs, 0, 300)));
        }

        if ($cle !== '') {
            Chiffrement::chiffrerFichier($temporaire, $destination, $cle);
            @unlink($temporaire);
        } else {
            rename($temporaire, $destination);
        }
    }

    /** Disque distant construit depuis le .env (aucune modification de config/filesystems.php). */
    private function disqueDistant()
    {
        $driver = env('HALI_SAUVEGARDE_DRIVER');
        if (! $driver) {
            return null;
        }

        $config = match ($driver) {
            'sftp', 'ftp' => array_filter([
                'driver' => $driver,
                'host' => env('HALI_SAUVEGARDE_HOTE'),
                'port' => (int) env('HALI_SAUVEGARDE_PORT', $driver === 'sftp' ? 22 : 21),
                'username' => env('HALI_SAUVEGARDE_UTILISATEUR'),
                'password' => env('HALI_SAUVEGARDE_MOT_DE_PASSE'),
                'root' => env('HALI_SAUVEGARDE_DOSSIER', '/sauvegardes-hali'),
                'timeout' => 60,
                'ssl' => $driver === 'ftp' ? (bool) env('HALI_SAUVEGARDE_FTP_SSL', true) : null,
            ], fn ($v) => $v !== null),
            's3' => array_filter([
                'driver' => 's3',
                'key' => env('HALI_SAUVEGARDE_S3_CLE'),
                'secret' => env('HALI_SAUVEGARDE_S3_SECRET'),
                'region' => env('HALI_SAUVEGARDE_S3_REGION', 'eu-west-3'),
                'bucket' => env('HALI_SAUVEGARDE_S3_BUCKET'),
                'endpoint' => env('HALI_SAUVEGARDE_S3_ENDPOINT'),
                'root' => env('HALI_SAUVEGARDE_DOSSIER', 'sauvegardes-hali'),
            ], fn ($v) => $v !== null),
            default => throw new RuntimeException("HALI_SAUVEGARDE_DRIVER inconnu : {$driver} (sftp, ftp ou s3)."),
        };

        return Storage::build($config);
    }

    /** Garde les sauvegardes des $jours derniers jours (d'après la date dans le nom du fichier). */
    private function rotation(array $fichiers, callable $supprimer, int $jours): void
    {
        $limite = now()->subDays(max(1, $jours))->format('Ymd');
        foreach ($fichiers as $f) {
            if (preg_match('/hali-(\d{8})-\d{6}\.sql\.gz/', basename($f), $m) && $m[1] < $limite) {
                $supprimer($f);
            }
        }
    }

    private function echec(string $message): int
    {
        $this->error('Sauvegarde ÉCHOUÉE : ' . $message);
        Log::error('Sauvegarde de la base ÉCHOUÉE', ['erreur' => $message]);

        if ($telephone = env('HALI_SAUVEGARDE_ALERTE_TEL')) {
            try {
                app(\App\Services\SmsService::class)->sendSms($telephone,
                    'HALI : la sauvegarde de la base a ECHOUE le ' . now()->format('d/m/Y H:i') . '. Voir storage/logs.',
                    ['type' => 'alerte_sauvegarde']);
            } catch (\Throwable) {
                // L'alerte ne doit pas masquer l'échec initial.
            }
        }

        return self::FAILURE;
    }

    private function octets(int $n): string
    {
        return $n >= 1048576 ? round($n / 1048576, 1) . ' Mo' : round($n / 1024) . ' Ko';
    }
}
