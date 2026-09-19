@extends('layouts.backend')

@php
    $types = ['Vacance' => ['Vacances', 'fa-umbrella-beach'], 'Maladie' => ['Maladie', 'fa-notes-medical'], 'Conference' => ['Conférence', 'fa-chalkboard-teacher'], 'Autre' => ['Autre', 'fa-calendar-minus']];
    $statuts = ['approved' => ['Validé', 'hl-s-succes'], 'rejected' => ['Refusé', 'hl-s-danger'], 'pending' => ['En attente', 'hl-s-alerte']];
    $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
    $aVenir = $leaves->filter(fn ($l) => $l->end_date->isFuture());
    $passes = $leaves->reject(fn ($l) => $l->end_date->isFuture());
    $enCours = $leaves->first(fn ($l) => $l->start_date->isPast() && $l->end_date->isFuture());
    $pausesParJour = $breaks->groupBy(fn ($b) => ucfirst(mb_strtolower($b->day_of_week)));
    $h = fn ($t) => \Carbon\Carbon::parse($t)->format('H:i');
@endphp

@section('style')
<style>
    .cg-onglets { display: flex; gap: 4px; padding: 4px; margin-bottom: 16px; border-radius: 12px; background: #f3f4f6; width: fit-content; }
    .cg-onglets button { min-height: 38px; padding: 0 16px; border: 0; border-radius: 9px; background: none; color: var(--hali-texte); font-size: .88rem; font-weight: 650; cursor: pointer; }
    .cg-onglets button.active { background: #fff; color: var(--hali-primaire-fonce); box-shadow: 0 1px 3px rgba(0, 0, 0, .08); }
    .cg-conge { display: flex; flex-wrap: wrap; align-items: center; gap: 12px 16px; padding: 14px 18px; border-top: 1px solid #f3f4f6; }
    .cg-conge:first-child { border-top: 0; }
    .cg-conge.est-passe { opacity: .6; }
    .cg-icone { display: grid; place-items: center; flex: none; width: 42px; height: 42px; border-radius: 12px; background: var(--hali-primaire-pale); color: var(--hali-primaire); }
    .cg-type { color: var(--hali-encre); font-weight: 700; }
    .cg-periode { color: var(--hali-texte); font-size: .88rem; }
    .cg-sous { display: block; color: var(--hali-discret); font-size: .8rem; }
    .cg-actions { margin-left: auto; display: flex; gap: 6px; }
    .cg-petit { min-height: 32px; padding: 0 12px; font-size: .8rem; }
    .cg-semaine { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 10px; padding: 18px; }
    .cg-jour { display: grid; align-content: start; gap: 8px; min-height: 140px; padding: 12px; border: 1px solid var(--hali-bordure); border-radius: 12px; }
    .cg-jour-nom { color: var(--hali-encre); font-size: .9rem; font-weight: 700; }
    .cg-pause { display: grid; gap: 4px; padding: 9px 10px; border-radius: 9px; background: #fffbeb; border: 1px solid #fde68a; }
    .cg-pause.est-inactive { background: #f9fafb; border: 1px dashed var(--hali-bordure); }
    .cg-pause-haut { display: flex; align-items: center; justify-content: space-between; gap: 6px; }
    .cg-pause strong { color: #78350f; font-size: .9rem; font-variant-numeric: tabular-nums; }
    .cg-pause.est-inactive strong { color: #9ca3af; text-decoration: line-through; }
    .cg-pause span { color: var(--hali-discret); font-size: .74rem; }
    .cg-pause .form-switch { margin: 0; padding-left: 2.2em; min-height: auto; }
    .cg-pause-actions { display: flex; gap: 8px; }
    .cg-pause-actions button { padding: 0; border: 0; background: none; color: var(--hali-primaire); font-size: .74rem; font-weight: 700; cursor: pointer; }
    .cg-pause-actions .est-risque { color: var(--hali-danger); }
    .cg-ajout { border: 1px dashed #d1d5db; border-radius: 9px; background: none; color: var(--hali-primaire); font-size: .78rem; font-weight: 700; min-height: 34px; cursor: pointer; }

    .cg-modal .modal-content { border: 0; border-radius: 14px; }
    .cg-modal .modal-header { padding: 18px 22px 6px; border: 0; }
    .cg-modal .modal-title { color: var(--hali-encre); font-weight: 700; }
    .cg-modal .modal-body { display: grid; gap: 14px; padding: 8px 22px 14px; }
    .cg-modal .form-label, .cg-modal label.cg-l { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .cg-modal .modal-footer { padding: 10px 22px 18px; border: 0; }
    .cg-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 1199.98px) { .cg-semaine { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    @media (max-width: 767.98px) { .cg-semaine { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cg-deux { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Congés et pauses</h1>
            <p>Pendant un congé ou une pause, aucun rendez-vous ne peut être pris avec vous.</p>
        </div>
        <div class="hl-entete-actions">
            <button type="button" class="hl-bouton" data-bs-toggle="modal" data-bs-target="#addBreakModal"><i class="fas fa-coffee" aria-hidden="true"></i> Ajouter une pause</button>
            <button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addLeaveModal"><i class="fa fa-plus" aria-hidden="true"></i> Déclarer un congé</button>
        </div>
    </header>

    @include('appointments.partials.nav-agenda')

    @if($errors->any())
        <div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if($enCours)
        <p class="hl-note hl-note-alerte mb-3"><i class="fas fa-plane-departure" aria-hidden="true"></i> Vous êtes actuellement en congé ({{ $types[$enCours->type][0] ?? $enCours->type }}) jusqu'au {{ $enCours->end_date->format('d/m/Y à H:i') }}.</p>
    @endif

    <div class="cg-onglets" role="tablist">
        <button class="active" id="leaves-tab" data-bs-toggle="tab" data-bs-target="#leaves" type="button" role="tab">Congés et absences <b>{{ $aVenir->count() }}</b></button>
        <button id="breaks-tab" data-bs-toggle="tab" data-bs-target="#breaks" type="button" role="tab">Pauses de la semaine <b>{{ $breaks->count() }}</b></button>
    </div>

    <div class="tab-content">
        {{-- ================================ Congés --}}
        <div class="tab-pane fade show active" id="leaves" role="tabpanel">
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">À venir et en cours</h2>
                @forelse($aVenir->sortBy('start_date') as $leave)
                    @include('appointments.partials.ligne-conge', ['leave' => $leave, 'passe' => false])
                @empty
                    <div class="hl-vide" style="padding:26px"><i class="fas fa-calendar-check" aria-hidden="true" style="color:#a7f3d0"></i>Aucun congé prévu.</div>
                @endforelse
            </section>
            @if($passes->isNotEmpty())
                <details class="hl-bloc hl-repli" style="margin-top:16px">
                    <summary class="hl-bloc-titre" style="cursor:pointer">Congés passés <small>{{ $passes->count() }}</small></summary>
                    @foreach($passes as $leave)
                        @include('appointments.partials.ligne-conge', ['leave' => $leave, 'passe' => true])
                    @endforeach
                </details>
            @endif
        </div>

        {{-- ================================ Pauses --}}
        <div class="tab-pane fade" id="breaks" role="tabpanel">
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Pauses récurrentes <small>chaque semaine, le même jour</small></h2>
                <div class="cg-semaine">
                    @foreach($jours as $jour)
                        <div class="cg-jour">
                            <span class="cg-jour-nom">{{ $jour }}</span>
                            @foreach($pausesParJour->get($jour, collect())->sortBy('start_time') as $break)
                                <div class="cg-pause {{ $break->is_active ? '' : 'est-inactive' }}">
                                    <div class="cg-pause-haut">
                                        <strong>{{ $h($break->start_time) }} – {{ $h($break->end_time) }}</strong>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-break" type="checkbox" role="switch" data-id="{{ $break->id }}" @checked($break->is_active) aria-label="Activer la pause {{ $break->label }}">
                                        </div>
                                    </div>
                                    <span>{{ $break->label ?: 'Pause' }} · {{ (int) round(abs(\Carbon\Carbon::parse($break->start_time)->diffInMinutes(\Carbon\Carbon::parse($break->end_time)))) }} min</span>
                                    <div class="cg-pause-actions">
                                        <button type="button" class="edit-break-button" data-id="{{ $break->id }}" data-day="{{ $break->day_of_week }}" data-start="{{ $h($break->start_time) }}" data-end="{{ $h($break->end_time) }}" data-label="{{ $break->label }}">Modifier</button>
                                        <button type="button" class="est-risque delete-break-button" data-id="{{ $break->id }}" data-label="{{ $break->label ?? 'cette pause' }}">Retirer</button>
                                    </div>
                                </div>
                            @endforeach
                            <button type="button" class="cg-ajout js-ajouter-pause" data-jour="{{ $jour }}">+ Pause</button>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    {{-- ================================ Déclarer un congé --}}
    <div class="modal fade cg-modal" id="addLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" method="POST" action="{{ route('medecin.leaves.store') }}" id="addLeaveForm">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Déclarer un congé</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div><label class="form-label">Type</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="">Choisir</option>
                            @foreach($types as $v => [$l])<option value="{{ $v }}" @selected(old('type') === $v)>{{ $l }}</option>@endforeach
                        </select></div>
                    <div class="cg-deux">
                        <div><label class="form-label">Du</label><input type="datetime-local" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required></div>
                        <div><label class="form-label">Au</label><input type="datetime-local" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required></div>
                    </div>
                    <div><label class="form-label">Motif (facultatif)</label><textarea name="reason" class="form-control @error('reason') is-invalid @enderror" rows="2">{{ old('reason') }}</textarea></div>
                    <p class="hl-note hl-note-alerte mb-0" style="font-size:.82rem"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Les rendez-vous déjà pris sur cette période seront <strong>annulés</strong> et les patients prévenus.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="addLeaveButton" class="hl-bouton hl-bouton-plein">Déclarer <span class="spinner-border spinner-border-sm d-none" role="status" id="addLeaveLoader"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================ Modifier un congé --}}
    <div class="modal fade cg-modal" id="editLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" method="POST" action="" id="editLeaveForm">
                @csrf @method('PUT')
                <input type="hidden" name="id" id="edit_leave_id">
                <div class="modal-header"><h5 class="modal-title">Modifier le congé</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div><label class="form-label">Type</label>
                        <select name="type" id="edit_leave_type" class="form-select" required>@foreach($types as $v => [$l])<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
                    <div class="cg-deux">
                        <div><label class="form-label">Du</label><input type="datetime-local" name="start_date" id="edit_leave_start" class="form-control" required></div>
                        <div><label class="form-label">Au</label><input type="datetime-local" name="end_date" id="edit_leave_end" class="form-control" required></div>
                    </div>
                    <div><label class="form-label">Motif</label><textarea name="reason" id="edit_leave_reason" class="form-control" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="editLeaveButton" class="hl-bouton hl-bouton-plein">Enregistrer <span class="spinner-border spinner-border-sm d-none" role="status" id="editLeaveLoader"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================ Supprimer un congé --}}
    <div class="modal fade cg-modal" id="deleteLeaveModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" method="POST" action="" id="deleteLeaveForm">
                @csrf @method('DELETE')
                <input type="hidden" name="id" id="delete_leave_id">
                <div class="modal-header"><h5 class="modal-title">Supprimer le congé</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body"><p class="mb-0">Supprimer ce congé (<strong id="delete_leave_type_text"></strong>) ? Les créneaux redeviennent réservables ; les rendez-vous annulés ne sont pas rétablis.</p></div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="deleteLeaveButton" class="hl-bouton" style="background:var(--hali-danger); border-color:var(--hali-danger); color:#fff">Supprimer <span class="spinner-border spinner-border-sm d-none" role="status" id="deleteLeaveLoader"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================ Ajouter une pause --}}
    <div class="modal fade cg-modal" id="addBreakModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" method="POST" action="{{ route('medecin.breaks.store') }}" id="addBreakForm">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Ajouter une pause</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div><label class="form-label">Jour</label>
                        <select name="day_of_week" id="addBreakDay" class="form-select @error('day_of_week') is-invalid @enderror" required>
                            <option value="">Choisir un jour</option>
                            @foreach($jours as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach
                        </select></div>
                    <div class="cg-deux">
                        <div><label class="form-label">Début</label><input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" value="13:00" required></div>
                        <div><label class="form-label">Fin</label><input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" value="14:00" required></div>
                    </div>
                    <div><label class="form-label">Nom</label><input type="text" name="label" class="form-control" placeholder="Pause déjeuner, prière…"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="addBreakButton" class="hl-bouton hl-bouton-plein">Ajouter <span class="spinner-border spinner-border-sm d-none" role="status" id="addBreakLoader"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================ Modifier une pause --}}
    <div class="modal fade cg-modal" id="editBreakModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" method="POST" action="" id="editBreakForm">
                @csrf @method('PUT')
                <input type="hidden" name="id" id="edit_break_id">
                <div class="modal-header"><h5 class="modal-title">Modifier la pause</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div><label class="form-label">Jour</label>
                        <select name="day_of_week" id="edit_break_day" class="form-select" required>@foreach($jours as $j)<option value="{{ $j }}">{{ $j }}</option>@endforeach</select></div>
                    <div class="cg-deux">
                        <div><label class="form-label">Début</label><input type="time" name="start_time" id="edit_break_start" class="form-control" required></div>
                        <div><label class="form-label">Fin</label><input type="time" name="end_time" id="edit_break_end" class="form-control" required></div>
                    </div>
                    <div><label class="form-label">Nom</label><input type="text" name="label" id="edit_break_label" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="editBreakButton" class="hl-bouton hl-bouton-plein">Enregistrer <span class="spinner-border spinner-border-sm d-none" role="status" id="editBreakLoader"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================ Supprimer une pause --}}
    <div class="modal fade cg-modal" id="deleteBreakModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" method="POST" action="" id="deleteBreakForm">
                @csrf @method('DELETE')
                <input type="hidden" name="id" id="delete_break_id">
                <div class="modal-header"><h5 class="modal-title">Retirer la pause</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body"><p class="mb-0">Retirer <strong id="delete_break_label"></strong> ?</p></div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="deleteBreakButton" class="hl-bouton" style="background:var(--hali-danger); border-color:var(--hali-danger); color:#fff">Retirer <span class="spinner-border spinner-border-sm d-none" role="status" id="deleteBreakLoader"></span></button>
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
    var val = function (id, v) { document.getElementById(id).value = v || ''; };

    document.querySelectorAll('.edit-leave-button').forEach(function (b) {
        b.addEventListener('click', function () {
            val('edit_leave_id', b.dataset.id); val('edit_leave_type', b.dataset.type);
            val('edit_leave_start', b.dataset.start); val('edit_leave_end', b.dataset.end); val('edit_leave_reason', b.dataset.reason);
            document.getElementById('editLeaveForm').action = '/medecin/leaves/' + b.dataset.id;
            modal('editLeaveModal').show();
        });
    });
    document.querySelectorAll('.delete-leave-button').forEach(function (b) {
        b.addEventListener('click', function () {
            val('delete_leave_id', b.dataset.id);
            document.getElementById('delete_leave_type_text').textContent = b.dataset.libelle || b.dataset.type;
            document.getElementById('deleteLeaveForm').action = '/medecin/leaves/' + b.dataset.id;
            modal('deleteLeaveModal').show();
        });
    });
    document.querySelectorAll('.edit-break-button').forEach(function (b) {
        b.addEventListener('click', function () {
            val('edit_break_id', b.dataset.id); val('edit_break_day', b.dataset.day);
            val('edit_break_start', b.dataset.start); val('edit_break_end', b.dataset.end); val('edit_break_label', b.dataset.label);
            document.getElementById('editBreakForm').action = '/medecin/breaks/' + b.dataset.id;
            modal('editBreakModal').show();
        });
    });
    document.querySelectorAll('.delete-break-button').forEach(function (b) {
        b.addEventListener('click', function () {
            val('delete_break_id', b.dataset.id);
            document.getElementById('delete_break_label').textContent = b.dataset.label;
            document.getElementById('deleteBreakForm').action = '/medecin/breaks/' + b.dataset.id;
            modal('deleteBreakModal').show();
        });
    });
    document.querySelectorAll('.js-ajouter-pause').forEach(function (b) {
        b.addEventListener('click', function () { val('addBreakDay', b.dataset.jour); modal('addBreakModal').show(); });
    });

    // Activer / suspendre une pause sans recharger toute la page
    document.querySelectorAll('.toggle-break').forEach(function (c) {
        c.addEventListener('change', function () {
            var carte = c.closest('.cg-pause');
            fetch('/medecin/breaks/' + c.dataset.id + '/toggle', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ is_active: c.checked ? 1 : 0 })
            }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok && d.success, d: d }; }); })
              .then(function (res) {
                  if (!res.ok) throw new Error(res.d.message || 'Erreur');
                  carte.classList.toggle('est-inactive', !c.checked);
              })
              .catch(function (e) { c.checked = !c.checked; alert(e.message || 'La pause n\'a pas pu être modifiée.'); });
        });
    });

    [['addLeaveForm', 'addLeaveButton', 'addLeaveLoader'], ['editLeaveForm', 'editLeaveButton', 'editLeaveLoader'], ['deleteLeaveForm', 'deleteLeaveButton', 'deleteLeaveLoader'],
     ['addBreakForm', 'addBreakButton', 'addBreakLoader'], ['editBreakForm', 'editBreakButton', 'editBreakLoader'], ['deleteBreakForm', 'deleteBreakButton', 'deleteBreakLoader']].forEach(function (t) {
        document.getElementById(t[0]).addEventListener('submit', function () {
            document.getElementById(t[1]).disabled = true; document.getElementById(t[2]).classList.remove('d-none');
        });
    });

    // Rouvre la bonne fenêtre si le serveur a refusé la saisie
    @if($errors->has('day_of_week') || $errors->has('start_time') || $errors->has('end_time'))
        document.getElementById('breaks-tab').click(); modal('addBreakModal').show();
    @elseif($errors->any())
        modal('addLeaveModal').show();
    @endif
})();
</script>
@endsection
