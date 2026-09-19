{{-- Pied de page du back-office : sobre, au nom de la plateforme. --}}
<footer class="footer hl-pied">
    <div class="container-fluid">
        <span>© {{ date('Y') }} <strong>Hali</strong> · Plateforme de gestion clinique</span>
        <span class="hl-pied-droite">
            @if(\App\Support\EtablissementContext::current())
                {{ \App\Support\EtablissementContext::current()->nom }}
            @endif
        </span>
    </div>
</footer>
