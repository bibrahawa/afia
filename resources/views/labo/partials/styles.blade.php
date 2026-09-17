{{-- Styles propres au module : s'appuient sur les variables --aprosafe-* de admin-theme.css --}}
<style>
    .labo-flag-anormal { color: #b45309; font-weight: 600; }
    .labo-flag-critique { color: #fff; background: #c0262d; font-weight: 700; padding: 0 .35rem; border-radius: 3px; }
    tr.labo-ligne-critique { background: rgba(192, 38, 45, .07); }
    .labo-kpi { border-left: 4px solid var(--aprosafe-primary, #087f6b); }
    .labo-kpi .labo-kpi-valeur { font-size: 1.9rem; font-weight: 700; line-height: 1; color: var(--aprosafe-primary, #087f6b); }
    .labo-urgent { border-left: 4px solid #c0262d !important; }
    .labo-tube { display: inline-block; width: 12px; height: 12px; border-radius: 50%; border: 1px solid rgba(0,0,0,.25); vertical-align: middle; margin-right: 4px; }
    .labo-tube-violet { background: #7b3fa0; } .labo-tube-bleu { background: #3b82c4; } .labo-tube-rouge { background: #c0262d; }
    .labo-tube-jaune { background: #e8b923; } .labo-tube-vert { background: #2f8f4e; } .labo-tube-gris { background: #8a8f98; }
    .labo-tube-pot, .labo-tube-ecouvillon, .labo-tube-lame { background: #fff; }
    .labo-anteriorite { font-size: .8rem; color: #6c757d; }
    .labo-saisie input.form-control, .labo-saisie select.form-select { max-width: 180px; }
    .labo-scanner { font-size: 1.4rem; letter-spacing: .15em; }
</style>
