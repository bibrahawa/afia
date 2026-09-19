<?php

namespace App\Services\Catalogue;

use App\Enums\Assurance\FamilleActe;
use App\Models\Department;
use App\Models\Medicament;
use App\Models\Service;
use App\Models\Test;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Lot S3 — Import du catalogue (actes, examens, médicaments) depuis un tableur.
 *
 * Deux temps : analyser() classe chaque ligne SANS rien écrire (aperçu montré à
 * l'utilisateur), puis importer() enregistre tout ou rien (transaction).
 * Un élément déjà présent (même nom, sans tenir compte des accents ni des
 * majuscules) n'est jamais dupliqué : il est ignoré, ou son prix est mis à jour
 * si l'utilisateur l'a demandé.
 */
class ImportCatalogueService
{
    /** Colonnes attendues par type, dans l'ordre du modèle ; et leurs intitulés reconnus. */
    public const COLONNES = [
        'actes' => [
            'nom' => ['nom', 'acte', 'libelle', 'designation', 'intitule', 'name', 'service'],
            'prix' => ['prix', 'tarif', 'montant', 'cout', 'price', 'amount'],
            'departement' => ['departement', 'dept', 'specialite', 'department'],
            'famille' => ['famille', 'categorie', 'famille dactes', 'famille acte'],
        ],
        'examens' => [
            'nom' => ['nom', 'examen', 'analyse', 'libelle', 'designation', 'name', 'test'],
            'prix' => ['prix', 'tarif', 'montant', 'cout', 'price', 'amount'],
            'type' => ['type', 'categorie', 'section', 'discipline'],
            'description' => ['description', 'remarque', 'preparation', 'note'],
        ],
        'medicaments' => [
            'nom' => ['nom', 'medicament', 'produit', 'libelle', 'designation', 'dci', 'name'],
            'forme' => ['forme', 'presentation'],
            'dosage' => ['dosage', 'dose'],
            'frequence' => ['posologie', 'frequence'],
            'duree' => ['duree', 'duree du traitement'],
            'prix' => ['prix', 'tarif', 'montant', 'cout', 'price', 'amount'],
            'instructions' => ['instructions', 'conseils', 'remarque'],
        ],
    ];

    public const TYPES_EXAMEN = ['BIOCHIMIE', 'IMMUNOLOGIE', 'INFECTOLOGIE', 'HEMATOLOGIE', 'HEMOSTASE', 'BACTERIOLOGIE', 'PARASITOLOGIE'];

    public const LIBELLES = ['actes' => 'actes', 'examens' => 'examens', 'medicaments' => 'médicaments'];

    /**
     * @param  array  $options  departement_defaut (id), famille_defaut (valeur FamilleActe), type_defaut (examens), mettre_a_jour_prix (bool)
     */
    public function analyser(string $type, array $lignes, array $options): array
    {
        $colonnes = self::COLONNES[$type];
        [$carte, $avecEntete] = $this->carteColonnes($lignes[0] ?? [], $colonnes);
        $donnees = $avecEntete ? array_slice($lignes, 1) : $lignes;

        $existants = $this->existants($type);
        $departements = $type === 'actes'
            ? Department::pluck('name', 'id')->mapWithKeys(fn ($nom, $id) => [$this->cle($nom) => ['id' => $id, 'nom' => $nom]])
            : collect();

        $vus = [];
        $resultat = [];
        $nouveauxDepartements = [];

        foreach ($donnees as $i => $cellules) {
            $numero = $i + ($avecEntete ? 2 : 1);   // numéro de ligne tel que vu dans Excel
            $v = fn (string $champ) => isset($carte[$champ]) ? trim((string) ($cellules[$carte[$champ]] ?? '')) : '';

            $ligne = ['ligne' => $numero, 'nom' => Str::limit(preg_replace('/\s+/', ' ', $v('nom')), 250, ''), 'statut' => 'nouveau', 'message' => null, 'valeurs' => []];

            if ($ligne['nom'] === '') {
                $ligne['statut'] = 'erreur';
                $ligne['message'] = 'Nom manquant';
                $resultat[] = $ligne;
                continue;
            }

            $cle = $this->cle($ligne['nom']);
            if (isset($vus[$cle])) {
                $ligne['statut'] = 'erreur';
                $ligne['message'] = "Doublon de la ligne {$vus[$cle]}";
                $resultat[] = $ligne;
                continue;
            }
            $vus[$cle] = $numero;

            // Prix
            $prixBrut = $v('prix');
            $prix = $this->prix($prixBrut);
            if ($prixBrut !== '' && $prix === null) {
                $ligne['statut'] = 'erreur';
                $ligne['message'] = "Prix illisible : « {$prixBrut} »";
                $resultat[] = $ligne;
                continue;
            }
            if ($prix === null && $type !== 'medicaments') {
                $ligne['statut'] = 'erreur';
                $ligne['message'] = 'Prix manquant';
                $resultat[] = $ligne;
                continue;
            }

            $valeurs = ['prix' => $prix];

            if ($type === 'actes') {
                $nomDep = $v('departement');
                if ($nomDep === '') {
                    $valeurs['department_id'] = $options['departement_defaut'] ?? null;
                    $valeurs['departement'] = $departements->firstWhere('id', $valeurs['department_id'])['nom'] ?? null;
                    if (! $valeurs['department_id']) {
                        $ligne['statut'] = 'erreur';
                        $ligne['message'] = 'Département manquant (choisissez un département par défaut)';
                        $resultat[] = $ligne;
                        continue;
                    }
                } elseif ($trouve = $departements->get($this->cle($nomDep))) {
                    $valeurs['department_id'] = $trouve['id'];
                    $valeurs['departement'] = $trouve['nom'];
                } else {
                    $nomPropre = Str::limit(trim($nomDep), 100, '');
                    $nouveauxDepartements[$this->cle($nomDep)] = $nomPropre;
                    $valeurs['department_id'] = null;
                    $valeurs['departement'] = $nomPropre;
                    $valeurs['departement_a_creer'] = $this->cle($nomDep);
                }
                $famille = $this->famille($v('famille')) ?? FamilleActe::tryFrom((string) ($options['famille_defaut'] ?? ''))?->value ?? FamilleActe::Consultation->value;
                $valeurs['famille_acte'] = $famille;
            }

            if ($type === 'examens') {
                // tests.report_type est obligatoire en base : type inconnu ou vide → type par défaut.
                $defaut = in_array($options['type_defaut'] ?? null, self::TYPES_EXAMEN, true) ? $options['type_defaut'] : 'BIOCHIMIE';
                $valeurs['report_type'] = $this->typeExamen($v('type')) ?? $defaut;
                $valeurs['description'] = Str::limit($v('description'), 1000, '') ?: null;
                if ($v('type') !== '' && ! $this->typeExamen($v('type'))) {
                    $ligne['message'] = "Type « {$v('type')} » inconnu : classé en " . ucfirst(mb_strtolower($defaut));
                }
            }

            if ($type === 'medicaments') {
                foreach (['forme', 'dosage', 'frequence', 'duree', 'instructions'] as $champ) {
                    $valeurs[$champ] = Str::limit($v($champ), 255, '') ?: null;
                }
                if ($valeurs['forme']) {
                    $valeurs['forme'] = mb_strtoupper($valeurs['forme']);
                }
            }

            // Déjà au catalogue ?
            if ($existant = $existants->get($cle)) {
                $ligne['existant_id'] = $existant['id'];
                $memePrix = $prix === null || abs((float) $existant['prix'] - $prix) < 1;
                if (! empty($options['mettre_a_jour_prix']) && ! $memePrix) {
                    $ligne['statut'] = 'maj';
                    $ligne['message'] = 'Prix : ' . number_format((float) $existant['prix'], 0, ',', ' ') . ' → ' . number_format($prix, 0, ',', ' ') . ' GNF';
                } else {
                    $ligne['statut'] = 'existe';
                    $ligne['message'] = $memePrix ? 'Déjà au catalogue' : 'Déjà au catalogue (prix différent, non modifié)';
                }
            }

            $ligne['valeurs'] = $valeurs;
            $resultat[] = $ligne;
        }

        return [
            'type' => $type,
            'lignes' => $resultat,
            'nouveaux_departements' => array_values($nouveauxDepartements),
            'avec_entete' => $avecEntete,
            'colonnes_reconnues' => array_keys($carte),
            'compte' => collect($resultat)->countBy('statut')->all(),
        ];
    }

    /** Enregistre les lignes « nouveau » et « maj ». Tout ou rien. */
    public function importer(array $analyse): array
    {
        $type = $analyse['type'];

        return DB::transaction(function () use ($analyse, $type) {
            $crees = 0;
            $majs = 0;
            $depsCrees = [];

            foreach ($analyse['lignes'] as $ligne) {
                if (! in_array($ligne['statut'], ['nouveau', 'maj'], true)) {
                    continue;
                }
                $v = $ligne['valeurs'];

                if ($ligne['statut'] === 'maj') {
                    $modele = $this->modele($type)::find($ligne['existant_id']);
                    if ($modele) {
                        $modele->update(['amount' => $v['prix']]);
                        $majs++;
                    }
                    continue;
                }

                if ($type === 'actes') {
                    $depId = $v['department_id'];
                    if (! $depId && isset($v['departement_a_creer'])) {
                        $depId = $depsCrees[$v['departement_a_creer']] ??= Department::create(['name' => $v['departement']])->id;
                    }
                    Service::create(['name' => $ligne['nom'], 'amount' => $v['prix'], 'department_id' => $depId, 'famille_acte' => $v['famille_acte']]);
                } elseif ($type === 'examens') {
                    Test::create(['name' => $ligne['nom'], 'amount' => $v['prix'], 'report_type' => $v['report_type'], 'description' => $v['description']]);
                } else {
                    Medicament::create([
                        'nom' => $ligne['nom'], 'amount' => $v['prix'], 'forme' => $v['forme'], 'dosage' => $v['dosage'],
                        'frequence' => $v['frequence'], 'duree' => $v['duree'], 'instructions' => $v['instructions'],
                    ]);
                }
                $crees++;
            }

            return ['crees' => $crees, 'mis_a_jour' => $majs, 'departements_crees' => count($depsCrees)];
        });
    }

    /** Contenu du fichier modèle (CSV « ; », UTF-8 avec BOM : s'ouvre directement dans Excel). */
    public function modeleCsv(string $type): string
    {
        $exemples = [
            'actes' => [['Nom', 'Prix', 'Département', 'Famille'], ['Consultation générale', '100000', 'Médecine générale', 'Consultations'], ['Échographie pelvienne', '250000', 'Gynécologie', 'Imagerie'], ['Pansement simple', '35000', 'Soins infirmiers', 'Soins']],
            'examens' => [['Nom', 'Prix', 'Type', 'Description'], ['Glycémie à jeun', '40000', 'Biochimie', 'Patient à jeun depuis 8 h'], ['NFS', '60000', 'Hématologie', ''], ['Goutte épaisse', '30000', 'Parasitologie', '']],
            'medicaments' => [['Nom', 'Forme', 'Dosage', 'Posologie', 'Durée', 'Prix', 'Instructions'], ['Paracétamol', 'Comprimé', '500 mg', '3 fois par jour', '5 jours', '5000', 'Pendant le repas'], ['Amoxicilline', 'Gélule', '500 mg', '3 fois par jour', '7 jours', '12000', '']],
        ][$type];

        $flux = fopen('php://temp', 'r+');
        fwrite($flux, "\xEF\xBB\xBF");
        foreach ($exemples as $ligne) {
            fputcsv($flux, $ligne, ';', '"', '');
        }
        rewind($flux);
        $contenu = stream_get_contents($flux);
        fclose($flux);

        return $contenu;
    }

    // ------------------------------------------------------------------

    /** Repère les colonnes par leur intitulé ; sans intitulé reconnu, ordre du modèle. */
    private function carteColonnes(array $premiere, array $colonnes): array
    {
        $carte = [];
        foreach ($premiere as $index => $intitule) {
            $cle = $this->cle((string) $intitule);
            foreach ($colonnes as $champ => $synonymes) {
                // « Prix », « Prix (GNF) », « Prix unitaire », « Nom du médicament »…
                $reconnu = $cle !== '' && collect($synonymes)->contains(fn ($syn) => $cle === $syn || str_starts_with($cle, $syn . ' '));
                if (! isset($carte[$champ]) && $reconnu) {
                    $carte[$champ] = $index;
                    break;
                }
            }
        }

        if (isset($carte['nom'])) {
            return [$carte, true];
        }

        return [array_flip(array_keys($colonnes)), false];
    }

    private function existants(string $type): \Illuminate\Support\Collection
    {
        [$champNom] = match ($type) { 'medicaments' => ['nom'], default => ['name'] };

        return $this->modele($type)::query()->get(['id', $champNom, 'amount'])
            ->mapWithKeys(fn ($m) => [$this->cle($m->{$champNom}) => ['id' => $m->id, 'prix' => (float) $m->amount]]);
    }

    private function modele(string $type): string
    {
        return ['actes' => Service::class, 'examens' => Test::class, 'medicaments' => Medicament::class][$type];
    }

    /** Clé de comparaison : sans accents, sans majuscules, espaces réduits. */
    private function cle(?string $texte): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii((string) $texte))));
    }

    /** « 150 000 », « 150.000 », « 150000 GNF », « 150 000 FG » → 150000. Montants entiers (GNF). */
    private function prix(string $brut): ?float
    {
        $s = trim(str_ireplace(['gnf', 'fg', 'francs', 'franc'], '', $brut));
        if ($s === '') {
            return null;
        }
        $s = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', $s);
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $s)) {   // séparateurs de milliers
            $s = preg_replace('/[.,]/', '', $s);
        }
        $s = str_replace(',', '.', $s);

        return is_numeric($s) && (float) $s >= 0 ? round((float) $s) : null;
    }

    private function famille(string $brut): ?string
    {
        if ($brut === '') {
            return null;
        }
        $cle = $this->cle($brut);
        foreach (FamilleActe::cases() as $f) {
            if ($cle === $this->cle($f->value) || $cle === $this->cle($f->libelle()) || rtrim($this->cle($f->libelle()), 's') === rtrim($cle, 's')) {
                return $f->value;
            }
        }

        return null;
    }

    private function typeExamen(string $brut): ?string
    {
        $cle = strtoupper(str_replace(' ', '', Str::ascii($brut)));
        if ($cle === 'INFECTIOLOGIE') {
            $cle = 'INFECTOLOGIE';
        }

        return in_array($cle, self::TYPES_EXAMEN, true) ? $cle : null;
    }
}
