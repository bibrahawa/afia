<?php

namespace App\Support\Catalogue;

use RuntimeException;

/**
 * Lit la première feuille d'un fichier Excel (.xlsx) ou un CSV, sans bibliothèque
 * externe : un .xlsx est une archive ZIP de fichiers XML (extensions PHP zip et
 * SimpleXML, présentes sur les hébergements usuels).
 *
 * Retourne une liste de lignes, chaque ligne étant une liste de cellules (chaînes).
 * Les lignes entièrement vides sont ignorées.
 */
class LecteurTableur
{
    public const LIGNES_MAX = 2000;
    public const OCTETS_MAX_PAR_FICHIER = 40 * 1024 * 1024;   // 40 Mo décompressés par partie du classeur
    public const OCTETS_MAX_TOTAL = 80 * 1024 * 1024;         // 80 Mo décompressés en tout

    public function lire(string $chemin, string $nomOriginal): array
    {
        $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));

        $lignes = match ($extension) {
            'xlsx' => $this->lireXlsx($chemin),
            'csv', 'txt' => $this->lireCsv($chemin),
            'xls' => throw new RuntimeException('Ancien format Excel (.xls) : ouvrez le fichier et « Enregistrer sous » Excel (.xlsx) ou CSV.'),
            default => throw new RuntimeException('Format non reconnu : envoyez un fichier Excel (.xlsx) ou CSV.'),
        };

        $lignes = array_values(array_filter($lignes, fn ($l) => implode('', array_map('trim', $l)) !== ''));

        if (count($lignes) > self::LIGNES_MAX) {
            throw new RuntimeException('Fichier trop long : ' . self::LIGNES_MAX . ' lignes au plus par import.');
        }

        return $lignes;
    }

    // ------------------------------------------------------------------ CSV

    private function lireCsv(string $chemin): array
    {
        $contenu = (string) file_get_contents($chemin);
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu);           // BOM UTF-8 (Excel)
        if (! mb_check_encoding($contenu, 'UTF-8')) {                         // Excel Windows : CP1252
            $contenu = mb_convert_encoding($contenu, 'UTF-8', 'Windows-1252');
        }
        $contenu = str_replace(["\r\n", "\r"], "\n", $contenu);

        // Séparateur : celui qui apparaît le plus sur la première ligne (Excel français : « ; »).
        $premiere = strtok($contenu, "\n") ?: '';
        $separateur = collect([';', ',', "\t"])->sortByDesc(fn ($s) => substr_count($premiere, $s))->first();

        $flux = fopen('php://temp', 'r+');
        fwrite($flux, $contenu);
        rewind($flux);
        $lignes = [];
        while (($ligne = fgetcsv($flux, 0, $separateur, '"', '')) !== false) {
            $lignes[] = array_map(fn ($c) => trim((string) $c), $ligne);
        }
        fclose($flux);

        return $lignes;
    }

    // ----------------------------------------------------------------- XLSX

    private function lireXlsx(string $chemin): array
    {
        if (! class_exists(\ZipArchive::class) || ! function_exists('simplexml_load_string')) {
            throw new RuntimeException('Lecture Excel indisponible sur ce serveur : enregistrez le fichier en CSV et réessayez.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($chemin) !== true) {
            throw new RuntimeException('Fichier Excel illisible ou endommagé.');
        }

        // Lot R — fichier piégé (« bombe zip ») : quelques Mo compressés peuvent se
        // décompresser en plusieurs Go et saturer la mémoire. Tailles vérifiées AVANT lecture.
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $info = $zip->statIndex($i);
            $total += (int) ($info['size'] ?? 0);
            if ((int) ($info['size'] ?? 0) > self::OCTETS_MAX_PAR_FICHIER || $total > self::OCTETS_MAX_TOTAL) {
                $zip->close();
                throw new RuntimeException('Fichier Excel trop volumineux une fois décompressé : enregistrez-le en CSV ou découpez-le.');
            }
        }

        try {
            $partages = $this->chainesPartagees($zip);
            $feuille = $this->cheminPremiereFeuille($zip);
            $xml = $zip->getFromName($feuille);
            if ($xml === false) {
                throw new RuntimeException('Aucune feuille trouvée dans le fichier Excel.');
            }

            $doc = $this->xml($xml);
            $lignes = [];
            foreach ($doc->sheetData->row ?? [] as $row) {
                $cellules = [];
                foreach ($row->c as $c) {
                    $colonne = $this->indexColonne((string) $c['r']);
                    $cellules[$colonne] = $this->valeurCellule($c, $partages);
                }
                if ($cellules) {
                    $max = max(array_keys($cellules));
                    $lignes[] = array_map(fn ($i) => $cellules[$i] ?? '', range(0, $max));
                }
            }

            return $lignes;
        } finally {
            $zip->close();
        }
    }

    private function chainesPartagees(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $chaines = [];
        foreach ($this->xml($xml)->si as $si) {
            $chaines[] = $this->texteEnrichi($si);
        }

        return $chaines;
    }

    /** Texte simple (<t>) ou enrichi, découpé en morceaux mis en forme (<r><t>). */
    private function texteEnrichi(?\SimpleXMLElement $noeud): string
    {
        if (! $noeud) {
            return '';
        }
        $texte = isset($noeud->t) ? (string) $noeud->t : '';
        foreach ($noeud->r ?? [] as $r) {
            $texte .= (string) $r->t;
        }

        return $texte;
    }

    private function cheminPremiereFeuille(\ZipArchive $zip): string
    {
        $classeur = $zip->getFromName('xl/workbook.xml');
        $liens = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($classeur !== false && $liens !== false) {
            $wb = $this->xml($classeur);
            $premiere = $wb->sheets->sheet[0] ?? null;
            $rid = $premiere ? (string) $premiere->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] : null;
            foreach ($this->xml($liens)->Relationship as $rel) {
                if ((string) $rel['Id'] === $rid) {
                    $cible = ltrim((string) $rel['Target'], '/');
                    return str_starts_with($cible, 'xl/') ? $cible : 'xl/' . $cible;
                }
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function valeurCellule(\SimpleXMLElement $c, array $partages): string
    {
        $type = (string) $c['t'];

        $valeur = match ($type) {
            's' => $partages[(int) $c->v] ?? '',
            'inlineStr' => $this->texteEnrichi($c->is),
            'b' => ((string) $c->v) === '1' ? 'VRAI' : 'FAUX',
            default => (string) $c->v,
        };

        // Nombres : « 150000.0 » ou « 1.5E5 » → forme lisible sans décimales inutiles.
        if ($type === '' || $type === 'n') {
            if (is_numeric($valeur) && (float) $valeur == floor((float) $valeur) && abs((float) $valeur) < 1e15) {
                $valeur = (string) (int) round((float) $valeur);
            }
        }

        return trim($valeur);
    }

    private function indexColonne(string $reference): int
    {
        preg_match('/^([A-Z]+)/', strtoupper($reference), $m);
        $index = 0;
        foreach (str_split($m[1] ?? 'A') as $lettre) {
            $index = $index * 26 + (ord($lettre) - 64);
        }

        return $index - 1;
    }

    private function xml(string $contenu): \SimpleXMLElement
    {
        $precedent = libxml_use_internal_errors(true);
        $doc = simplexml_load_string($contenu, \SimpleXMLElement::class, LIBXML_NONET);
        libxml_use_internal_errors($precedent);
        if ($doc === false) {
            throw new RuntimeException('Fichier Excel endommagé (XML illisible).');
        }

        return $doc;
    }
}
