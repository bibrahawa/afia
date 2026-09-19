@extends('layouts.backend')

@php
    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    // Classement alphabétique en base (Dimanche, Jeudi, Lundi…) : on remet la semaine dans l'ordre.
    $parJour = $availabilities->keyBy(fn ($a) => ucfirst(mb_strtolower($a->day_of_week)));
    // (int) round(abs()) : Carbon 3 (Laravel 11) renvoie des durées décimales et signées.
    $minutes = fn ($a) => (int) round(abs($a->start_time->diffInMinutes($a->end_time)));
    $totalMinutes = (int) $availabilities->where('is_active', true)->sum($minutes);
    $heures = fn ($m) => intdiv($m, 60) . ' h' . ($m % 60 ? ' ' . str_pad($m % 60, 2, '0', STR_PAD_LEFT) : '');
    $joursOuverts = $availabilities->where('is_active', true)->count();
@endphp

@section('style')
<style>
    .ds-semaine { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 10px; padding: 18px; }
    .ds-jour { display: grid; grid-template-rows: auto 1fr; gap: 8px; min-height: 170px; padding: 12px; border: 1px solid var(--hali-bordure); border-radius: 12px; background: #fff; }
    .ds-jour.est-aujourdhui { border-color: var(--hali-primaire); box-shadow: 0 0 0 3px var(--hali-primaire-pale); }
    .ds-jour-nom { display: flex; align-items: center; justify-content: space-between; color: var(--hali-encre); font-size: .9rem; font-weight: 700; }
    .ds-jour-nom small { color: var(--hali-primaire); font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
    .ds-plage { display: grid; align-content: start; gap: 6px; padding: 10px; border-radius: 10px; background: var(--hali-primaire-pale); border: 1px solid var(--hali-primaire-clair); cursor: pointer; text-align: left; width: 100%; }
    .ds-plage:hover { border-color: var(--hali-primaire); }
    .ds-plage.est-inactive { background: #f9fafb; border-color: var(--hali-bordure); border-style: dashed; }
    .ds-horaire { color: var(--hali-primaire-fonce); font-size: 1rem; font-weight: 750; font-variant-numeric: tabular-nums; }
    .ds-plage.est-inactive .ds-horaire { color: #9ca3af; text-decoration: line-through; }
    .ds-duree { color: var(--hali-discret); font-size: .76rem; }
    .ds-ferme { display: grid; place-items: center; gap: 6px; border: 1px dashed #e5e7eb; border-radius: 10px; color: #9ca3af; font-size: .8rem; text-align: center; }
    .ds-ferme button { border: 0; background: none; color: var(--hali-primaire); font-size: .8rem; font-weight: 700; cursor: pointer; }
    .ds-actions { display: flex; gap: 6px; margin-top: 2px; }
    .ds-actions button { flex: 1; min-height: 28px; border: 1px solid var(--hali-bordure); border-radius: 7px; background: #fff; color: var(--hali-texte); font-size: .74rem; font-weight: 600; cursor: pointer; }
    .ds-actions button:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); }
    .ds-actions .est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); }

    .ds-modal .modal-content { border: 0; border-radius: 14px; }
    .ds-modal .modal-header { padding: 18px 22px 6px; border: 0; }
    .ds-modal .modal-title { color: var(--hali-encre); font-weight: 700; }
    .ds-modal .modal-body { display: grid; gap: 14px; padding: 8px 22px 14px; }
    .ds-modal label { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .ds-modal .modal-footer { padding: 10px 22px 18px; border: 0; }
    .ds-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ds-modal input[type=time] { min-height: 46px; font-size: 1.05rem; font-variant-numeric: tabular-nums; }
    @media (max-width: 1199.98px) { .ds-semaine { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    @media (max-width: 767.98px) { .ds-semaine { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Mes horaires</h1>
            <p>Les heures où les patients peuvent prendre rendez-vous avec vous, jour par jour.</p>
        </div>
    </header>

    @include('appointments.partials.nav-agenda')

    @if($errors->any())
        <div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Jours de consultation</span><span class="hl-kpi-valeur">{{ $joursOuverts }} <small>/ 7</small></span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Heures par semaine</span><span class="hl-kpi-valeur">{{ $heures($totalMinutes) }}</span></div>
    </div>

    <section class="hl-bloc">
        <h2 class="hl-bloc-titre">Semaine type
            <small>
                La durée de chaque rendez-vous vient du motif choisi par le patient
                @can('motif_rdv.view')
                    (<a href="{{ route('motifs-rdv.index') }}">motifs</a>)
                @endcan
            </small></h2>
        <div class="ds-semaine">
            @foreach($jours as $jour)
                @php($plage = $parJour->get($jour))
                <div class="ds-jour {{ ucfirst(now()->locale('fr')->dayName) === $jour ? 'est-aujourdhui' : '' }}">
                    <div class="ds-jour-nom">{{ $jour }} @if(ucfirst(now()->locale('fr')->dayName) === $jour)<small>Aujourd'hui</small>@endif</div>
                    @if($plage)
                        <div>
                            <button type="button" class="ds-plage edit-button {{ $plage->is_active ? '' : 'est-inactive' }}"
                                    data-id="{{ $plage->id }}" data-day="{{ $plage->day_of_week }}"
                                    data-start="{{ $plage->start_time->format('H:i') }}" data-end="{{ $plage->end_time->format('H:i') }}"
                                    data-active="{{ $plage->is_active ? 1 : 0 }}" title="Modifier">
                                <span class="ds-horaire">{{ $plage->start_time->format('H:i') }} – {{ $plage->end_time->format('H:i') }}</span>
                                <span class="ds-duree">{{ $plage->is_active ? $heures($minutes($plage)) . ' de consultation' : 'Suspendu : aucun rendez-vous' }}</span>
                            </button>
                            @can('medecin.availabilities')
                                <div class="ds-actions">
                                    <button type="button" class="edit-button" data-id="{{ $plage->id }}" data-day="{{ $plage->day_of_week }}"
                                            data-start="{{ $plage->start_time->format('H:i') }}" data-end="{{ $plage->end_time->format('H:i') }}" data-active="{{ $plage->is_active ? 1 : 0 }}">Modifier</button>
                                    <button type="button" class="est-risque delete-button" data-id="{{ $plage->id }}" data-name="{{ $jour }}">Retirer</button>
                                </div>
                            @endcan
                        </div>
                    @else
                        <div class="ds-ferme">
                            <span>Pas de consultation</span>
                            @can('medecin.availabilities')<button type="button" class="js-ajouter" data-jour="{{ $jour }}">+ Ajouter des horaires</button>@endcan
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- ================================ Ajouter --}}
    <div class="modal fade ds-modal" id="addRowModal" tabindex="-1" aria-labelledby="dsTitreAjout" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" action="{{ route('medecin.availabilities.store') }}" method="POST" id="addAvailabilityForm">
                @csrf
                <div class="modal-header"><h5 class="modal-title" id="dsTitreAjout">Ajouter des horaires</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div><label for="addJour">Jour</label>
                        <select name="day_of_week" id="addJour" class="form-control" required>
                            <option value="">Choisir un jour</option>
                            @foreach($jours as $jour)<option value="{{ $jour }}" @disabled($parJour->has($jour))>{{ $jour }}{{ $parJour->has($jour) ? ' (déjà configuré)' : '' }}</option>@endforeach
                        </select></div>
                    <div class="ds-deux">
                        <div><label for="addDebut">Début</label><input type="time" name="start_time" id="addDebut" class="form-control" value="08:00" required></div>
                        <div><label for="addFin">Fin</label><input type="time" name="end_time" id="addFin" class="form-control" value="17:00" required></div>
                    </div>
                    <p class="small text-muted mb-0">Pour une pause (déjeuner…), utilisez « Congés et pauses » : les rendez-vous l'éviteront.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="addRowButton" class="hl-bouton hl-bouton-plein">Enregistrer <span class="spinner-border spinner-border-sm d-none" role="status" id="addLoader"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================ Modifier --}}
    <div class="modal fade ds-modal" id="editAvailabilityModal" tabindex="-1" aria-labelledby="dsTitreModif" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" method="POST" action="" id="editAvailabilityForm">
                @csrf @method('PUT')
                <input type="hidden" name="id" id="edit_availability_id">
                <div class="modal-header"><h5 class="modal-title" id="dsTitreModif">Modifier les horaires</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div><label for="edit_day_of_week">Jour</label>
                        <select name="day_of_week" id="edit_day_of_week" class="form-control" required>
                            @foreach($jours as $jour)<option value="{{ $jour }}">{{ $jour }}</option>@endforeach
                        </select></div>
                    <div class="ds-deux">
                        <div><label for="edit_start_time">Début</label><input type="time" name="start_time" id="edit_start_time" class="form-control" required></div>
                        <div><label for="edit_end_time">Fin</label><input type="time" name="end_time" id="edit_end_time" class="form-control" required></div>
                    </div>
                    <label class="d-flex gap-2 mb-0" style="font-weight:500">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" style="margin-top:3px">
                        <span>Jour ouvert aux rendez-vous<span class="d-block small text-muted">Décocher suspend ce jour sans le supprimer. Les rendez-vous déjà pris restent à reprogrammer.</span></span>
                    </label>
                    <p class="hl-note hl-note-alerte mb-0" style="font-size:.82rem">Réduire les horaires annule les rendez-vous déjà pris en dehors ; les patients sont prévenus.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="editAvailabilityButton" class="hl-bouton hl-bouton-plein">Enregistrer <span class="spinner-border spinner-border-sm d-none" role="status" id="editAvailabilityLoader"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================ Retirer --}}
    <div class="modal fade ds-modal" id="deleteAvailabilityModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" method="POST" action="" id="deleteAvailabilityForm">
                @csrf @method('DELETE')
                <input type="hidden" name="id" id="delete_availability_id">
                <div class="modal-header"><h5 class="modal-title">Retirer ce jour</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body"><p id="availability_to_delete_text" class="mb-0"></p></div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="deleteAvailabilityButton" class="hl-bouton" style="background:var(--hali-danger); border-color:var(--hali-danger); color:#fff">Retirer <span class="spinner-border spinner-border-sm d-none" role="status" id="deleteAvailabilityLoader"></span></button>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection

@section('script')
<script>
(function () {
    var modal = function (id) { return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); };

    document.querySelectorAll('.edit-button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('edit_availability_id').value = b.dataset.id;
            document.getElementById('edit_day_of_week').value = b.dataset.day;
            document.getElementById('edit_start_time').value = b.dataset.start;
            document.getElementById('edit_end_time').value = b.dataset.end;
            document.getElementById('edit_is_active').checked = b.dataset.active === '1';
            document.getElementById('editAvailabilityForm').action = '/medecin/availabilities/' + b.dataset.id;
            modal('editAvailabilityModal').show();
        });
    });
    document.querySelectorAll('.delete-button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('delete_availability_id').value = b.dataset.id;
            document.getElementById('availability_to_delete_text').textContent = 'Plus aucun rendez-vous ne pourra être pris le ' + b.dataset.name.toLowerCase() + '. Les rendez-vous déjà pris ne sont pas annulés automatiquement.';
            document.getElementById('deleteAvailabilityForm').action = '/medecin/availabilities/' + b.dataset.id;
            modal('deleteAvailabilityModal').show();
        });
    });
    document.querySelectorAll('.js-ajouter').forEach(function (b) {
        b.addEventListener('click', function () { document.getElementById('addJour').value = b.dataset.jour; modal('addRowModal').show(); });
    });
    [['addAvailabilityForm', 'addRowButton', 'addLoader'], ['editAvailabilityForm', 'editAvailabilityButton', 'editAvailabilityLoader'], ['deleteAvailabilityForm', 'deleteAvailabilityButton', 'deleteAvailabilityLoader']].forEach(function (t) {
        document.getElementById(t[0]).addEventListener('submit', function () {
            document.getElementById(t[1]).disabled = true; document.getElementById(t[2]).classList.remove('d-none');
        });
    });
})();
</script>
@endsection
