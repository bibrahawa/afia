@extends('layouts.backend')

@php
    $statuts = [
        'pending' => ['À confirmer', 'hl-s-alerte'], 'confirmed' => ['Confirmé', 'hl-s-succes'], 'completed' => ['Honoré', 'hl-s-neutre'],
        'cancelled' => ['Annulé', 'hl-s-danger'], 'no_show' => ['Absent', 'hl-s-danger'],
    ];
    [$libelleStatut, $tonStatut] = $statuts[$appointment->status] ?? [$appointment->status, 'hl-s-neutre'];
    $p = $appointment->patient;
    $initiales = $p ? mb_strtoupper(mb_substr((string) $p->first_name, 0, 1) . mb_substr((string) $p->last_name, 0, 1)) : '?';
    $quand = $appointment->appointment_datetime;
    $modifiable = auth()->user()?->can('appointment.edit') && $appointment->canBeCancelled();
@endphp

@section('style')
<style>
    .rv-grille { display: grid; grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr); gap: 16px; align-items: start; }
    .rv-date { display: flex; align-items: center; gap: 16px; padding: 18px; border-bottom: 1px solid var(--hali-bordure); }
    .rv-cal { display: grid; place-items: center; width: 68px; flex: none; overflow: hidden; border: 1px solid var(--hali-bordure); border-radius: 12px; text-align: center; }
    .rv-cal span { width: 100%; padding: 3px 0; background: var(--hali-primaire); color: #fff; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
    .rv-cal b { padding: 4px 0 2px; color: var(--hali-encre); font-size: 1.6rem; line-height: 1; }
    .rv-cal small { padding-bottom: 5px; color: var(--hali-discret); font-size: .72rem; }
    .rv-heure { color: var(--hali-encre); font-size: 1.6rem; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: -.02em; }
    .rv-sous { display: block; color: var(--hali-discret); font-size: .84rem; }
    .rv-infos { margin: 0; padding: 6px 18px 14px; }
    .rv-infos div { display: grid; grid-template-columns: 130px minmax(0, 1fr); gap: 10px; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-size: .88rem; }
    .rv-infos div:last-child { border-bottom: 0; }
    .rv-infos dt { color: var(--hali-discret); font-weight: 500; }
    .rv-infos dd { margin: 0; color: var(--hali-encre); font-weight: 600; }
    .rv-motif { display: inline-flex; align-items: center; gap: 7px; }
    .rv-motif i { width: 10px; height: 10px; border-radius: 3px; }
    .rv-patient { display: flex; align-items: center; gap: 12px; padding: 16px 18px; }
    .rv-patient .hl-avatar { width: 46px; height: 46px; flex-basis: 46px; border-radius: 12px; }
    .rv-form { display: grid; gap: 12px; padding: 16px 18px; }
    .rv-form label { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .rv-jours { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 4px; }
    .rv-jours button { flex: none; display: grid; min-width: 62px; padding: 8px 6px; border: 1px solid var(--hali-bordure); border-radius: 10px; background: #fff; color: var(--hali-texte); font-size: .74rem; cursor: pointer; }
    .rv-jours button b { color: var(--hali-encre); font-size: 1.05rem; }
    .rv-jours button.est-choisi { background: var(--hali-primaire); border-color: var(--hali-primaire); color: #fff; }
    .rv-jours button.est-choisi b { color: #fff; }
    .rv-heures { display: grid; grid-template-columns: repeat(auto-fill, minmax(72px, 1fr)); gap: 6px; }
    .rv-heures button { min-height: 38px; border: 1px solid var(--hali-bordure); border-radius: 9px; background: #fff; font-weight: 700; font-variant-numeric: tabular-nums; cursor: pointer; }
    .rv-heures button.est-choisi { background: var(--hali-primaire); border-color: var(--hali-primaire); color: #fff; }
    .rv-aide { margin: 0; color: var(--hali-discret); font-size: .82rem; }
    @media (max-width: 991.98px) { .rv-grille { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Rendez-vous</h1>
            <p>{{ ucfirst($quand->translatedFormat('l d F Y')) }} à {{ $quand->format('H:i') }}</p>
        </div>
        <div class="hl-entete-actions">
            <a href="{{ route('appointment.index', ['date' => $quand->toDateString()]) }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Agenda du jour</a>
        </div>
    </header>

    <div class="rv-grille">
        <section class="hl-bloc">
            <div class="rv-date">
                <div class="rv-cal" aria-hidden="true"><span>{{ $quand->translatedFormat('M') }}</span><b>{{ $quand->format('d') }}</b><small>{{ $quand->translatedFormat('D') }}</small></div>
                <div>
                    <span class="rv-heure">{{ $quand->format('H:i') }}</span>
                    <span class="rv-sous">{{ $appointment->duree_minutes }} min · {{ $quand->isToday() ? "aujourd'hui" : $quand->diffForHumans() }}</span>
                </div>
                <span class="hl-statut {{ $tonStatut }}" style="margin-left:auto">{{ $libelleStatut }}</span>
            </div>
            <dl class="rv-infos">
                <div><dt>Médecin</dt><dd>{{ $appointment->employee?->nom_affiche }}@if($appointment->employee?->department)<span class="rv-sous">{{ $appointment->employee->department->name }}</span>@endif</dd></div>
                <div><dt>Motif</dt><dd><span class="rv-motif">@if($appointment->motifRdv)<i style="background: {{ $appointment->motifRdv->couleur ?: '#9ca3af' }}"></i>{{ $appointment->motifRdv->nom }}@else — @endif</span></dd></div>
                @if($appointment->description)<div><dt>Note du patient</dt><dd style="font-weight:500">{{ $appointment->description }}</dd></div>@endif
                @if($appointment->status === 'cancelled')<div><dt>Annulation</dt><dd style="font-weight:500; color:var(--hali-danger)">{{ $appointment->cancellation_reason ?: 'Sans motif' }}</dd></div>@endif
            </dl>
        </section>

        <div style="display:grid; gap:16px">
            <section class="hl-bloc">
                <div class="rv-patient">
                    <span class="hl-avatar" aria-hidden="true">{{ $initiales }}</span>
                    <div style="min-width:0">
                        <strong style="color:var(--hali-encre)">{{ $p?->full_name ?? 'Patient inconnu' }}</strong>
                        <span class="rv-sous">{{ $p?->telephone }}@if($p?->telephone && $p?->identifiant_national_sante) · @endif{{ $p?->identifiant_national_sante }}</span>
                    </div>
                    @if($p && Route::has('parcours.dossier.show'))
                        @can('parcours.dossier')<a href="{{ route('parcours.dossier.show', $p->id) }}" class="hl-bouton" style="margin-left:auto; min-height:34px">Dossier</a>@endcan
                    @endif
                </div>
            </section>

            @if($modifiable)
                <section class="hl-bloc">
                    <h2 class="hl-bloc-titre">Reprogrammer <small>même médecin, même motif</small></h2>
                    <div class="rv-form">
                        <div><label>Nouvelle date</label><div class="rv-jours" id="reprogJours"><span class="rv-aide">Chargement des dates…</span></div></div>
                        <div><label>Nouvelle heure</label><div class="rv-heures" id="reprogHeures"><span class="rv-aide">Choisissez d'abord une date.</span></div></div>
                        <p class="rv-aide" id="reprogErreur" style="color:var(--hali-danger)"></p>
                        <button type="button" id="reprogBouton" class="hl-bouton hl-bouton-plein" disabled>Reprogrammer</button>
                        <p class="rv-aide">Le patient est prévenu par SMS du nouvel horaire.</p>
                    </div>
                </section>

                <section class="hl-bloc">
                    <h2 class="hl-bloc-titre">Annuler le rendez-vous</h2>
                    <form action="{{ route('appointment.cancel', $appointment) }}" method="POST" class="rv-form" onsubmit="return confirm('Annuler ce rendez-vous ?');">
                        @csrf @method('DELETE')
                        <div><label for="rvMotif">Motif (facultatif)</label><input type="text" name="reason" id="rvMotif" class="form-control" placeholder="Demande du patient, médecin absent…"></div>
                        <button type="submit" class="hl-bouton" style="color:var(--hali-danger); border-color:#fecaca">Annuler ce rendez-vous</button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</div></div>

@if($modifiable)
<script>
(function () {
    var employeeId = {{ (int) $appointment->employee_id }};
    var motifId = {{ $appointment->motif_rdv_id ? (int) $appointment->motif_rdv_id : 'null' }};
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    var jours = document.getElementById('reprogJours'), heures = document.getElementById('reprogHeures');
    var bouton = document.getElementById('reprogBouton'), erreur = document.getElementById('reprogErreur');
    var choix = { date: null, heure: null };
    var json = function (url) { return fetch(url, { headers: { 'Accept': 'application/json' } }).then(function (r) { if (!r.ok) throw r; return r.json(); }); };

    json('{{ url('/appointment-form/dates-disponibles') }}?employee_id=' + employeeId + '&motif_rdv_id=' + motifId)
        .then(function (liste) {
            jours.innerHTML = '';
            if (!liste.length) { jours.innerHTML = '<span class="rv-aide">Aucune date disponible dans les 2 prochains mois.</span>'; return; }
            liste.forEach(function (iso) {
                var d = new Date(iso + 'T00:00:00'), b = document.createElement('button');
                b.type = 'button';
                b.innerHTML = d.toLocaleDateString('fr-FR', { weekday: 'short' }) + '<b>' + d.getDate() + '</b>' + d.toLocaleDateString('fr-FR', { month: 'short' });
                b.addEventListener('click', function () { choisirDate(iso, b); });
                jours.appendChild(b);
            });
        })
        .catch(function () { jours.innerHTML = '<span class="rv-aide" style="color:var(--hali-danger)">Dates indisponibles (droits insuffisants ou connexion).</span>'; });

    function choisirDate(iso, b) {
        jours.querySelectorAll('button').forEach(function (x) { x.classList.remove('est-choisi'); });
        b.classList.add('est-choisi'); choix.date = iso; choix.heure = null; bouton.disabled = true;
        heures.innerHTML = '<span class="rv-aide">Chargement…</span>';
        json('{{ url('/appointment-form/creneaux') }}?employee_id=' + employeeId + '&motif_rdv_id=' + motifId + '&date=' + iso)
            .then(function (creneaux) {
                heures.innerHTML = '';
                if (!creneaux.length) { heures.innerHTML = '<span class="rv-aide">Plus de créneau libre ce jour-là.</span>'; return; }
                creneaux.forEach(function (c) {
                    var h = document.createElement('button'); h.type = 'button'; h.textContent = c.debut;
                    h.addEventListener('click', function () {
                        heures.querySelectorAll('button').forEach(function (x) { x.classList.remove('est-choisi'); });
                        h.classList.add('est-choisi'); choix.heure = c.debut; bouton.disabled = false;
                        bouton.textContent = 'Reprogrammer au ' + new Date(iso + 'T00:00:00').toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' }) + ' à ' + c.debut;
                    });
                    heures.appendChild(h);
                });
            })
            .catch(function () { heures.innerHTML = '<span class="rv-aide" style="color:var(--hali-danger)">Créneaux indisponibles.</span>'; });
    }

    bouton.addEventListener('click', function () {
        bouton.disabled = true; erreur.textContent = '';
        fetch('{{ url('/appointment') }}/{{ $appointment->id }}/reprogrammer', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ appointment_date: choix.date, appointment_time: choix.heure })
        }).then(function (r) {
            if (r.ok || r.redirected) { window.location.reload(); return; }
            return r.json().then(function (d) { erreur.textContent = d.error || d.message || 'Reprogrammation impossible.'; bouton.disabled = false; });
        }).catch(function () { erreur.textContent = 'Connexion interrompue : réessayez.'; bouton.disabled = false; });
    });
})();
</script>
@endif
@endsection
