{{--
    Documents imprimés depuis le navigateur (ordonnances, demandes d'examens) :
    à l'écran, une feuille A5 centrée et une barre d'outils ; à l'impression, la feuille seule.
--}}
<style>
    @media screen {
        html { background: #e5e7eb; }
        body { width: 148mm; min-height: 210mm; margin: 64px auto 24px; padding: 9mm 9mm 18mm; background: #fff; box-shadow: 0 6px 24px rgba(0, 0, 0, .15); position: relative; }
        .d-pied { position: absolute; bottom: 5mm; left: 9mm; right: 9mm; }
    }
    .d-outils { position: fixed; top: 0; left: 0; right: 0; z-index: 10; display: flex; justify-content: center; gap: 8px; padding: 10px; background: #111827; font-family: "Segoe UI", Arial, sans-serif; }
    .d-outils button, .d-outils a { padding: 8px 16px; border: 0; border-radius: 8px; background: #0f766e; color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
    .d-outils a { background: #374151; }
    @media print {
        .d-outils { display: none !important; }
        /* Navigateur : le pied se place en bas de la zone imprimable (le décalage négatif sert à DomPDF). */
        .d-pied { bottom: 0; }
        body { padding-bottom: 12mm; }
    }
</style>
