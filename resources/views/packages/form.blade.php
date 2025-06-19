@extends('layouts.backend')

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="{{url('/')}}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ url('/') }}">Admin</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ route('package.index') }}">Package</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">{{ isset($packageId) ? 'Modifier' : 'Ajouter' }} Package</a>
                </li>
            </ul>
        </div>

        <div class="row">
            {{-- Colonne Gauche : Formulaire de création/modification Livewire --}}
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">{{ isset($packageId) ? 'Modifier' : 'Ajouter' }} un package</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- C'est ici que le composant Livewire AddPackage est inclus --}}
                        @livewire('add-package', ['packageId' => $packageId ?? null])
                    </div>
                </div>
            </div>

            {{-- Colonne Droite : Prévisualisation de l'ordonnance --}}
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Prévisualisation du package</h4>
                    </div>
                    <div class="card-body p-0" style="min-height: 800px; max-height: 80vh; overflow-y: auto;">
                        {{-- C'est ici que le composant Livewire PackagePreview est inclus --}}
                        @livewire('package-preview')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            // Initialiser TOUS les selectpicker AU CHARGEMENT DE LA PAGE
            // Cela inclura les départements et les tests (car ils sont toujours présents)
            $('.selectpicker').selectpicker();

            // Écouter l'événement 'contentChanged' déclenché par Livewire
            Livewire.on('contentChanged', () => {
                console.log('Livewire event contentChanged received. Checking selectpickers...');

                // Important: Réinitialiser/rafraîchir TOUS les selectpicker.
                // Le `.selectpicker()` initialisera ceux qui ne le sont pas encore (comme les services nouvellement apparus).
                // Le `.selectpicker('refresh')` mettra à jour les options de ceux qui sont déjà initialisés.
                $('.selectpicker').selectpicker(); // Initialise ceux qui ne le sont pas
                $('.selectpicker').selectpicker('refresh'); // Rafraîchit les options et la sélection

                // Optionnel: Si des valeurs étaient déjà sélectionnées et que le DOM a été re-rendu,
                // on peut vouloir réappliquer les sélections.
                // Pour les services, si vous voulez que les valeurs sélectionnées du composant soient reflétées,
                // vous pouvez les repasser via une variable JavaScript si nécessaire.
                // Par exemple, si vous passez `selectedServices` à la vue comme une variable Blade:
                // var selectedServicesValues = @json($selectedServices ?? []);
                // $('.selectpicker[wire:model.live="selectedServices"]').selectpicker('val', selectedServicesValues);
            });

            // Alternative (peut être plus robuste si l'événement `contentChanged` ne suffit pas à tout couvrir)
            // Ce hook Livewire s'exécute chaque fois qu'un élément du DOM est mis à jour par Livewire.
            // Il s'assure que tout selectpicker mis à jour est rafraîchi.
            Livewire.hook('element.updated', (el, component) => {
                // Si l'élément mis à jour ou l'un de ses enfants est un selectpicker
                if ($(el).hasClass('selectpicker') || $(el).find('.selectpicker').length) {
                    $(el).find('.selectpicker').selectpicker(); // Initialise s'il n'est pas déjà
                    $(el).find('.selectpicker').selectpicker('refresh');
                }
            });
        });
    </script>
@endsection


