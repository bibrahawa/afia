<?php

namespace App\Support\Assurance;

use App\Models\Chambre;
use App\Models\Labo\LaboExamen;
use App\Models\Medicament;
use App\Models\Package;
use App\Models\Service;
use App\Models\Test;
use App\Support\Facturation\TypesFacturables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Actes facturables de l'établissement courant (listes des écrans de
 * convention) : libellé et prix catalogue, quel que soit le type d'acte.
 */
class CatalogueActes
{
    public const TYPES = [
        'service' => ['classe' => Service::class, 'libelle' => 'Services', 'nom' => 'name', 'prix' => 'amount'],
        'package' => ['classe' => Package::class, 'libelle' => 'Packages / forfaits', 'nom' => 'name', 'prix' => 'price'],
        'test' => ['classe' => Test::class, 'libelle' => 'Examens', 'nom' => 'name', 'prix' => 'amount'],
        'labo_examen' => ['classe' => LaboExamen::class, 'libelle' => 'Examens de laboratoire', 'nom' => 'nom', 'prix' => 'prix'],
        'medicament' => ['classe' => Medicament::class, 'libelle' => 'Médicaments', 'nom' => 'nom', 'prix' => 'amount'],
        'chambre' => ['classe' => Chambre::class, 'libelle' => 'Chambres (par jour)', 'nom' => 'numero', 'prix' => 'prix_par_jour'],
    ];

    /** @return Collection<string, Collection> actes par type (alias) */
    public function parType(): Collection
    {
        return collect(self::TYPES)->map(fn ($def) => $def['classe']::query()->orderBy($def['nom'])->get()
            ->map(fn (Model $acte) => ['id' => $acte->id, 'nom' => $this->nom($acte, $def), 'prix' => (float) $acte->{$def['prix']}]));
    }

    /** Acte de l'établissement courant (global scope), ou null. */
    public function trouver(string $type, int $id): ?Model
    {
        $alias = $this->alias($type);

        return $alias ? self::TYPES[$alias]['classe']::find($id) : null;
    }

    public function libelle(?Model $acte): string
    {
        if (! $acte) {
            return 'Acte supprimé du catalogue';
        }

        $alias = $this->alias($acte->getMorphClass());

        return $alias ? $this->nom($acte, self::TYPES[$alias]) : class_basename($acte);
    }

    public function prixCatalogue(?Model $acte): ?float
    {
        $alias = $acte ? $this->alias($acte->getMorphClass()) : null;

        return $alias ? (float) $acte->{self::TYPES[$alias]['prix']} : null;
    }

    public function alias(string $type): ?string
    {
        $classe = TypesFacturables::classe($type);
        $alias = $classe ? array_search($classe, TypesFacturables::CARTE, true) : false;

        return $alias && isset(self::TYPES[$alias]) ? $alias : null;
    }

    private function nom(Model $acte, array $def): string
    {
        return $def['nom'] === 'numero' ? 'Chambre ' . $acte->numero : (string) $acte->{$def['nom']};
    }
}
