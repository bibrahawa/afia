@extends('layouts.backend')
@section('style')
    @include('labo.partials.styles')
    <style>
        .dc-cote { position: sticky; top: calc(var(--hali-haut, 78px) + 12px); }
        .dc-choisi { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 10px; background: var(--hali-primaire-pale); border: 1px solid var(--hali-primaire-clair); }
        .dc-choisi .hl-avatar { width: 38px; height: 38px; flex-basis: 38px; }
        .dc-choisi strong { color: var(--hali-encre); }
        #patientResultats .list-group-item { border-radius: 8px !important; margin-top: 4px; border: 1px solid var(--hali-bordure); }
        .dc-nouveau { display: grid; gap: 10px; padding: 12px; border: 1px dashed var(--hali-bordure); border-radius: 10px; }
        .dc-filtre { position: relative; }
        .dc-filtre i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
        .dc-filtre input { padding-left: 40px; min-height: 44px; }
        .dc-section { margin: 18px 0 8px; color: var(--hali-encre); font-size: .85rem; font-weight: 700; }
        .dc-examens { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 8px; }
        .dc-examen { position: relative; margin: 0; }
        .dc-examen input { position: absolute; opacity: 0; pointer-events: none; }
        .dc-examen > span { display: flex; align-items: center; gap: 10px; min-height: 52px; padding: 8px 12px; border: 1px solid var(--hali-bordure); border-radius: 10px; background: #fff; cursor: pointer; transition: border-color .12s ease, background-color .12s ease; }
        .dc-examen > span:hover { border-color: var(--hali-primaire); }
        .dc-examen > span::before { content: ""; width: 18px; height: 18px; flex: none; border: 2px solid #d1d5db; border-radius: 5px; background: #fff center / 12px no-repeat; }
        .dc-examen input:checked + span { background: var(--hali-primaire-pale); border-color: var(--hali-primaire); }
        .dc-examen input:checked + span::before { border-color: var(--hali-primaire); background-color: var(--hali-primaire); background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12'%3E%3Cpath d='M2 6.5l2.5 2.5L10 3.5' fill='none' stroke='%23fff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); }
        .dc-examen input:focus-visible + span { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
        .dc-examen b { display: block; color: var(--hali-encre); font-size: .87rem; font-weight: 600; }
        .dc-examen small { display: block; color: var(--hali-discret); font-size: .76rem; }
        .dc-total { display: flex; align-items: baseline; justify-content: space-between; padding: 14px 16px; border-radius: 10px; background: var(--hali-primaire-pale); }
        .dc-total span { color: var(--hali-discret); font-size: .85rem; font-weight: 600; }
        .dc-total strong { color: var(--hali-encre); font-size: 1.5rem; font-variant-numeric: tabular-nums; }
        .dc-origines { display: flex; flex-wrap: wrap; gap: 8px; }
        @media (max-width: 1199.98px) { .dc-cote { position: static; } }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Nouvelle demande d\'analyses', 'fil' => [route('labo.demandes.index') => 'Demandes', 0 => 'Nouvelle'], 'sousTitre' => 'Patient, examens, prescription : la facture est préparée à l\'enregistrement.'])

    @if($consultation && $examensPrescrits)
        <p class="lb-alerte lb-alerte-info"><i class="fas fa-info-circle mt-1" aria-hidden="true"></i>
            <span>Les examens prescrits pendant la consultation sont pré-cochés. S'ils ont déjà été facturés avec la consultation, choisissez « Incluse dans la facture de la consultation » pour ne pas les facturer deux fois. Un examen ajouté ici en plus doit faire l'objet d'une demande séparée, facturée au laboratoire.</span></p>
    @endif

    @if($errors->any())
        <div class="lb-alerte lb-alerte-erreur" role="alert"><i class="fas fa-exclamation-circle mt-1" aria-hidden="true"></i>
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('labo.demandes.store') }}" id="formDemande">@csrf
    <div class="lb-grille-large">
        <div class="lb-colonne">
            {{-- ------------------------------------------------ 1. Patient --}}
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">1. Patient</h2>
                <div class="lb-form">
                    @php($patientId = old('patient_id', $patient?->id))
                    <input type="hidden" name="patient_id" id="patientId" value="{{ $patientId }}">
                    @if($consultation)<input type="hidden" name="consultation_id" value="{{ $consultation->id }}">@endif

                    <div id="patientChoisi" class="dc-choisi" @if(!$patientId) hidden @endif>
                        <span class="hl-avatar" aria-hidden="true"><i class="fas fa-user"></i></span>
                        <div>
                            <strong class="js-nom">{{ $patient?->full_name ?? 'Patient sélectionné' }}</strong>
                            @if($consultation)<span class="lb-sous">Consultation du {{ $consultation->created_at->format('d/m/Y') }}</span>@endif
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="patientRecherche">{{ $patientId ? 'Changer de patient' : 'Rechercher le patient' }}</label>
                        <input type="text" id="patientRecherche" class="form-control" placeholder="Nom, téléphone ou identifiant santé (2 caractères min.)" autocomplete="off">
                        <div id="patientResultats" class="list-group"></div>
                    </div>

                    <button type="button" class="hl-bouton" id="btnNouveauPatient" style="justify-self:start"><i class="fas fa-user-plus" aria-hidden="true"></i> Patient absent : le créer</button>
                    <div id="nouveauPatient" hidden class="dc-nouveau">
                        <div class="lb-deux">
                            <input id="npPrenom" class="form-control" placeholder="Prénom">
                            <input id="npNom" class="form-control" placeholder="Nom">
                            <select id="npGenre" class="form-select"><option value="Homme">Homme</option><option value="Femme">Femme</option></select>
                            <input id="npTelephone" class="form-control" placeholder="Téléphone (9 chiffres)" inputmode="numeric" maxlength="9">
                        </div>
                        <div id="npErreur" class="small" style="color:var(--hali-danger)"></div>
                        <button type="button" id="npCreer" class="hl-bouton hl-bouton-plein" style="justify-self:start">Créer et sélectionner</button>
                    </div>
                    <p class="lb-aide">L'âge et le sexe déterminent les normes : vérifiez la date de naissance dans le dossier du patient.</p>
                </div>
            </section>

            {{-- ------------------------------------------------ 2. Examens --}}
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">2. Examens</h2>
                <div class="lb-form">
                    <label class="dc-filtre mb-0">
                        <span class="sr-only visually-hidden">Filtrer les examens</span>
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="text" id="filtreExamens" class="form-control" placeholder="Filtrer : NFS, glycémie, VIH…">
                    </label>

                    @if($bilans->isNotEmpty())
                        <div>
                            <span class="lb-libelle">Bilans <span class="lb-sous" style="display:inline">(cochent leurs examens)</span></span>
                            <div class="lb-coches">
                                @foreach($bilans as $b)
                                    <label class="lb-coche">
                                        <input type="checkbox" class="bilan" name="bilans[]" value="{{ $b->id }}" id="bilan{{ $b->id }}" data-examens="{{ $b->examens->pluck('id')->implode(',') }}" autocomplete="off" @checked(in_array($b->id, old('bilans', [])))>
                                        <span>{{ $b->nom }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        @forelse($sections as $section)
                            @continue($section->examens->isEmpty())
                            <h3 class="dc-section">{{ $section->nom }}</h3>
                            <div class="dc-examens">
                                @foreach($section->examens as $ex)
                                    <div class="examen-item" data-texte="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($ex->nom . ' ' . $ex->abreviation . ' ' . $ex->code)) }}">
                                        <label class="dc-examen" for="ex{{ $ex->id }}">
                                            <input class="examen" type="checkbox" name="examens[]" value="{{ $ex->id }}" id="ex{{ $ex->id }}" data-prix="{{ (float) $ex->prix }}" data-ajeun="{{ $ex->a_jeun ? 1 : 0 }}" @checked(in_array($ex->id, old('examens', $examensPrescrits ?? [])))>
                                            <span>
                                                <span style="min-width:0">
                                                    <b>@if($ex->tube)<span class="labo-tube labo-tube-{{ $ex->tube }}"></span>@endif{{ $ex->nom }}</b>
                                                    <small>{{ number_format($ex->prix, 0, ',', ' ') }} GNF @if($ex->a_jeun)· à jeun @endif @if($ex->sous_traite)· sous-traité @endif</small>
                                                </span>
                                            </span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="hl-vide" style="padding:24px">Catalogue vide. <a href="{{ route('labo.catalogue.index') }}">Configurer le catalogue</a>.</div>
                        @endforelse
                    </div>
                    <p id="alerteJeun" class="lb-alerte lb-alerte-avert" hidden style="margin:0"><i class="fas fa-utensils mt-1" aria-hidden="true"></i><span>Un examen coché demande le jeûne : confirmez que le patient est à jeun, sinon notez-le dans les renseignements cliniques.</span></p>
                </div>
            </section>
        </div>

        <div class="lb-colonne dc-cote">
            {{-- ------------------------------------------------ 3. Prescription --}}
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">3. Prescription</h2>
                <div class="lb-form">
                    <div class="dc-origines">
                        @foreach(\App\Enums\Labo\OrigineDemande::cases() as $o)
                            <label class="lb-coche">
                                <input type="radio" name="origine" value="{{ $o->value }}" id="orig{{ $o->value }}" @checked(old('origine', $consultation ? 'interne' : 'externe') === $o->value)>
                                <span>{{ $o->libelle() }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div data-origine="interne">
                        <label class="form-label">Médecin prescripteur</label>
                        <select name="prescripteur_employee_id" class="form-select"><option value="">—</option>
                            @foreach($medecins as $m)<option value="{{ $m->id }}" @selected(old('prescripteur_employee_id', $consultation?->medecin_id) == $m->id)>{{ $m->nom_affiche }}</option>@endforeach
                        </select>
                    </div>
                    <div class="lb-deux" data-origine="externe">
                        <div><label class="form-label">Médecin ou structure</label><input name="prescripteur_externe" value="{{ old('prescripteur_externe') }}" class="form-control"></div>
                        <div><label class="form-label">Téléphone</label><input name="prescripteur_telephone" value="{{ old('prescripteur_telephone') }}" class="form-control" inputmode="numeric"></div>
                    </div>
                    <div><label class="form-label">Renseignements cliniques</label>
                        <textarea name="renseignements_cliniques" rows="2" class="form-control" placeholder="Fièvre depuis 3 jours, suivi diabète…">{{ old('renseignements_cliniques', $consultation ? trim(($consultation->motif ?? '') . ($consultation->diagnostic ? ' — ' . $consultation->diagnostic : '')) : '') }}</textarea></div>
                    <div class="lb-coches">
                        <label class="lb-coche est-danger"><input type="checkbox" name="urgence" value="1" id="urgence" @checked(old('urgence'))><span><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span></label>
                        <label class="lb-coche"><input type="checkbox" name="a_jeun_confirme" value="1" id="ajeun" @checked(old('a_jeun_confirme'))><span>Patient à jeun</span></label>
                        <label class="lb-coche"><input type="checkbox" name="grossesse" value="1" id="grossesse" @checked(old('grossesse'))><span>Enceinte</span></label>
                        <input type="number" name="semaines_amenorrhee" min="1" max="45" value="{{ old('semaines_amenorrhee') }}" class="form-control" placeholder="SA" style="width:80px; min-height:36px" aria-label="Semaines d'aménorrhée">
                    </div>
                </div>
            </section>

            {{-- ------------------------------------------------ 4. Facturation --}}
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">4. Facturation</h2>
                <div class="lb-form">
                    @php($modeDefaut = old('mode_facturation', $consultation && $examensPrescrits && ($consultation->est_facturee || $consultation->transaction) ? 'consultation' : 'labo'))
                    <select name="mode_facturation" class="form-select" aria-label="Mode de facturation">
                        <option value="labo" @selected($modeDefaut === 'labo')>Facturée au laboratoire</option>
                        @if($consultation)
                            <option value="consultation" @selected($modeDefaut === 'consultation')>Incluse dans la facture de la consultation</option>
                        @endif
                        <option value="gratuit" @selected($modeDefaut === 'gratuit')>Gratuit</option>
                    </select>
                    <label class="d-flex gap-2 mb-0" style="font-weight:500; font-size:.84rem; color:var(--hali-texte)">
                        <input type="checkbox" name="resultats_retenus_si_impaye" value="1" id="retenus" @checked(old('resultats_retenus_si_impaye', true)) style="margin-top:3px">
                        Retenir la remise des résultats au patient tant que sa part n'est pas réglée
                    </label>
                    <div class="dc-total"><span>Total indicatif</span><strong id="total">0 GNF</strong></div>
                    <p class="lb-aide" style="margin-top:-6px">La part assurance est calculée à la facturation.</p>
                    <button class="hl-bouton hl-bouton-plein" style="min-height:48px; font-size:.95rem"><i class="fas fa-check" aria-hidden="true"></i> Enregistrer la demande</button>
                </div>
            </section>
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
        const c = document.getElementById('patientChoisi'); c.hidden = false; c.querySelector('.js-nom').textContent = nom;
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
