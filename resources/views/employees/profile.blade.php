@extends('layouts.backend')

@php
    $utilisateur = auth()->user();
    $compte = $estMonProfil ? $utilisateur : $employee?->user;
    $initiales = $employee
        ? mb_strtoupper(mb_substr((string) $employee->first_name, 0, 1) . mb_substr((string) $employee->last_name, 0, 1))
        : mb_strtoupper(mb_substr((string) $utilisateur?->name, 0, 2));
    $estMedecin = $employee?->type === 'Doctor';
    $role = $compte?->getRoleNames()->first();
    $stats = $estMedecin ? [
        'consultations' => \App\Models\Consultation::where('medecin_id', $employee->id)->where('created_at', '>=', now()->startOfMonth())->count(),
        'rdv' => $employee->appointments()->whereIn('status', ['pending', 'confirmed'])->where('appointment_datetime', '>', now())->count(),
    ] : null;
@endphp

@section('style')
<style>
    .pf-grille { display: grid; grid-template-columns: minmax(0, 340px) minmax(0, 1fr); gap: 16px; align-items: start; }
    .pf-carte { display: grid; justify-items: center; gap: 6px; padding: 26px 20px 20px; text-align: center; }
    .pf-avatar { display: grid; place-items: center; width: 86px; height: 86px; margin-bottom: 6px; border-radius: 24px; background: var(--hali-primaire); color: #fff; font-size: 1.8rem; font-weight: 800; }
    .pf-nom { margin: 0; color: var(--hali-encre); font-size: 1.25rem; font-weight: 750; }
    .pf-sous { color: var(--hali-discret); font-size: .88rem; }
    .pf-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%; margin-top: 12px; }
    .pf-stats div { padding: 12px; border-radius: 12px; background: var(--hali-primaire-pale); }
    .pf-stats b { display: block; color: var(--hali-primaire-fonce); font-size: 1.4rem; }
    .pf-stats span { color: var(--hali-discret); font-size: .76rem; }
    .pf-liens { display: grid; gap: 4px; width: 100%; margin-top: 12px; text-align: left; }
    .pf-liens a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 9px; color: var(--hali-texte); font-weight: 600; font-size: .88rem; text-decoration: none; }
    .pf-liens a:hover { background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); }
    .pf-liens i { width: 18px; color: var(--hali-primaire); text-align: center; }
    .pf-infos { margin: 0; padding: 6px 18px 12px; }
    .pf-infos div { display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 10px; padding: 11px 0; border-bottom: 1px solid #f3f4f6; font-size: .9rem; }
    .pf-infos div:last-child { border-bottom: 0; }
    .pf-infos dt { color: var(--hali-discret); font-weight: 500; }
    .pf-infos dd { margin: 0; color: var(--hali-encre); font-weight: 600; white-space: pre-line; }
    .pf-form { display: grid; gap: 12px; max-width: 420px; padding: 16px 18px 20px; }
    .pf-form label { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .pf-provisoire { display: flex; gap: 14px; margin-bottom: 16px; padding: 16px 18px; border-radius: 14px; background: var(--hali-primaire); color: #fff; }
    .pf-provisoire i { font-size: 1.4rem; margin-top: 2px; }
    .pf-provisoire strong, .pf-provisoire span { display: block; }
    .pf-provisoire span { margin-top: 3px; opacity: .9; font-size: .9rem; }
    @media (max-width: 991.98px) { .pf-grille { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div><h1>{{ $estMonProfil ? 'Mon profil' : 'Fiche du personnel' }}</h1><p>{{ $employee?->department?->name }}</p></div>
        <div class="hl-entete-actions">
            @if(! $estMonProfil)<a href="{{ route('employee.index') }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Personnel</a>@endif
            @if($employee)
                @can('employee.edit')<a href="{{ route('employee.edit', $employee->id) }}" class="hl-bouton"><i class="fa fa-pen" aria-hidden="true"></i> Modifier la fiche</a>@endcan
            @endif
        </div>
    </header>

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    @if($estMonProfil && auth()->user()?->doit_changer_mot_de_passe)
        <div class="pf-provisoire" role="alert">
            <i class="fas fa-lock" aria-hidden="true"></i>
            <div><strong>Choisissez votre propre mot de passe pour continuer.</strong>
                <span>Vous êtes connecté(e) avec le mot de passe provisoire reçu par SMS. Saisissez-le comme « mot de passe actuel », puis choisissez le vôtre ci-dessous.</span></div>
        </div>
    @endif

    <div class="pf-grille">
        <section class="hl-bloc">
            <div class="pf-carte">
                <span class="pf-avatar" aria-hidden="true">{{ $initiales }}</span>
                <h2 class="pf-nom">{{ $employee?->nom_affiche ?? $utilisateur?->name }}</h2>
                <span class="pf-sous">{{ $employee?->speciality ?: $employee?->type_libelle }}</span>
                @if($employee && ! $employee->is_active)<span class="hl-statut hl-s-danger">Fiche désactivée</span>@endif
                @if($stats)
                    <div class="pf-stats">
                        <div><b>{{ $stats['consultations'] }}</b><span>consultations ce mois</span></div>
                        <div><b>{{ $stats['rdv'] }}</b><span>rendez-vous à venir</span></div>
                    </div>
                @endif
                @if($estMonProfil && $estMedecin)
                    <nav class="pf-liens" aria-label="Mon organisation">
                        @if(Route::has('parcours.file.index'))@can('parcours.file')<a href="{{ route('parcours.file.index') }}"><i class="fas fa-users" aria-hidden="true"></i> Ma file d'attente</a>@endcan @endif
                        @can('medecin.appointments')<a href="{{ route('medecin.appointments') }}"><i class="fas fa-calendar-check" aria-hidden="true"></i> Mes rendez-vous</a>@endcan
                        @can('medecin.availabilities')<a href="{{ route('medecin.availabilities.index') }}"><i class="fas fa-clock" aria-hidden="true"></i> Mes horaires</a>@endcan
                        @can('medecin.leaves')<a href="{{ route('medecin.leaves.index') }}"><i class="fas fa-umbrella-beach" aria-hidden="true"></i> Congés et pauses</a>@endcan
                    </nav>
                @endif
            </div>
        </section>

        <div style="display:grid; gap:16px">
            <section class="hl-bloc">
                <h2 class="hl-bloc-titre">Informations</h2>
                <dl class="pf-infos">
                    @if($employee)
                        <div><dt>Fonction</dt><dd>{{ $employee->type_libelle }}</dd></div>
                        <div><dt>Département</dt><dd>{{ $employee->department?->name ?? '—' }}</dd></div>
                        @if($employee->speciality)<div><dt>Spécialité</dt><dd>{{ $employee->speciality }}</dd></div>@endif
                        @if($employee->address)<div><dt>Adresse</dt><dd>{{ $employee->address }}</dd></div>@endif
                        @if($employee->education)<div><dt>Formation</dt><dd style="font-weight:500">{{ $employee->education }}</dd></div>@endif
                        @if($employee->certificate)<div><dt>Diplômes</dt><dd style="font-weight:500">{{ $employee->certificate }}</dd></div>@endif
                        @if($employee->description)<div><dt>Présentation</dt><dd style="font-weight:500">{{ $employee->description }}</dd></div>@endif
                    @endif
                    <div><dt>Compte de connexion</dt><dd>@if($compte){{ $compte->phone ?: $compte->email }}@if($compte->email && $compte->phone)<span class="d-block pf-sous" style="font-weight:500">{{ $compte->email }}</span>@endif @else<span class="pf-sous">Aucun : cette personne ne peut pas se connecter.</span>@endif</dd></div>
                    @if($role)<div><dt>Rôle</dt><dd>{{ ucfirst($role) }}</dd></div>@endif
                </dl>
                @if($estMonProfil && ! $employee)
                    <p class="hl-note hl-note-info" style="margin:0 18px 16px">Votre compte n'est relié à aucune fiche du personnel. Demandez à l'administrateur de la créer si vous recevez des patients.</p>
                @endif
            </section>

            @if($estMonProfil && Route::has('employee.mot-de-passe'))
                <section class="hl-bloc">
                    <h2 class="hl-bloc-titre">Changer mon mot de passe</h2>
                    <form method="POST" action="{{ route('employee.mot-de-passe') }}" class="pf-form" id="pfMdp">
                        @csrf @method('PUT')
                        <div><label for="pfActuel">{{ auth()->user()?->doit_changer_mot_de_passe ? 'Mot de passe provisoire (reçu par SMS)' : 'Mot de passe actuel' }}</label><input type="password" id="pfActuel" name="mot_de_passe_actuel" class="form-control" autocomplete="current-password" required></div>
                        <div><label for="pfNouveau">Nouveau mot de passe</label><input type="password" id="pfNouveau" name="password" class="form-control" autocomplete="new-password" minlength="8" required>
                            <p class="pf-sous mb-0 mt-1" style="font-size:.78rem">8 caractères au moins. Évitez votre date de naissance ou votre numéro.</p></div>
                        <div><label for="pfConfirme">Confirmer le nouveau mot de passe</label><input type="password" id="pfConfirme" name="password_confirmation" class="form-control" autocomplete="new-password" minlength="8" required></div>
                        <button type="submit" class="hl-bouton hl-bouton-plein" style="justify-self:start">Changer le mot de passe</button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</div></div>
@endsection
