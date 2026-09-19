@extends('layouts.backend')

@php
    $initiales = fn ($e) => mb_strtoupper(mb_substr((string) $e->first_name, 0, 1) . mb_substr((string) $e->last_name, 0, 1)) ?: '?';
    $parType = $employees->where('is_active', true)->countBy('type');
@endphp

@section('style')
<style>
    .em-ligne { display: grid; grid-template-columns: minmax(220px, 1.5fr) minmax(140px, 1fr) minmax(160px, 1fr) minmax(170px, 1fr) auto; align-items: center; gap: 14px; padding: 12px 18px; border-top: 1px solid #f3f4f6; }
    .em-ligne:first-of-type { border-top: 0; }
    .em-ligne:hover { background: var(--hali-primaire-pale); }
    .em-ligne.est-inactif { opacity: .55; }
    .em-qui { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .em-qui .hl-avatar { width: 40px; height: 40px; flex-basis: 40px; border-radius: 11px; }
    .em-qui .hl-avatar.est-medecin { background: var(--hali-primaire); color: #fff; }
    .em-nom { display: block; color: var(--hali-encre); font-weight: 650; text-decoration: none; }
    .em-sous { display: block; color: var(--hali-discret); font-size: .78rem; }
    .em-compte { font-size: .84rem; }
    .em-actions { display: flex; justify-content: flex-end; gap: 4px; }
    .em-icone { display: inline-grid; place-items: center; width: 34px; height: 34px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; text-decoration: none; }
    .em-icone:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); text-decoration: none; }
    .em-icone.est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); background: var(--hali-danger-pale); }
    .em-outils { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .em-outils .em-recherche { position: relative; flex: 1 1 240px; }
    .em-outils .em-recherche i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .em-outils .em-recherche input { width: 100%; min-height: 40px; padding-left: 40px; }
    @media (max-width: 991.98px) { .em-ligne { grid-template-columns: 1fr 1fr; } .em-qui { grid-column: 1 / -1; } .em-actions { grid-column: 1 / -1; justify-content: flex-start; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Personnel</h1>
            <p>Médecins, infirmiers, accueil, caisse… Les médecins actifs apparaissent à l'accueil et dans la prise de rendez-vous.</p>
        </div>
        <div class="hl-entete-actions">
            @can('users.create')
                @if(Route::has('users.create'))<a href="{{ route('users.create') }}" class="hl-bouton"><i class="fas fa-user-lock" aria-hidden="true"></i> Créer un compte de connexion</a>@endif
            @endcan
            @can('employee.create')
                <a href="{{ route('employee.create') }}" class="hl-bouton hl-bouton-plein"><i class="fa fa-plus" aria-hidden="true"></i> Nouvel employé</a>
            @endcan
        </div>
    </header>

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Personnel actif</span><span class="hl-kpi-valeur">{{ $employees->where('is_active', true)->count() }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Médecins</span><span class="hl-kpi-valeur">{{ $parType['Doctor'] ?? 0 }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Sans compte de connexion</span><span class="hl-kpi-valeur">{{ $employees->whereNull('user_id')->count() }}</span><span class="hl-kpi-detail">ne peuvent pas se connecter</span></div>
    </div>

    <section class="hl-bloc">
        <div class="em-outils">
            <label class="em-recherche mb-0">
                <span class="sr-only visually-hidden">Rechercher</span>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="emRecherche" class="form-control" placeholder="Nom, spécialité, département…" autocomplete="off">
            </label>
            <div class="hl-puces" role="group" aria-label="Fonction">
                <button type="button" class="hl-puce est-actif" data-type="">Tous</button>
                @foreach(\App\Models\Employee::TYPES as $v => $l)
                    @if($employees->where('type', $v)->isNotEmpty())<button type="button" class="hl-puce" data-type="{{ $v }}">{{ $l }} <b>{{ $employees->where('type', $v)->count() }}</b></button>@endif
                @endforeach
            </div>
        </div>

        @if($employees->isEmpty())
            <div class="hl-vide"><i class="fas fa-user-md" aria-hidden="true"></i>Aucun employé.</div>
        @else
            <div id="emListe">
                @foreach($employees as $employee)
                    <div class="em-ligne {{ $employee->is_active ? '' : 'est-inactif' }}" data-type="{{ $employee->type }}"
                         data-recherche="{{ mb_strtolower($employee->full_name . ' ' . $employee->speciality . ' ' . $employee->department?->name) }}">
                        <div class="em-qui">
                            <span class="hl-avatar {{ $employee->type === 'Doctor' ? 'est-medecin' : '' }}" aria-hidden="true">{{ $initiales($employee) }}</span>
                            <div style="min-width:0">
                                <a href="{{ route('employee.show', $employee->id) }}" class="em-nom">{{ $employee->nom_affiche }}</a>
                                <span class="em-sous">{{ $employee->speciality ?: $employee->type_libelle }}</span>
                            </div>
                        </div>
                        <div><span class="hl-statut {{ $employee->type === 'Doctor' ? 'hl-s-info' : 'hl-s-neutre' }}">{{ $employee->type_libelle }}</span></div>
                        <div style="font-size:.86rem">{{ $employee->department?->name ? ucfirst(mb_strtolower($employee->department->name)) : '—' }}</div>
                        <div class="em-compte">
                            @if($employee->user)
                                <span style="color:var(--hali-succes); font-weight:600"><i class="fas fa-check-circle" aria-hidden="true"></i> Compte actif</span>
                                <span class="em-sous">{{ $employee->user->phone ?: $employee->user->email }}</span>
                            @else
                                <span class="em-sous"><i class="fas fa-user-slash" aria-hidden="true"></i> Sans compte</span>
                            @endif
                            @unless($employee->is_active)<span class="em-sous" style="color:var(--hali-danger)">Fiche désactivée</span>@endunless
                        </div>
                        <div class="em-actions">
                            @if($employee->type === 'Doctor')
                                @can('employee.edit')<a href="{{ route('employees.motifs', $employee) }}" class="em-icone" title="Motifs pratiqués" aria-label="Motifs pratiqués par {{ $employee->nom_affiche }}"><i class="fas fa-calendar-plus"></i></a>@endcan
                            @endif
                            @can('employee.edit')<a href="{{ route('employee.edit', $employee->id) }}" class="em-icone" title="Modifier" aria-label="Modifier {{ $employee->nom_affiche }}"><i class="fa fa-pen"></i></a>@endcan
                            @can('employee.delete')
                                @unless($employee->user_id)
                                    <button type="button" class="em-icone est-risque delete-button" title="Supprimer" aria-label="Supprimer {{ $employee->nom_affiche }}" data-id="{{ $employee->id }}" data-name="{{ $employee->nom_affiche }}"><i class="fa fa-trash"></i></button>
                                @endunless
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="hl-vide" id="emAucun" hidden>Personne ne correspond.</div>
        @endif
    </section>

    <div class="modal fade" id="deleteRowModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" id="deleteEmployeForm" action="#" method="POST" style="border:0; border-radius:14px">
                @csrf @method('DELETE')
                <input type="hidden" id="delete_id" name="id">
                <div class="modal-header" style="border:0"><h5 class="modal-title">Supprimer la fiche</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                <div class="modal-body"><p class="mb-0" id="emSupprimerTexte"></p></div>
                <div class="modal-footer" style="border:0">
                    <button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="hl-bouton" style="background:var(--hali-danger); border-color:var(--hali-danger); color:#fff">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div></div>
@endsection

@section('script')
<script>
(function () {
    var type = '', champ = document.getElementById('emRecherche');
    function appliquer() {
        var t = champ.value.trim().toLowerCase(), n = 0;
        document.querySelectorAll('.em-ligne').forEach(function (l) {
            var ok = (!type || l.dataset.type === type) && (!t || l.dataset.recherche.indexOf(t) !== -1);
            l.hidden = !ok; if (ok) n++;
        });
        var a = document.getElementById('emAucun'); if (a) a.hidden = n > 0;
    }
    champ.addEventListener('input', appliquer);
    document.querySelectorAll('[data-type].hl-puce').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('[data-type].hl-puce').forEach(function (x) { x.classList.remove('est-actif'); });
            b.classList.add('est-actif'); type = b.dataset.type; appliquer();
        });
    });
    document.querySelectorAll('.delete-button').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('delete_id').value = b.dataset.id;
            document.getElementById('emSupprimerTexte').textContent = 'Supprimer la fiche de ' + b.dataset.name + ' ? Si ce soignant a déjà reçu des patients, désactivez plutôt sa fiche.';
            document.getElementById('deleteEmployeForm').action = '/employee/' + b.dataset.id;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteRowModal')).show();
        });
    });
})();
</script>
@endsection
