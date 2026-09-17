{{--
    Sélecteur de patient réutilisable.
    @include('assurance.partials.choix-patient', ['id' => 'adherent', 'libelle' => 'Adhérent'])

    Recherche : nom / identifiant parmi les patients suivis par l'établissement ;
    numéro complet (9 chiffres) ou identifiant national exact pour les autres.
    Le terme saisi est transmis (preuve_identite) : le serveur vérifie qu'un patient
    non suivi a bien été trouvé par son numéro ou son identifiant exact.
--}}
@php $id = $id ?? 'patient'; @endphp
<div class="assurance-choix-patient mb-2 position-relative" data-url="{{ route('assurance.patients.recherche') }}">
    <label class="form-label">{{ $libelle ?? 'Patient' }} <span class="text-danger">*</span></label>
    <input type="hidden" name="patient_id" class="js-patient-id" required>
    <input type="hidden" name="preuve_identite" class="js-preuve">
    <input type="text" class="form-control js-recherche" autocomplete="off"
           placeholder="Nom, téléphone (9 chiffres) ou identifiant national" id="{{ $id }}-recherche">
    <div class="list-group position-absolute shadow js-resultats" style="z-index: 1060; max-width: 460px"></div>
    <div class="small mt-1 js-choisi text-success"></div>
</div>

