@extends('layouts.backend')
@section('style')
    @include('labo.partials.styles')
    <style>
        .rc-scanner { display: flex; gap: 10px; padding: 18px; }
        .rc-scanner button { min-width: 160px; }
        .rc-mini { margin: 0; padding: 0; list-style: none; }
        .rc-mini li { display: flex; align-items: center; gap: 10px; padding: 9px 18px; border-top: 1px solid #f3f4f6; font-size: .85rem; }
        .rc-mini li:first-child { border-top: 0; }
        .rc-mini time { margin-left: auto; color: var(--hali-discret); font-variant-numeric: tabular-nums; }
        @media (max-width: 575.98px) { .rc-scanner { flex-direction: column; } }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Réception des échantillons', 'fil' => [route('labo.reception.index') => 'Réception']])

    <section class="hl-bloc" style="margin-bottom:16px">
        <form method="POST" action="{{ route('labo.reception.scanner') }}" class="rc-scanner">@csrf
            <input type="text" name="code_barres" class="form-control labo-scanner" placeholder="Scannez le code-barres du tube…" autofocus autocomplete="off" inputmode="numeric" aria-label="Code-barres du tube">
            <button class="hl-bouton hl-bouton-plein"><i class="fa fa-barcode" aria-hidden="true"></i> Réceptionner</button>
        </form>
        <p class="lb-aide" style="padding:0 18px 14px; margin-top:-8px">Le champ reste actif : scannez les tubes les uns après les autres.</p>
    </section>

    <div class="lb-grille-large">
        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">En attente de réception <small>{{ $enAttente->count() }}</small></h2>
            @if($enAttente->isEmpty())
                <div class="hl-vide" style="padding:24px"><i class="fas fa-check-circle" aria-hidden="true" style="color:#a7f3d0"></i>Rien en attente.</div>
            @else
                <div class="table-responsive">
                    <table class="lb-table">
                        <thead><tr><th>Tube</th><th>Patient</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                        @foreach($enAttente as $e)
                            <tr class="{{ $e->demande->urgence ? 'labo-urgent' : '' }}">
                                <td>@if($e->tube)<span class="labo-tube labo-tube-{{ $e->tube }}"></span>@endif<span class="lb-fort">{{ $e->libelleContenant() }}</span>
                                    <span class="lb-sous"><code>{{ $e->code_barres }}</code> · {{ $e->examens->pluck('examen_nom')->implode(', ') }}</span></td>
                                <td>{{ $e->demande->patient->full_name }}
                                    @if($e->demande->urgence) <span class="labo-pastille-urgent"><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span>@endif
                                    <span class="lb-sous"><a href="{{ route('labo.demandes.show', $e->demande) }}">{{ $e->demande->numero }}</a></span></td>
                                <td><span class="badge badge-{{ $e->statut->couleur() }}">{{ $e->statut->libelle() }}</span>
                                    @if($e->preleve_le)<span class="lb-sous">prélevé {{ $e->preleve_le->diffForHumans() }}</span>@endif</td>
                                <td class="lb-actions">
                                    <form method="POST" action="{{ route('labo.echantillons.recu', $e) }}">@csrf<button class="hl-bouton hl-bouton-plein lb-petit">Reçu</button></form>
                                    <button type="button" class="hl-bouton lb-petit lb-risque btn-rejeter" data-action="{{ route('labo.echantillons.rejeter', $e) }}" data-code="{{ $e->code_barres }}">Rejeter</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div class="lb-colonne">
            @if($sousTraitance->isNotEmpty())
                <section class="hl-bloc">
                    <h2 class="hl-bloc-titre">À envoyer en sous-traitance <small>{{ $sousTraitance->count() }}</small></h2>
                    <ul class="rc-mini">
                        @foreach($sousTraitance as $l)
                            <li>
                                <div><span class="lb-fort">{{ $l->examen_nom }}</span> → {{ $l->laboratoire_sous_traitant }}<span class="lb-sous">{{ $l->demande->patient->full_name }}</span></div>
                                <form method="POST" action="{{ route('labo.lignes.sous-traitance', $l) }}" style="margin-left:auto">@csrf<button class="hl-bouton lb-petit">Envoyé</button></form>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Reçus aujourd'hui <small>{{ $recusAujourdhui->count() }}</small></h2>
                @if($recusAujourdhui->isEmpty())
                    <div class="hl-vide" style="padding:18px">Aucun pour l'instant.</div>
                @else
                    <ul class="rc-mini">
                        @foreach($recusAujourdhui as $e)
                            <li><code>{{ $e->code_barres }}</code> {{ $e->demande->patient->full_name }} <time>{{ $e->recu_le->format('H:i') }}</time></li>
                        @endforeach
                    </ul>
                @endif
            </section>
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Rejets du jour <small>{{ $rejetsAujourdhui->count() }}</small></h2>
                @if($rejetsAujourdhui->isEmpty())
                    <div class="hl-vide" style="padding:18px">Aucun rejet.</div>
                @else
                    <ul class="rc-mini">
                        @foreach($rejetsAujourdhui as $e)
                            <li><div><code>{{ $e->code_barres }}</code> {{ $e->demande->patient->full_name }}<span class="lb-sous" style="color:var(--hali-danger)">{{ $motifsRejet[$e->motif_rejet] ?? $e->motif_rejet }}</span></div></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</div></div>

<div class="modal fade" id="modalRejet" tabindex="-1"><div class="modal-dialog"><form method="POST" id="formRejet" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Rejeter l'échantillon <span id="rejetCode"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="form-label">Motif</label>
        <select name="motif" class="form-select mb-2" required>@foreach($motifsRejet as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
        <textarea name="commentaire" class="form-control" rows="2" placeholder="Commentaire (obligatoire si « Autre »)"></textarea>
        <p class="small text-muted mt-2 mb-0">Un nouveau contenant sera créé automatiquement et les examens repasseront « à prélever ».</p>
    </div>
    <div class="modal-footer"><button class="hl-bouton lb-plein-risque">Rejeter l'échantillon</button></div>
</form></div></div>
@endsection

@section('script')
<script>
document.querySelectorAll('.btn-rejeter').forEach(b => b.addEventListener('click', function () {
    document.getElementById('formRejet').action = this.dataset.action;
    document.getElementById('rejetCode').textContent = this.dataset.code;
    new bootstrap.Modal(document.getElementById('modalRejet')).show();
}));
</script>
@endsection
