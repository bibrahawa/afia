@extends('layouts.backend')

@php
    $statuts = [
        'pending' => ['À confirmer', 'hl-s-alerte'],
        'confirmed' => ['Confirmé', 'hl-s-succes'],
        'completed' => ['Honoré', 'hl-s-neutre'],
        'cancelled' => ['Annulé', 'hl-s-danger'],
        'no_show' => ['Absent', 'hl-s-danger'],
    ];
    $jour = \Carbon\Carbon::parse($date);
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $libelleJour = ($jour->isToday() ? "Aujourd'hui, " : ($jour->isTomorrow() ? 'Demain, ' : ($jour->isYesterday() ? 'Hier, ' : ''))) . $jours[$jour->dayOfWeek] . ' ' . $jour->day . ' ' . $mois[$jour->month];
    $avecJour = fn ($d) => request()->fullUrlWithQuery(['date' => $d->toDateString(), 'page' => null]);
@endphp

@section('style')
<style>
    .rv-jour { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .rv-jour h2 { margin: 0 8px; color: var(--hali-encre); font-size: 1.1rem; font-weight: 700; }
    .rv-fleche { display: inline-grid; place-items: center; width: 36px; height: 36px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); text-decoration: none; }
    .rv-fleche:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); text-decoration: none; }
    .rv-jour input[type=date] { width: auto; min-height: 36px; }
    .rv-filtres { display: flex; flex-wrap: wrap; gap: 10px; padding: 12px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .rv-filtres select { width: auto; min-width: 190px; min-height: 40px; }
    .rv-filtres .rv-recherche { position: relative; flex: 1 1 240px; }
    .rv-filtres .rv-recherche i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .rv-filtres .rv-recherche input { width: 100%; min-height: 40px; padding-left: 40px; }

    .rv-ligne { display: grid; grid-template-columns: 70px minmax(200px, 1.4fr) minmax(150px, 1fr) minmax(150px, 1fr) 120px auto; align-items: center; gap: 14px; padding: 12px 18px; border-top: 1px solid #f3f4f6; }
    .rv-ligne:first-of-type { border-top: 0; }
    .rv-ligne:hover { background: var(--hali-primaire-pale); }
    .rv-ligne.est-passe { opacity: .7; }
    .rv-heure { color: var(--hali-encre); font-size: 1.05rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .rv-patient { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .rv-patient .hl-avatar { width: 34px; height: 34px; flex-basis: 34px; font-size: .75rem; border-radius: 9px; }
    .rv-patient strong { display: block; color: var(--hali-encre); }
    .rv-sous { display: block; color: var(--hali-discret); font-size: .8rem; }
    .rv-motif { display: inline-flex; align-items: center; gap: 7px; color: var(--hali-texte); font-size: .88rem; }
    .rv-motif i { width: 9px; height: 9px; border-radius: 3px; flex: none; }
    .rv-actions { display: flex; justify-content: flex-end; gap: 6px; }
    .rv-actions form { margin: 0; }
    .rv-icone { display: inline-grid; place-items: center; width: 34px; height: 34px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; text-decoration: none; }
    .rv-icone:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); text-decoration: none; }
    .rv-icone.est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); background: var(--hali-danger-pale); }
    .rv-pagination { padding: 14px 18px; border-top: 1px solid var(--hali-bordure); }
    .rv-pagination nav { display: flex; justify-content: center; }
    @media (max-width: 991.98px) {
        .rv-ligne { grid-template-columns: 60px 1fr; gap: 6px 12px; }
        .rv-ligne > :nth-child(n+3) { grid-column: 2; }
        .rv-actions { justify-content: flex-start; }
    }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner hl">

      <header class="hl-entete">
          <div>
              <h1>Rendez-vous de la clinique</h1>
              <p>Tous les médecins, jour par jour.</p>
          </div>
          <div class="hl-entete-actions">
              @can('parcours.accueil')
                  <a href="{{ route('parcours.accueil.index') }}" class="hl-bouton"><i class="fas fa-door-open" aria-hidden="true"></i> Accueil du jour</a>
              @endcan
              @can('appointment.create')
                  <button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#nouveauRdvModal">
                      <i class="fa fa-plus" aria-hidden="true"></i> Nouveau rendez-vous
                  </button>
              @endcan
          </div>
      </header>

      <div class="hl-kpis">
          <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Rendez-vous du jour</span><span class="hl-kpi-valeur">{{ $stats['total'] }}</span></div>
          <div class="hl-bloc hl-kpi {{ $stats['pending'] > 0 ? 'est-alerte' : '' }}"><span class="hl-kpi-libelle">À confirmer</span><span class="hl-kpi-valeur">{{ $stats['pending'] }}</span></div>
          <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Confirmés</span><span class="hl-kpi-valeur">{{ $stats['confirmed'] }}</span></div>
          <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Honorés</span><span class="hl-kpi-valeur">{{ $stats['completed'] }}</span><span class="hl-kpi-detail">{{ $stats['cancelled'] }} annulé{{ $stats['cancelled'] > 1 ? 's' : '' }}</span></div>
      </div>

      <section class="hl-bloc">
          <div class="rv-jour">
              <a class="rv-fleche" href="{{ $avecJour($jour->copy()->subDay()) }}" title="Jour précédent" aria-label="Jour précédent"><i class="fas fa-chevron-left"></i></a>
              <h2>{{ ucfirst($libelleJour) }}</h2>
              <a class="rv-fleche" href="{{ $avecJour($jour->copy()->addDay()) }}" title="Jour suivant" aria-label="Jour suivant"><i class="fas fa-chevron-right"></i></a>
              @unless($jour->isToday())
                  <a class="hl-puce" href="{{ $avecJour(today()) }}">Aujourd'hui</a>
              @endunless
              <form method="GET" action="{{ route('appointment.index') }}" style="margin-left:auto">
                  @foreach(request()->except(['date', 'page']) as $cle => $valeur)
                      @if(is_string($valeur))<input type="hidden" name="{{ $cle }}" value="{{ $valeur }}">@endif
                  @endforeach
                  <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()" aria-label="Choisir une date">
              </form>
          </div>

          <form method="GET" action="{{ route('appointment.index') }}" class="rv-filtres">
              <input type="hidden" name="date" value="{{ $date }}">
              <label class="rv-recherche mb-0">
                  <span class="sr-only visually-hidden">Rechercher</span>
                  <i class="fas fa-search" aria-hidden="true"></i>
                  <input type="search" name="search" class="form-control" placeholder="Patient, téléphone, motif… puis Entrée" value="{{ request('search') }}">
              </label>
              <select name="employee_id" class="form-control" onchange="this.form.submit()" aria-label="Médecin">
                  <option value="">Tous les médecins</option>
                  @foreach($medecins as $m)
                      <option value="{{ $m->id }}" @selected((int) request('employee_id') === $m->id)>{{ $m->nom_affiche }}</option>
                  @endforeach
              </select>
              <select name="status" class="form-control" onchange="this.form.submit()" aria-label="Statut">
                  <option value="">Tous les statuts</option>
                  @foreach($statuts as $valeur => [$libelle])
                      <option value="{{ $valeur }}" @selected(request('status') === $valeur)>{{ $libelle }}</option>
                  @endforeach
              </select>
          </form>

          @if($appointments->isEmpty())
              <div class="hl-vide">
                  <i class="fas fa-calendar-day" aria-hidden="true"></i>
                  Aucun rendez-vous {{ request()->hasAny(['search', 'status', 'employee_id']) && (request('search') || request('status') || request('employee_id')) ? 'ne correspond à ces filtres' : 'ce jour-là' }}.
              </div>
          @else
              @foreach($appointments as $rdv)
                  @php
                      [$libelleStatut, $tonStatut] = $statuts[$rdv->status] ?? [$rdv->status, 'hl-s-neutre'];
                      $patient = $rdv->patient;
                      $initiales = $patient ? mb_strtoupper(mb_substr((string) $patient->first_name, 0, 1) . mb_substr((string) $patient->last_name, 0, 1)) : '?';
                  @endphp
                  <div class="rv-ligne {{ in_array($rdv->status, ['completed', 'cancelled', 'no_show'], true) ? 'est-passe' : '' }}">
                      <span class="rv-heure">{{ $rdv->appointment_time?->format('H:i') }}</span>
                      <div class="rv-patient">
                          <span class="hl-avatar" aria-hidden="true">{{ $initiales }}</span>
                          <div style="min-width:0">
                              <strong>{{ $patient?->full_name ?? 'Patient inconnu' }}</strong>
                              <span class="rv-sous">{{ $patient?->telephone ?: $patient?->identifiant_national_sante }}</span>
                          </div>
                      </div>
                      <span class="rv-motif">
                          @if($rdv->motifRdv)<i style="background: {{ $rdv->motifRdv->couleur ?: '#9ca3af' }}"></i>{{ $rdv->motifRdv->nom }}@else — @endif
                      </span>
                      <span class="rv-sous" style="font-size:.88rem; color: var(--hali-texte)">{{ $rdv->employee?->nom_affiche }}</span>
                      <span><span class="hl-statut {{ $tonStatut }}">{{ $libelleStatut }}</span></span>
                      <div class="rv-actions">
                          <a href="{{ route('appointment.show', $rdv) }}" class="rv-icone" title="Voir le rendez-vous" aria-label="Voir le rendez-vous"><i class="fa fa-eye"></i></a>
                          @can('appointment.edit')
                              @if(in_array($rdv->status, ['pending', 'confirmed'], true))
                                  <form action="{{ route('appointment.cancel', $rdv) }}" method="POST" onsubmit="return confirm('Annuler ce rendez-vous ? Le patient sera prévenu si les SMS sont activés.');">
                                      @csrf @method('DELETE')
                                      <input type="hidden" name="reason" value="Annulé par la réception">
                                      <button type="submit" class="rv-icone est-risque" title="Annuler" aria-label="Annuler le rendez-vous"><i class="fa fa-times"></i></button>
                                  </form>
                              @endif
                          @endcan
                      </div>
                  </div>
              @endforeach

              @if($appointments->hasPages())
                  <div class="rv-pagination">{{ $appointments->withQueryString()->links() }}</div>
              @endif
          @endif
      </section>

      {{-- ============ MODAL NOUVEAU RENDEZ-VOUS ============ --}}
      <div class="modal fade" id="nouveauRdvModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg"><div class="modal-content">
          <div class="modal-header border-0">
            <h5 class="modal-title">Nouveau rendez-vous</h5>
            <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
          </div>
          <div class="modal-body">

            <label>Patient</label>
            <input type="text" id="rdvPatientRecherche" class="form-control mb-1" placeholder="Nom, téléphone ou identifiant national santé...">
            <div id="rdvPatientResultats" class="list-group mb-2"></div>
            <div id="rdvPatientChoisi" class="alert alert-success py-2" hidden></div>

            <div id="rdvNouveauPatient" hidden class="border rounded p-2 mb-3">
                <div class="row g-2">
                    <div class="col-md-4"><input type="text" id="rdvPrenom" class="form-control" placeholder="Prénom"></div>
                    <div class="col-md-4"><input type="text" id="rdvNom" class="form-control" placeholder="Nom"></div>
                    <div class="col-md-3">
                        <select id="rdvGenre" class="form-control">
                            <option value="">Sexe</option>
                            <option value="Femme">Femme</option>
                            <option value="Homme">Homme</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-md-8"><input type="text" id="rdvTelephone" class="form-control" placeholder="Téléphone (9 chiffres)" maxlength="9"></div>
                    <div class="col-md-4"><button type="button" id="rdvCreerPatient" class="btn btn-success w-100">Créer ce patient</button></div>
                </div>
            </div>
            <button type="button" id="rdvBoutonNouveauPatient" class="btn btn-link p-0 mb-3">+ Nouveau patient</button>

            <hr>

            <label>Motif</label>
            <select id="rdvMotif" class="form-control mb-2"><option value="">Chargement…</option></select>

            <label>Médecin</label>
            <select id="rdvMedecin" class="form-control mb-2" disabled><option value="">Choisir un motif d'abord</option></select>

            <label>Date</label>
            <select id="rdvDate" class="form-control mb-2" disabled><option value="">Choisir un médecin d'abord</option></select>

            <label>Heure</label>
            <select id="rdvHeure" class="form-control mb-2" disabled><option value="">Choisir une date d'abord</option></select>

            <label>Notes (facultatif)</label>
            <textarea id="rdvDescription" class="form-control"></textarea>

            <p class="text-danger mt-2" id="rdvErreur"></p>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="button" id="rdvSoumettre" class="btn btn-primary" disabled>Créer le rendez-vous</button>
          </div>
        </div></div>
      </div>
    </div>
</div>

<script>
(function(){
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const etat = { patientId: null, motifId: null, medecinId: null, date: null, heure: null };

    function json(url, options = {}) {
        return fetch(url, { headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf, 'X-Requested-With':'XMLHttpRequest'}, ...options })
            .then(r => r.json().then(data => ({status:r.status, data})));
    }

    function majBouton(){
        document.getElementById('rdvSoumettre').disabled = !(etat.patientId && etat.motifId && etat.medecinId && etat.date && etat.heure);
    }

    // --- Recherche patient ---
    let rechercheTimeout;
    document.getElementById('rdvPatientRecherche').addEventListener('input', function(){
        clearTimeout(rechercheTimeout);
        const q = this.value;
        if(q.length < 2) { document.getElementById('rdvPatientResultats').innerHTML = ''; return; }
        rechercheTimeout = setTimeout(() => {
            json(`{{ route('appointment-form.patients.recherche') }}?q=${encodeURIComponent(q)}`).then(({data}) => {
                const conteneur = document.getElementById('rdvPatientResultats');
                conteneur.innerHTML = '';
                data.forEach(p => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    item.textContent = `${p.nom} — ${p.telephone || 'sans téléphone'} (${p.identifiant})`;
                    item.addEventListener('click', () => choisirPatient(p.id, p.nom));
                    conteneur.appendChild(item);
                });
            });
        }, 400);
    });

    function choisirPatient(id, nom){
        etat.patientId = id;
        document.getElementById('rdvPatientResultats').innerHTML = '';
        document.getElementById('rdvPatientRecherche').value = '';
        const choisi = document.getElementById('rdvPatientChoisi');
        choisi.hidden = false;
        choisi.textContent = `Patient sélectionné : ${nom}`;
        majBouton();
    }

    document.getElementById('rdvBoutonNouveauPatient').addEventListener('click', function(){
        document.getElementById('rdvNouveauPatient').hidden = false;
        this.hidden = true;
    });

    document.getElementById('rdvCreerPatient').addEventListener('click', function(){
        const body = {
            prenom: document.getElementById('rdvPrenom').value,
            nom: document.getElementById('rdvNom').value,
            genre: document.getElementById('rdvGenre').value,
            telephone: document.getElementById('rdvTelephone').value,
        };
        json(`{{ route('appointment-form.patients.store') }}`, {method:'POST', body: JSON.stringify(body)}).then(({status, data}) => {
            if(status !== 201){ document.getElementById('rdvErreur').textContent = JSON.stringify(data.errors || data.error); return; }
            choisirPatient(data.patient_id, data.nom);
            document.getElementById('rdvNouveauPatient').hidden = true;
        });
    });

    // --- Motif -> médecin -> date -> heure ---
    json(`{{ route('appointment-form.motifs') }}`).then(({data}) => {
        const select = document.getElementById('rdvMotif');
        select.innerHTML = '<option value="">Choisir un motif</option>';
        data.forEach(dep => {
            const groupe = document.createElement('optgroup');
            groupe.label = dep.name;
            dep.motifs_rdv.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = `${m.nom} (${m.duree_minutes_defaut} min)`;
                groupe.appendChild(opt);
            });
            select.appendChild(groupe);
        });
    });

    document.getElementById('rdvMotif').addEventListener('change', function(){
        etat.motifId = this.value || null;
        etat.medecinId = null; etat.date = null; etat.heure = null;
        majBouton();
        const medecinSelect = document.getElementById('rdvMedecin');
        medecinSelect.disabled = true;
        medecinSelect.innerHTML = '<option value="">Chargement…</option>';
        document.getElementById('rdvDate').disabled = true;
        document.getElementById('rdvDate').innerHTML = '<option value="">Choisir un médecin d\'abord</option>';
        document.getElementById('rdvHeure').disabled = true;
        document.getElementById('rdvHeure').innerHTML = '<option value="">Choisir une date d\'abord</option>';

        if(!etat.motifId) return;

        json(`{{ url('/appointment-form/motifs') }}/${etat.motifId}/medecins`).then(({data}) => {
            medecinSelect.innerHTML = '<option value="">Choisir un médecin</option>';
            data.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id; opt.textContent = m.nom;
                medecinSelect.appendChild(opt);
            });
            medecinSelect.disabled = false;
        });
    });

    document.getElementById('rdvMedecin').addEventListener('change', function(){
        etat.medecinId = this.value || null;
        etat.date = null; etat.heure = null;
        majBouton();
        const dateSelect = document.getElementById('rdvDate');
        dateSelect.disabled = true;
        dateSelect.innerHTML = '<option value="">Chargement…</option>';
        document.getElementById('rdvHeure').disabled = true;
        document.getElementById('rdvHeure').innerHTML = '<option value="">Choisir une date d\'abord</option>';

        if(!etat.medecinId) return;

        json(`{{ route('appointment-form.dates') }}?employee_id=${etat.medecinId}&motif_rdv_id=${etat.motifId}`).then(({data}) => {
            dateSelect.innerHTML = '<option value="">Choisir une date</option>';
            data.forEach(iso => {
                const opt = document.createElement('option');
                opt.value = iso; opt.textContent = new Date(iso+'T00:00:00').toLocaleDateString('fr-FR', {weekday:'short', day:'numeric', month:'short'});
                dateSelect.appendChild(opt);
            });
            dateSelect.disabled = false;
        });
    });

    document.getElementById('rdvDate').addEventListener('change', function(){
        etat.date = this.value || null;
        etat.heure = null;
        majBouton();
        const heureSelect = document.getElementById('rdvHeure');
        heureSelect.disabled = true;
        heureSelect.innerHTML = '<option value="">Chargement…</option>';

        if(!etat.date) return;

        json(`{{ route('appointment-form.creneaux') }}?employee_id=${etat.medecinId}&motif_rdv_id=${etat.motifId}&date=${etat.date}`).then(({data}) => {
            heureSelect.innerHTML = '<option value="">Choisir une heure</option>';
            data.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.debut; opt.textContent = c.debut;
                heureSelect.appendChild(opt);
            });
            heureSelect.disabled = false;
        });
    });

    document.getElementById('rdvHeure').addEventListener('change', function(){
        etat.heure = this.value || null;
        majBouton();
    });

    document.getElementById('rdvSoumettre').addEventListener('click', function(){
        this.disabled = true;
        json(`{{ route('appointment.store') }}`, {method:'POST', body: JSON.stringify({
            patient_id: etat.patientId,
            motif_rdv_id: etat.motifId,
            employee_id: etat.medecinId,
            appointment_date: etat.date,
            appointment_time: etat.heure,
            description: document.getElementById('rdvDescription').value,
        })}).then(({status, data}) => {
            if(status === 201 || status === 200){ window.location.reload(); return; }
            document.getElementById('rdvErreur').textContent = data.error || 'Erreur lors de la création.';
            this.disabled = false;
        });
    });
})();
</script>
@endsection
