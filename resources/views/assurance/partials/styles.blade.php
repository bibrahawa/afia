{{-- Style commun des écrans d'assurance (limité aux pages marquées « as-page »). --}}
@once
<style>
    .as-fil { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 4px; font-size: .8rem; }
    .as-fil a { color: var(--hali-primaire); font-weight: 600; text-decoration: none; }
    .as-fil a:hover { text-decoration: underline; }
    .as-fil i { color: #cbd5e1; font-size: .6rem; }
    .as-fil span { color: var(--hali-discret); }
    .as-entete h1 { overflow-wrap: anywhere; }

    .as-onglets { display: flex; gap: 4px; overflow-x: auto; width: fit-content; max-width: 100%; margin-bottom: 18px; padding: 4px; border-radius: 12px; background: #f3f4f6; }
    .as-onglets a { display: inline-flex; align-items: center; gap: 7px; flex: none; min-height: 38px; padding: 0 14px; border-radius: 9px; color: var(--hali-texte); font-size: .86rem; font-weight: 650; text-decoration: none; white-space: nowrap; }
    .as-onglets a i { color: #9ca3af; }
    .as-onglets a:hover { color: var(--hali-primaire-fonce); }
    .as-onglets a.est-actif { background: #fff; color: var(--hali-primaire-fonce); box-shadow: 0 1px 3px rgba(0, 0, 0, .08); }
    .as-onglets a.est-actif i { color: var(--hali-primaire); }

    /* Finitions des composants Bootstrap existants, sans changer leur balisage */
    .as-page > p.text-muted { max-width: 820px; margin-bottom: 16px; font-size: .9rem; line-height: 1.55; }
    .as-page .card { overflow: hidden; }
    .as-page .card + .card { margin-top: 16px; }
    .as-page .btn { border-radius: 9px; font-weight: 600; }
    .as-page .btn-primary { background: var(--hali-primaire); border-color: var(--hali-primaire); }
    .as-page .btn-primary:hover { background: var(--hali-primaire-fonce); border-color: var(--hali-primaire-fonce); }
    .as-page .btn-outline-primary { border-color: var(--hali-bordure); color: var(--hali-primaire-fonce); background: #fff; }
    .as-page .btn-outline-primary:hover { border-color: var(--hali-primaire); background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); }
    .as-page .btn-outline-danger { border-color: #fecaca; color: var(--hali-danger); background: #fff; }
    .as-page .btn-link { color: var(--hali-primaire); font-weight: 600; text-decoration: none; }
    .as-page .form-label, .as-page label { color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .as-page .form-control, .as-page .form-select { border-radius: 9px; }
    .as-page .form-control:focus, .as-page .form-select:focus { border-color: var(--hali-primaire); box-shadow: 0 0 0 3px var(--hali-primaire-pale); }
    .as-page .alert { border: 0; border-radius: 12px; font-size: .9rem; }
    .as-page .alert-warning { background: var(--hali-alerte-pale); color: #78350f; }
    .as-page .alert-info { background: var(--hali-info-pale); color: #1e3a8a; }
    .as-page .alert-danger { background: var(--hali-danger-pale); color: #7f1d1d; }
    .as-page .alert-success { background: var(--hali-succes-pale); color: #14532d; }
    .as-page .list-group-item { border-color: #f3f4f6; }
    .as-page details > summary { font-weight: 650; }
    .as-page .table-sm > :not(caption) > * > * { padding: 8px 12px; }
    .as-page .pagination { margin: 14px 0 0; }
    .modal-content { border: 0; border-radius: 14px; }
    .modal-header, .modal-footer { border-color: #f3f4f6; }

    /* Montants : chiffres alignés */
    .as-page .text-end, .as-montant { font-variant-numeric: tabular-nums; }

    @media print { .as-onglets, .as-fil { display: none !important; } }
</style>
@endonce
