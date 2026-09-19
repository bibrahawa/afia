@extends('layouts.backend')

@php
    $initiale = mb_strtoupper(mb_substr($identite->nom, 0, 1));
    $logo = $identite->logoWeb();
    $lienAffiche = preg_replace('#^https?://#', '', $lien);
    // Coupure propre à l'impression : « domaine/rdv/ » puis « nom-de-la-clinique ».
    $urlDebut = \Illuminate\Support\Str::beforeLast($lienAffiche, '/') . '/';
    $urlFin = \Illuminate\Support\Str::afterLast($lienAffiche, '/');
    $messageWhatsapp = "Prenez rendez-vous en ligne avec {$identite->nom} : {$lien}";
@endphp

@section('style')
<style>
    .af-grille { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 16px; align-items: start; }
    .af-outils { display: grid; gap: 12px; padding: 18px; }
    .af-lien { display: flex; gap: 6px; }
    .af-lien input { font-family: "SF Mono", Consolas, monospace; font-size: .82rem; }
    .af-apercu { padding: 18px; background: #eef1f1; border-radius: var(--hali-rayon); overflow: auto; }
    .af-onglets { display: flex; gap: 4px; margin-bottom: 12px; }

    /* ---------- Affiche A4 (210 × 297 mm) ---------- */
    .af-feuille { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; box-shadow: 0 4px 24px rgba(0, 0, 0, .12); color: #111827; font-family: "Segoe UI", Roboto, Arial, sans-serif; }
    .af-affiche { display: flex; flex-direction: column; align-items: center; padding: 22mm 20mm 16mm; text-align: center; }
    .af-marque { display: flex; align-items: center; gap: 12px; }
    .af-logo { display: grid; place-items: center; width: 18mm; height: 18mm; border-radius: 5mm; background: #0f766e; color: #fff; font-size: 30pt; font-weight: 800; }
    .af-logo img { max-width: 100%; max-height: 100%; }
    .af-nom { font-size: 24pt; font-weight: 800; letter-spacing: -.01em; }
    .af-titre { margin: 14mm 0 3mm; font-size: 38pt; font-weight: 850; line-height: 1.05; letter-spacing: -.02em; color: #0f766e; }
    .af-sous { margin: 0 0 12mm; color: #4b5563; font-size: 16pt; }
    .af-qr { display: grid; place-items: center; width: 100mm; height: 100mm; padding: 6mm; border: 1.2mm solid #0f766e; border-radius: 8mm; }
    .af-qr svg { width: 100%; height: 100%; }
    .af-scan { margin: 6mm 0 2mm; font-size: 15pt; font-weight: 700; }
    .af-url { font-family: "SF Mono", Consolas, monospace; font-size: 13pt; color: #0f766e; overflow-wrap: anywhere; }
    .af-etapes { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6mm; width: 100%; margin-top: 12mm; }
    .af-etape { padding: 5mm 4mm; border-radius: 4mm; background: #f0fdfa; font-size: 12pt; line-height: 1.3; }
    .af-etape b { display: grid; place-items: center; width: 10mm; height: 10mm; margin: 0 auto 3mm; border-radius: 50%; background: #0f766e; color: #fff; font-size: 14pt; }
    .af-pied { margin-top: auto; padding-top: 10mm; color: #4b5563; font-size: 12pt; }
    .af-pied strong { color: #111827; font-size: 15pt; }

    /* ---------- Cartes 85 × 55 mm, 10 par feuille ---------- */
    .af-cartes { display: grid; grid-template-columns: repeat(2, 85mm); grid-auto-rows: 55mm; justify-content: center; align-content: start; gap: 0; padding: 11mm 0; }
    .af-carte { display: grid; grid-template-columns: 1fr 30mm; align-items: center; gap: 4mm; padding: 5mm; border: .2mm dashed #d1d5db; }
    .af-carte-nom { font-size: 11pt; font-weight: 800; line-height: 1.15; }
    .af-carte-titre { margin: 2mm 0 1.5mm; color: #0f766e; font-size: 9.5pt; font-weight: 700; }
    .af-carte-url { font-family: "SF Mono", Consolas, monospace; font-size: 6.5pt; color: #4b5563; overflow-wrap: anywhere; }
    .af-carte-tel { margin-top: 1.5mm; font-size: 8pt; font-weight: 700; }
    .af-carte-qr svg { width: 30mm; height: 30mm; }

    [data-feuille][hidden] { display: none !important; }
    @media (max-width: 1199.98px) { .af-grille { grid-template-columns: 1fr; } .af-apercu { order: 2; } }

    @media print {
        @page { size: A4; margin: 0; }
        body * { visibility: hidden !important; }
        .af-imprimable, .af-imprimable * { visibility: visible !important; }
        .af-imprimable { position: absolute; left: 0; top: 0; box-shadow: none !important; }
        .af-carte { border-color: #e5e7eb; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Affiche et QR code</h1>
            <p>La prise de rendez-vous en ligne ne sert que si vos patients la trouvent : affichez-la en salle d'attente, donnez une carte à chaque patient.</p>
        </div>
    </header>

    <div class="af-grille">
        <div class="af-apercu">
            <div class="af-onglets hl-puces" role="tablist">
                <button type="button" class="hl-puce est-actif" data-montrer="affiche">Affiche A4</button>
                <button type="button" class="hl-puce" data-montrer="cartes">Cartes de visite (×10)</button>
            </div>

            {{-- Affiche --}}
            <div class="af-feuille af-affiche" data-feuille="affiche">
                <div class="af-marque">
                    <span class="af-logo">@if($logo)<img src="{{ $logo }}" alt="">@else{{ $initiale }}@endif</span>
                    <span class="af-nom">{{ $identite->nom }}</span>
                </div>
                <h2 class="af-titre">Prenez rendez-vous<br>en ligne</h2>
                <p class="af-sous">Sans attendre au téléphone, 24 h/24, 7 j/7</p>
                <div class="af-qr js-qr" data-taille="grand" aria-label="QR code vers la prise de rendez-vous"></div>
                <p class="af-scan">Scannez avec l'appareil photo de votre téléphone</p>
                <p class="af-url">{{ $urlDebut }}<wbr>{{ $urlFin }}</p>
                <div class="af-etapes">
                    <div class="af-etape"><b>1</b>Choisissez le motif et le médecin</div>
                    <div class="af-etape"><b>2</b>Choisissez le jour et l'heure</div>
                    <div class="af-etape"><b>3</b>Recevez la confirmation par SMS</div>
                </div>
                @if($identite->contact || $identite->adresse)
                    <p class="af-pied">@if($identite->contact)Pas de smartphone ? Appelez-nous : <strong>{{ $identite->contact }}</strong><br>@endif{{ $identite->adresse }}</p>
                @endif
            </div>

            {{-- Cartes --}}
            <div class="af-feuille af-cartes" data-feuille="cartes" hidden>
                @for($i = 0; $i < 10; $i++)
                    <div class="af-carte">
                        <div>
                            <div class="af-carte-nom">{{ $identite->nom }}</div>
                            <div class="af-carte-titre">Prenez rendez-vous en ligne</div>
                            <div class="af-carte-url">{{ $urlDebut }}<br>{{ $urlFin }}</div>
                            @if($identite->contact)<div class="af-carte-tel">{{ $identite->contact }}</div>@endif
                        </div>
                        <div class="af-carte-qr js-qr"></div>
                    </div>
                @endfor
            </div>
        </div>

        <section class="hl-bloc">
            <h2 class="hl-bloc-titre">Diffuser</h2>
            <div class="af-outils">
                <button type="button" class="hl-bouton hl-bouton-plein" id="afImprimer"><i class="fas fa-print" aria-hidden="true"></i> <span>Imprimer l'affiche</span></button>
                <div>
                    <label class="d-block mb-1" style="font-size:.83rem; font-weight:650; color:var(--hali-encre)" for="afLien">Lien de prise de rendez-vous</label>
                    <div class="af-lien">
                        <input type="text" id="afLien" class="form-control" value="{{ $lien }}" readonly>
                        <button type="button" class="hl-bouton" id="afCopier" title="Copier le lien"><i class="fas fa-copy" aria-hidden="true"></i></button>
                    </div>
                </div>
                <a href="https://wa.me/?text={{ rawurlencode($messageWhatsapp) }}" target="_blank" rel="noopener" class="hl-bouton"><i class="fab fa-whatsapp" aria-hidden="true"></i> Partager sur WhatsApp</a>
                <a href="{{ $lien }}" target="_blank" rel="noopener" class="hl-bouton"><i class="fas fa-external-link-alt" aria-hidden="true"></i> Ouvrir la page patient</a>
                <p class="hl-note hl-note-info mb-0" style="font-size:.82rem"><span>Idées : sur les ordonnances et les reçus, en statut WhatsApp, sur la page Facebook de la clinique. Testez le QR code avec votre téléphone avant d'imprimer.</span></p>
            </div>
        </section>
    </div>
</div></div>
@endsection

@section('script')
{{-- Génère le QR en SVG (net à toutes les tailles d'impression). --}}
<script src="{{ asset('assets/js/vendor/qrcode-generator-1.4.4.js') }}"></script>
<script>
(function () {
    var lien = @json($lien);
    var feuille = 'affiche';

    function dessiner() {
        if (typeof qrcode !== 'function') {
            document.querySelectorAll('.js-qr').forEach(function (el) { el.innerHTML = '<small style="color:#b91c1c">QR indisponible : connexion internet requise pour le générer.</small>'; });
            return;
        }
        var qr = qrcode(0, 'M');   // correction d'erreur moyenne : lisible même un peu abîmé
        qr.addData(lien); qr.make();
        var svg = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
        document.querySelectorAll('.js-qr').forEach(function (el) { el.innerHTML = svg; });
    }

    document.querySelectorAll('[data-montrer]').forEach(function (b) {
        b.addEventListener('click', function () {
            feuille = b.dataset.montrer;
            document.querySelectorAll('[data-montrer]').forEach(function (x) { x.classList.toggle('est-actif', x === b); });
            document.querySelectorAll('[data-feuille]').forEach(function (f) { f.hidden = f.dataset.feuille !== feuille; });
            document.querySelector('#afImprimer span').textContent = feuille === 'affiche' ? "Imprimer l'affiche" : 'Imprimer les cartes';
        });
    });

    document.getElementById('afImprimer').addEventListener('click', function () {
        var f = document.querySelector('[data-feuille="' + feuille + '"]');
        f.classList.add('af-imprimable'); window.print();
        setTimeout(function () { f.classList.remove('af-imprimable'); }, 500);
    });

    document.getElementById('afCopier').addEventListener('click', function () {
        var champ = document.getElementById('afLien'), b = this;
        (navigator.clipboard ? navigator.clipboard.writeText(champ.value) : Promise.reject())
            .catch(function () { champ.select(); document.execCommand('copy'); })
            .finally(function () { b.innerHTML = '<i class="fas fa-check"></i>'; setTimeout(function () { b.innerHTML = '<i class="fas fa-copy"></i>'; }, 1500); });
    });

    dessiner();
})();
</script>
@endsection
