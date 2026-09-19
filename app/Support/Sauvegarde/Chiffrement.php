<?php

namespace App\Support\Sauvegarde;

use RuntimeException;

/**
 * Chiffrement des sauvegardes (libsodium, fourni avec PHP) — en flux, par blocs de 1 Mo :
 * un export de plusieurs Go ne passe jamais en mémoire.
 *
 * Format : « HALIENC1 » | sel (16 o) | en-tête secretstream (24 o) | blocs [longueur 4 o | bloc chiffré]
 * Clé : Argon2id(phrase, sel). XChaCha20-Poly1305 authentifie chaque bloc ; le dernier
 * porte l'étiquette FINAL, donc un fichier tronqué ou modifié est détecté au déchiffrement.
 */
class Chiffrement
{
    private const MAGIQUE = 'HALIENC1';
    private const BLOC = 1 << 20;

    public static function chiffrerFichier(string $source, string $destination, string $phrase): void
    {
        $sel = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $cle = self::cle($phrase, $sel);
        [$etat, $entete] = sodium_crypto_secretstream_xchacha20poly1305_init_push($cle);

        $in = fopen($source, 'rb');
        $out = fopen($destination, 'wb');
        fwrite($out, self::MAGIQUE . $sel . $entete);

        $suivant = fread($in, self::BLOC);
        do {
            $courant = $suivant;
            $suivant = feof($in) ? '' : fread($in, self::BLOC);
            $dernier = $suivant === '' || $suivant === false;
            $chiffre = sodium_crypto_secretstream_xchacha20poly1305_push($etat, (string) $courant, '',
                $dernier ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE);
            fwrite($out, pack('N', strlen($chiffre)) . $chiffre);
        } while (! $dernier);

        fclose($in);
        fclose($out);
        sodium_memzero($cle);
    }

    public static function dechiffrerFichier(string $source, string $destination, string $phrase): void
    {
        $in = fopen($source, 'rb');
        if (fread($in, strlen(self::MAGIQUE)) !== self::MAGIQUE) {
            throw new RuntimeException('Ce fichier n\'est pas une sauvegarde chiffrée Hali.');
        }
        $sel = fread($in, SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $entete = fread($in, SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);
        $cle = self::cle($phrase, $sel);
        $etat = sodium_crypto_secretstream_xchacha20poly1305_init_pull($entete, $cle);

        $out = fopen($destination, 'wb');
        $fini = false;
        while (! $fini) {
            $taille = fread($in, 4);
            if ($taille === false || strlen($taille) < 4) {
                throw new RuntimeException('Fichier tronqué : la sauvegarde est incomplète.');
            }
            $bloc = fread($in, unpack('N', $taille)[1]);
            $resultat = sodium_crypto_secretstream_xchacha20poly1305_pull($etat, $bloc);
            if ($resultat === false) {
                throw new RuntimeException('Phrase secrète incorrecte ou fichier modifié.');
            }
            [$clair, $etiquette] = $resultat;
            fwrite($out, $clair);
            $fini = $etiquette === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
        }
        fclose($in);
        fclose($out);
        sodium_memzero($cle);
    }

    private static function cle(string $phrase, string $sel): string
    {
        if (mb_strlen($phrase) < 12) {
            throw new RuntimeException('HALI_SAUVEGARDE_CLE doit contenir au moins 12 caractères.');
        }

        return sodium_crypto_pwhash(SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES, $phrase, $sel,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE, SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE, SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13);
    }
}
