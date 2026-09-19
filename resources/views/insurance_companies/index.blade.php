@extends('layouts.backend')

@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $libellesTypes = collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->libelle()]);
    $actifs = $organismes->where('status', 'active');
    $totalAssures = (int) $assures->sum();
    $totalDu = (float) $creances->sum();
    $voirCreances = auth()->user()?->can('assurance.creances.view') && Route::has('assurance.creances.show');
@endphp

@section('style')
<style>
    .og-grille { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 14px; }
    .og-carte { display: grid; grid-template-rows: auto auto 1fr auto; gap: 12px; padding: 18px; border: 1px solid var(--hali-bordure); border-radius: var(--hali-rayon); background: #fff; }
    .og-carte.est-inactif { background: #fafafa; }
    .og-carte.est-inactif .og-nom { color: var(--hali-discret); }
    .og-haut { display: flex; align-items: flex-start; gap: 12px; }
    .og-initiale { display: grid; place-items: center; width: 44px; height: 44px; flex: none; border-radius: 12px; background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); font-weight: 800; }
    .og-carte.est-inactif .og-initiale { background: #f3f4f6; color: #9ca3af; }
    .og-nom { display: block; color: var(--hali-encre); font-size: 1.05rem; font-weight: 750; line-height: 1.25; }
    .og-sous { display: block; color: var(--hali-discret); font-size: .8rem; }
    .og-code { padding: 1px 7px; border-radius: 6px; background: #f3f4f6; font-family: "SF Mono", Consolas, monospace; font-size: .72rem; letter-spacing: .04em; }
    .og-chiffres { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .og-chiffres > * { padding: 8px; border-radius: 9px; background: #f9fafb; color: inherit; text-align: center; text-decoration: none; }
    .og-chiffres a:hover { background: var(--hali-primaire-pale); text-decoration: none; }
    .og-chiffres b { display: block; color: var(--hali-encre); font-size: 1rem; font-variant-numeric: tabular-nums; }
    .og-chiffres span { color: var(--hali-discret); font-size: .72rem; }
    .og-contact { display: grid; gap: 3px; font-size: .84rem; }
    .og-contact i { width: 16px; color: #9ca3af; text-align: center; }
    .og-pied { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding-top: 12px; border-top: 1px solid #f3f4f6; }
    .og-pied form { margin: 0; }
    .og-actions { display: flex; gap: 4px; }
    .og-bouton { display: inline-grid; place-items: center; width: 34px; height: 34px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; }
    .og-bouton:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); }
    .og-bouton.est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); background: var(--hali-danger-pale); }
    .og-outils { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 14px; }
    .og-outils input { flex: 1 1 260px; min-height: 40px; }
    .og-modal .modal-content { border: 0; border-radius: 16px; }
    .og-modal .modal-body { display: grid; gap: 14px; padding: 6px 24px 16px; }
    .og-modal .modal-header, .og-modal .modal-footer { padding: 18px 24px 8px; border: 0; }
    .og-modal .modal-footer { padding: 8px 24px 20px; }
    .og-modal label { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .og-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .og-trois { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
    .og-types { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .og-type { position: relative; margin: 0; cursor: pointer; }
    .og-type input { position: absolute; opacity: 0; }
    .og-type span { display: grid; gap: 4px; place-items: center; height: 100%; padding: 10px 6px; border: 1.5px solid var(--hali-bordure); border-radius: 10px; font-size: .8rem; font-weight: 650; text-align: center; }
    .og-type i { color: #9ca3af; font-size: 1.1rem; }
    .og-type input:checked + span { border-color: var(--hali-primaire); background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); }
    .og-type input:checked + span i { color: var(--hali-primaire); }
    .og-type input:focus-visible + span { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
    .og-suffixe { position: relative; }
    .og-suffixe input { padding-right: 34px; }
    .og-suffixe span { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: var(--hali-discret); font-weight: 600; }
    @media (max-width: 767.98px) { .og-deux, .og-trois { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Organismes payeurs</h1>
            <p>Compagnies d'assurance, mutuelles et organismes publics qui prennent en charge une part des soins.</p>
        </div>
        @can('insurance_company.create')
            <div class="hl-entete-actions"><button type="button" class="hl-bouton hl-bouton-plein js-ajouter"><i class="fa fa-plus" aria-hidden="true"></i> Nouvel organisme</button></div>
        @endcan
    </header>

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Organismes actifs</span><span class="hl-kpi-valeur">{{ $actifs->count() }}</span>@if($organismes->count() > $actifs->count())<span class="hl-kpi-detail">{{ $organismes->count() - $actifs->count() }} désactivé(s)</span>@endif</div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Patients couverts</span><span class="hl-kpi-valeur">{{ $totalAssures }}</span><span class="hl-kpi-detail">couvertures en cours</span></div>
        @if($voirCreances)
            <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Reste dû par les organismes</span><span class="hl-kpi-valeur">{{ $gnf($totalDu) }} <small>GNF</small></span></div>
        @endif
    </div>

    @if($organismes->isEmpty())
        <section class="hl-bloc"><div class="hl-vide"><i class="fas fa-building" aria-hidden="true"></i>Aucun organisme payeur. Ajoutez la première compagnie d'assurance avec laquelle la clinique travaille.</div></section>
    @else
        <div class="og-outils">
            <input type="search" id="ogRecherche" class="form-control" placeholder="Rechercher un organisme, un code…" aria-label="Rechercher un organisme">
        </div>
        <div class="og-grille">
            @foreach($organismes as $o)
                @php
                    $inactif = $o->status !== 'active';
                    $du = (float) ($creances[$o->id] ?? 0);
                    $finContrat = $o->contract_end_date;
                    $jours = $finContrat ? (int) floor(today()->diffInDays($finContrat, false)) : null;
                    $donnees = $o->only(['id', 'type', 'name', 'code', 'contact_person', 'phone', 'email', 'address', 'default_coverage_percentage', 'status', 'notes'])
                        + ['contract_start_date' => $o->contract_start_date?->toDateString(), 'contract_end_date' => $o->contract_end_date?->toDateString()];
                @endphp
                <article class="og-carte {{ $inactif ? 'est-inactif' : '' }}" data-recherche="{{ mb_strtolower($o->name . ' ' . $o->code . ' ' . $o->contact_person) }}">
                    <div class="og-haut">
                        <span class="og-initiale" aria-hidden="true">{{ mb_strtoupper(mb_substr($o->name, 0, 1)) }}</span>
                        <div style="min-width:0; flex:1">
                            <span class="og-nom">{{ $o->name }}</span>
                            <span class="og-sous">{{ $libellesTypes[$o->type instanceof \BackedEnum ? $o->type->value : (string) $o->type] ?? 'Compagnie d\'assurance' }} · <span class="og-code">{{ $o->code }}</span></span>
                        </div>
                        @if($inactif)<span class="hl-statut hl-s-neutre">Désactivé</span>@else<span class="hl-statut hl-s-succes">Actif</span>@endif
                    </div>

                    <div class="og-chiffres">
                        <div><b>{{ (int) ($assures[$o->id] ?? 0) }}</b><span>patients</span></div>
                        <div><b>{{ rtrim(rtrim(number_format((float) $o->default_coverage_percentage, 2, ',', ''), '0'), ',') }} %</b><span>par défaut</span></div>
                        @if($voirCreances)
                            <a href="{{ route('assurance.creances.show', $o) }}" title="Voir les créances"><b style="{{ $du > 0 ? 'color:var(--hali-alerte)' : '' }}">{{ $du > 0 ? $gnf($du) : '—' }}</b><span>reste dû</span></a>
                        @else
                            <div><b>{{ (int) ($contrats[$o->id] ?? 0) }}</b><span>contrats</span></div>
                        @endif
                    </div>

                    <div class="og-contact">
                        @if($o->contact_person)<span><i class="fas fa-user" aria-hidden="true"></i> {{ $o->contact_person }}</span>@endif
                        @if($o->phone)<span><i class="fas fa-phone" aria-hidden="true"></i> <a href="tel:{{ preg_replace('/\s+/', '', $o->phone) }}">{{ $o->phone }}</a></span>@endif
                        @if($o->email)<span><i class="fas fa-envelope" aria-hidden="true"></i> <a href="mailto:{{ $o->email }}">{{ $o->email }}</a></span>@endif
                        @if(! $o->contact_person && ! $o->phone && ! $o->email)<span class="og-sous">Aucun contact renseigné.</span>@endif
                    </div>

                    <div class="og-pied">
                        <span class="og-sous" style="{{ $jours !== null && $jours < 0 ? 'color:var(--hali-danger); font-weight:700' : ($jours !== null && $jours <= 30 ? 'color:#b45309; font-weight:700' : '') }}">
                            @if($finContrat){{ $jours < 0 ? 'Convention expirée le ' : 'Convention jusqu\'au ' }}{{ $finContrat->format('d/m/Y') }}@else Sans date de fin @endif
                        </span>
                        <div class="og-actions">
                            @can('insurance_company.edit')
                                <button type="button" class="og-bouton js-modifier" data-organisme="{{ json_encode($donnees) }}" title="Modifier" aria-label="Modifier {{ $o->name }}"><i class="fa fa-pen"></i></button>
                            @endcan
                            @can('insurance_company.delete')
                                @unless($inactif)
                                    <form method="POST" action="{{ route('insurance-companies.destroy') }}" onsubmit="return confirm('Supprimer {{ addslashes($o->name) }} ? S\'il a déjà des patients, des factures ou des réclamations, il sera seulement désactivé.');">
                                        @csrf @method('DELETE')<input type="hidden" name="id" value="{{ $o->id }}">
                                        <button type="submit" class="og-bouton est-risque" title="Supprimer ou désactiver" aria-label="Supprimer {{ $o->name }}"><i class="fa fa-trash"></i></button>
                                    </form>
                                @endunless
                            @endcan
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="hl-vide" id="ogAucun" hidden>Aucun organisme ne correspond.</div>
    @endif

    {{-- ================= Fenêtre unique : ajout et modification ================= --}}
    @canany(['insurance_company.create', 'insurance_company.edit'])
    <div class="modal fade og-modal" id="ogModal" tabindex="-1" aria-hidden="true" aria-labelledby="ogTitre">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" method="POST" id="ogForm" data-ajout="{{ route('insurance-companies.store') }}" data-modif="{{ route('insurance-companies.update') }}" action="{{ route('insurance-companies.store') }}">
                @csrf
                <input type="hidden" name="_method" id="ogMethode" value="POST">
                <input type="hidden" name="id" id="ogId">
                <div class="modal-header"><h5 class="modal-title" id="ogTitre">Nouvel organisme</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body">
                    <div>
                        <span style="display:block; margin-bottom:5px; color:var(--hali-encre); font-size:.83rem; font-weight:650">Type</span>
                        <div class="og-types" role="radiogroup" aria-label="Type d'organisme">
                            @foreach($types as $t)
                                <label class="og-type"><input type="radio" name="type" value="{{ $t->value }}" @checked(old('type', 'assureur') === $t->value)>
                                    <span><i class="fas {{ ['assureur' => 'fa-shield-alt', 'mutuelle' => 'fa-hands-helping', 'etat' => 'fa-landmark'][$t->value] ?? 'fa-building' }}" aria-hidden="true"></i>{{ $t->libelle() }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <div class="og-trois" style="grid-template-columns: 2fr 1fr 1fr">
                        <div><label for="ogNom">Nom *</label><input id="ogNom" name="name" class="form-control" maxlength="255" required value="{{ old('name') }}"></div>
                        <div><label for="ogCode">Code *</label><input id="ogCode" name="code" class="form-control" maxlength="30" required placeholder="NSIA" style="text-transform:uppercase" value="{{ old('code') }}"></div>
                        <div><label for="ogTaux">Prise en charge</label><div class="og-suffixe"><input id="ogTaux" type="number" name="default_coverage_percentage" class="form-control" min="0" max="100" step="0.01" placeholder="80" value="{{ old('default_coverage_percentage') }}"><span>%</span></div></div>
                    </div>
                    <div class="og-trois">
                        <div><label for="ogContact">Interlocuteur</label><input id="ogContact" name="contact_person" class="form-control" value="{{ old('contact_person') }}"></div>
                        <div><label for="ogTel">Téléphone</label><input id="ogTel" name="phone" type="tel" class="form-control" value="{{ old('phone') }}"></div>
                        <div><label for="ogEmail">E-mail</label><input id="ogEmail" name="email" type="email" class="form-control" value="{{ old('email') }}"></div>
                    </div>
                    <div class="og-trois">
                        <div><label for="ogDebut">Convention du</label><input id="ogDebut" name="contract_start_date" type="date" class="form-control" value="{{ old('contract_start_date') }}"></div>
                        <div><label for="ogFin">au</label><input id="ogFin" name="contract_end_date" type="date" class="form-control" value="{{ old('contract_end_date') }}"></div>
                        <div id="ogStatutBloc" hidden><label for="ogStatut">Statut</label>
                            <select id="ogStatut" name="status" class="form-control"><option value="active">Actif</option><option value="inactive">Désactivé (plus proposé)</option></select></div>
                    </div>
                    <div><label for="ogAdresse">Adresse</label><input id="ogAdresse" name="address" class="form-control" maxlength="500" value="{{ old('address') }}"></div>
                    <div><label for="ogNotes">Notes</label><textarea id="ogNotes" name="notes" class="form-control" rows="2" maxlength="2000" placeholder="Délais de paiement, pièces exigées, interlocuteur au service des sinistres…">{{ old('notes') }}</textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button><button type="submit" class="hl-bouton hl-bouton-plein" id="ogValider">Enregistrer</button></div>
            </form>
        </div>
    </div>
    @endcanany
</div></div>
@endsection

@section('script')
<script>
(function () {
    var modal = document.getElementById('ogModal'), form = document.getElementById('ogForm');
    function ouvrir(o) {
        if (!form) return;
        var modif = !!o;
        form.action = modif ? form.dataset.modif : form.dataset.ajout;
        document.getElementById('ogMethode').value = modif ? 'PUT' : 'POST';
        document.getElementById('ogTitre').textContent = modif ? 'Modifier ' + o.name : 'Nouvel organisme';
        document.getElementById('ogStatutBloc').hidden = !modif;
        o = o || { type: 'assureur', status: 'active' };
        document.getElementById('ogId').value = o.id || '';
        var champs = { name: 'ogNom', code: 'ogCode', default_coverage_percentage: 'ogTaux', contact_person: 'ogContact', phone: 'ogTel', email: 'ogEmail',
                       contract_start_date: 'ogDebut', contract_end_date: 'ogFin', address: 'ogAdresse', notes: 'ogNotes', status: 'ogStatut' };
        Object.keys(champs).forEach(function (k) { var el = document.getElementById(champs[k]); if (el) el.value = o[k] == null ? (k === 'status' ? 'active' : '') : o[k]; });
        var radio = form.querySelector('input[name="type"][value="' + (o.type || 'assureur') + '"]');
        if (radio) radio.checked = true;
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
    document.querySelectorAll('.js-ajouter').forEach(function (b) { b.addEventListener('click', function () { ouvrir(null); }); });
    document.querySelectorAll('.js-modifier').forEach(function (b) { b.addEventListener('click', function () { ouvrir(JSON.parse(b.dataset.organisme)); }); });
    if (form) form.addEventListener('submit', function () { document.getElementById('ogValider').disabled = true; });

    // Retour d'erreur de validation : on rouvre la fenêtre, champs déjà remplis par old().
    @if($errors->any())
        if (form) {
            @if(old('id')) form.action = form.dataset.modif; document.getElementById('ogMethode').value = 'PUT'; document.getElementById('ogId').value = @json(old('id')); document.getElementById('ogStatutBloc').hidden = false; @endif
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }
    @endif

    var champ = document.getElementById('ogRecherche');
    if (champ) champ.addEventListener('input', function () {
        var t = champ.value.trim().toLowerCase(), n = 0;
        document.querySelectorAll('.og-carte').forEach(function (c) { var ok = !t || c.dataset.recherche.indexOf(t) !== -1; c.hidden = !ok; if (ok) n++; });
        document.getElementById('ogAucun').hidden = n > 0;
    });
})();
</script>
@endsection
