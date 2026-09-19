<?php

namespace App\Support\Images;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Images d'identité (logos, signatures, cachets) : contrôle strict AVANT enregistrement.
 *
 * Chaque type a sa fiche : formats, poids, dimensions minimales et maximales, proportions,
 * transparence. Le contrôle ne dépend pas de l'extension GD :
 *  - type réel du fichier lu par fileinfo (pas par son extension) ;
 *  - dimensions lues par getimagesize() ;
 *  - transparence d'un PNG lue dans son en-tête (type de couleur 4 ou 6, ou bloc tRNS).
 * Si GD est présent, l'image est en plus ré-encodée (métadonnées et contenu parasite retirés).
 *
 * Stockage : les logos sont publics (disque « public ») ; signatures et cachets sont PRIVÉS
 * (disque « local », hors du dossier public) : jamais accessibles par une adresse web,
 * seulement intégrés aux documents au moment de les produire.
 */
class ImageControlee
{
    /** Fiches techniques. [largeur, hauteur] en pixels ; ratio = largeur / hauteur. */
    public const FICHES = [
        'logo' => [
            'libelle' => 'Logo de l\'application',
            'formats' => ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'],
            'ko_max' => 1024, 'min' => [128, 128], 'max' => [2048, 2048], 'ratio' => [0.8, 4.0],
            'transparence' => false, 'prive' => false,
            'conseil' => 'Idéal : 512 × 512 px (carré) ou 800 × 400 px, PNG à fond transparent.',
        ],
        'logo_documents' => [
            'libelle' => 'Logo des documents',
            'formats' => ['image/png' => 'png', 'image/jpeg' => 'jpg'],
            'ko_max' => 1024, 'min' => [300, 80], 'max' => [2400, 1200], 'ratio' => [1.0, 6.0],
            'transparence' => false, 'prive' => false,
            'conseil' => 'Idéal : 1200 × 300 px, PNG. Une version noir et blanc s\'imprime mieux sur les tickets thermiques.',
        ],
        'signature' => [
            'libelle' => 'Signature',
            'formats' => ['image/png' => 'png'],
            'ko_max' => 500, 'min' => [300, 100], 'max' => [1600, 800], 'ratio' => [1.5, 6.0],
            'transparence' => true, 'prive' => true,
            'conseil' => 'Idéal : 900 × 300 px, PNG à fond transparent, encre noire ou bleu foncé, signature recadrée au plus près.',
        ],
        'cachet' => [
            'libelle' => 'Cachet',
            'formats' => ['image/png' => 'png'],
            'ko_max' => 500, 'min' => [250, 250], 'max' => [1200, 1200], 'ratio' => [0.8, 1.25],
            'transparence' => true, 'prive' => true,
            'conseil' => 'Idéal : 600 × 600 px, PNG à fond transparent, cachet recadré au plus près.',
        ],
    ];

    public static function fiche(string $type): array
    {
        return self::FICHES[$type] ?? throw new \InvalidArgumentException("Type d'image inconnu : {$type}");
    }

    /**
     * Contrôle puis enregistre l'image ; retourne le chemin relatif au disque.
     * $champ : nom du champ de formulaire (pour placer l'erreur au bon endroit).
     */
    public static function enregistrer(UploadedFile $fichier, string $type, string $dossier, string $champ, ?string $ancien = null): string
    {
        $fiche = self::fiche($type);
        $contenu = self::controler($fichier, $fiche, $champ);

        $nom = Str::slug($type) . '-' . Str::lower(Str::random(12)) . '.' . $contenu['extension'];
        $chemin = trim($dossier, '/') . '/' . $nom;
        $disque = Storage::disk($fiche['prive'] ? 'local' : 'public');
        $disque->put($chemin, $contenu['octets'], $fiche['prive'] ? 'private' : 'public');

        if ($ancien) {
            self::supprimer($type, $ancien);
        }

        return $chemin;
    }

    public static function supprimer(string $type, ?string $chemin): void
    {
        if (! $chemin || str_starts_with($chemin, 'http') || str_starts_with($chemin, 'assets/')) {
            return;   // image livrée avec l'application ou externe : jamais supprimée
        }
        Storage::disk(self::fiche($type)['prive'] ? 'local' : 'public')->delete($chemin);
    }

    /** Chemin absolu (pour DomPDF), ou null. */
    public static function cheminAbsolu(string $type, ?string $chemin): ?string
    {
        if (! $chemin) {
            return null;
        }
        $absolu = Storage::disk(self::fiche($type)['prive'] ? 'local' : 'public')->path($chemin);
        if (is_file($absolu)) {
            return $absolu;
        }
        // Logos installés avant cet écran, directement dans public/ (ex. assets/img/aprosafe.png).
        // Jamais pour une signature ou un cachet : ils ne doivent pas être publics.
        if (! self::fiche($type)['prive'] && function_exists('public_path') && is_file(public_path($chemin))) {
            return public_path($chemin);
        }

        return null;
    }

    /** Image en « data: URI » (documents imprimés par le navigateur, aperçus protégés), ou null. */
    public static function dataUri(string $type, ?string $chemin): ?string
    {
        $absolu = self::cheminAbsolu($type, $chemin);
        if (! $absolu) {
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolu) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($absolu));
    }

    // ------------------------------------------------------------------ contrôle

    private static function controler(UploadedFile $fichier, array $fiche, string $champ): array
    {
        $refus = fn (string $message) => throw ValidationException::withMessages([$champ => $fiche['libelle'] . ' : ' . $message]);

        if (! $fichier->isValid()) {
            $refus('le fichier n\'a pas pu être reçu. Réessayez.');
        }

        $octets = (string) file_get_contents($fichier->getRealPath());
        $taille = strlen($octets);
        if ($taille > $fiche['ko_max'] * 1024) {
            $refus(sprintf('le fichier pèse %s Ko, le maximum est %d Ko. Réduisez-le ou exportez-le en PNG optimisé.', number_format($taille / 1024, 0, ',', ' '), $fiche['ko_max']));
        }

        // Type RÉEL (contenu), pas l'extension : un .png renommé en autre chose est refusé.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($octets) ?: '';
        if (! isset($fiche['formats'][$mime])) {
            $refus('format refusé (' . ($mime ?: 'inconnu') . '). Formats acceptés : ' . strtoupper(implode(', ', array_unique($fiche['formats']))) . '.');
        }

        $infos = @getimagesizefromstring($octets);
        if (! $infos || $infos[0] < 1 || $infos[1] < 1) {
            $refus('image illisible ou endommagée.');
        }
        [$l, $h] = $infos;

        if ($l < $fiche['min'][0] || $h < $fiche['min'][1]) {
            $refus("image trop petite ({$l} × {$h} px) : elle serait floue à l'impression. Minimum {$fiche['min'][0]} × {$fiche['min'][1]} px. {$fiche['conseil']}");
        }
        if ($l > $fiche['max'][0] || $h > $fiche['max'][1]) {
            $refus("image trop grande ({$l} × {$h} px). Maximum {$fiche['max'][0]} × {$fiche['max'][1]} px : réduisez-la. {$fiche['conseil']}");
        }

        $ratio = $l / $h;
        if ($ratio < $fiche['ratio'][0] || $ratio > $fiche['ratio'][1]) {
            $forme = $fiche['ratio'][1] <= 1.3 ? 'à peu près carrée' : sprintf('plus large que haute (de %s à %s fois)', self::nombre($fiche['ratio'][0]), self::nombre($fiche['ratio'][1]));
            $refus("proportions inadaptées ({$l} × {$h} px). L'image doit être {$forme}. Recadrez-la au plus près. {$fiche['conseil']}");
        }

        if ($fiche['transparence'] && ! self::pngTransparent($octets)) {
            $refus('le fond doit être transparent (PNG avec transparence). Une signature ou un cachet sur fond blanc masquerait le texte du document. ' . $fiche['conseil']);
        }

        return ['octets' => self::reencoder($octets, $mime), 'extension' => $fiche['formats'][$mime]];
    }

    /** PNG avec canal alpha (type de couleur 4 ou 6) ou bloc de transparence tRNS. */
    public static function pngTransparent(string $octets): bool
    {
        if (strncmp($octets, "\x89PNG\r\n\x1a\n", 8) !== 0 || strlen($octets) < 33) {
            return false;
        }
        $typeCouleur = ord($octets[25]);   // IHDR : largeur(4) hauteur(4) profondeur(1) type(1)
        if (in_array($typeCouleur, [4, 6], true)) {
            return true;
        }
        // Bloc tRNS avant les données d'image (IDAT).
        $idat = strpos($octets, 'IDAT');

        return ($t = strpos($octets, 'tRNS')) !== false && ($idat === false || $t < $idat);
    }

    /** Ré-encodage (retire métadonnées et contenu parasite) si GD est disponible ; sinon octets d'origine. */
    private static function reencoder(string $octets, string $mime): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $octets;
        }
        $image = @imagecreatefromstring($octets);
        if (! $image) {
            return $octets;
        }
        ob_start();
        if ($mime === 'image/png') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, null, 6);
        } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
            imagewebp($image, null, 90);
        } else {
            imagejpeg($image, null, 90);
        }
        $propre = (string) ob_get_clean();
        imagedestroy($image);

        return $propre !== '' ? $propre : $octets;
    }

    private static function nombre(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, ',', ''), '0'), ',');
    }
}
