@extends('layouts.backend')

@section('content')
<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home"><a href="{{url('/')}}"><i class="icon-home"></i></a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item"><a href="{{ route('appointment.index') }}">Rendez-vous</a></li>
        </ul>
      </div>

      <div class="row mb-3">
        <div class="col-6 col-md-2">
          <div class="card text-center p-2"><div class="h4 mb-0">{{ $stats['total'] }}</div><small class="text-muted">Total du jour</small></div>
        </div>
        <div class="col-6 col-md-2">
          <div class="card text-center p-2"><div class="h4 mb-0 text-warning">{{ $stats['pending'] }}</div><small class="text-muted">En attente</small></div>
        </div>
        <div class="col-6 col-md-2">
          <div class="card text-center p-2"><div class="h4 mb-0 text-success">{{ $stats['confirmed'] }}</div><small class="text-muted">Confirmés</small></div>
        </div>
        <div class="col-6 col-md-2">
          <div class="card text-center p-2"><div class="h4 mb-0 text-primary">{{ $stats['completed'] }}</div><small class="text-muted">Terminés</small></div>
        </div>
        <div class="col-6 col-md-2">
          <div class="card text-center p-2"><div class="h4 mb-0 text-danger">{{ $stats['cancelled'] }}</div><small class="text-muted">Annulés</small></div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Rendez-vous de la clinique</h4>
                @can('appointment.create')
                    <button class="btn btn-primary btn-round ms-auto" data-bs-toggle="modal" data-bs-target="#nouveauRdvModal">
                        <i class="fa fa-plus"></i> Nouveau rendez-vous
                    </button>
                @endcan
              </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('appointment.index') }}" class="row g-2 mb-3">
                    <div class="col-md-2">
                        <input type="date" name="date" class="form-control" value="{{ $date }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-3">
                        <select name="employee_id" class="form-control" onchange="this.form.submit()">
                            <option value="">Tous les médecins</option>
                            @foreach($medecins as $m)
                                <option value="{{ $m->id }}" {{ request('employee_id') == $m->id ? 'selected' : '' }}>
                                    Dr. {{ $m->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="pending" {{ request('status')=='pending'?'selected':'' }}>En attente</option>
                            <option value="confirmed" {{ request('status')=='confirmed'?'selected':'' }}>Confirmé</option>
                            <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Terminé</option>
                            <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Annulé</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Patient, téléphone, motif..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search"></i></button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Heure</th>
                                <th>Patient</th>
                                <th>Médecin</th>
                                <th>Motif</th>
                                <th>Statut</th>
                                <th style="width:15%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($appointments as $rdv)
                                <tr>
                                    <td>{{ $rdv->appointment_time->format('H:i') }}</td>
                                    <td>
                                        {{ $rdv->patient?->getFullName() }}
                                        <br><small class="text-muted">{{ $rdv->patient?->identifiant_national_sante }}</small>
                                    </td>
                                    <td>Dr. {{ $rdv->employee->full_name }}</td>
                                    <td>
                                        @if($rdv->motifRdv)
                                            <span style="display:inline-block;width:10px;height:10px;background:{{ $rdv->motifRdv->couleur }};border-radius:2px;"></span>
                                            {{ $rdv->motifRdv->nom }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ ['pending'=>'warning','confirmed'=>'success','completed'=>'primary','cancelled'=>'danger','no_show'=>'secondary'][$rdv->status] }}">
                                            {{ ['pending'=>'En attente','confirmed'=>'Confirmé','completed'=>'Terminé','cancelled'=>'Annulé','no_show'=>'Absence'][$rdv->status] }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="form-button-action">
                                            <a href="{{ route('appointment.show', $rdv) }}" class="btn btn-info btn-round btn-sm"><i class="fa fa-eye"></i></a>
                                            @can('appointment.edit')
                                                @if(in_array($rdv->status, ['pending','confirmed']))
                                                    <form action="{{ route('appointment.cancel', $rdv) }}" method="POST" class="d-inline" onsubmit="return confirm('Annuler ce rendez-vous ?');">
                                                        @csrf @method('DELETE')
                                                        <input type="hidden" name="reason" value="Annulé par la réception">
                                                        <button type="submit" class="btn btn-danger btn-round btn-sm"><i class="fa fa-times"></i></button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted">Aucun rendez-vous pour cette journée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $appointments->withQueryString()->links() }}
            </div>
          </div>
        </div>
      </div>

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
