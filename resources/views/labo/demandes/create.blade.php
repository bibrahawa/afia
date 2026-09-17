@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Nouvelle demande d\'analyses', 'fil' => [route('labo.demandes.index') => 'Demandes', 0 => 'Nouvelle']])

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('labo.demandes.store') }}" id="formDemande">@csrf
    <div class="row">
        <div class="col-lg-5">
            {{-- Patient --}}
            <div class="card">
                <div class="card-header"><h4 class="card-title">1. Patient</h4></div>
                <div class="card-body">
                    <input type="hidden" name="patient_id" id="patientId" value="{{ old('patient_id') }}">
                    <div id="patientChoisi" class="alert alert-success py-2" @if(!old('patient_id')) hidden @endif>Patient sélectionné</div>
                    <input type="text" id="patientRecherche" class="form-control" placeholder="Nom, téléphone ou identifiant santé (2 caractères min.)" autocomplete="off">
                    <div id="patientResultats" class="list-group mt-1"></div>
                    <button type="button" class="btn btn-link btn-sm px-0" id="btnNouveauPatient">+ Patient absent : le créer</button>
                    <div id="nouveauPatient" hidden class="border rounded p-2 mt-2">
                        <div class="row g-2">
                            <div class="col-6"><input id="npPrenom" class="form-control form-control-sm" placeholder="Prénom"></div>
                            <div class="col-6"><input id="npNom" class="form-control form-control-sm" placeholder="Nom"></div>
                            <div class="col-6"><select id="npGenre" class="form-select form-select-sm"><option value="Homme">Homme</option><option value="Femme">Femme</option></select></div>
                            <div class="col-6"><input id="npTelephone" class="form-control form-control-sm" placeholder="Téléphone (9 chiffres)"></div>
                        </div>
                        <div id="npErreur" class="text-danger small mt-1"></div>
                        <button type="button" id="npCreer" class="btn btn-primary btn-sm mt-2">Créer et sélectionner</button>
                    </div>
                    <p class="small text-muted mt-2 mb-0">L'âge et le sexe du patient déterminent les normes : vérifiez sa date de naissance dans son dossier.</p>
                </div>
            </div>

            {{-- Prescription --}}
            <div class="card">
                <div class="card-header"><h4 class="card-title">2. Prescription</h4></div>
                <div class="card-body">
                    <div class="mb-2">
                        @foreach(\App\Enums\Labo\OrigineDemande::cases() as $o)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="origine" value="{{ $o->value }}" id="orig{{ $o->value }}" @checked(old('origine', 'externe') === $o->value)>
                                <label class="form-check-label" for="orig{{ $o->value }}">{{ $o->libelle() }}</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-2" data-origine="interne">
                        <label class="form-label">Médecin prescripteur</label>
                        <select name="prescripteur_employee_id" class="form-select"><option value="">—</option>
                            @foreach($medecins as $m)<option value="{{ $m->id }}" @selected(old('prescripteur_employee_id') == $m->id)>Dr {{ $m->first_name }} {{ $m->last_name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-2" data-origine="externe">
                        <div class="col-7"><label class="form-label">Médecin / structure</label><input name="prescripteur_externe" value="{{ old('prescripteur_externe') }}" class="form-control"></div>
                        <div class="col-5"><label class="form-label">Téléphone</label><input name="prescripteur_telephone" value="{{ old('prescripteur_telephone') }}" class="form-control"></div>
                    </div>
                    <div class="mb-2"><label class="form-label">Renseignements cliniques</label>
                        <textarea name="renseignements_cliniques" rows="2" class="form-control" placeholder="Fièvre depuis 3 jours, suivi diabète…">{{ old('renseignements_cliniques') }}</textarea></div>
                    <div class="row g-2">
                        <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="urgence" value="1" id="urgence" @checked(old('urgence'))><label class="form-check-label text-danger fw-bold" for="urgence">Urgent</label></div></div>
                        <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="a_jeun_confirme" value="1" id="ajeun" @checked(old('a_jeun_confirme'))><label class="form-check-label" for="ajeun">Patient à jeun</label></div></div>
                        <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" name="grossesse" value="1" id="grossesse" @checked(old('grossesse'))><label class="form-check-label" for="grossesse">Enceinte</label></div></div>
                        <div class="col-6"><input type="number" name="semaines_amenorrhee" min="1" max="45" value="{{ old('semaines_amenorrhee') }}" class="form-control form-control-sm" placeholder="SA"></div>
                    </div>
                </div>
            </div>

            {{-- Facturation --}}
            <div class="card">
                <div class="card-header"><h4 class="card-title">4. Facturation</h4></div>
                <div class="card-body">
                    <select name="mode_facturation" class="form-select mb-2">
                        <option value="labo" @selected(old('mode_facturation', 'labo') === 'labo')>Facturée au laboratoire</option>
                        <option value="gratuit" @selected(old('mode_facturation') === 'gratuit')>Gratuit</option>
                    </select>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="resultats_retenus_si_impaye" value="1" id="retenus" @checked(old('resultats_retenus_si_impaye', true))>
                        <label class="form-check-label" for="retenus">Retenir la remise des résultats au patient tant que la part patient n'est pas réglée</label></div>
                    <div class="d-flex mt-3 fs-5"><span>Total indicatif</span><strong class="ms-auto" id="total">0 GNF</strong></div>
                    <p class="small text-muted">La part assurance est calculée à la facturation.</p>
                    <button class="btn btn-primary w-100">Enregistrer la demande</button>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">3. Examens</h4>
                    <input type="text" id="filtreExamens" class="form-control mt-2" placeholder="Filtrer (NFS, glycémie, VIH…)">
                </div>
                <div class="card-body">
                    @if($bilans->isNotEmpty())
                        <p class="mb-1 small fw-bold">Bilans (cochent leurs examens)</p>
                        <div class="mb-3">
                            @foreach($bilans as $b)
                                <input type="checkbox" class="btn-check bilan" name="bilans[]" value="{{ $b->id }}" id="bilan{{ $b->id }}" data-examens="{{ $b->examens->pluck('id')->implode(',') }}" autocomplete="off" @checked(in_array($b->id, old('bilans', [])))>
                                <label class="btn btn-outline-primary btn-sm mb-1" for="bilan{{ $b->id }}">{{ $b->nom }}</label>
                            @endforeach
                        </div>
                    @endif
                    @forelse($sections as $section)
                        @continue($section->examens->isEmpty())
                        <h5 class="mt-3 border-bottom pb-1">{{ $section->nom }}</h5>
                        <div class="row">
                            @foreach($section->examens as $ex)
                                <div class="col-md-6 examen-item" data-texte="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($ex->nom . ' ' . $ex->abreviation . ' ' . $ex->code)) }}">
                                    <div class="form-check">
                                        <input class="form-check-input examen" type="checkbox" name="examens[]" value="{{ $ex->id }}" id="ex{{ $ex->id }}" data-prix="{{ (float) $ex->prix }}" data-ajeun="{{ $ex->a_jeun ? 1 : 0 }}" @checked(in_array($ex->id, old('examens', [])))>
                                        <label class="form-check-label" for="ex{{ $ex->id }}">
                                            @if($ex->tube)<span class="labo-tube labo-tube-{{ $ex->tube }}"></span>@endif
                                            {{ $ex->nom }}
                                            <span class="small text-muted">— {{ number_format($ex->prix, 0, ',', ' ') }} GNF @if($ex->a_jeun)· à jeun @endif @if($ex->sous_traite)· sous-traité @endif</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p class="text-muted">Catalogue vide. <a href="{{ route('labo.catalogue.index') }}">Configurer le catalogue</a>.</p>
                    @endforelse
                    <div id="alerteJeun" class="alert alert-warning mt-3" hidden>Un examen coché nécessite le jeûne : confirmez que le patient est à jeun (sinon, notez-le dans les renseignements).</div>
                </div>
            </div>
        </div>
    </div>
    </form>
</div></div>
@endsection

@section('script')
<script>
(function () {
    const csrf = '{{ csrf_token() }}';
    const json = (url, opts = {}) => fetch(url, Object.assign({headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf}}, opts))
        .then(r => r.json().then(data => ({status: r.status, data})));

    function choisirPatient(id, nom) {
        document.getElementById('patientId').value = id;
        const c = document.getElementById('patientChoisi'); c.hidden = false; c.textContent = 'Patient : ' + nom;
        document.getElementById('patientResultats').innerHTML = '';
        document.getElementById('patientRecherche').value = '';
    }

    let t;
    document.getElementById('patientRecherche').addEventListener('input', function () {
        clearTimeout(t); const q = this.value;
        const zone = document.getElementById('patientResultats');
        if (q.length < 2) { zone.innerHTML = ''; return; }
        t = setTimeout(() => json(`{{ route('appointment-form.patients.recherche') }}?q=${encodeURIComponent(q)}`).then(({data}) => {
            zone.innerHTML = '';
            data.forEach(p => {
                const b = document.createElement('button'); b.type = 'button'; b.className = 'list-group-item list-group-item-action';
                b.textContent = `${p.nom} — ${p.telephone || 'sans téléphone'} (${p.identifiant || ''})`;
                b.addEventListener('click', () => choisirPatient(p.id, p.nom)); zone.appendChild(b);
            });
        }), 400);
    });

    document.getElementById('btnNouveauPatient').addEventListener('click', function () { document.getElementById('nouveauPatient').hidden = false; this.hidden = true; });
    document.getElementById('npCreer').addEventListener('click', function () {
        const body = {prenom: npPrenom.value, nom: npNom.value, genre: npGenre.value, telephone: npTelephone.value};
        json(`{{ route('appointment-form.patients.store') }}`, {method: 'POST', body: JSON.stringify(body)}).then(({status, data}) => {
            if (status !== 201) { npErreur.textContent = Object.values(data.errors || {error: [data.error]}).flat().join(' '); return; }
            choisirPatient(data.patient_id, data.nom); document.getElementById('nouveauPatient').hidden = true;
        });
    });

    // Origine → champs prescripteur
    function majOrigine() {
        const o = document.querySelector('input[name=origine]:checked')?.value;
        document.querySelectorAll('[data-origine]').forEach(el => el.hidden = el.dataset.origine !== o);
    }
    document.querySelectorAll('input[name=origine]').forEach(r => r.addEventListener('change', majOrigine)); majOrigine();

    // Bilans → examens ; total et jeûne
    const examens = () => [...document.querySelectorAll('.examen')];
    function majTotal() {
        const coches = examens().filter(e => e.checked);
        document.getElementById('total').textContent = coches.reduce((s, e) => s + parseFloat(e.dataset.prix || 0), 0).toLocaleString('fr-FR') + ' GNF';
        document.getElementById('alerteJeun').hidden = !(coches.some(e => e.dataset.ajeun === '1') && !ajeun.checked);
    }
    document.querySelectorAll('.bilan').forEach(b => b.addEventListener('change', function () {
        this.dataset.examens.split(',').filter(Boolean).forEach(id => { const cb = document.getElementById('ex' + id); if (cb) cb.checked = this.checked; });
        majTotal();
    }));
    examens().forEach(e => e.addEventListener('change', majTotal));
    ajeun.addEventListener('change', majTotal); majTotal();

    document.getElementById('filtreExamens').addEventListener('input', function () {
        const q = this.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        document.querySelectorAll('.examen-item').forEach(el => el.hidden = q && !el.dataset.texte.includes(q));
    });

    document.getElementById('formDemande').addEventListener('submit', function (ev) {
        if (!document.getElementById('patientId').value) { ev.preventDefault(); swal('Patient manquant', 'Sélectionnez ou créez le patient.', 'warning'); return; }
        if (!examens().some(e => e.checked)) { ev.preventDefault(); swal('Aucun examen', 'Cochez au moins un examen ou un bilan.', 'warning'); }
    });
})();
</script>
@endsection
