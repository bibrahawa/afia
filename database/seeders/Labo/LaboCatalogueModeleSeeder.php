<?php

namespace Database\Seeders\Labo;

use App\Models\Labo\LaboAntibiotique;
use App\Models\Labo\LaboBilan;
use App\Models\Labo\LaboExamen;
use App\Models\Labo\LaboGerme;
use App\Models\Labo\LaboParametre;
use App\Models\Labo\LaboSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * CATALOGUE MODÈLE DE LA PLATEFORME (etablissement_id = NULL).
 *
 * php artisan db:seed --class="Database\Seeders\Labo\LaboCatalogueModeleSeeder"
 *
 * À LIRE AVANT TOUTE MISE EN SERVICE :
 * - Les normes ci-dessous sont des valeurs ADULTES usuelles de la
 *   littérature francophone, fournies comme point de départ. Elles DOIVENT
 *   être relues et validées par le biologiste responsable de chaque labo :
 *   elles dépendent des réactifs, de l'automate et de la méthode.
 * - Aucune norme pédiatrique n'est fournie VOLONTAIREMENT : un enfant
 *   obtient « norme non définie » (aucun flag) plutôt qu'une norme adulte
 *   fausse. Chaque labo ajoute ses plages pédiatriques.
 * - Tous les prix sont à 0 : chaque labo fixe ses tarifs.
 *
 * Idempotent : relançable sans doublon (clé = code).
 */
class LaboCatalogueModeleSeeder extends Seeder
{
    private const ADULTE = 16 * 365;

    public function run(): void
    {
        if (\App\Support\EtablissementContext::id()) {
            throw new \RuntimeException('Ce seeder crée le catalogue MODÈLE : lancez-le hors de toute session établissement.');
        }

        DB::transaction(function () {
            $sections = $this->sections();
            $examens = [];

            foreach ($this->examens() as $def) {
                $examens[$def['code']] = $this->creerExamen($def, $sections[$def['section']]);
            }

            foreach ($this->bilans() as $code => [$nom, $codesExamens]) {
                $bilan = LaboBilan::withoutGlobalScope('etablissement')->updateOrCreate(
                    ['etablissement_id' => null, 'code' => $code],
                    ['nom' => $nom, 'actif' => true]
                );
                $bilan->examens()->sync(collect($codesExamens)->map(fn ($c) => $examens[$c]->id)->all());
            }

            foreach ($this->germes() as $nom) {
                LaboGerme::withoutGlobalScope('etablissement')->firstOrCreate(['etablissement_id' => null, 'nom' => $nom], ['actif' => true]);
            }

            foreach ($this->antibiotiques() as $i => [$nom, $famille]) {
                LaboAntibiotique::withoutGlobalScope('etablissement')->updateOrCreate(
                    ['etablissement_id' => null, 'nom' => $nom],
                    ['famille' => $famille, 'ordre' => $i, 'actif' => true]
                );
            }
        });
    }

    private function sections(): array
    {
        $defs = [
            'HEMATO' => 'Hématologie',
            'BIOCHIMIE' => 'Biochimie',
            'HORMONO' => 'Hormonologie',
            'SEROLOGIE' => 'Sérologie / Immunologie',
            'PARASITO' => 'Parasitologie',
            'BACTERIO' => 'Bactériologie',
            'URINES' => 'Analyses urinaires',
        ];

        $ids = [];
        $ordre = 0;
        foreach ($defs as $code => $nom) {
            $ids[$code] = LaboSection::withoutGlobalScope('etablissement')->updateOrCreate(
                ['etablissement_id' => null, 'code' => $code],
                ['nom' => $nom, 'ordre' => $ordre += 10, 'actif' => true]
            )->id;
        }

        return $ids;
    }

    private function creerExamen(array $def, int $sectionId): LaboExamen
    {
        $examen = LaboExamen::withoutGlobalScope('etablissement')->updateOrCreate(
            ['etablissement_id' => null, 'code' => $def['code']],
            [
                'section_id' => $sectionId,
                'nom' => $def['nom'],
                'abreviation' => $def['abreviation'] ?? null,
                'type_examen' => $def['type_examen'] ?? 'standard',
                'type_echantillon' => $def['echantillon'],
                'tube' => $def['tube'] ?? null,
                'a_jeun' => $def['a_jeun'] ?? false,
                'instructions_patient' => $def['instructions'] ?? null,
                'delai_rendu_heures' => $def['delai'] ?? 24,
                'prix' => 0,
                // Maladies à déclaration obligatoire : indicatif, à valider avec l'autorité sanitaire.
                'mdo_maladie' => self::MDO[$def['code']][0] ?? null,
                'mdo_immediate' => self::MDO[$def['code']][1] ?? false,
                'ordre' => $def['ordre'] ?? 0,
                'actif' => true,
            ]
        );

        foreach ($def['parametres'] as $ordre => $p) {
            $parametre = LaboParametre::withoutGlobalScope('etablissement')->updateOrCreate(
                ['examen_id' => $examen->id, 'code' => $p[0]],
                [
                    'etablissement_id' => null,
                    'libelle' => $p[1],
                    'type_resultat' => $p[2],
                    'unite' => $p[3] ?? null,
                    'decimales' => $p[4] ?? 1,
                    'options' => $p['options'] ?? null,
                    'formule' => $p['formule'] ?? null,
                    'groupe' => $p['groupe'] ?? null,
                    'obligatoire' => $p['obligatoire'] ?? true,
                    'ordre' => ($ordre + 1) * 10,
                ]
            );

            $parametre->valeursReference()->delete();
            foreach ($p['normes'] ?? [] as $norme) {
                $parametre->valeursReference()->create($norme);
            }
        }

        return $examen;
    }

    // ------------------------------------------------------------------
    // Petits constructeurs de normes
    // ------------------------------------------------------------------

    private static function n(?float $min, ?float $max, ?float $cmin = null, ?float $cmax = null, ?string $sexe = null, ?string $texte = null): array
    {
        return [
            'sexe' => $sexe, 'age_min_jours' => self::ADULTE, 'age_max_jours' => null, 'grossesse' => null,
            'min' => $min, 'max' => $max, 'critique_min' => $cmin, 'critique_max' => $cmax,
            'texte_affiche' => $texte,
        ];
    }

    /** Qualitatif : valable à tout âge (un VIH négatif n'a pas d'âge). */
    private static function q(string $attendue): array
    {
        return ['sexe' => null, 'age_min_jours' => null, 'age_max_jours' => null, 'grossesse' => null, 'valeur_attendue' => $attendue];
    }

    private const NEG_POS = ['Négatif', 'Positif'];
    /** [code examen => [maladie, notification immédiate]] — même liste que la migration 2026_09_20_100001. */
    private const MDO = [
        'GE' => ['Paludisme', false],
        'TDR_PALU' => ['Paludisme', false],
        'WIDAL' => ['Fièvre typhoïde', false],
        'VIH' => ['Infection à VIH', false],
        'SYPH' => ['Syphilis', false],
        'AGHBS' => ['Hépatite B', false],
        'HCV' => ['Hépatite C', false],
        'COPRO' => ['Diarrhée bactérienne (dont choléra, shigellose)', true],
    ];

    private const NON_REACTIF = ['Non réactif', 'Réactif', 'Indéterminé'];
    private const CROIX = ['Négatif', 'Traces', '+', '++', '+++'];

    private function examens(): array
    {
        return [
            // ================= HÉMATOLOGIE =================
            [
                'code' => 'NFS', 'nom' => 'Numération Formule Sanguine', 'abreviation' => 'NFS', 'section' => 'HEMATO',
                'echantillon' => 'sang', 'tube' => 'violet', 'delai' => 4, 'ordre' => 10,
                'parametres' => [
                    ['GB', 'Leucocytes', 'numerique', '10³/µL', 2, 'normes' => [self::n(4.0, 10.0, 2.0, 30.0)]],
                    ['GR', 'Hématies', 'numerique', '10⁶/µL', 2, 'normes' => [self::n(4.5, 5.9, null, null, 'M'), self::n(4.0, 5.2, null, null, 'F')]],
                    ['HB', 'Hémoglobine', 'numerique', 'g/dL', 1, 'normes' => [self::n(13.0, 17.0, 7.0, 20.0, 'M'), self::n(12.0, 16.0, 7.0, 20.0, 'F')]],
                    ['HT', 'Hématocrite', 'numerique', '%', 1, 'normes' => [self::n(40, 52, null, null, 'M'), self::n(37, 47, null, null, 'F')]],
                    ['VGM', 'VGM', 'numerique', 'fL', 1, 'normes' => [self::n(80, 100)]],
                    ['TCMH', 'TCMH', 'numerique', 'pg', 1, 'normes' => [self::n(27, 32)]],
                    ['CCMH', 'CCMH', 'numerique', 'g/dL', 1, 'normes' => [self::n(32, 36)]],
                    ['PLQ', 'Plaquettes', 'numerique', '10³/µL', 0, 'normes' => [self::n(150, 400, 20, 1000)]],
                    ['PNN', 'Polynucléaires neutrophiles', 'numerique', '%', 1, 'groupe' => 'Formule leucocytaire', 'normes' => [self::n(40, 75)]],
                    ['LYM', 'Lymphocytes', 'numerique', '%', 1, 'groupe' => 'Formule leucocytaire', 'normes' => [self::n(20, 45)]],
                    ['MONO', 'Monocytes', 'numerique', '%', 1, 'groupe' => 'Formule leucocytaire', 'normes' => [self::n(2, 10)]],
                    ['PNE', 'Polynucléaires éosinophiles', 'numerique', '%', 1, 'groupe' => 'Formule leucocytaire', 'normes' => [self::n(1, 6)]],
                    ['PNB', 'Polynucléaires basophiles', 'numerique', '%', 1, 'groupe' => 'Formule leucocytaire', 'normes' => [self::n(0, 1)]],
                ],
            ],
            [
                'code' => 'VS', 'nom' => 'Vitesse de sédimentation', 'abreviation' => 'VS', 'section' => 'HEMATO',
                'echantillon' => 'sang', 'tube' => 'violet', 'delai' => 4, 'ordre' => 20,
                'parametres' => [
                    ['VS1H', 'VS 1re heure', 'numerique', 'mm', 0, 'normes' => [self::n(null, 15, null, null, 'M'), self::n(null, 20, null, null, 'F')]],
                ],
            ],
            [
                'code' => 'GSRH', 'nom' => 'Groupe sanguin ABO / Rhésus', 'section' => 'HEMATO',
                'echantillon' => 'sang', 'tube' => 'violet', 'delai' => 4, 'ordre' => 30,
                'parametres' => [
                    ['ABO', 'Groupe ABO', 'liste', null, 0, 'options' => ['A', 'B', 'AB', 'O']],
                    ['RH', 'Rhésus (D)', 'liste', null, 0, 'options' => ['Positif', 'Négatif']],
                ],
            ],
            [
                'code' => 'EHB', 'nom' => 'Électrophorèse de l\'hémoglobine', 'section' => 'HEMATO',
                'echantillon' => 'sang', 'tube' => 'violet', 'delai' => 72, 'ordre' => 40,
                'parametres' => [
                    ['PHENO', 'Phénotype hémoglobinique', 'liste', null, 0, 'options' => ['AA', 'AS', 'SS', 'AC', 'SC', 'CC', 'Autre']],
                    ['COMM', 'Commentaire', 'texte', null, 0, 'obligatoire' => false],
                ],
            ],

            // ================= BIOCHIMIE =================
            [
                'code' => 'GLY', 'nom' => 'Glycémie à jeun', 'section' => 'BIOCHIMIE', 'echantillon' => 'sang', 'tube' => 'gris',
                'a_jeun' => true, 'instructions' => 'À jeun depuis 8 à 12 heures (eau autorisée).', 'delai' => 4, 'ordre' => 10,
                'parametres' => [['GLY', 'Glycémie', 'numerique', 'g/L', 2, 'normes' => [self::n(0.70, 1.10, 0.40, 5.00)]]],
            ],
            [
                'code' => 'HBA1C', 'nom' => 'Hémoglobine glyquée', 'abreviation' => 'HbA1c', 'section' => 'BIOCHIMIE',
                'echantillon' => 'sang', 'tube' => 'violet', 'delai' => 24, 'ordre' => 15,
                'parametres' => [['HBA1C', 'HbA1c', 'numerique', '%', 1, 'normes' => [self::n(4.0, 6.0)]]],
            ],
            [
                'code' => 'UREE', 'nom' => 'Urée sanguine', 'section' => 'BIOCHIMIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 4, 'ordre' => 20,
                'parametres' => [['UREE', 'Urée', 'numerique', 'g/L', 2, 'normes' => [self::n(0.15, 0.45)]]],
            ],
            [
                'code' => 'CREA', 'nom' => 'Créatininémie', 'section' => 'BIOCHIMIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 4, 'ordre' => 30,
                'parametres' => [['CREA', 'Créatinine', 'numerique', 'mg/L', 1, 'normes' => [self::n(7, 13, null, null, 'M'), self::n(5, 11, null, null, 'F')]]],
            ],
            [
                'code' => 'AU', 'nom' => 'Acide urique', 'section' => 'BIOCHIMIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 24, 'ordre' => 40,
                'parametres' => [['AU', 'Acide urique', 'numerique', 'mg/L', 0, 'normes' => [self::n(35, 70, null, null, 'M'), self::n(25, 60, null, null, 'F')]]],
            ],
            [
                'code' => 'LIPIDES', 'nom' => 'Bilan lipidique', 'section' => 'BIOCHIMIE', 'echantillon' => 'sang', 'tube' => 'jaune',
                'a_jeun' => true, 'instructions' => 'À jeun depuis 12 heures.', 'delai' => 24, 'ordre' => 50,
                'parametres' => [
                    ['CT', 'Cholestérol total', 'numerique', 'g/L', 2, 'normes' => [self::n(null, 2.00)]],
                    ['HDL', 'HDL-cholestérol', 'numerique', 'g/L', 2, 'normes' => [self::n(0.40, null, null, null, 'M'), self::n(0.50, null, null, null, 'F')]],
                    ['TG', 'Triglycérides', 'numerique', 'g/L', 2, 'normes' => [self::n(null, 1.50)]],
                    // Friedewald : non valable si TG > 3,40 g/L — le biologiste commente.
                    ['LDL', 'LDL-cholestérol (calculé)', 'calcule', 'g/L', 2, 'formule' => 'CT - HDL - TG / 5', 'normes' => [self::n(null, 1.60)]],
                    ['CT_HDL', 'Rapport CT / HDL', 'calcule', null, 2, 'formule' => 'CT / HDL', 'obligatoire' => false, 'normes' => [self::n(null, 5.0)]],
                ],
            ],
            [
                'code' => 'TRANSA', 'nom' => 'Transaminases', 'section' => 'BIOCHIMIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 24, 'ordre' => 60,
                'parametres' => [
                    ['ASAT', 'ASAT (TGO)', 'numerique', 'UI/L', 0, 'normes' => [self::n(null, 35)]],
                    ['ALAT', 'ALAT (TGP)', 'numerique', 'UI/L', 0, 'normes' => [self::n(null, 45)]],
                ],
            ],
            [
                'code' => 'GGT', 'nom' => 'Gamma GT', 'section' => 'BIOCHIMIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 24, 'ordre' => 70,
                'parametres' => [['GGT', 'Gamma GT', 'numerique', 'UI/L', 0, 'normes' => [self::n(null, 55, null, null, 'M'), self::n(null, 38, null, null, 'F')]]],
            ],
            [
                'code' => 'IONO', 'nom' => 'Ionogramme sanguin', 'section' => 'BIOCHIMIE', 'echantillon' => 'sang', 'tube' => 'vert', 'delai' => 4, 'ordre' => 80,
                'parametres' => [
                    ['NA', 'Sodium', 'numerique', 'mmol/L', 0, 'normes' => [self::n(135, 145, 120, 160)]],
                    ['K', 'Potassium', 'numerique', 'mmol/L', 1, 'normes' => [self::n(3.5, 5.0, 2.5, 6.5)]],
                    ['CL', 'Chlore', 'numerique', 'mmol/L', 0, 'normes' => [self::n(98, 107)]],
                ],
            ],
            [
                'code' => 'CA', 'nom' => 'Calcémie', 'section' => 'BIOCHIMIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 24, 'ordre' => 90,
                'parametres' => [['CA', 'Calcium', 'numerique', 'mg/L', 0, 'normes' => [self::n(85, 105, 65, 130)]]],
            ],
            [
                'code' => 'CRP', 'nom' => 'Protéine C réactive', 'abreviation' => 'CRP', 'section' => 'BIOCHIMIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 4, 'ordre' => 100,
                'parametres' => [['CRP', 'CRP', 'numerique', 'mg/L', 1, 'normes' => [self::n(null, 5.0)]]],
            ],

            // ================= HORMONOLOGIE =================
            [
                'code' => 'TSH', 'nom' => 'TSH', 'section' => 'HORMONO', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 48, 'ordre' => 10,
                'parametres' => [['TSH', 'TSH ultrasensible', 'numerique', 'mUI/L', 2, 'normes' => [self::n(0.40, 4.00)]]],
            ],
            [
                'code' => 'PSA', 'nom' => 'PSA total', 'section' => 'HORMONO', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 48, 'ordre' => 20,
                'parametres' => [['PSA', 'PSA total', 'numerique', 'ng/mL', 2, 'normes' => [self::n(null, 4.00, null, null, 'M')]]],
            ],
            [
                'code' => 'BHCG', 'nom' => 'Test de grossesse (β-hCG urinaire)', 'section' => 'HORMONO', 'echantillon' => 'urine', 'tube' => 'pot', 'delai' => 2, 'ordre' => 30,
                'instructions' => 'Premières urines du matin de préférence.',
                // Pas de valeur « attendue » : un test positif n'est pas un résultat anormal.
                'parametres' => [['BHCG', 'β-hCG urinaire', 'liste', null, 0, 'options' => self::NEG_POS]],
            ],

            // ================= SÉROLOGIE =================
            [
                'code' => 'VIH', 'nom' => 'Sérologie VIH 1 & 2 (dépistage)', 'section' => 'SEROLOGIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 24, 'ordre' => 10,
                'parametres' => [['VIH', 'Anticorps anti-VIH 1 & 2', 'qualitatif', null, 0, 'options' => self::NON_REACTIF, 'normes' => [self::q('Non réactif')]]],
            ],
            [
                'code' => 'AGHBS', 'nom' => 'Antigène HBs', 'section' => 'SEROLOGIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 24, 'ordre' => 20,
                'parametres' => [['AGHBS', 'Ag HBs', 'qualitatif', null, 0, 'options' => self::NEG_POS, 'normes' => [self::q('Négatif')]]],
            ],
            [
                'code' => 'HCV', 'nom' => 'Sérologie hépatite C', 'section' => 'SEROLOGIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 24, 'ordre' => 30,
                'parametres' => [['HCV', 'Anticorps anti-VHC', 'qualitatif', null, 0, 'options' => self::NEG_POS, 'normes' => [self::q('Négatif')]]],
            ],
            [
                'code' => 'SYPH', 'nom' => 'Sérologie syphilis (TPHA / VDRL)', 'section' => 'SEROLOGIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 24, 'ordre' => 40,
                'parametres' => [
                    ['TPHA', 'TPHA', 'qualitatif', null, 0, 'options' => self::NEG_POS, 'normes' => [self::q('Négatif')]],
                    ['VDRL', 'VDRL / RPR', 'qualitatif', null, 0, 'options' => self::NEG_POS, 'normes' => [self::q('Négatif')]],
                ],
            ],
            [
                'code' => 'WIDAL', 'nom' => 'Sérodiagnostic de Widal et Félix', 'section' => 'SEROLOGIE', 'echantillon' => 'sang', 'tube' => 'jaune', 'delai' => 24, 'ordre' => 50,
                'parametres' => [
                    ['TO', 'Salmonella Typhi O', 'liste', null, 0, 'options' => ['< 1/80', '1/80', '1/160', '1/320', '≥ 1/640']],
                    ['TH', 'Salmonella Typhi H', 'liste', null, 0, 'options' => ['< 1/80', '1/80', '1/160', '1/320', '≥ 1/640']],
                    ['COMM', 'Interprétation', 'texte', null, 0, 'obligatoire' => false],
                ],
            ],

            // ================= PARASITOLOGIE =================
            [
                'code' => 'GE', 'nom' => 'Goutte épaisse / frottis (paludisme)', 'section' => 'PARASITO', 'echantillon' => 'sang', 'tube' => 'violet', 'delai' => 2, 'ordre' => 10,
                'parametres' => [
                    ['GE', 'Recherche de Plasmodium', 'qualitatif', null, 0, 'options' => self::NEG_POS, 'normes' => [self::q('Négatif')]],
                    ['ESPECE', 'Espèce', 'liste', null, 0, 'obligatoire' => false, 'options' => ['P. falciparum', 'P. malariae', 'P. ovale', 'P. vivax', 'Non déterminée']],
                    ['DP', 'Densité parasitaire', 'numerique', 'parasites/µL', 0, 'obligatoire' => false],
                ],
            ],
            [
                'code' => 'TDR_PALU', 'nom' => 'TDR paludisme', 'section' => 'PARASITO', 'echantillon' => 'sang', 'delai' => 1, 'ordre' => 20,
                'parametres' => [['TDR', 'TDR paludisme', 'qualitatif', null, 0, 'options' => ['Négatif', 'Positif (P. falciparum)', 'Positif (Pan)', 'Invalide'], 'normes' => [self::q('Négatif')]]],
            ],
            [
                'code' => 'KAOP', 'nom' => 'Examen parasitologique des selles (KAOP)', 'section' => 'PARASITO', 'echantillon' => 'selles', 'tube' => 'pot', 'delai' => 24, 'ordre' => 30,
                'parametres' => [
                    ['RESULTAT', 'Parasites', 'qualitatif', null, 0, 'options' => ['Absence de parasites', 'Présence de parasites'], 'normes' => [self::q('Absence de parasites')]],
                    ['DETAIL', 'Parasites observés', 'texte', null, 0, 'obligatoire' => false],
                ],
            ],

            // ================= URINES =================
            [
                'code' => 'BU', 'nom' => 'Bandelette urinaire', 'section' => 'URINES', 'echantillon' => 'urine', 'tube' => 'pot', 'delai' => 2, 'ordre' => 10,
                'parametres' => [
                    ['LEU', 'Leucocytes', 'semi_quanti', null, 0, 'options' => self::CROIX, 'normes' => [self::q('Négatif')]],
                    ['NIT', 'Nitrites', 'qualitatif', null, 0, 'options' => self::NEG_POS, 'normes' => [self::q('Négatif')]],
                    ['PROT', 'Protéines', 'semi_quanti', null, 0, 'options' => self::CROIX, 'normes' => [self::q('Négatif')]],
                    ['GLU', 'Glucose', 'semi_quanti', null, 0, 'options' => self::CROIX, 'normes' => [self::q('Négatif')]],
                    ['SANG', 'Sang', 'semi_quanti', null, 0, 'options' => self::CROIX, 'normes' => [self::q('Négatif')]],
                    ['CET', 'Corps cétoniques', 'semi_quanti', null, 0, 'options' => self::CROIX, 'normes' => [self::q('Négatif')]],
                    ['PH', 'pH', 'numerique', null, 1, 'normes' => [self::n(5.0, 8.0)]],
                ],
            ],

            // ================= BACTÉRIOLOGIE =================
            [
                'code' => 'ECBU', 'nom' => 'Examen cytobactériologique des urines (ECBU)', 'abreviation' => 'ECBU', 'section' => 'BACTERIO',
                'type_examen' => 'bacteriologie', 'echantillon' => 'urine', 'tube' => 'pot', 'delai' => 72, 'ordre' => 10,
                'instructions' => 'Premières urines du matin, après toilette, milieu de jet, dans le pot stérile.',
                'parametres' => [
                    ['ASPECT', 'Aspect', 'liste', null, 0, 'options' => ['Clair', 'Légèrement trouble', 'Trouble', 'Hématique']],
                    ['LEUCO', 'Leucocytes', 'numerique', '/mL', 0, 'groupe' => 'Cytologie', 'normes' => [self::n(null, 10000)]],
                    ['HEMA', 'Hématies', 'numerique', '/mL', 0, 'groupe' => 'Cytologie', 'normes' => [self::n(null, 10000)]],
                    ['CRIST', 'Cristaux / cylindres', 'texte', null, 0, 'groupe' => 'Cytologie', 'obligatoire' => false],
                    ['CULTURE', 'Culture', 'qualitatif', null, 0, 'groupe' => 'Culture', 'options' => ['Stérile', 'Positive', 'Polymicrobienne (contamination probable)'], 'normes' => [self::q('Stérile')]],
                    ['NUMERATION', 'Numération bactérienne', 'liste', null, 0, 'groupe' => 'Culture', 'obligatoire' => false, 'options' => ['< 10³ UFC/mL', '10³ UFC/mL', '10⁴ UFC/mL', '10⁵ UFC/mL', '> 10⁵ UFC/mL']],
                ],
            ],
            [
                'code' => 'COPRO', 'nom' => 'Coproculture', 'section' => 'BACTERIO', 'type_examen' => 'bacteriologie',
                'echantillon' => 'selles', 'tube' => 'pot', 'delai' => 72, 'ordre' => 20,
                'parametres' => [
                    ['CULTURE', 'Culture', 'qualitatif', null, 0, 'options' => ['Absence de germe pathogène', 'Germe pathogène isolé'], 'normes' => [self::q('Absence de germe pathogène')]],
                ],
            ],
        ];
    }

    private function bilans(): array
    {
        return [
            'PRENATAL' => ['Bilan prénatal', ['NFS', 'GSRH', 'GLY', 'VIH', 'AGHBS', 'SYPH', 'EHB', 'BU']],
            'PREOP' => ['Bilan pré-opératoire', ['NFS', 'GSRH', 'GLY', 'UREE', 'CREA', 'VIH', 'AGHBS']],
            'RENAL' => ['Bilan rénal', ['UREE', 'CREA', 'IONO']],
            'HEPATIQUE' => ['Bilan hépatique', ['TRANSA', 'GGT']],
            'FIEVRE' => ['Bilan fébrile', ['NFS', 'GE', 'CRP', 'WIDAL']],
        ];
    }

    private function germes(): array
    {
        return [
            'Escherichia coli', 'Klebsiella pneumoniae', 'Proteus mirabilis', 'Enterobacter spp.',
            'Citrobacter spp.', 'Pseudomonas aeruginosa', 'Acinetobacter baumannii', 'Staphylococcus aureus',
            'Staphylocoque à coagulase négative', 'Enterococcus spp.', 'Streptococcus spp.',
            'Salmonella spp.', 'Shigella spp.', 'Candida albicans',
        ];
    }

    private function antibiotiques(): array
    {
        return [
            ['Amoxicilline', 'Pénicillines'], ['Amoxicilline + acide clavulanique', 'Pénicillines'],
            ['Oxacilline', 'Pénicillines'], ['Céfalotine', 'Céphalosporines'], ['Céfotaxime', 'Céphalosporines'],
            ['Ceftriaxone', 'Céphalosporines'], ['Ceftazidime', 'Céphalosporines'], ['Imipénème', 'Carbapénèmes'],
            ['Gentamicine', 'Aminosides'], ['Amikacine', 'Aminosides'], ['Acide nalidixique', 'Quinolones'],
            ['Ciprofloxacine', 'Fluoroquinolones'], ['Lévofloxacine', 'Fluoroquinolones'],
            ['Triméthoprime + sulfaméthoxazole', 'Sulfamides'], ['Nitrofurantoïne', 'Nitrofuranes'],
            ['Fosfomycine', 'Divers'], ['Érythromycine', 'Macrolides'], ['Clindamycine', 'Lincosamides'],
            ['Vancomycine', 'Glycopeptides'], ['Doxycycline', 'Cyclines'], ['Chloramphénicol', 'Phénicolés'],
        ];
    }
}
