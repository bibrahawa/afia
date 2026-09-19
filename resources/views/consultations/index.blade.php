@extends('layouts.backend')

@php
    $gnf = fn ($v) => number_format((float) $v, 0, ',', ' ');
    $initiales = fn ($p) => $p ? (mb_strtoupper(mb_substr((string) $p->first_name, 0, 1) . mb_substr((string) $p->last_name, 0, 1)) ?: 'P') : '?';
    $periodes = ['' => 'Toutes', 'jour' => "Aujourd'hui", 'semaine' => 'Cette semaine', 'mois' => 'Ce mois'];
    $filtreActif = ! empty($filtres['q']) || ! empty($filtres['statut']) || ! empty($filtres['periode']) || ! empty($filtres['medecin_id']);
@endphp

@section('style')
<style>
    .cl-filtres { display: grid; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .cl-ligne-filtres { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
    .cl-recherche { position: relative; flex: 1 1 280px; }
    .cl-recherche i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .cl-recherche input { width: 100%; min-height: 42px; padding-left: 40px; }
    .cl-ligne-filtres select { width: auto; min-width: 180px; min-height: 42px; }

    .cl-entetes, .cl-ligne { display: grid; align-items: center; gap: 14px; grid-template-columns: 120px minmax(200px, 1.4fr) minmax(160px, 1.2fr) minmax(130px, .9fr) minmax(110px, .7fr) 120px auto; padding: 12px 18px; }
    .cl-entetes { padding-top: 10px; padding-bottom: 10px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; }
    .cl-ligne { position: relative; border-top: 1px solid #f3f4f6; }
    .cl-ligne:first-of-type { border-top: 0; }
    .cl-ligne:hover { background: var(--hali-primaire-pale); }
    .cl-date { color: var(--hali-encre); font-weight: 600; font-variant-numeric: tabular-nums; }
    .cl-sous { display: block; color: var(--hali-discret); font-size: .8rem; font-weight: 500; }
    .cl-patient { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .cl-patient .hl-avatar { width: 34px; height: 34px; flex-basis: 34px; font-size: .75rem; border-radius: 9px; }
    .cl-patient a { color: var(--hali-encre); font-weight: 650; text-decoration: none; }
    .cl-patient a::after { content: ""; position: absolute; inset: 0; }
    .cl-texte { overflow: hidden; color: var(--hali-texte); font-size: .88rem; text-overflow: ellipsis; white-space: nowrap; }
    .cl-montant { color: var(--hali-encre); font-weight: 600; font-variant-numeric: tabular-nums; text-align: right; white-space: nowrap; }
    .cl-actions { position: relative; z-index: 2; display: flex; justify-content: flex-end; gap: 6px; }
    .cl-icone { display: inline-grid; place-items: center; width: 34px; height: 34px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; text-decoration: none; }
    .cl-icone:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); text-decoration: none; }
    .cl-plus { position: relative; }
    .cl-plus > summary { list-style: none; }
    .cl-plus > summary::-webkit-details-marker { display: none; }
    .cl-menu { position: absolute; right: 0; top: calc(100% + 6px); z-index: 30; min-width: 200px; padding: 6px; border: 1px solid var(--hali-bordure); border-radius: 10px; background: #fff; box-shadow: var(--hali-ombre-forte); }
    .cl-menu a, .cl-menu button { display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px; border: 0; border-radius: 7px; background: none; color: var(--hali-texte); font-size: .86rem; text-align: left; text-decoration: none; }
    .cl-menu a:hover, .cl-menu button:hover { background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); }
    .cl-menu i { width: 16px; color: #9ca3af; text-align: center; }
    .cl-menu .est-risque, .cl-menu .est-risque i { color: var(--hali-danger); }
    .cl-menu .est-risque:hover { background: var(--hali-danger-pale); }
    .cl-menu form { margin: 0; }
    .cl-menu hr { margin: 4px 0; }
    .cl-etiquette { display: none; }
    .cl-pagination { padding: 14px 18px; border-top: 1px solid var(--hali-bordure); }
    .cl-pagination nav { display: flex; justify-content: center; }

    @media (max-width: 1199.98px) {
        .cl-entetes { display: none; }
        .cl-ligne { grid-template-columns: 1fr 1fr; gap: 8px 16px; }
        .cl-patient { grid-column: 1 / -1; order: -1; }
        .cl-actions { grid-column: 1 / -1; justify-content: flex-start; }
        .cl-montant { text-align: left; }
        .cl-etiquette { display: block; color: var(--hali-discret); font-size: .75rem; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div>
            <h1>Consultations</h1>
            <p>
                {{ $consultations->total() }} consultation{{ $consultations->total() > 1 ? 's' : '' }}{{ $filtreActif ? ' correspondant aux filtres' : '' }}
                @if($enCours > 0) · <strong style="color: var(--hali-alerte)">{{ $enCours }} encore en cours</strong>@endif
            </p>
        </div>
        <div class="hl-entete-actions">
            @can('parcours.accueil')
                <a href="{{ route('parcours.accueil.index') }}" class="hl-bouton"><i class="fas fa-door-open" aria-hidden="true"></i> Accueil du jour</a>
            @endcan
            @if(auth()->user()?->employee?->type === 'Doctor' && auth()->user()->can('parcours.file'))
                <a href="{{ route('parcours.consultation.nouvelle') }}" class="hl-bouton hl-bouton-plein"><i class="fa fa-plus" aria-hidden="true"></i> Recevoir un patient</a>
            @endif
        </div>
    </header>

    <section class="hl-bloc">
        <form method="GET" action="{{ route('consultation.index') }}" class="cl-filtres" id="clFiltres">
            <div class="cl-ligne-filtres">
                <label class="cl-recherche mb-0">
                    <span class="sr-only visually-hidden">Rechercher</span>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" name="q" class="form-control" value="{{ $filtres['q'] ?? '' }}" placeholder="Patient, identifiant santé, motif ou diagnostic… puis Entrée">
                </label>
                <select name="statut" class="form-control js-auto" aria-label="Statut">
                    <option value="">Tous les statuts</option>
                    <option value="en_cours" @selected(($filtres['statut'] ?? '') === 'en_cours')>En cours</option>
                    <option value="terminee" @selected(($filtres['statut'] ?? '') === 'terminee')>Terminées</option>
                    <option value="annulee" @selected(($filtres['statut'] ?? '') === 'annulee')>Patient reparti</option>
                </select>
                <select name="medecin_id" class="form-control js-auto" aria-label="Médecin">
                    <option value="">Tous les médecins</option>
                    @foreach($medecins as $m)
                        <option value="{{ $m->id }}" @selected((int) ($filtres['medecin_id'] ?? 0) === $m->id)>{{ $m->nom_affiche }}</option>
                    @endforeach
                </select>
            </div>
            <div class="hl-puces" role="group" aria-label="Période">
                @foreach($periodes as $valeur => $libelle)
                    <a class="hl-puce {{ ($filtres['periode'] ?? '') === $valeur ? 'est-actif' : '' }}"
                       href="{{ request()->fullUrlWithQuery(['periode' => $valeur ?: null, 'page' => null]) }}">{{ $libelle }}</a>
                @endforeach
                @if($filtreActif)
                    <a href="{{ route('consultation.index') }}" style="margin-left:auto; align-self:center; font-size:.85rem; font-weight:600">Effacer les filtres</a>
                @endif
            </div>
        </form>

        @if($consultations->isEmpty())
            <div class="hl-vide">
                <i class="fas fa-stethoscope" aria-hidden="true"></i>
                {{ $filtreActif ? 'Aucune consultation ne correspond à ces filtres.' : 'Aucune consultation enregistrée.' }}
            </div>
        @else
            <div class="cl-entetes" aria-hidden="true">
                <span>Date</span><span>Patient</span><span>Motif</span><span>Médecin</span><span>Statut</span><span style="text-align:right">Montant</span><span></span>
            </div>

            @foreach($consultations as $consultation)
                @php
                    $patient = $consultation->patient;
                    $enCoursLigne = $consultation->statut === \App\Models\Consultation::EN_COURS;
                    $transaction = $consultation->transaction;
                    $regle = $transaction && in_array($transaction->status, ['paid', 'approved'], true);
                @endphp
                <div class="cl-ligne">
                    <div>
                        <span class="cl-date">{{ $consultation->created_at->format('d/m/Y') }}</span>
                        <span class="cl-sous">{{ $consultation->created_at->format('H:i') }}</span>
                    </div>

                    <div class="cl-patient">
                        <span class="hl-avatar" aria-hidden="true">{{ $initiales($patient) }}</span>
                        <div style="min-width:0">
                            @can('consultation.view')
                                <a href="{{ route('consultation.show', $consultation->id) }}">{{ $patient?->full_name ?? 'Patient supprimé' }}</a>
                            @else
                                <strong>{{ $patient?->full_name ?? 'Patient supprimé' }}</strong>
                            @endcan
                            <span class="cl-sous">{{ $consultation->department?->name ? ucfirst(mb_strtolower($consultation->department->name)) : '—' }}</span>
                        </div>
                    </div>

                    <div style="min-width:0">
                        <span class="cl-etiquette">Motif</span>
                        <span class="cl-texte" title="{{ $consultation->motif }}">{{ $consultation->motif ?: '—' }}</span>
                        @if($consultation->diagnostic && $consultation->diagnostic !== 'N/A')
                            <span class="cl-sous cl-texte" title="{{ $consultation->diagnostic }}">{{ $consultation->diagnostic }}</span>
                        @endif
                    </div>

                    <div><span class="cl-etiquette">Médecin</span><span class="cl-texte">{{ $consultation->medecin?->nom_affiche ?? '—' }}</span></div>

                    <div>
                        <span class="cl-etiquette">Statut</span>
                        @if($enCoursLigne)
                            <span class="hl-statut hl-s-info">En cours</span>
                        @elseif($consultation->statut === \App\Models\Consultation::ANNULEE)
                            <span class="hl-statut hl-s-neutre" title="Le patient est reparti sans consulter">Patient reparti</span>
                        @else
                            <span class="hl-statut hl-s-succes">Terminée</span>
                        @endif
                    </div>

                    <div class="cl-montant">
                        <span class="cl-etiquette">Montant</span>
                        @if($transaction)
                            {{ $gnf($transaction->total) }}
                            <span class="cl-sous">{{ $regle ? 'réglé' : 'à encaisser' }}</span>
                        @else
                            <span class="cl-sous">—</span>
                        @endif
                    </div>

                    <div class="cl-actions">
                        @if($enCoursLigne && Route::has('parcours.consultation.show'))
                            @can('parcours.file')
                                <a href="{{ route('parcours.consultation.show', $consultation) }}" class="hl-bouton hl-bouton-plein" style="min-height:34px">Reprendre</a>
                            @endcan
                        @endif
                        @canany(['consultation.edit', 'consultation.delete'])
                            <details class="cl-plus">
                                <summary class="cl-icone" title="Autres actions" aria-label="Autres actions"><i class="fas fa-ellipsis-h"></i></summary>
                                <div class="cl-menu">
                                    @can('consultation.view')
                                        <a href="{{ route('consultation.show', $consultation->id) }}"><i class="fas fa-file-medical"></i> Fiche complète</a>
                                    @endcan
                                    @can('consultation.edit')
                                        <a href="{{ route('consultation.edit', $consultation->id) }}"><i class="fas fa-pen"></i> Modifier</a>
                                    @endcan
                                    @can('consultation.delete')
                                        <hr>
                                        <form action="{{ route('consultation.destroy', $consultation->id) }}" method="POST"
                                              onsubmit="return confirm('Supprimer cette consultation ? Une consultation déjà encaissée ne peut pas être supprimée.');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="est-risque"><i class="fas fa-trash"></i> Supprimer</button>
                                        </form>
                                    @endcan
                                </div>
                            </details>
                        @endcanany
                    </div>
                </div>
            @endforeach

            @if($consultations->hasPages())
                <div class="cl-pagination">{{ $consultations->links() }}</div>
            @endif
        @endif
    </section>
</div></div>

<script>
(function () {
    document.querySelectorAll('#clFiltres .js-auto').forEach(function (s) {
        s.addEventListener('change', function () { s.form.submit(); });
    });
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details.cl-plus[open]').forEach(function (m) { if (!m.contains(e.target)) m.removeAttribute('open'); });
    });
})();
</script>
@endsection
