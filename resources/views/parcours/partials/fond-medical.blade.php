{{--
    Fond médical des pages publiques (option A : pictogrammes en filigrane).
    @include('partials.fond-medical')                                    — à placer juste avant </head>
    @include('partials.fond-medical', ['transparents' => '.rdv-shell'])  — conteneurs à rendre transparents sur téléphone
    Motif vectoriel intégré (moins de 1 Ko), aucune image à télécharger : net sur tout écran,
    se répète quelle que soit la hauteur de la page. Plus pâle sur téléphone pour la lisibilité.
--}}
<style>
    html body {
        background-color: #f4f6f6;
        background-image: url("data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22140%22%20height%3D%22140%22%20viewBox%3D%220%200%20140%20140%22%3E%3Cg%20fill%3D%22none%22%20stroke%3D%22%230f766e%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20opacity%3D%22.13%22%3E%3Cpath%20d%3D%22M20%2012v16M12%2020h16%22%2F%3E%3Crect%20x%3D%2282%22%20y%3D%2216%22%20width%3D%2230%22%20height%3D%2212%22%20rx%3D%226%22%20transform%3D%22rotate%28-30%2097%2022%29%22%2F%3E%3Cpath%20d%3D%22M30%2092c-6-7-16-2-12%206%203%205%2012%2011%2012%2011s9-6%2012-11c4-8-6-13-12-6z%22%2F%3E%3Cpath%20d%3D%22M78%2096h10l5-10%206%2020%205-10h14%22%2F%3E%3Cpath%20d%3D%22M104%2058c0%2012%206%2018%2014%2018s14-6%2014-18%22%2F%3E%3Ccircle%20cx%3D%22118%22%20cy%3D%2284%22%20r%3D%224%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E");
        background-repeat: repeat;
        background-size: 140px 140px;
    }
    /* Grand écran à souris : le motif reste en place pendant le défilement. */
    @media (min-width: 1024px) and (hover: hover) { html body { background-attachment: fixed; } }
    @media (max-width: 767.98px) {
        html body { background-image: url("data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22140%22%20height%3D%22140%22%20viewBox%3D%220%200%20140%20140%22%3E%3Cg%20fill%3D%22none%22%20stroke%3D%22%230f766e%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20opacity%3D%22.08%22%3E%3Cpath%20d%3D%22M20%2012v16M12%2020h16%22%2F%3E%3Crect%20x%3D%2282%22%20y%3D%2216%22%20width%3D%2230%22%20height%3D%2212%22%20rx%3D%226%22%20transform%3D%22rotate%28-30%2097%2022%29%22%2F%3E%3Cpath%20d%3D%22M30%2092c-6-7-16-2-12%206%203%205%2012%2011%2012%2011s9-6%2012-11c4-8-6-13-12-6z%22%2F%3E%3Cpath%20d%3D%22M78%2096h10l5-10%206%2020%205-10h14%22%2F%3E%3Cpath%20d%3D%22M104%2058c0%2012%206%2018%2014%2018s14-6%2014-18%22%2F%3E%3Ccircle%20cx%3D%22118%22%20cy%3D%2284%22%20r%3D%224%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E"); background-size: 110px 110px; }
        @if(! empty($transparents)) {{ $transparents }} { background-color: transparent !important; } @endif
    }
    @media print { html body { background: #fff !important; } }
</style>
