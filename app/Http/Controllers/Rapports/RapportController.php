<?php

namespace App\Http\Controllers\Rapports;

use App\Http\Controllers\Controller;
use App\Services\Rapports\RapportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Écrans de rapports : un catalogue, un écran générique, un export CSV. */
class RapportController extends Controller
{
    public function __construct(private RapportService $rapports)
    {
    }

    public function index()
    {
        return view('rapports.index', [
            'catalogue' => collect($this->rapports->catalogue())->groupBy('famille'),
            'periode' => $this->periodeParDefaut(),
        ]);
    }

    public function show(Request $request, string $rapport)
    {
        abort_unless($this->rapports->existe($rapport), 404);

        [$debut, $fin] = $this->periode($request);

        return view('rapports.show', [
            'rapport' => $this->rapports->produire($rapport, $debut, $fin),
            'catalogue' => $this->rapports->catalogue(),
            'filtres' => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
        ]);
    }

    /** Export CSV, ouvert directement dans Excel (séparateur point-virgule, BOM UTF-8). */
    public function csv(Request $request, string $rapport): StreamedResponse
    {
        abort_unless($this->rapports->existe($rapport), 404);

        [$debut, $fin] = $this->periode($request);
        $donnees = $this->rapports->produire($rapport, $debut, $fin);

        $nom = $rapport . '-' . $debut->format('Ymd') . '-' . $fin->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($donnees) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF");

            fputcsv($sortie, [$donnees['titre']], ';');
            fputcsv($sortie, ['Période', $donnees['periode']['debut']->format('d/m/Y'), $donnees['periode']['fin']->format('d/m/Y')], ';');
            fputcsv($sortie, [], ';');

            foreach ($donnees['indicateurs'] as $libelle => $valeur) {
                fputcsv($sortie, [$libelle, $valeur], ';');
            }

            fputcsv($sortie, [], ';');
            fputcsv($sortie, $donnees['colonnes'], ';');

            foreach ($donnees['lignes'] as $ligne) {
                fputcsv($sortie, $ligne, ';');
            }

            if ($donnees['totaux']) {
                fputcsv($sortie, $donnees['totaux'], ';');
            }

            fclose($sortie);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function periode(Request $request): array
    {
        $filtres = $request->validate([
            'debut' => ['nullable', 'date'],
            'fin' => ['nullable', 'date', 'after_or_equal:debut'],
        ]);

        $defaut = $this->periodeParDefaut();

        return [
            Carbon::parse($filtres['debut'] ?? $defaut['debut']),
            Carbon::parse($filtres['fin'] ?? $defaut['fin']),
        ];
    }

    private function periodeParDefaut(): array
    {
        return ['debut' => today()->startOfMonth()->toDateString(), 'fin' => today()->toDateString()];
    }
}
