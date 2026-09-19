@extends('layouts.backend')

@php
    $e = $etablissement;
    $v = optional($e->updated_at)->timestamp;
    $apercu = fn ($type) => $e->{$type} && \App\Support\Images\ImageControlee::cheminAbsolu($type, $e->{$type}) ? route('identite.image', $type) . '?v=' . $v : null;
@endphp

@section('style')
<style>
    .id-grille { display: grid; grid-template-columns: minmax(0, 1.4fr) minmax(300px, 1fr); gap: 16px; align-items: start; }
    .id-section { padding: 18px; }
    .id-section h2 { margin: 0 0 4px; color: var(--hali-encre); font-size: 1rem; font-weight: 700; }
    .id-section > p { margin: 0 0 14px; color: var(--hali-discret); font-size: .85rem; }
    .id-champs { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .id-champs .id-large { grid-column: 1 / -1; }
    .id-champs label { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .id-images { display: grid; gap: 12px; }
    .id-apercu { position: sticky; top: 90px; padding: 18px; }
    .id-feuille { aspect-ratio: 148 / 210; padding: 7% 7% 6%; border: 1px solid var(--hali-bordure); border-radius: 6px; background: #fff; box-shadow: 0 6px 20px rgba(0, 0, 0, .06); font-size: .5rem; color: #374151; display: flex; flex-direction: column; }
    .id-feuille-tete { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; padding-bottom: 6px; border-bottom: 1.5px solid #0f766e; }
    .id-feuille-tete img { max-width: 72px; max-height: 26px; object-fit: contain; }
    .id-feuille-nom { font-size: .72rem; font-weight: 800; color: #111827; }
    .id-feuille-type { color: #0f766e; font-size: .7rem; font-weight: 800; text-align: right; }
    .id-lignes { margin-top: 10px; display: grid; gap: 5px; }
    .id-lignes span { display: block; height: 6px; border-radius: 3px; background: #eef2f2; }
    .id-feuille-pied { margin-top: auto; display: flex; justify-content: flex-end; }
    .id-validation { position: relative; width: 45%; min-height: 58px; text-align: center; }
    .id-validation .id-cachet { position: absolute; left: 0; bottom: 6px; width: 46px; height: 46px; object-fit: contain; opacity: .9; }
    .id-validation .id-signature { position: relative; max-width: 100%; max-height: 34px; object-fit: contain; }
    .id-validation small { display: block; border-top: .5px solid #9ca3af; padding-top: 2px; font-size: .45rem; }
    .id-manque { color: #9ca3af; font-style: italic; }
    .id-pied { position: sticky; bottom: 0; z-index: 5; display: flex; justify-content: flex-end; gap: 8px; margin-top: 16px; padding: 12px 18px; border: 1px solid var(--hali-bordure); border-radius: 12px; background: rgba(255, 255, 255, .96); }
    @media (max-width: 1199.98px) { .id-grille { grid-template-columns: 1fr; } .id-apercu { position: static; } }
    @media (max-width: 767.98px) { .id-champs { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Identité de la clinique</h1>
            <p>Ce qui figure sur vos documents (factures, reçus, rapports, ordonnances) et sur vos pages en ligne. Les images sont contrôlées avant d'être acceptées.</p>
        </div>
    </header>

    @unless(\App\Support\Etablissement\IdentiteDocument::imagesPdfPossibles())
        <div class="hl-note hl-note-alerte mb-3" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span><strong>Extension PHP « GD » absente sur le serveur :</strong> les logos, signatures et cachets ne peuvent pas être imprimés sur les PDF (factures, reçus). Ils restent visibles à l'écran. Demandez à votre hébergeur d'activer GD.</span></div>
    @endunless
    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('identite.update') }}" enctype="multipart/form-data" id="idForm">
        @csrf @method('PUT')
        <div class="id-grille">
            <div style="display:grid; gap:16px">
                <section class="hl-bloc id-section">
                    <h2>Coordonnées imprimées</h2>
                    <p>En-tête et pied de page de tous les documents. Le nom de la clinique ({{ $e->nom }}) se change auprès de l'équipe Hali.</p>
                    <div class="id-champs">
                        <div class="id-large"><label for="idAdresse">Adresse</label><input id="idAdresse" name="adresse" class="form-control" maxlength="255" value="{{ old('adresse', $e->adresse) }}" placeholder="Quartier, commune, ville"></div>
                        <div><label for="idContact">Téléphone</label><input id="idContact" name="contact" class="form-control" maxlength="60" value="{{ old('contact', $e->contact) }}"></div>
                        <div><label for="idEmail">E-mail</label><input id="idEmail" name="email" type="email" class="form-control" value="{{ old('email', $e->email) }}"></div>
                        <div><label for="idSite">Site web</label><input id="idSite" name="site_web" type="url" class="form-control" value="{{ old('site_web', $e->site_web) }}" placeholder="https://"></div>
                        <div><label for="idRccm">N° d'enregistrement (RCCM, agrément)</label><input id="idRccm" name="numero_enregistrement" class="form-control" maxlength="120" value="{{ old('numero_enregistrement', $e->numero_enregistrement) }}"></div>
                        <div class="id-large"><label for="idMessage">Message en bas des factures</label><input id="idMessage" name="message_facture" class="form-control" maxlength="300" value="{{ old('message_facture', $e->message_facture) }}" placeholder="Merci de votre confiance. Conservez ce document pour votre assurance."></div>
                    </div>
                </section>

                <section class="hl-bloc id-section">
                    <h2>Logos</h2>
                    <p>Le logo de l'application apparaît dans le menu, sur la page de rendez-vous et sur les documents. Le logo des documents est facultatif : sans lui, le logo de l'application est utilisé.</p>
                    <div class="id-images">
                        @include('partials.image-controlee', ['type' => 'logo', 'champ' => 'logo', 'apercu' => $apercu('logo'), 'supprimable' => true])
                        @include('partials.image-controlee', ['type' => 'logo_documents', 'champ' => 'logo_documents', 'apercu' => $apercu('logo_documents'), 'supprimable' => true])
                    </div>
                </section>

                <section class="hl-bloc id-section">
                    <h2>Signature et cachet de la clinique</h2>
                    <p>Apposés sur les factures, les reçus et la facture d'hospitalisation. Ils ne sont jamais accessibles par une adresse web : seulement intégrés aux documents au moment de les produire. Les ordonnances et certificats portent la signature du médecin, pas celle-ci.</p>
                    <div class="id-champs" style="margin-bottom:12px">
                        <div><label for="idSigNom">Nom du signataire</label><input id="idSigNom" name="signataire_nom" class="form-control" maxlength="120" value="{{ old('signataire_nom', $e->signataire_nom) }}" placeholder="Dr Mamadou Camara"></div>
                        <div><label for="idSigFonction">Fonction</label><input id="idSigFonction" name="signataire_fonction" class="form-control" maxlength="120" value="{{ old('signataire_fonction', $e->signataire_fonction) }}" placeholder="Directeur"></div>
                    </div>
                    <div class="id-images">
                        @include('partials.image-controlee', ['type' => 'signature', 'champ' => 'signature', 'apercu' => $apercu('signature'), 'supprimable' => true])
                        @include('partials.image-controlee', ['type' => 'cachet', 'champ' => 'cachet', 'apercu' => $apercu('cachet'), 'supprimable' => true])
                    </div>
                </section>
            </div>

            <aside class="hl-bloc id-apercu" aria-label="Aperçu d'une facture">
                <h2 style="margin:0 0 10px; font-size:1rem; font-weight:700; color:var(--hali-encre)">Aperçu sur une facture A5</h2>
                <div class="id-feuille">
                    <div class="id-feuille-tete">
                        <div>
                            @if($apercu('logo_documents') ?? $apercu('logo'))<img src="{{ $apercu('logo_documents') ?? $apercu('logo') }}" alt="" id="apLogo">@else<img alt="" id="apLogo" hidden><span class="id-manque" id="apLogoVide">Logo</span>@endif
                            <div class="id-feuille-nom">{{ $e->nom }}</div>
                            <div id="apAdresse">{{ $e->adresse }}</div>
                        </div>
                        <div class="id-feuille-type">FACTURE<br><span style="color:#374151; font-size:.5rem">N° FAC-001842</span></div>
                    </div>
                    <div class="id-lignes"><span style="width:70%"></span><span></span><span style="width:85%"></span><span></span><span style="width:60%"></span></div>
                    <div class="id-feuille-pied">
                        <div class="id-validation">
                            @if($apercu('cachet'))<img src="{{ $apercu('cachet') }}" alt="" class="id-cachet" id="apCachet">@else<img alt="" class="id-cachet" id="apCachet" hidden>@endif
                            @if($apercu('signature'))<img src="{{ $apercu('signature') }}" alt="" class="id-signature" id="apSignature">@else<img alt="" class="id-signature" id="apSignature" hidden><div class="id-manque" id="apSignatureVide" style="height:30px">signature</div>@endif
                            <small id="apSignataire">{{ $e->signataire_nom ?: 'Nom du signataire' }}{{ $e->signataire_fonction ? ' · ' . $e->signataire_fonction : '' }}</small>
                        </div>
                    </div>
                </div>
                <p class="small text-muted mt-2 mb-0">L'aperçu se met à jour dès qu'une image conforme est choisie.</p>
            </aside>
        </div>

        <div class="id-pied">
            <a href="{{ url()->previous() }}" class="hl-bouton">Annuler</a>
            <button type="submit" class="hl-bouton hl-bouton-plein" id="idValider"><i class="fas fa-check" aria-hidden="true"></i> Enregistrer</button>
        </div>
    </form>
</div></div>
@endsection

@section('script')
<script>
(function () {
    // Aperçu vivant : reprend l'image locale dès qu'elle est jugée conforme par le contrôle.
    var cibles = { logo: 'apLogo', logo_documents: 'apLogo', signature: 'apSignature', cachet: 'apCachet' };
    document.querySelectorAll('.js-fichier').forEach(function (champ) {
        champ.addEventListener('change', function () {
            setTimeout(function () {
                var f = champ.files[0], cible = document.getElementById(cibles[champ.name]);
                if (!f || !cible || champ.validationMessage) return;
                if (champ.name === 'logo' && document.querySelector('input[name="logo_documents"]').files.length) return;
                cible.src = URL.createObjectURL(f); cible.hidden = false;
                var vide = document.getElementById(cible.id + 'Vide'); if (vide) vide.hidden = true;
            }, 400);
        });
    });
    ['idSigNom', 'idSigFonction'].forEach(function (id) {
        document.getElementById(id).addEventListener('input', function () {
            var n = document.getElementById('idSigNom').value.trim(), f = document.getElementById('idSigFonction').value.trim();
            document.getElementById('apSignataire').textContent = (n || 'Nom du signataire') + (f ? ' · ' + f : '');
        });
    });
    document.getElementById('idForm').addEventListener('submit', function () { document.getElementById('idValider').disabled = true; });
})();
</script>
@endsection
