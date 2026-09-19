{{--
    Carte d'envoi d'image contrôlée (logo, signature, cachet).
    @include('partials.image-controlee', ['type' => 'signature', 'champ' => 'signature', 'apercu' => url|null, 'supprimable' => bool])
    Les règles viennent de App\Support\Images\ImageControlee::FICHES : le navigateur vérifie avant
    l'envoi (retour immédiat), le serveur revérifie toujours.
--}}
@php
    $fiche = \App\Support\Images\ImageControlee::fiche($type);
    $formats = strtoupper(implode(', ', array_unique($fiche['formats'])));
    $idc = 'ic-' . $champ;
@endphp
<div class="ic-carte" data-image-controlee data-fiche="{{ json_encode(['libelle' => $fiche['libelle'], 'mimes' => array_keys($fiche['formats']), 'ko' => $fiche['ko_max'], 'min' => $fiche['min'], 'max' => $fiche['max'], 'ratio' => $fiche['ratio'], 'transparence' => $fiche['transparence']]) }}">
    <div class="ic-apercu {{ $fiche['transparence'] ? 'est-damier' : '' }}">
        @if($apercu)<img src="{{ $apercu }}" alt="{{ $fiche['libelle'] }} actuel(le)" class="js-apercu">@else<span class="ic-vide js-vide"><i class="fas fa-image" aria-hidden="true"></i> Aucune image</span><img alt="" class="js-apercu" hidden>@endif
    </div>
    <div class="ic-corps">
        <strong class="ic-titre">{{ $fiche['libelle'] }}</strong>
        <dl class="ic-regles">
            <div><dt>Format</dt><dd>{{ $formats }}{{ $fiche['transparence'] ? ', fond transparent obligatoire' : '' }}</dd></div>
            <div><dt>Dimensions</dt><dd>de {{ $fiche['min'][0] }} × {{ $fiche['min'][1] }} à {{ $fiche['max'][0] }} × {{ $fiche['max'][1] }} px</dd></div>
            <div><dt>Poids</dt><dd>{{ $fiche['ko_max'] >= 1024 ? ($fiche['ko_max'] / 1024) . ' Mo' : $fiche['ko_max'] . ' Ko' }} au plus</dd></div>
        </dl>
        <p class="ic-conseil">{{ $fiche['conseil'] }}</p>
        <label class="ic-bouton" for="{{ $idc }}"><i class="fas fa-upload" aria-hidden="true"></i> {{ $apercu ? 'Remplacer' : 'Choisir une image' }}</label>
        <input type="file" id="{{ $idc }}" name="{{ $champ }}" accept="{{ implode(',', array_keys($fiche['formats'])) }}" class="ic-fichier js-fichier" @if(! empty($formulaire)) form="{{ $formulaire }}" @endif>
        <p class="ic-etat js-etat" role="status" aria-live="polite"></p>
        @if($apercu && ! empty($supprimable))
            <label class="ic-retirer"><input type="checkbox" name="supprimer[]" value="{{ $champ }}"> Retirer cette image</label>
        @endif
    </div>
</div>

@once
<style>
    .ic-carte { display: grid; grid-template-columns: 180px minmax(0, 1fr); gap: 16px; padding: 16px; border: 1px solid var(--hali-bordure); border-radius: 12px; background: #fff; }
    .ic-apercu { display: grid; place-items: center; min-height: 120px; padding: 10px; border-radius: 10px; background: #f9fafb; overflow: hidden; }
    .ic-apercu.est-damier { background-color: #fff; background-image: linear-gradient(45deg, #eef0f2 25%, transparent 25%), linear-gradient(-45deg, #eef0f2 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #eef0f2 75%), linear-gradient(-45deg, transparent 75%, #eef0f2 75%); background-size: 16px 16px; background-position: 0 0, 0 8px, 8px -8px, -8px 0; }
    .ic-apercu img { max-width: 100%; max-height: 110px; object-fit: contain; }
    .ic-vide { color: #9ca3af; font-size: .82rem; text-align: center; }
    .ic-titre { display: block; margin-bottom: 6px; color: var(--hali-encre); }
    .ic-regles { display: grid; gap: 2px; margin: 0 0 6px; font-size: .8rem; }
    .ic-regles div { display: flex; gap: 6px; }
    .ic-regles dt { min-width: 84px; color: var(--hali-discret); font-weight: 500; }
    .ic-regles dd { margin: 0; color: var(--hali-encre); }
    .ic-conseil { margin: 0 0 10px; color: var(--hali-discret); font-size: .78rem; }
    .ic-bouton { display: inline-flex; align-items: center; gap: 8px; min-height: 36px; padding: 0 14px; border: 1px solid var(--hali-bordure); border-radius: 9px; background: #fff; color: var(--hali-primaire-fonce); font-size: .85rem; font-weight: 600; cursor: pointer; }
    .ic-bouton:hover { border-color: var(--hali-primaire); background: var(--hali-primaire-pale); }
    .ic-fichier { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .ic-fichier:focus-visible + .ic-etat { outline: 2px solid var(--hali-primaire); }
    .ic-etat { min-height: 1.2em; margin: 8px 0 0; font-size: .82rem; }
    .ic-etat.est-ok { color: var(--hali-succes); }
    .ic-etat.est-ko { color: var(--hali-danger); font-weight: 600; }
    .ic-retirer { display: flex; gap: 6px; margin: 8px 0 0; color: var(--hali-danger); font-size: .82rem; font-weight: 500; }
    @media (max-width: 575.98px) { .ic-carte { grid-template-columns: 1fr; } }
</style>
<script>
/* Contrôle côté navigateur : mêmes règles que le serveur (ImageControlee), retour immédiat. */
document.addEventListener('DOMContentLoaded', function () {
    function pngTransparent(buffer) {
        var o = new Uint8Array(buffer);
        var signature = [137, 80, 78, 71, 13, 10, 26, 10];
        for (var i = 0; i < 8; i++) if (o[i] !== signature[i]) return false;
        if (o[25] === 4 || o[25] === 6) return true;
        var texte = String.fromCharCode.apply(null, o.subarray(0, Math.min(o.length, 65536)));
        var t = texte.indexOf('tRNS'), d = texte.indexOf('IDAT');
        return t !== -1 && (d === -1 || t < d);
    }
    document.querySelectorAll('[data-image-controlee]').forEach(function (carte) {
        var fiche = JSON.parse(carte.dataset.fiche), champ = carte.querySelector('.js-fichier'), etat = carte.querySelector('.js-etat');
        var apercu = carte.querySelector('.js-apercu'), vide = carte.querySelector('.js-vide');
        function dire(message, ok) { etat.textContent = message; etat.className = 'ic-etat js-etat ' + (ok ? 'est-ok' : 'est-ko'); champ.setCustomValidity(ok ? '' : message); }
        champ.addEventListener('change', function () {
            var f = champ.files[0];
            if (!f) { dire('', true); return; }
            if (fiche.mimes.indexOf(f.type) === -1) return dire('Format refusé. Accepté : ' + fiche.mimes.map(function (m) { return m.split('/')[1].toUpperCase(); }).join(', ') + '.', false);
            if (f.size > fiche.ko * 1024) return dire('Fichier de ' + Math.round(f.size / 1024) + ' Ko : ' + fiche.ko + ' Ko au plus.', false);
            var lecteur = new FileReader();
            lecteur.onload = function () {
                if (fiche.transparence && !pngTransparent(lecteur.result)) return dire('Le fond doit être transparent (PNG avec transparence).', false);
                var url = URL.createObjectURL(f), img = new Image();
                img.onload = function () {
                    var l = img.naturalWidth, h = img.naturalHeight, r = l / h;
                    if (l < fiche.min[0] || h < fiche.min[1]) return dire('Trop petite (' + l + ' × ' + h + ' px) : minimum ' + fiche.min[0] + ' × ' + fiche.min[1] + ' px.', false);
                    if (l > fiche.max[0] || h > fiche.max[1]) return dire('Trop grande (' + l + ' × ' + h + ' px) : maximum ' + fiche.max[0] + ' × ' + fiche.max[1] + ' px.', false);
                    if (r < fiche.ratio[0] || r > fiche.ratio[1]) return dire('Proportions inadaptées (' + l + ' × ' + h + ' px) : recadrez l\'image au plus près.', false);
                    apercu.src = url; apercu.hidden = false; if (vide) vide.hidden = true;
                    dire('Image conforme (' + l + ' × ' + h + ' px, ' + Math.round(f.size / 1024) + ' Ko) : enregistrez pour l\'appliquer.', true);
                };
                img.onerror = function () { dire('Image illisible ou endommagée.', false); };
                img.src = url;
            };
            lecteur.readAsArrayBuffer(f.slice(0, 65536));
        });
    });
});
</script>
@endonce
