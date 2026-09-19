@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div><h1>{{ $employee->nom_affiche }}</h1><p>{{ $employee->type_libelle }} · {{ $employee->department?->name }}</p></div>
        <div class="hl-entete-actions">
            @if($employee->type === 'Doctor' && Route::has('employees.motifs'))
                <a href="{{ route('employees.motifs', $employee) }}" class="hl-bouton"><i class="fas fa-calendar-plus" aria-hidden="true"></i> Motifs pratiqués</a>
            @endif
            <a href="{{ route('employee.index') }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Personnel</a>
        </div>
    </header>

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    @if(session('mot_de_passe_provisoire'))
        @php $mp = session('mot_de_passe_provisoire'); @endphp
        <div class="ed-secret" role="alert">
            <i class="fas fa-key" aria-hidden="true"></i>
            <div>
                <strong>Le SMS n'est pas parti : transmettez ces identifiants de vive voix.</strong>
                <span>Identifiant <b>{{ $mp['telephone'] }}</b> · mot de passe provisoire <b class="ed-mdp">{{ $mp['mot_de_passe'] }}</b></span>
                <small>Ce mot de passe ne sera plus jamais affiché. La personne devra le changer à sa première connexion.</small>
            </div>
        </div>
    @endif
    @if(session('info'))<p class="hl-note hl-note-info mb-3"><i class="fas fa-paper-plane" aria-hidden="true"></i> <span>{{ session('info') }}</span></p>@endif

    <div class="ed-grille">
    <form action="{{ route('employee.update', $employee->id) }}" method="POST" class="hl-bloc" id="emForm">
        @csrf @method('PUT')
        <div class="em-form">
            @include('employees._champs', ['employee' => $employee])
            <label class="d-flex gap-2 mb-0" style="font-weight:500">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $employee->is_active)) style="margin-top:3px">
                <span>Fiche active<span class="d-block cat-aide" style="margin:0">Décocher retire ce soignant des listes (accueil, rendez-vous) sans effacer son historique.</span></span>
            </label>
        </div>
        <div class="em-pied">
            <a href="{{ route('employee.index') }}" class="hl-bouton">Annuler</a>
            <button type="submit" class="hl-bouton hl-bouton-plein" id="emValider">Enregistrer</button>
        </div>
    </form>

    {{-- ====================================== Accès à Hali (lot E1) --}}
    @php
        $compte = $employee->user;
        $suspendu = $compte && $compte->status !== null && ! (bool) $compte->status;
    @endphp
    <section class="hl-bloc ed-acces">
        <h2 class="hl-bloc-titre"><i class="fas fa-key" aria-hidden="true" style="color:var(--hali-primaire)"></i> Accès à Hali</h2>
        @if($compte)
            <div class="ed-etat">
                @if($suspendu)
                    <span class="hl-statut hl-s-danger">Suspendu</span>
                @elseif($compte->doit_changer_mot_de_passe)
                    <span class="hl-statut hl-s-alerte">Mot de passe provisoire</span>
                @else
                    <span class="hl-statut hl-s-succes">Actif</span>
                @endif
                <span class="ed-sous">{{ $compte->last_login_at ? 'Dernière connexion ' . \Illuminate\Support\Carbon::parse($compte->last_login_at)->diffForHumans() : 'Jamais connecté(e)' }}</span>
            </div>

            @can('users.edit')
                <form method="POST" action="{{ route('employee.acces.modifier', $employee->id) }}" class="ed-form">
                    @csrf @method('PUT')
                    @include('employees._acces', ['roles' => $roles, 'utilisateur' => $compte, 'avecSms' => false])
                    <button type="submit" class="hl-bouton">Enregistrer l'accès</button>
                </form>
            @else
                <dl class="ed-infos"><div><dt>Identifiant</dt><dd>{{ $compte->phone }}</dd></div><div><dt>Rôle</dt><dd>{{ ucfirst($compte->getRoleNames()->first() ?? '—') }}</dd></div></dl>
            @endcan

            <div class="ed-actions">
                @can('users.change_password')
                    <form method="POST" action="{{ route('employee.acces.mot-de-passe', $employee->id) }}" onsubmit="return confirm('Envoyer un nouveau mot de passe provisoire par SMS au {{ $compte->phone }} ? L\'ancien ne fonctionnera plus.');">
                        @csrf<button type="submit" class="hl-bouton"><i class="fas fa-sms" aria-hidden="true"></i> Nouveau mot de passe par SMS</button></form>
                @endcan
                @can('users.disable')
                    @if($compte->id !== auth()->id())
                        <form method="POST" action="{{ route('employee.acces.statut', $employee->id) }}" onsubmit="return confirm('{{ $suspendu ? 'Réactiver cet accès ?' : 'Suspendre cet accès ? La personne sera déconnectée immédiatement.' }}');">
                            @csrf @method('PATCH')
                            <button type="submit" class="hl-bouton" style="{{ $suspendu ? '' : 'color:var(--hali-danger); border-color:#fecaca' }}">
                                <i class="fas {{ $suspendu ? 'fa-unlock' : 'fa-ban' }}" aria-hidden="true"></i> {{ $suspendu ? 'Réactiver' : 'Suspendre' }}</button>
                        </form>
                    @endif
                @endcan
                @can('users.permissions')
                    <a href="{{ route('users.listePermissions', $compte->id) }}" class="hl-bouton"><i class="fas fa-user-shield" aria-hidden="true"></i> Autorisations fines</a>
                @endcan
            </div>
        @else
            <p class="ed-sous" style="margin:0 0 12px">Cette personne ne peut pas se connecter à Hali.</p>
            @can('users.create')
                @if($roles->isNotEmpty())
                    <form method="POST" action="{{ route('employee.acces.creer', $employee->id) }}" class="ed-form">
                        @csrf
                        @include('employees._acces', ['roles' => $roles, 'utilisateur' => null])
                        <button type="submit" class="hl-bouton hl-bouton-plein">Créer l'accès</button>
                    </form>
                @endif
            @endcan
        @endif
    </section>
    </div>
</div></div>
<script>document.getElementById('emForm').addEventListener('submit', function () { document.getElementById('emValider').disabled = true; });</script>
<style>
    .ed-grille { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr); gap: 16px; align-items: start; }
    .ed-acces { padding-bottom: 18px; }
    .ed-etat { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 0 18px 12px; }
    .ed-sous { color: var(--hali-discret); font-size: .84rem; }
    .ed-form { display: grid; gap: 14px; padding: 4px 18px 0; }
    .ed-form > button { justify-self: start; }
    .ed-actions { display: flex; flex-wrap: wrap; gap: 8px; margin: 16px 18px 0; padding-top: 16px; border-top: 1px solid var(--hali-bordure); }
    .ed-actions form { margin: 0; }
    .ed-infos { margin: 0 18px; }
    .ed-infos div { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    .ed-secret { display: flex; gap: 14px; margin-bottom: 16px; padding: 16px 18px; border: 2px solid #f59e0b; border-radius: 14px; background: #fffbeb; color: #78350f; }
    .ed-secret > i { font-size: 1.4rem; }
    .ed-secret strong, .ed-secret span, .ed-secret small { display: block; }
    .ed-secret span { margin-top: 4px; font-size: .95rem; }
    .ed-mdp { padding: 2px 10px; border-radius: 6px; background: #fff; font-family: "SF Mono", Consolas, monospace; font-size: 1.1rem; letter-spacing: .08em; }
    .ed-secret small { margin-top: 4px; }
    @media (max-width: 1199.98px) { .ed-grille { grid-template-columns: 1fr; } }
</style>
@endsection
