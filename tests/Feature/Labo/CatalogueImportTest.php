<?php

namespace Tests\Feature\Labo;

use App\Models\Labo\LaboExamen;
use App\Services\Labo\CatalogueImportService;

class CatalogueImportTest extends LaboTestCase
{
    public function test_import_idempotent_et_personnalisations_conservees(): void
    {
        $etab = $this->creerEtablissement('clinique-a'); // 1er import
        $this->actingAs($this->creerBiologiste($etab));

        $nombre = LaboExamen::count();
        $nfs = LaboExamen::where('code', 'NFS')->firstOrFail();
        $nfs->update(['prix' => 75000, 'nom' => 'NFS maison']);

        $bilan = app(CatalogueImportService::class)->importer($etab->id); // 2e import

        $this->assertSame(0, $bilan['examens']);
        $this->assertSame($nombre, LaboExamen::count());
        $nfs->refresh();
        $this->assertEquals(75000, $nfs->prix);
        $this->assertSame('NFS maison', $nfs->nom);
    }
}
