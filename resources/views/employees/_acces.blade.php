{{--
    Champs de l'accès à Hali (création avec la fiche, création depuis la fiche, modification).
    $roles : rôles attribuables ; $utilisateur : compte existant ou null ; $avecSms : afficher l'option SMS.
--}}
@php
    $utilisateur = $utilisateur ?? null;
    $avecSms = $avecSms ?? true;
    $roleActuel = old('role', $utilisateur?->getRoleNames()->first());
    $libellesRoles = ['admin' => ['Administrateur', 'Tout gérer dans la clinique'], 'medecin' => ['Médecin', 'File, consultations, ordonnances'],
        'secretaire' => ['Secrétariat', 'Accueil, rendez-vous, patients'], 'comptable' => ['Comptabilité', 'Caisse, factures, assurances'],
        'infirmier' => ['Infirmier', 'Constantes, soins'], 'laborantin' => ['Laboratoire', 'Prélèvements, résultats'], 'biologiste' => ['Biologiste', 'Validation des résultats'],
        'caissier' => ['Caisse', 'Encaissements']];
@endphp
<div class="ac-champs">
    <div class="ac-deux">
        <div><label class="cat-l" for="acTel">Téléphone (identifiant de connexion) *</label>
            <input type="tel" name="telephone" id="acTel" class="form-control" inputmode="numeric" maxlength="9" pattern="[0-9]{9}" placeholder="622000000"
                   value="{{ old('telephone', $utilisateur?->phone) }}" autocomplete="off"></div>
        <div><label class="cat-l" for="acEmail">E-mail <span style="font-weight:500; color:var(--hali-discret)">(facultatif)</span></label>
            <input type="email" name="email" id="acEmail" class="form-control" value="{{ old('email', $utilisateur?->email) }}" autocomplete="off"></div>
    </div>
    <div>
        <span class="cat-l">Rôle : ce que la personne pourra faire *</span>
        @if($roles->isEmpty())
            <p class="hl-note hl-note-alerte mb-0">Aucun rôle que vous puissiez attribuer.</p>
        @else
            <div class="ac-roles" role="radiogroup" aria-label="Rôle">
                @foreach($roles as $r)
                    <label class="ac-role">
                        <input type="radio" name="role" value="{{ $r->name }}" @checked($roleActuel === $r->name)>
                        <span><strong>{{ $libellesRoles[$r->name][0] ?? ucfirst($r->name) }}</strong><small>{{ $libellesRoles[$r->name][1] ?? $r->permissions->count() . ' autorisations' }}</small></span>
                    </label>
                @endforeach
            </div>
        @endif
    </div>
    @if($avecSms)
        <label class="d-flex gap-2 mb-0" style="font-weight:500">
            <input type="hidden" name="envoyer_sms" value="0">
            <input type="checkbox" name="envoyer_sms" value="1" @checked(old('envoyer_sms', '1') === '1') style="margin-top:3px">
            <span>Envoyer l'identifiant et un mot de passe provisoire par SMS
                <span class="d-block small text-muted">La personne devra choisir son propre mot de passe à la première connexion. Sans SMS, le mot de passe s'affiche une seule fois à l'écran.</span></span>
        </label>
    @endif
</div>

@once
<style>
    .ac-champs { display: grid; gap: 14px; }
    .ac-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .ac-roles { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 8px; }
    .ac-role { position: relative; margin: 0; cursor: pointer; }
    .ac-role input { position: absolute; opacity: 0; }
    .ac-role span { display: grid; gap: 2px; height: 100%; padding: 10px 12px; border: 1.5px solid var(--hali-bordure); border-radius: 10px; background: #fff; }
    .ac-role strong { color: var(--hali-encre); font-size: .88rem; }
    .ac-role small { color: var(--hali-discret); font-size: .74rem; font-weight: 500; }
    .ac-role input:checked + span { border-color: var(--hali-primaire); background: var(--hali-primaire-pale); box-shadow: 0 0 0 3px var(--hali-primaire-pale); }
    .ac-role input:focus-visible + span { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
    @media (max-width: 767.98px) { .ac-deux { grid-template-columns: 1fr; } }
</style>
@endonce
