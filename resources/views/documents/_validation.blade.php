{{--
    Bloc de validation d'un document : cachet de la clinique, signature, nom et fonction.
    @include('documents._validation', [
        'pdf' => true|false,              // DomPDF (chemins) ou navigateur (data: URI)
        'etiquette' => 'La direction',     // titre du bloc
        'avecSignature' => true,           // false : cachet seul (ex. reçu signé par la caisse)
        'nom' => '…', 'fonction' => '…',   // facultatifs : sinon signataire de la clinique
        'signatureMedecin' => $employee,   // facultatif : signature PROPRE du médecin à la place
    ])
    Signature et cachet ne sont jamais servis par une adresse publique.
--}}
@php
    $pdf = $pdf ?? true;
    $avecSignature = $avecSignature ?? true;
    $medecin = $signatureMedecin ?? null;
    if ($medecin) {
        $imgSignature = $pdf ? \App\Support\Etablissement\IdentiteDocument::signatureMedecinPdf($medecin) : \App\Support\Etablissement\IdentiteDocument::signatureMedecinData($medecin);
        $imgCachet = null;   // un document médical engage le médecin, pas la clinique
    } else {
        $imgSignature = $avecSignature ? ($pdf ? $identite->signaturePdf() : $identite->signatureData()) : null;
        $imgCachet = $pdf ? $identite->cachetPdf() : $identite->cachetData();
    }
    $nomSignataire = $nom ?? ($medecin ? $medecin->nom_affiche : $identite->signataireNom);
    $fonctionSignataire = $fonction ?? ($medecin ? null : $identite->signataireFonction);
@endphp
<div class="d-valide">
    <div class="d-etiquette">{{ $etiquette ?? 'Signature' }}</div>
    <div class="d-valide-zone">
        @if($imgCachet)<img src="{{ $imgCachet }}" alt="" class="d-valide-cachet">@endif
        @if($imgSignature)<img src="{{ $imgSignature }}" alt="" class="d-valide-signature">@endif
    </div>
    <div class="d-valide-ligne"></div>
    @if($nomSignataire)<div class="d-petit"><strong>{{ $nomSignataire }}</strong></div>@endif
    @if($fonctionSignataire)<div class="d-petit">{{ $fonctionSignataire }}</div>@endif
</div>
