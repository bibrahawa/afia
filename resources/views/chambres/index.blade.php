@extends('layouts.backend')

@php
    $gnf = fn ($m) => number_format((float) $m, 0, ',', ' ');
    // L'état réel vient des séjours en cours, pas seulement du champ « statut ».
    $etat = function ($c) {
        if ($c->statut === 'En maintenance') return 'maintenance';
        return $c->hospitalisations->isNotEmpty() || $c->statut === 'Occupée' ? 'occupee' : 'libre';
    };
    $compte = $chambres->groupBy($etat)->map->count();
    $types = $chambres->pluck('type')->filter()->unique()->sort()->values();
@endphp

@section('style')
<style>
    .ch-outils { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .ch-grille { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 12px; padding: 18px; }
    .ch-carte { position: relative; display: grid; gap: 8px; padding: 14px 14px 12px; border: 1px solid var(--hali-bordure); border-top: 4px solid var(--ch-couleur); border-radius: 12px; background: #fff; }
    .ch-carte.est-libre { --ch-couleur: var(--hali-succes); }
    .ch-carte.est-occupee { --ch-couleur: var(--hali-primaire); background: #fbfefe; }
    .ch-carte.est-maintenance { --ch-couleur: #9ca3af; background: #fafafa; }
    .ch-haut { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
    .ch-numero { color: var(--hali-encre); font-size: 1.35rem; font-weight: 800; line-height: 1; letter-spacing: -.01em; }
    .ch-type { display: block; margin-top: 4px; color: var(--hali-discret); font-size: .78rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
    .ch-prix { color: var(--hali-texte); font-size: .84rem; }
    .ch-prix b { color: var(--hali-encre); font-variant-numeric: tabular-nums; }
    .ch-occupant { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px; background: var(--hali-primaire-pale); font-size: .84rem; }
    .ch-occupant strong { display: block; color: var(--hali-encre); }
    .ch-occupant span { color: var(--hali-discret); font-size: .76rem; }
    .ch-libre { padding: 8px 10px; border-radius: 8px; background: #f0fdf4; color: var(--hali-succes); font-size: .84rem; font-weight: 600; }
    .ch-maintenance { padding: 8px 10px; border-radius: 8px; background: #f3f4f6; color: #6b7280; font-size: .84rem; font-weight: 600; }
    .ch-actions { display: flex; gap: 6px; margin-top: 2px; }
    .ch-actions form { margin: 0; }
    .ch-icone { display: inline-grid; place-items: center; width: 32px; height: 32px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; }
    .ch-icone:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); }
    .ch-icone.est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); background: var(--hali-danger-pale); }
    .ch-legende { display: flex; flex-wrap: wrap; gap: 14px; margin-left: auto; color: var(--hali-discret); font-size: .8rem; }
    .ch-legende i { display: inline-block; width: 10px; height: 10px; margin-right: 5px; border-radius: 3px; vertical-align: -1px; }

    .ch-modal .modal-content { border: 0; border-radius: 14px; }
    .ch-modal .modal-header { padding: 18px 22px 8px; border: 0; }
    .ch-modal .modal-title { color: var(--hali-encre); font-weight: 700; }
    .ch-modal .modal-body { display: grid; gap: 14px; padding: 8px 22px 16px; }
    .ch-modal label { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .ch-modal .modal-footer { padding: 12px 22px 18px; border: 0; }
    .ch-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ch-choix { display: flex; flex-wrap: wrap; gap: 8px; }
    .ch-choix label { margin: 0; font-weight: 500; }
    .ch-choix input { position: absolute; opacity: 0; pointer-events: none; }
    .ch-choix span { display: inline-flex; align-items: center; min-height: 36px; padding: 0 13px; border: 1px solid var(--hali-bordure); border-radius: 999px; background: #fff; font-size: .85rem; font-weight: 600; cursor: pointer; }
    .ch-choix input:checked + span { background: var(--hali-primaire-pale); border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); }
    .ch-choix input:focus-visible + span { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div>
            <h1>Plan des chambres</h1>
            <p>Qui occupe quelle chambre, et ce qui reste disponible.</p>
        </div>
        <div class="hl-entete-actions">
            @can('hospitalisation.view')
                <a href="{{ route('hospitalisations.index') }}" class="hl-bouton"><i class="fas fa-procedures" aria-hidden="true"></i> Hospitalisations</a>
            @endcan
            @can('chambre.create')
                <button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addRowModal"><i class="fa fa-plus" aria-hidden="true"></i> Nouvelle chambre</button>
            @endcan
        </div>
    </header>

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Chambres</span><span class="hl-kpi-valeur">{{ $chambres->count() }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Libres</span><span class="hl-kpi-valeur" style="color:var(--hali-succes)">{{ $compte['libre'] ?? 0 }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Occupées</span><span class="hl-kpi-valeur">{{ $compte['occupee'] ?? 0 }}</span>
            @if($chambres->count())<span class="hl-kpi-detail">taux d'occupation {{ round(($compte['occupee'] ?? 0) / max(1, $chambres->count() - ($compte['maintenance'] ?? 0)) * 100) }} %</span>@endif</div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">En maintenance</span><span class="hl-kpi-valeur">{{ $compte['maintenance'] ?? 0 }}</span></div>
    </div>

    <section class="hl-bloc">
        <div class="ch-outils">
            <div class="hl-puces" role="group" aria-label="Filtrer">
                <button type="button" class="hl-puce est-actif" data-filtre="">Toutes</button>
                <button type="button" class="hl-puce" data-filtre="libre">Libres</button>
                <button type="button" class="hl-puce" data-filtre="occupee">Occupées</button>
                @foreach($types as $t)<button type="button" class="hl-puce" data-type="{{ $t }}">{{ $t }}</button>@endforeach
            </div>
            <span class="ch-legende" aria-hidden="true">
                <span><i style="background:var(--hali-succes)"></i>Libre</span>
                <span><i style="background:var(--hali-primaire)"></i>Occupée</span>
                <span><i style="background:#9ca3af"></i>Maintenance</span>
            </span>
        </div>

        @if($chambres->isEmpty())
            <div class="hl-vide"><i class="fas fa-bed" aria-hidden="true"></i>Aucune chambre. Ajoutez vos chambres pour pouvoir hospitaliser.</div>
        @else
            <div class="ch-grille">
                @foreach($chambres as $chambre)
                    @php
                        $e = $etat($chambre);
                        $sejour = $chambre->hospitalisations->first();
                    @endphp
                    <article class="ch-carte est-{{ $e }}" data-etat="{{ $e }}" data-type="{{ $chambre->type }}">
                        <div class="ch-haut">
                            <div>
                                <span class="ch-numero">{{ $chambre->numero }}</span>
                                <span class="ch-type">{{ $chambre->type }}</span>
                            </div>
                            <span class="ch-prix"><b>{{ $gnf($chambre->prix_par_jour) }}</b> GNF/j</span>
                        </div>

                        @if($sejour)
                            <div class="ch-occupant">
                                <i class="fas fa-user-injured" aria-hidden="true" style="color:var(--hali-primaire)"></i>
                                <div style="min-width:0">
                                    <strong>{{ $sejour->patient?->full_name }}</strong>
                                    <span>sortie prévue le {{ $sejour->date_sortie_prevue }}</span>
                                </div>
                            </div>
                        @elseif($e === 'maintenance')
                            <div class="ch-maintenance"><i class="fas fa-tools" aria-hidden="true"></i> En maintenance</div>
                        @elseif($e === 'occupee')
                            <div class="ch-maintenance" title="Marquée occupée sans séjour en cours : vérifiez son statut">Occupée (sans séjour enregistré)</div>
                        @else
                            <div class="ch-libre"><i class="fas fa-check" aria-hidden="true"></i> Libre</div>
                        @endif

                        <div class="ch-actions">
                            @can('chambre.edit')
                                <button type="button" class="ch-icone edit-button" title="Modifier" aria-label="Modifier la chambre {{ $chambre->numero }}"
                                        data-info="{{ json_encode($chambre->only(['id', 'numero', 'type', 'prix_par_jour', 'statut'])) }}"><i class="fa fa-pen"></i></button>
                            @endcan
                            @can('chambre.delete')
                                @unless($sejour)
                                    <form action="{{ route('chambres.destroy', $chambre->id) }}" method="POST" onsubmit="return confirm('Supprimer la chambre {{ $chambre->numero }} ?')">
                                        @csrf @method('DELETE')
                                        <button class="ch-icone est-risque" title="Supprimer" aria-label="Supprimer la chambre {{ $chambre->numero }}"><i class="fa fa-trash"></i></button>
                                    </form>
                                @endunless
                            @endcan
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="hl-vide" id="chAucune" hidden>Aucune chambre dans ce filtre.</div>
        @endif
    </section>

    {{-- ============================================ Nouvelle chambre --}}
    <div class="modal fade ch-modal" id="addRowModal" tabindex="-1" aria-labelledby="chTitreAjout" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="addChambreForm" action="{{ route('chambres.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="chTitreAjout">Nouvelle chambre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="ch-deux">
                        <div><label for="addNumero">Numéro</label><input type="text" name="numero" id="addNumero" class="form-control" placeholder="101" required></div>
                        <div><label for="addPrix">Prix par jour (GNF)</label><input type="number" min="0" step="1" name="prix_par_jour" id="addPrix" class="form-control" placeholder="150000" required inputmode="numeric"></div>
                    </div>
                    <div>
                        <label>Type</label>
                        <div class="ch-choix">
                            <label><input type="radio" name="type" value="Standard" checked><span>Standard</span></label>
                            <label><input type="radio" name="type" value="VIP"><span>VIP</span></label>
                        </div>
                    </div>
                    <div>
                        <label>Statut</label>
                        <div class="ch-choix">
                            <label><input type="radio" name="statut" value="Libre" checked><span>Libre</span></label>
                            <label><input type="radio" name="statut" value="En maintenance"><span>En maintenance</span></label>
                        </div>
                        <p class="small text-muted mb-0 mt-1">« Occupée » est posé automatiquement à l'admission d'un patient.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" id="addRowButton" class="hl-bouton hl-bouton-plein">Ajouter la chambre
                        <span class="spinner-border spinner-border-sm" role="status" id="addLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============================================ Modifier une chambre --}}
    <div class="modal fade ch-modal" id="editRowModal" tabindex="-1" aria-labelledby="chTitreModif" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="editChambreForm" action="{{ route('chambre.update') }}" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="chTitreModif">Modifier la chambre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="ch-deux">
                        <div><label for="numero">Numéro</label><input type="text" name="numero" id="numero" class="form-control" required></div>
                        <div><label for="prix_par_jour">Prix par jour (GNF)</label><input type="number" min="0" step="1" name="prix_par_jour" id="prix_par_jour" class="form-control" required inputmode="numeric"></div>
                        <div><label for="type">Type</label>
                            <select name="type" id="type" class="form-control" required><option value="Standard">Standard</option><option value="VIP">VIP</option></select></div>
                        <div><label for="statut">Statut</label>
                            {{-- CORRIGÉ : ce champ n'avait pas d'identifiant, le script ne le remplissait pas —
                                 toute modification remettait la chambre « Libre », même occupée. --}}
                            <select name="statut" id="statut" class="form-control" required>
                                <option value="Libre">Libre</option><option value="Occupée">Occupée</option><option value="En maintenance">En maintenance</option>
                            </select></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="hl-bouton hl-bouton-plein" id="editRowButton">Enregistrer
                        <span class="spinner-border spinner-border-sm" role="status" id="editLoader" style="display:none"></span></button>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection

@section('script')
<script>
(function () {
    var etat = '', type = '';
    function appliquer() {
        var visibles = 0;
        document.querySelectorAll('.ch-carte').forEach(function (c) {
            var ok = (!etat || c.dataset.etat === etat) && (!type || c.dataset.type === type);
            c.hidden = !ok; if (ok) visibles++;
        });
        var a = document.getElementById('chAucune'); if (a) a.hidden = visibles > 0;
    }
    document.querySelectorAll('[data-filtre], [data-type].hl-puce').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('.ch-outils .hl-puce').forEach(function (x) { x.classList.remove('est-actif'); });
            b.classList.add('est-actif');
            etat = b.dataset.filtre || ''; type = b.dataset.type || ''; appliquer();
        });
    });

    document.querySelectorAll('.edit-button').forEach(function (b) {
        b.addEventListener('click', function () {
            var c = JSON.parse(b.dataset.info);
            ['id', 'numero', 'type', 'prix_par_jour', 'statut'].forEach(function (k) {
                var champ = document.getElementById(k === 'id' ? 'edit_id' : k);
                if (champ) champ.value = k === 'prix_par_jour' ? Math.round(parseFloat(c[k] || 0)) : c[k];
            });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editRowModal')).show();
        });
    });

    [['addChambreForm', 'addRowButton', 'addLoader'], ['editChambreForm', 'editRowButton', 'editLoader']].forEach(function (t) {
        document.getElementById(t[0]).addEventListener('submit', function () {
            document.getElementById(t[1]).disabled = true; document.getElementById(t[2]).style.display = 'inline-block';
        });
    });
})();
</script>
@endsection
