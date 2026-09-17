@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header">
        <h3 class="fw-bold mb-3">Accueil — {{ today()->translatedFormat('l d F Y') }}</h3>
    </div>

    <div class="row">
        {{-- ------------------------------------------------ Rendez-vous à accueillir --}}
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h4 class="card-title">Rendez-vous du jour <span class="badge badge-primary">{{ $rendezVous->count() }}</span></h4></div>
                <ul class="list-group list-group-flush">
                    @forelse($rendezVous as $rdv)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>{{ $rdv->appointment_time?->format('H:i') }}</strong> — {{ $rdv->patient?->full_name }}
                                    <div class="small text-muted">Dr {{ $rdv->employee?->full_name }} · {{ $rdv->motifRdv?->nom ?? 'Consultation' }}</div>
                                </div>
                                <div class="d-flex gap-1">
                                    <form method="POST" action="{{ route('parcours.accueil.arrivee-rdv', $rdv) }}">@csrf
                                        <button class="btn btn-sm btn-success" title="Patient arrivé"><i class="fa fa-check"></i> Arrivé</button></form>
                                    @if($rdv->appointment_time && now()->gt(today()->setTimeFrom($rdv->appointment_time)->addHour()))
                                        <form method="POST" action="{{ route('parcours.accueil.absent', $rdv) }}" onsubmit="return confirm('Marquer ce rendez-vous absent ?');">@csrf
                                            <button class="btn btn-sm btn-outline-secondary" title="Absent">Absent</button></form>
                                    @endif
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Aucun rendez-vous restant à accueillir.</li>
                    @endforelse
                </ul>
            </div>

            <div class="card">
                <div class="card-header"><h4 class="card-title">Patient sans rendez-vous</h4></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('parcours.accueil.arrivee') }}" class="row g-2">@csrf
                        <div class="col-12">@include('assurance.partials.choix-patient', ['id' => 'visite', 'libelle' => 'Patient', 'url' => route('parcours.patients.recherche')])
                            <a href="{{ route('patient.index') }}" class="small">Nouveau patient ?</a></div>
                        <div class="col-md-6"><label class="form-label small">Médecin *</label>
                            <select name="medecin_id" class="form-control form-control-sm" required>
                                @foreach($medecins as $m)<option value="{{ $m->id }}">Dr {{ $m->full_name }}</option>@endforeach
                            </select></div>
                        <div class="col-md-6"><label class="form-label small">Motif</label>
                            <select name="motif_rdv_id" class="form-control form-control-sm js-motif">
                                <option value="">Consultation</option>
                                @foreach($motifs as $motif)<option value="{{ $motif->id }}" data-service="{{ $motif->service_id }}">{{ $motif->nom }}</option>@endforeach
                            </select></div>
                        <div class="col-md-8"><label class="form-label small">Acte à facturer</label>
                            <select name="service_id" class="form-control form-control-sm js-service">
                                <option value="">— Aucun pour l'instant —</option>
                                @foreach($services as $s)<option value="{{ $s->id }}">{{ $s->name }} — {{ $gnf($s->amount) }} GNF</option>@endforeach
                            </select></div>
                        <div class="col-md-4 pt-4"><input type="hidden" name="urgence" value="0">
                            <label class="text-danger small"><input type="checkbox" name="urgence" value="1"> Urgence</label></div>
                        <div class="col-12"><input name="notes_accueil" class="form-control form-control-sm" maxlength="1000" placeholder="Précision (facultatif) : fièvre depuis 3 jours…"></div>
                        <div class="col-12"><button class="btn btn-primary btn-sm w-100">Ajouter à la file d'attente</button></div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ------------------------------------------------ File du jour --}}
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex flex-wrap align-items-center gap-2">
                    <h4 class="card-title mb-0">File d'attente du jour</h4>
                    @php $ordreActuel = \App\Support\EtablissementContext::current()?->ordre_file ?? 'arrivee'; @endphp
                    <form method="POST" action="{{ route('parcours.accueil.reglage-ordre') }}" class="ms-auto d-flex gap-1 align-items-center">@csrf
                        <span class="small text-muted">Les patients passent :</span>
                        <select name="ordre_file" class="form-control form-control-sm" style="width:auto">
                            <option value="arrivee" @selected($ordreActuel === 'arrivee')>dans l'ordre d'arrivée</option>
                            <option value="rendez_vous" @selected($ordreActuel === 'rendez_vous')>rendez-vous d'abord</option>
                        </select>
                        <button class="btn btn-sm btn-outline-primary">Appliquer</button>
                    </form>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Arrivée</th><th>Patient</th><th>Médecin</th><th>Constantes</th><th>Caisse</th><th>Statut</th><th></th></tr></thead>
                        <tbody>
                        @forelse($visites as $v)
                            @php $transaction = $v->consultation?->transaction; @endphp
                            <tr class="{{ $v->urgence && $v->statut->estActive() ? 'table-danger' : '' }}">
                                <td class="small">{{ $v->arrivee_le->format('H:i') }}
                                    @if($v->statut === \App\Enums\Parcours\StatutVisite::EnAttente)<div class="text-muted">{{ $v->minutesAttente() }} min</div>@endif
                                    @if($v->rang !== null)<span class="badge badge-light" title="Ordre imposé par l'accueil">ordre manuel</span>@endif</td>
                                <td>{{ $v->patient->full_name }}<div class="small text-muted">{{ $v->motif }}</div></td>
                                <td class="small">Dr {{ $v->medecin->full_name }}</td>
                                <td>
                                    @include('parcours.partials.constantes', ['c' => $v->derniereConstante])
                                    @if($v->statut->estActive())
                                        @can('parcours.constantes')
                                            <div><button class="btn btn-link btn-sm p-0" data-bs-toggle="modal" data-bs-target="#modalConstantes{{ $v->id }}">{{ $v->derniereConstante ? 'Reprendre' : 'Saisir' }}</button></div>
                                        @endcan
                                    @endif
                                </td>
                                <td class="small">
                                    @if($transaction)
                                        @if($transaction->status === 'paid' || $transaction->status === 'approved')<span class="badge badge-success">Réglé</span>
                                        @else<a href="{{ route('consultation.show', $v->consultation) }}" class="badge badge-warning">À encaisser {{ $gnf($transaction->total) }}</a>@endif
                                    @else — @endif
                                </td>
                                <td><span class="badge badge-{{ $v->statut->badge() }}">{{ $v->statut->libelle() }}</span></td>
                                <td class="text-end text-nowrap">
                                    @if($v->statut === \App\Enums\Parcours\StatutVisite::EnAttente)
                                        <form method="POST" action="{{ route('parcours.accueil.prioriser', $v) }}" class="d-inline">@csrf
                                            <button class="btn btn-sm btn-outline-success" title="Faire passer maintenant"><i class="fa fa-angle-double-up"></i></button></form>
                                        <form method="POST" action="{{ route('parcours.accueil.deplacer', $v) }}" class="d-inline">@csrf
                                            <input type="hidden" name="direction" value="haut">
                                            <button class="btn btn-sm btn-outline-secondary" title="Monter d'une place"><i class="fa fa-arrow-up"></i></button></form>
                                        <form method="POST" action="{{ route('parcours.accueil.deplacer', $v) }}" class="d-inline">@csrf
                                            <input type="hidden" name="direction" value="bas">
                                            <button class="btn btn-sm btn-outline-secondary" title="Descendre d'une place"><i class="fa fa-arrow-down"></i></button></form>
                                        <details class="d-inline-block">
                                            <summary class="btn btn-sm btn-outline-secondary"><i class="fa fa-ellipsis-h"></i></summary>
                                            <div class="position-absolute bg-white border shadow p-2" style="z-index:10; right:1rem; width:280px">
                                                <form method="POST" action="{{ route('parcours.accueil.transferer', $v) }}" class="d-flex gap-1 mb-2">@csrf
                                                    <select name="medecin_id" class="form-control form-control-sm">
                                                        @foreach($medecins as $m)<option value="{{ $m->id }}" @selected($m->id === $v->medecin_id)>Dr {{ $m->full_name }}</option>@endforeach
                                                    </select>
                                                    <button class="btn btn-sm btn-primary">Transférer</button>
                                                </form>
                                                <form method="POST" action="{{ route('parcours.accueil.ordre-defaut', $v) }}" class="mb-2">@csrf
                                                    <button class="btn btn-sm btn-outline-secondary w-100">Revenir à l'ordre de la clinique</button></form>
                                                <form method="POST" action="{{ route('parcours.accueil.partie', $v) }}" onsubmit="return confirm('Le patient est reparti sans consulter ?');">@csrf
                                                    <input name="motif" class="form-control form-control-sm mb-1" maxlength="255" placeholder="Motif (facultatif)">
                                                    <label class="small d-block mb-1"><input type="hidden" name="annuler_facture" value="0"><input type="checkbox" name="annuler_facture" value="1" checked> Annuler la facture de l'acte (si rien n'est encaissé)</label>
                                                    <button class="btn btn-sm btn-outline-danger w-100">Reparti sans consulter</button></form>
                                            </div>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Aucun patient arrivé aujourd'hui.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div></div>

@can('parcours.constantes')
    @foreach($visites->filter(fn ($v) => $v->statut->estActive()) as $v)
        <div class="modal fade" id="modalConstantes{{ $v->id }}" tabindex="-1"><div class="modal-dialog">
            <form method="POST" action="{{ route('parcours.accueil.constantes', $v) }}" class="modal-content">@csrf
                <div class="modal-header"><h5 class="modal-title">Constantes — {{ $v->patient->full_name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="small text-muted">Remplir seulement ce qui a été mesuré. La taille d'un adulte est reprise de la dernière mesure.</p>
                    <div class="row g-2">
                        <div class="col-4"><label class="form-label small">Température (°C)</label><input type="number" step="0.1" name="temperature" class="form-control" inputmode="decimal"></div>
                        <div class="col-4"><label class="form-label small">TA systolique</label><input type="number" name="tension_systolique" class="form-control" inputmode="numeric" placeholder="120"></div>
                        <div class="col-4"><label class="form-label small">TA diastolique</label><input type="number" name="tension_diastolique" class="form-control" inputmode="numeric" placeholder="80"></div>
                        <div class="col-4"><label class="form-label small">Pouls (bpm)</label><input type="number" name="pouls" class="form-control" inputmode="numeric"></div>
                        <div class="col-4"><label class="form-label small">SpO₂ (%)</label><input type="number" name="saturation_o2" class="form-control" inputmode="numeric"></div>
                        <div class="col-4"><label class="form-label small">Fréq. resp.</label><input type="number" name="frequence_respiratoire" class="form-control" inputmode="numeric"></div>
                        <div class="col-4"><label class="form-label small">Poids (kg)</label><input type="number" step="0.01" name="poids_kg" class="form-control" inputmode="decimal"></div>
                        <div class="col-4"><label class="form-label small">Taille (cm)</label><input type="number" step="0.1" name="taille_cm" class="form-control" inputmode="decimal"></div>
                        <div class="col-4"><label class="form-label small">Glycémie (g/L)</label><input type="number" step="0.01" name="glycemie" class="form-control" inputmode="decimal"></div>
                        @if($v->patient->gender === 'Femme')
                            <div class="col-6"><label class="form-label small">Date des dernières règles</label><input type="date" name="ddr" class="form-control" max="{{ today()->toDateString() }}"></div>
                        @endif
                        <div class="col-12"><input name="notes" class="form-control form-control-sm" maxlength="255" placeholder="Remarque (facultatif)"></div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Enregistrer</button></div>
            </form>
        </div></div>
    @endforeach
@endcan
@endsection

@section('script')
    @include('assurance.partials.choix-patient-script')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var motif = document.querySelector('.js-motif'), service = document.querySelector('.js-service');
        if (!motif || !service) return;
        motif.addEventListener('change', function () {
            var s = motif.options[motif.selectedIndex].dataset.service;
            if (s) service.value = s;
        });
    });
    </script>
@endsection
