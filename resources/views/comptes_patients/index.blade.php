@extends('layouts.backend')

@php
    $roles = ['titulaire' => 'Titulaire', 'tuteur' => 'Tuteur (mineur)'];
    $numero = fn ($t) => preg_replace('/^(\d{3})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4', (string) $t);
@endphp

@section('style')
<style>
    .cp-ligne { display: grid; grid-template-columns: 200px minmax(0, 1fr) auto; align-items: start; gap: 16px; padding: 14px 18px; border-top: 1px solid #f3f4f6; }
    .cp-ligne:first-of-type { border-top: 0; }
    .cp-tel { display: block; color: var(--hali-encre); font-size: 1rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .cp-sous { display: block; color: var(--hali-discret); font-size: .78rem; overflow: hidden; text-overflow: ellipsis; }
    .cp-dossiers { display: flex; flex-wrap: wrap; gap: 6px; }
    .cp-dossier { display: inline-flex; align-items: center; gap: 8px; padding: 5px 6px 5px 12px; border: 1px solid var(--hali-bordure); border-radius: 999px; background: #fff; font-size: .84rem; }
    .cp-dossier b { color: var(--hali-encre); font-weight: 650; }
    .cp-dossier small { color: var(--hali-discret); }
    .cp-dossier form { margin: 0; }
    .cp-dossier button { display: grid; place-items: center; width: 22px; height: 22px; border: 0; border-radius: 50%; background: #f3f4f6; color: var(--hali-discret); cursor: pointer; }
    .cp-dossier button:hover { background: var(--hali-danger-pale); color: var(--hali-danger); }
    .cp-autres { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; background: #f9fafb; color: var(--hali-discret); font-size: .8rem; }
    .cp-actions { display: flex; gap: 4px; }
    .cp-actions form { margin: 0; }
    .cp-icone { display: inline-grid; place-items: center; width: 34px; height: 34px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; }
    .cp-icone:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); }
    .cp-icone.est-risque:hover { border-color: var(--hali-danger); color: var(--hali-danger); background: var(--hali-danger-pale); }
    .cp-outils { display: flex; gap: 8px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .cp-outils input { flex: 1; min-height: 40px; }
    .cp-modal .modal-body { display: grid; gap: 14px; padding: 8px 22px 14px; }
    .cp-modal .modal-header { padding: 18px 22px 6px; border: 0; }
    .cp-modal .modal-footer { padding: 10px 22px 18px; border: 0; }
    .cp-modal label.cp-l { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .cp-verif { display: flex; gap: 10px; margin: 0; padding: 12px; border-radius: 10px; background: var(--hali-alerte-pale); color: #78350f; font-size: .85rem; cursor: pointer; }
    .cp-verif input { width: 18px; height: 18px; margin-top: 2px; accent-color: #b45309; }
    .cp-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media (max-width: 991.98px) { .cp-ligne { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Comptes du portail patient</h1>
            <p>Le téléphone d'un compte reçoit le code de connexion à « Mon espace santé ». Un compte peut donner accès à plusieurs dossiers (une mère et ses enfants).</p>
        </div>
        @can('patient.edit')
            <div class="hl-entete-actions"><button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addCompteModal"><i class="fa fa-plus" aria-hidden="true"></i> Nouveau compte</button></div>
        @endcan
    </header>

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <p class="hl-note hl-note-info mb-3"><i class="fas fa-user-shield" aria-hidden="true"></i> <span>Seuls les comptes liés aux patients de votre clinique apparaissent. Un compte donne accès au dossier médical complet : vérifiez toujours l'identité (pièce d'identité) avant de rattacher un dossier ou de changer un téléphone.</span></p>

    <section class="hl-bloc">
        <form method="GET" class="cp-outils" role="search">
            <input type="search" name="q" value="{{ $recherche }}" class="form-control" placeholder="Téléphone, nom du patient, identifiant santé…" aria-label="Rechercher un compte">
            <button type="submit" class="hl-bouton"><i class="fas fa-search" aria-hidden="true"></i> Rechercher</button>
            @if($recherche !== '')<a href="{{ route('comptes-patients.index') }}" class="hl-bouton">Effacer</a>@endif
        </form>

        @forelse($comptes as $c)
            @php
                $dossiers = $c->patients->filter(fn ($p) => $idsClinique->has($p->id));
                $autres = $c->patients->count() - $dossiers->count();
            @endphp
            <div class="cp-ligne">
                <div>
                    <span class="cp-tel">{{ $numero($c->telephone) }}</span>
                    @if($c->email)<span class="cp-sous">{{ $c->email }}</span>@endif
                    <span style="display:inline-block; margin-top:4px">@if($c->statut === 'suspendu')<span class="hl-statut hl-s-danger">Suspendu</span>@else<span class="hl-statut hl-s-succes">Actif</span>@endif</span>
                </div>
                <div class="cp-dossiers">
                    @foreach($dossiers as $p)
                        <span class="cp-dossier">
                            <span><b>{{ $p->getFullName() }}</b> <small>· {{ $roles[$p->pivot->role] ?? $p->pivot->role }} · {{ $p->identifiant_national_sante }}</small></span>
                            @can('patient.edit')
                                <form method="POST" action="{{ route('comptes-patients.detacher', [$c, $p]) }}" onsubmit="return confirm('Détacher le dossier de {{ addslashes($p->getFullName()) }} ? Ce téléphone n\'y aura plus accès.');">
                                    @csrf @method('DELETE')<button type="submit" title="Détacher" aria-label="Détacher {{ $p->getFullName() }}"><i class="fas fa-times" style="font-size:.7rem"></i></button>
                                </form>
                            @endcan
                        </span>
                    @endforeach
                    @if($autres > 0)<span class="cp-autres" title="Dossiers suivis dans une autre clinique : non affichés"><i class="fas fa-lock" aria-hidden="true"></i> + {{ $autres }} dossier{{ $autres > 1 ? 's' : '' }} d'une autre clinique</span>@endif
                </div>
                @can('patient.edit')
                    <div class="cp-actions">
                        <button type="button" class="cp-icone js-attacher" data-action="{{ route('comptes-patients.attacher', $c) }}" data-tel="{{ $numero($c->telephone) }}" title="Rattacher un dossier" aria-label="Rattacher un dossier au {{ $c->telephone }}"><i class="fas fa-link"></i></button>
                        <button type="button" class="cp-icone js-modifier" data-action="{{ route('comptes-patients.update', $c) }}" data-tel="{{ $c->telephone }}" data-email="{{ $c->email }}" data-statut="{{ $c->statut }}" title="Modifier" aria-label="Modifier le compte {{ $c->telephone }}"><i class="fa fa-pen"></i></button>
                        @if($autres === 0)
                            <form method="POST" action="{{ route('comptes-patients.delete', $c) }}" onsubmit="return confirm('Supprimer ce compte ? Les dossiers patients sont conservés, mais ce téléphone ne pourra plus se connecter.');">
                                @csrf @method('DELETE')<button type="submit" class="cp-icone est-risque" title="Supprimer" aria-label="Supprimer le compte {{ $c->telephone }}"><i class="fa fa-trash"></i></button>
                            </form>
                        @endif
                    </div>
                @endcan
            </div>
        @empty
            <div class="hl-vide"><i class="fas fa-mobile-alt" aria-hidden="true"></i>{{ $recherche !== '' ? 'Aucun compte ne correspond.' : 'Aucun compte lié à vos patients.' }}</div>
        @endforelse
        @if($comptes->hasPages())<div style="padding:14px 18px; border-top:1px solid var(--hali-bordure)">{{ $comptes->links() }}</div>@endif
    </section>

    @can('patient.edit')
        {{-- ============ Nouveau compte (avec son premier dossier) --}}
        <div class="modal fade cp-modal" id="addCompteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form class="modal-content" method="POST" action="{{ route('comptes-patients.add') }}">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Nouveau compte portail</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                    <div class="modal-body">
                        <div class="cp-deux">
                            <div><label class="cp-l" for="cpTel">Téléphone *</label><input type="tel" id="cpTel" name="telephone" class="form-control" inputmode="numeric" maxlength="9" pattern="[0-9]{9}" value="{{ old('telephone') }}" required placeholder="622000000"></div>
                            <div><label class="cp-l" for="cpEmail">E-mail</label><input type="email" id="cpEmail" name="email" class="form-control" value="{{ old('email') }}"></div>
                        </div>
                        <div class="cp-deux">
                            <div><label class="cp-l" for="cpIns">Identifiant santé du dossier *</label><input type="text" id="cpIns" name="identifiant_national_sante" class="form-control" value="{{ old('identifiant_national_sante') }}" required placeholder="GN26A1B2C3"></div>
                            <div><label class="cp-l" for="cpRole">En tant que</label>
                                <select name="role" id="cpRole" class="form-control">@foreach($roles as $v => $l)<option value="{{ $v }}" @selected(old('role') === $v)>{{ $l }}</option>@endforeach</select></div>
                        </div>
                        <label class="cp-verif"><input type="checkbox" name="identite_verifiee" value="1" required>
                            <span><strong>J'ai vérifié l'identité</strong> de la personne à qui appartient ce téléphone (pièce d'identité), et elle est le patient ou son représentant légal.</span></label>
                        <p class="small text-muted mb-0">Si ce numéro a déjà un compte, le dossier y est simplement ajouté.</p>
                    </div>
                    <div class="modal-footer"><button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button><button type="submit" class="hl-bouton hl-bouton-plein">Créer le compte</button></div>
                </form>
            </div>
        </div>

        {{-- ============ Rattacher un dossier --}}
        <div class="modal fade cp-modal" id="attacherModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form class="modal-content" method="POST" action="" id="attacherForm">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Rattacher un dossier au <span id="attacherTel"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                    <div class="modal-body">
                        <div class="cp-deux">
                            <div><label class="cp-l" for="atIns">Identifiant santé *</label><input type="text" id="atIns" name="identifiant_national_sante" class="form-control" required placeholder="GN26A1B2C3"></div>
                            <div><label class="cp-l" for="atRole">En tant que</label>
                                <select name="role" id="atRole" class="form-control">@foreach($roles as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
                        </div>
                        <label class="cp-verif"><input type="checkbox" name="identite_verifiee" value="1" required>
                            <span><strong>J'ai vérifié l'identité</strong> : ce téléphone appartient au patient ou à son représentant légal (tuteur d'un mineur).</span></label>
                    </div>
                    <div class="modal-footer"><button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button><button type="submit" class="hl-bouton hl-bouton-plein">Rattacher</button></div>
                </form>
            </div>
        </div>

        {{-- ============ Modifier --}}
        <div class="modal fade cp-modal" id="modifierModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <form class="modal-content" method="POST" action="" id="modifierForm">
                    @csrf @method('PUT')
                    <div class="modal-header"><h5 class="modal-title">Modifier le compte</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button></div>
                    <div class="modal-body">
                        <div class="cp-deux">
                            <div><label class="cp-l" for="moTel">Téléphone *</label><input type="tel" id="moTel" name="telephone" class="form-control" inputmode="numeric" maxlength="9" pattern="[0-9]{9}" required></div>
                            <div><label class="cp-l" for="moEmail">E-mail</label><input type="email" id="moEmail" name="email" class="form-control"></div>
                        </div>
                        <div><label class="cp-l" for="moStatut">Statut</label>
                            <select name="statut" id="moStatut" class="form-control"><option value="actif">Actif</option><option value="suspendu">Suspendu (ne peut plus se connecter)</option></select></div>
                        <label class="cp-verif" id="moVerif" hidden><input type="checkbox" name="identite_verifiee" value="1">
                            <span><strong>Nouveau téléphone : j'ai vérifié l'identité.</strong> Ce numéro recevra désormais les codes de connexion et aura accès aux dossiers.</span></label>
                    </div>
                    <div class="modal-footer"><button type="button" class="hl-bouton" data-bs-dismiss="modal">Annuler</button><button type="submit" class="hl-bouton hl-bouton-plein">Enregistrer</button></div>
                </form>
            </div>
        </div>
    @endcan
</div></div>
@endsection

@section('script')
<script>
(function () {
    var modal = function (id) { return bootstrap.Modal.getOrCreateInstance(document.getElementById(id)); };
    document.querySelectorAll('.js-attacher').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('attacherForm').action = b.dataset.action;
            document.getElementById('attacherTel').textContent = b.dataset.tel;
            modal('attacherModal').show();
        });
    });
    var telInitial = '';
    document.querySelectorAll('.js-modifier').forEach(function (b) {
        b.addEventListener('click', function () {
            document.getElementById('modifierForm').action = b.dataset.action;
            telInitial = b.dataset.tel;
            document.getElementById('moTel').value = b.dataset.tel;
            document.getElementById('moEmail').value = b.dataset.email || '';
            document.getElementById('moStatut').value = b.dataset.statut || 'actif';
            document.getElementById('moVerif').hidden = true;
            modal('modifierModal').show();
        });
    });
    var moTel = document.getElementById('moTel');
    if (moTel) moTel.addEventListener('input', function () {
        // Changer le téléphone donne l'accès à un autre numéro : attestation demandée.
        var change = moTel.value !== telInitial, verif = document.getElementById('moVerif');
        verif.hidden = !change; verif.querySelector('input').required = change;
    });
    @if($errors->has('telephone') || $errors->has('identifiant_national_sante') || $errors->has('identite_verifiee'))
        @if(old('telephone')) modal('addCompteModal').show(); @endif
    @endif
})();
</script>
@endsection
