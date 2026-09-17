@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Réception des échantillons', 'fil' => [route('labo.reception.index') => 'Réception']])

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('labo.reception.scanner') }}" class="d-flex gap-2">@csrf
                <input type="text" name="code_barres" class="form-control labo-scanner" placeholder="Scannez le code-barres du tube…" autofocus autocomplete="off" inputmode="numeric">
                <button class="btn btn-primary">Réceptionner</button>
            </form>
            <p class="small text-muted mt-2 mb-0">Le champ reste actif : scannez les tubes les uns après les autres.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h4 class="card-title">En attente de réception ({{ $enAttente->count() }})</h4></div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Code</th><th>Patient</th><th>Contenant</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                        @forelse($enAttente as $e)
                            <tr class="{{ $e->demande->urgence ? 'labo-urgent' : '' }}">
                                <td><code>{{ $e->code_barres }}</code></td>
                                <td>{{ $e->demande->patient->full_name }}<br><a href="{{ route('labo.demandes.show', $e->demande) }}" class="small">{{ $e->demande->numero }}</a></td>
                                <td>@if($e->tube)<span class="labo-tube labo-tube-{{ $e->tube }}"></span>@endif{{ $e->libelleContenant() }}<br><span class="small text-muted">{{ $e->examens->pluck('examen_nom')->implode(', ') }}</span></td>
                                <td><span class="badge badge-{{ $e->statut->couleur() }}">{{ $e->statut->libelle() }}</span>
                                    @if($e->preleve_le)<br><span class="small text-muted">{{ $e->preleve_le->diffForHumans() }}</span>@endif</td>
                                <td class="text-end text-nowrap">
                                    <form method="POST" action="{{ route('labo.echantillons.recu', $e) }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary">Reçu</button></form>
                                    <button class="btn btn-sm btn-outline-danger btn-rejeter" data-action="{{ route('labo.echantillons.rejeter', $e) }}" data-code="{{ $e->code_barres }}">Rejeter</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center">Rien en attente.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            @if($sousTraitance->isNotEmpty())
                <div class="card">
                    <div class="card-header"><h4 class="card-title">À envoyer en sous-traitance</h4></div>
                    <div class="card-body">
                        @foreach($sousTraitance as $l)
                            <div class="d-flex border-bottom py-2 align-items-center">
                                <div class="small"><strong>{{ $l->examen_nom }}</strong> → {{ $l->laboratoire_sous_traitant }}<br>{{ $l->demande->patient->full_name }}</div>
                                <form method="POST" action="{{ route('labo.lignes.sous-traitance', $l) }}" class="ms-auto">@csrf<button class="btn btn-sm btn-outline-primary">Envoyé</button></form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="card">
                <div class="card-header"><h4 class="card-title">Reçus aujourd'hui ({{ $recusAujourdhui->count() }})</h4></div>
                <div class="card-body small">
                    @forelse($recusAujourdhui as $e)
                        <div class="border-bottom py-1"><code>{{ $e->code_barres }}</code> {{ $e->demande->patient->full_name }} <span class="text-muted float-end">{{ $e->recu_le->format('H:i') }}</span></div>
                    @empty <span class="text-muted">Aucun.</span> @endforelse
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h4 class="card-title">Rejets du jour ({{ $rejetsAujourdhui->count() }})</h4></div>
                <div class="card-body small">
                    @forelse($rejetsAujourdhui as $e)
                        <div class="border-bottom py-1"><code>{{ $e->code_barres }}</code> {{ $e->demande->patient->full_name }}<br><span class="text-danger">{{ $motifsRejet[$e->motif_rejet] ?? $e->motif_rejet }}</span></div>
                    @empty <span class="text-muted">Aucun.</span> @endforelse
                </div>
            </div>
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
    <div class="modal-footer"><button class="btn btn-danger">Rejeter</button></div>
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
