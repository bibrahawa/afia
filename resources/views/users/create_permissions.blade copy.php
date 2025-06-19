@extends('layouts.backend')

@section('content')


  <div class="pagetitle">
      <h1>Liste des utilisateurs</h1>
      <nav>
          <ol class="breadcrumb">
          <li class="breadcrumb-item active"> <a href="#">Listes des utilisateurs</a></li>
          </ol>
      </nav>
  </div>

  <style>
    .toggle-password {
      cursor: pointer;
      position: absolute;
      right: 15px;
      top: 77%;
      transform: translateY(-50%);
    }
  </style>


  <section class="section">

    <div class="row">
        {{-- <div class="mb-3">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> Retour à la liste
            </a>
        </div>   --}}
        <div class="card shadow-lg">
          <div class="bg-primary text-white text-center">
              <h5><i class="bi bi-files"></i> Permissions </h5>
          </div>
            <form action="{{ route('users.store_permissions', $user->id) }}" method="POST" enctype="multipart/form-data" id="AddBtn">
                @csrf
                <div class="row">
                    <h5 color="black mb-3">Tableaux de board</h5>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="tableau_bord"><i class="fas fa-list"></i>Tableau de board</label>
                        <input type="checkbox" id="tableau_bord" name="tableau_bord" {{$user->hasPermissionTo('tableau_bord') ?
                                                            'checked' :''}}
                                                            >
                    </div>
                <hr>
                    <h5 color="black mb-3">Entreprises</h5>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="liste_entreprise"><i class="fas fa-list"></i>Liste des entreprise</label>
                        <input type="checkbox" id="liste_entreprise" name="liste_entreprise" {{$user->hasPermissionTo('liste_entreprise') ?
                                                            'checked' :''}}>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="creer_entreprise"><i class="fas fa-plus"></i>creer une entreprise</label>
                        <input type="checkbox" id="creer_entreprise" name="creer_entreprise" {{$user->hasPermissionTo('creer_entreprise') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modifier_entreprise"><i class="fas fa-edit"></i>Modifier une entreprise</label>
                        <input type="checkbox" id="modifier_entreprise" name="modifier_entreprise" {{$user->hasPermissionTo('modifier_entreprise') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="afficher_entreprise"><i class="fas fa-eye"></i>Afficher une entreprise</label>
                        <input type="checkbox" id="afficher_entreprise" name="afficher_entreprise" {{$user->hasPermissionTo('afficher_entreprise') ?
                                                            'checked' :''}} >
                    </div>
                    <hr>
                    <h5 color="black mb-3">Employeurs</h5>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="liste_employeur"><i class="fas fa-list"></i>Liste des employeurs</label>
                        <input type="checkbox" id="liste_employeur" name="liste_employeur" {{$user->hasPermissionTo('liste_employeur') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="creer_employeur"><i class="fas fa-plus"></i>creer un employeur</label>
                        <input type="checkbox" id="creer_employeur" name="creer_employeur" {{$user->hasPermissionTo('creer_employeur') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modifier_employeur"><i class="fas fa-edit"></i>Modifier un employeur</label>
                        <input type="checkbox" id="modifier_employeur" name="modifier_employeur" {{$user->hasPermissionTo('modifier_employeur') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="afficher_employeur"><i class="fas fa-eye"></i>Afficher un Employeur</label>
                        <input type="checkbox" id="afficher_employeur" name="afficher_employeur" {{$user->hasPermissionTo('afficher_employeur') ?
                                                            'checked' :''}} >
                    </div>
                    <hr>
                    <h5 color="black mb-3">Employé(es)</h5>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="liste_employe"><i class="fas fa-list"></i>Liste des Employés(ées)</label>
                        <input type="checkbox" id="liste_employe" name="liste_employe" {{$user->hasPermissionTo('liste_employe') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="creer_employe"><i class="fas fa-plus"></i>creer un Employé(es)</label>
                        <input type="checkbox" id="creer_employe" name="creer_employe" {{$user->hasPermissionTo('creer_employe') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modifier_employe"><i class="fas fa-edit"></i>Modifier un Employé(es)</label>
                        <input type="checkbox" id="modifier_employe" name="modifier_employe" {{$user->hasPermissionTo('modifier_employe') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="afficher_employe"><i class="fas fa-eye"></i>Afficher un Employé(és)</label>
                        <input type="checkbox" id="afficher_employe" name="afficher_employe" {{$user->hasPermissionTo('afficher_employe') ?
                                                            'checked' :''}} >
                    </div>
                    <hr>
                    <h5 color="black mb-3">Gestion des charges sociales</h5>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="liste_charge"><i class="fas fa-list"></i>Liste des charges sociales</label>
                        <input type="checkbox" id="liste_charge" name="liste_charge" {{$user->hasPermissionTo('liste_charge') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="creer_charge"><i class="fas fa-plus"></i>creer une charge sociale</label>
                        <input type="checkbox" id="creer_charge" name="creer_charge" {{$user->hasPermissionTo('creer_charge') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modifier_charge"><i class="fas fa-edit"></i>Modifier une charge sociale</label>
                        <input type="checkbox" id="modifier_charge" name="modifier_charge" {{$user->hasPermissionTo('modifier_charge') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="afficher_charge"><i class="fas fa-eye"></i>Afficher une charge sociale</label>
                        <input type="checkbox" id="afficher_charge" name="afficher_charge" {{$user->hasPermissionTo('afficher_charge') ?
                                                            'checked' :''}} >
                    </div>
                    <hr>
                    <h5 color="black mb-3">Comptabilites</h5>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="liste_comptabilite"><i class="fas fa-list"></i>Liste des Comptabilites</label>
                        <input type="checkbox" id="liste_comptabilite" name="liste_comptabilite" {{$user->hasPermissionTo('liste_comptabilite') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="creer_comptabilite"><i class="fas fa-plus"></i>creer une Comptabilite</label>
                        <input type="checkbox" id="creer_comptabilite" name="creer_comptabilite" {{$user->hasPermissionTo('creer_comptabilite') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modifier_comptabilite"><i class="fas fa-edit"></i>Modifier une Comptabilite</label>
                        <input type="checkbox" id="modifier_comptabilite" name="modifier_comptabilite" {{$user->hasPermissionTo('modifier_comptabilite') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="afficher_comptabilite"><i class="fas fa-eye"></i>Afficher une Comptabilite</label>
                        <input type="checkbox" id="afficher_comptabilite" name="afficher_comptabilite" {{$user->hasPermissionTo('afficher_comptabilite') ?
                                                            'checked' :''}} >
                    </div>
                    <hr>
                    <h5 color="black mb-3">Gestion de contrats de travail</h5>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="liste_contrat"><i class="fas fa-list"></i>Liste des contrats de travaille</label>
                        <input type="checkbox" id="liste_contrat" name="liste_contrat" {{$user->hasPermissionTo('liste_contrat') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="creer_contrat"><i class="fas fa-plus"></i>creer un contrat de travaille</label>
                        <input type="checkbox" id="creer_contrat" name="creer_contrat" {{$user->hasPermissionTo('creer_contrat') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modifier_contrat"><i class="fas fa-edit"></i>Modifier un contrat de travaille</label>
                        <input type="checkbox" id="modifier_contrat" name="modifier_contrat" {{$user->hasPermissionTo('modifier_contrat') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="afficher_contrat"><i class="fas fa-eye"></i>Afficher un contrat de travaille</label>
                        <input type="checkbox" id="afficher_contrat" name="afficher_contrat" {{$user->hasPermissionTo('afficher_contrat') ?
                                                            'checked' :''}} >
                    </div>
                    <hr>
                    <h5 color="black mb-3">Gestion de suivi de conger</h5>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="liste_conge"><i class="fas fa-list"></i>Liste des suivis de conger</label>
                        <input type="checkbox" id="liste_conge" name="liste_conge" {{$user->hasPermissionTo('liste_conge') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="creer_conge"><i class="fas fa-plus"></i>creer un suivi de conger</label>
                        <input type="checkbox" id="creer_conge" name="creer_conge" {{$user->hasPermissionTo('creer_conge') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modifier_conge"><i class="fas fa-edit"></i>Modifier un suivi de conger</label>
                        <input type="checkbox" id="modifier_conge" name="modifier_conge" {{$user->hasPermissionTo('modifier_conge') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="afficher_conge"><i class="fas fa-eye"></i>Afficher un suivi de conger</label>
                        <input type="checkbox" id="afficher_conge" name="afficher_conge" {{$user->hasPermissionTo('afficher_conge') ?
                                                            'checked' :''}} >
                    </div>
                    <hr>
                    <h5 color="black mb-3">Gestion d'abscence</h5>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="liste_absence"><i class="fas fa-list"></i>Liste des Abscences</label>
                        <input type="checkbox" id="liste_absence" name="liste_absence" {{$user->hasPermissionTo('liste_absence') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="creer_absence"><i class="fas fa-plus"></i>creer une Absence</label>
                        <input type="checkbox" id="creer_absence" name="creer_absence" {{$user->hasPermissionTo('creer_absence') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modifier_absence"><i class="fas fa-edit"></i>Modifier une Abscence</label>
                        <input type="checkbox" id="modifier_absence" name="modifier_absence" {{$user->hasPermissionTo('modifier_absence') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="afficher_absence"><i class="fas fa-eye"></i>Afficher une Absence</label>
                        <input type="checkbox" id="afficher_absence" name="afficher_absence" {{$user->hasPermissionTo('afficher_absence') ?
                                                            'checked' :''}} >
                    </div>
                    <hr>
                    <h5 color="black mb-3">Gestion Indemnites</h5>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="liste_indemnite"><i class="fas fa-list"></i>Liste des Indemnites</label>
                        <input type="checkbox" id="liste_indemnite" name="liste_indemnite" {{$user->hasPermissionTo('liste_indemnite') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="creer_indemnite"><i class="fas fa-plus"></i>creer une Indemnite</label>
                        <input type="checkbox" id="creer_indemnite" name="creer_indemnite" {{$user->hasPermissionTo('creer_indemnite') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modifier_indemnite"><i class="fas fa-edit"></i>Modifier une Indemnite</label>
                        <input type="checkbox" id="modifier_indemnite" name="modifier_indemnite" {{$user->hasPermissionTo('modifier_indemnite') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="afficher_indemnite"><i class="fas fa-eye"></i>Afficher une Indemnite</label>
                        <input type="checkbox" id="afficher_indemnite" name="afficher_indemnite" {{$user->hasPermissionTo('afficher_indemnite') ?
                                                            'checked' :''}} >
                    </div>
                    <hr>
                    <h5 color="black mb-3">Rapports</h5>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="rapport_rh"><i class="fas fa-cubes"></i>Rapports RH</label>
                        <input type="checkbox" id="rapport_rh" name="rapport_rh" {{$user->hasPermissionTo('rapport_rh') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="rapport_paie"><i class="fas fa-chart-bar"></i>Rapports Paie</label>
                        <input type="checkbox" id="rapport_paie" name="rapport_paie" {{$user->hasPermissionTo('rapport_paie') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="rapport_comptabilites"><i class="fas fa-cash-register"></i>Comptabilité</label>
                        <input type="checkbox" id="rapport_comptabilites" name="rapport_comptabilites" {{$user->hasPermissionTo('rapport_comptabilites') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="rapport_entreprise"><i class="fas fa-chart-pie"></i>Entreprises</label>
                        <input type="checkbox" id="rapport_entreprise" name="rapport_entreprise" {{$user->hasPermissionTo('rapport_entreprise') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="rapport_employe"><i class="fas fa-chart-area"></i>Employés</label>
                        <input type="checkbox" id="rapport_employe" name="rapport_employe" {{$user->hasPermissionTo('rapport_employe') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="rapport_indemnites"><i class="fas fa-file-invoice-dollar"></i>Indemnites</label>
                        <input type="checkbox" id="rapport_indemnites" name="rapport_indemnites" {{$user->hasPermissionTo('rapport_indemnites') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="rapport_contrat"><i class="fas fa-briefcase"></i>Contrats</label>
                        <input type="checkbox" id="rapport_contrat" name="rapport_contrat" {{$user->hasPermissionTo('rapport_contrat') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label" for="rapport_absence"><i class="fas fa-user-times"></i>Absences</label>
                        <input type="checkbox" id="rapport_absence" name="rapport_absence" {{$user->hasPermissionTo('rapport_absence') ?
                                                            'checked' :''}} >
                    </div>
                    <hr>
                    <h5 color="black mb-3">Gestion des utilisateurs</h5>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="liste_utilisateur"><i class="fas fa-list"></i>Liste des utilisateurs</label>
                        <input type="checkbox" id="liste_utilisateur" name="liste_utilisateur" {{$user->hasPermissionTo('liste_utilisateur') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="creer_utilisateur"><i class="fas fa-plus"></i>creer un utilisateur</label>
                        <input type="checkbox" id="creer_utilisateur" name="creer_utilisateur" {{$user->hasPermissionTo('creer_utilisateur') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="modifier_utilisateur"><i class="fas fa-edit"></i>Modifier un utilisateur</label>
                        <input type="checkbox" id="modifier_utilisateur" name="modifier_utilisateur" {{$user->hasPermissionTo('modifier_utilisateur') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="afficher_utilisateur"><i class="fas fa-eye"></i>Afficher un utilisateur</label>
                        <input type="checkbox" id="afficher_utilisateur" name="afficher_utilisateur" {{$user->hasPermissionTo('afficher_utilisateur') ?
                                                            'checked' :''}} >
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-10">
                            <button type="submit" class="btn btn-primary" id="SaveBtn"><i class="bi bi-save"></i> Enregistrer</button>
                        </div>
                    </div>
                </div>
            </form>
      </div>
  </div>
  </section>

@endsection
