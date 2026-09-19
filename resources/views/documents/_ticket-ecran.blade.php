{{-- Tickets 80 mm imprimés depuis le navigateur : aperçu à l'écran, bande seule à l'impression. --}}
<style>
    @page { size: 80mm auto; margin: 3mm; }
    @media screen {
        html { background: #e5e7eb; }
        body { width: 80mm; margin: 64px auto 24px; padding: 4mm; background: #fff; box-shadow: 0 6px 24px rgba(0, 0, 0, .15); }
    }
    .d-outils { position: fixed; top: 0; left: 0; right: 0; z-index: 10; display: flex; justify-content: center; gap: 8px; padding: 10px; background: #111827; font-family: "Segoe UI", Arial, sans-serif; }
    .d-outils button, .d-outils a { padding: 8px 16px; border: 0; border-radius: 8px; background: #0f766e; color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; cursor: pointer; }
    .d-outils a { background: #374151; }
    @media print { .d-outils { display: none !important; } body { width: 74mm; } }
</style>
