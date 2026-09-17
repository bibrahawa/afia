<?php

namespace Tests\Feature;

use App\Models\Etablissement;
use App\Services\NumerotationDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumerotationDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequence_propre_a_chaque_etablissement(): void
    {
        $a = Etablissement::create(['nom' => 'A', 'slug' => 'a', 'type' => 'clinique', 'statut' => 'actif']);
        $b = Etablissement::create(['nom' => 'B', 'slug' => 'b', 'type' => 'clinique', 'statut' => 'actif']);
        $n = app(NumerotationDocumentService::class);
        $annee = now()->year;

        $this->assertSame("T-{$annee}00001", $n->numero($a->id, 'T', 'transaction'));
        $this->assertSame("T-{$annee}00002", $n->numero($a->id, 'T', 'transaction'));
        $this->assertSame("T-{$annee}00001", $n->numero($b->id, 'T', 'transaction'));
        $this->assertSame("P-{$annee}00001", $n->numero($a->id, 'P', 'paiement'));
    }

    public function test_pas_de_doublon_sur_1000_numeros(): void
    {
        $a = Etablissement::create(['nom' => 'A', 'slug' => 'a', 'type' => 'clinique', 'statut' => 'actif']);
        $n = app(NumerotationDocumentService::class);

        $numeros = collect(range(1, 1000))->map(fn () => $n->numero($a->id, 'T', 'transaction'));

        $this->assertCount(1000, $numeros->unique());
    }
}
