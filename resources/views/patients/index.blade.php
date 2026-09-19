@extends('layouts.backend')

@php
    // Le téléphone est porté par le patient ; l'ancien compte utilisateur sert de repli.
    $telephoneDe = fn ($p) => $p->telephone ?: ($p->user->phone ?? null);
    $initialesDe = function ($p) {
        return mb_strtoupper(mb_substr((string) $p->first_name, 0, 1) . mb_substr((string) $p->last_name, 0, 1)) ?: 'P';
    };
    $totalPatients = method_exists($patients, 'total') ? $patients->total() : $patients->count();
@endphp

@section('style')
<style>
    /* ------------------------------------------------ Liste des patients (pt-) */
    .pt-recherche { position: relative; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .pt-recherche i { position: absolute; left: 32px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .pt-recherche input { width: 100%; min-height: 44px; padding-left: 40px; padding-right: 40px; font-size: .95rem; }
    .pt-recherche .pt-effacer { position: absolute; right: 32px; top: 50%; transform: translateY(-50%); color: #9ca3af; }
    .pt-recherche .pt-effacer:hover { color: var(--hali-danger); }
    .pt-resultat { padding: 10px 18px; border-bottom: 1px solid #f3f4f6; color: var(--hali-discret); font-size: .85rem; }

    .pt-entetes, .patient-card {
        display: grid; align-items: center; gap: 14px;
        grid-template-columns: minmax(220px, 1.6fr) minmax(120px, .9fr) minmax(140px, 1.1fr) minmax(110px, .7fr) auto;
        padding: 12px 18px;
    }
    .pt-entetes { padding-top: 10px; padding-bottom: 10px; background: #fafbfc; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-discret); font-size: .78rem; font-weight: 600; }
    .patient-card { position: relative; border-top: 1px solid #f3f4f6; transition: background-color .12s ease; }
    .patient-card:first-of-type { border-top: 0; }
    .patient-card:hover { background: var(--hali-primaire-pale); }

    .pt-nom { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .pt-nom a { color: var(--hali-encre); font-weight: 650; text-decoration: none; }
    .pt-nom a::after { content: ""; position: absolute; inset: 0; }
    .pt-nom a:focus-visible { outline: none; }
    .pt-nom a:focus-visible::after { outline: 2px solid var(--hali-primaire); outline-offset: -2px; }
    .pt-sous { display: block; color: var(--hali-discret); font-size: .8rem; }
    .pt-valeur { color: var(--hali-texte); font-size: .88rem; font-variant-numeric: tabular-nums; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pt-manque { color: #9ca3af; font-size: .85rem; }
    .pt-etiquette { display: none; }

    /* Les actions passent au-dessus du lien qui couvre la ligne */
    .pt-actions { position: relative; z-index: 2; display: flex; align-items: center; justify-content: flex-end; gap: 6px; }
    .pt-icone {
        display: inline-grid; place-items: center; width: 34px; height: 34px;
        border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); cursor: pointer; text-decoration: none;
    }
    .pt-icone:hover { border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); background: var(--hali-primaire-pale); text-decoration: none; }
    .pt-plus { position: relative; }
    .pt-plus > summary { list-style: none; }
    .pt-plus > summary::-webkit-details-marker { display: none; }
    .pt-plus-menu {
        position: absolute; right: 0; top: calc(100% + 6px); z-index: 30; min-width: 210px; padding: 6px;
        border: 1px solid var(--hali-bordure); border-radius: 10px; background: #fff; box-shadow: var(--hali-ombre-forte);
    }
    .pt-plus-menu button, .pt-plus-menu a {
        display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px; border: 0; border-radius: 7px;
        background: none; color: var(--hali-texte); font-size: .86rem; text-align: left; text-decoration: none;
    }
    .pt-plus-menu button:hover, .pt-plus-menu a:hover { background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); }
    .pt-plus-menu i { width: 16px; color: #9ca3af; text-align: center; }
    .pt-plus-menu .est-risque, .pt-plus-menu .est-risque i { color: var(--hali-danger); }
    .pt-plus-menu .est-risque:hover { background: var(--hali-danger-pale); color: var(--hali-danger); }
    .pt-plus-menu hr { margin: 4px 0; }

    .pt-pagination { padding: 14px 18px; border-top: 1px solid var(--hali-bordure); }
    .pt-pagination nav { display: flex; justify-content: center; }

    /* Fenêtres : sections du formulaire */
    .form-section + .form-section { margin-top: 8px; padding-top: 16px; border-top: 1px solid #f3f4f6; }
    .form-section-title { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; color: var(--hali-encre); font-size: .9rem; font-weight: 650; }
    .form-section-title i { color: var(--hali-primaire); }
    .form-label-enhanced { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .82rem; font-weight: 600; }
    .pt-genre { display: flex; gap: 8px; }
    .pt-genre label { flex: 1; margin: 0; }
    .pt-genre input { position: absolute; opacity: 0; pointer-events: none; }
    .pt-genre span { display: flex; align-items: center; justify-content: center; min-height: 40px; border: 1px solid var(--hali-bordure); border-radius: 8px; background: #fff; color: var(--hali-texte); font-weight: 600; cursor: pointer; }
    .pt-genre input:checked + span { background: var(--hali-primaire-pale); border-color: var(--hali-primaire); color: var(--hali-primaire-fonce); }
    .pt-genre input:focus-visible + span { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
    .file-upload-zone { padding: 28px 16px; border: 2px dashed var(--hali-bordure); border-radius: 12px; text-align: center; cursor: pointer; transition: border-color .12s ease, background-color .12s ease; }
    .file-upload-zone:hover, .file-upload-zone.est-survole { border-color: var(--hali-primaire); background: var(--hali-primaire-pale); }
    .file-upload-zone i { color: var(--hali-primaire) !important; }
    .file-name-display { display: none; margin-top: 10px; padding: 8px 12px; border-radius: 8px; background: var(--hali-primaire-pale); color: var(--hali-primaire-fonce); font-size: .85rem; font-weight: 600; }
    .btn-loading { opacity: .7; pointer-events: none; }

    @media (max-width: 991.98px) {
        .pt-entetes { display: none; }
        .patient-card { grid-template-columns: 1fr 1fr; gap: 8px 16px; }
        .pt-nom { grid-column: 1 / -1; }
        .pt-actions { grid-column: 1 / -1; justify-content: flex-start; }
        .pt-etiquette { display: block; color: var(--hali-discret); font-size: .75rem; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete">
        <div>
            <h1>Patients</h1>
            <p>{{ $totalPatients }} patient{{ $totalPatients > 1 ? 's' : '' }} suivi{{ $totalPatients > 1 ? 's' : '' }} par la clinique</p>
        </div>
        <div class="hl-entete-actions">
            <button type="button" class="hl-bouton hl-bouton-plein" data-bs-toggle="modal" data-bs-target="#addRowModal">
                <i class="fas fa-user-plus" aria-hidden="true"></i> Nouveau patient
            </button>
        </div>
    </header>

    <section class="hl-bloc">
        <form method="GET" action="{{ route('patient.index') }}" id="searchForm" class="pt-recherche" role="search">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" name="search" id="searchInput" class="form-control"
                   placeholder="Rechercher par nom, téléphone ou numéro de dossier…"
                   value="{{ $search ?? '' }}" autocomplete="off" aria-label="Rechercher un patient">
            @if($search)
                <a href="{{ route('patient.index') }}" class="pt-effacer" title="Effacer la recherche" aria-label="Effacer la recherche"><i class="fas fa-times" style="position:static; transform:none"></i></a>
            @endif
        </form>

        @if($search)
            <div class="pt-resultat">
                {{ $totalPatients }} résultat{{ $totalPatients > 1 ? 's' : '' }} pour « {{ $search }} » ·
                <a href="{{ route('patient.index') }}">voir tous les patients</a>
            </div>
        @endif

        @if($patients->isEmpty())
            <div class="hl-vide">
                <i class="fas fa-user-friends" aria-hidden="true"></i>
                @if($search)
                    Aucun patient ne correspond à « {{ $search }} ».<br>
                    Vérifiez l'orthographe, ou cherchez par numéro de téléphone.
                @else
                    Aucun patient pour l'instant.<br>
                    Ajoutez le premier avec le bouton « Nouveau patient ».
                @endif
            </div>
        @else
            <div class="pt-entetes" aria-hidden="true">
                <span>Patient</span><span>Téléphone</span><span>Adresse</span><span>Solde du compte</span><span></span>
            </div>

            <div id="patientsGrid">
                @foreach($patients as $patient)
                    @php $telephone = $telephoneDe($patient); @endphp
                    <div class="patient-card"
                         data-patient-name="{{ mb_strtolower($patient->first_name . ' ' . $patient->last_name) }}"
                         data-patient-phone="{{ $telephone }}"
                         data-patient-id="{{ $patient->id }}">

                        <div class="pt-nom">
                            <span class="hl-avatar" aria-hidden="true">{{ $initialesDe($patient) }}</span>
                            <div style="min-width:0">
                                @can('patient.view')
                                    <a href="{{ route('patient.show', $patient->id) }}">{{ $patient->first_name }} {{ $patient->last_name }}</a>
                                @else
                                    <strong style="color: var(--hali-encre)">{{ $patient->first_name }} {{ $patient->last_name }}</strong>
                                @endcan
                                <span class="pt-sous">
                                    #{{ str_pad($patient->id, 4, '0', STR_PAD_LEFT) }}
                                    @if($patient->gender) · {{ $patient->gender }}@endif
                                    @if($patient->age !== null) · {{ $patient->age }} ans @endif
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="pt-etiquette">Téléphone</span>
                            @if($telephone)<span class="pt-valeur">{{ $telephone }}</span>@else<span class="pt-manque">Non renseigné</span>@endif
                        </div>

                        <div style="min-width:0">
                            <span class="pt-etiquette">Adresse</span>
                            @if($patient->location)<span class="pt-valeur" title="{{ $patient->location }}">{{ $patient->location }}</span>@else<span class="pt-manque">Non renseignée</span>@endif
                        </div>

                        <div>
                            <span class="pt-etiquette">Solde du compte</span>
                            {{-- Solde du compte : ce que le patient et ses assurances doivent encore à la clinique. --}}
                            @php $solde = (float) ($patient->account?->balance ?? 0); @endphp
                            @if(abs($solde) >= 1)
                                <span class="hl-statut {{ $solde > 0 ? 'hl-s-alerte' : 'hl-s-info' }}"
                                      title="{{ $solde > 0 ? 'Reste dû, parts patient et assurance comprises' : 'Avance du patient' }}">{{ number_format(abs($solde), 0, ',', ' ') }} GNF{{ $solde < 0 ? ' d\'avance' : '' }}</span>
                            @else
                                <span class="pt-manque">À jour</span>
                            @endif
                        </div>

                        <div class="pt-actions">
                            @can('parcours.dossier')
                                <a href="{{ route('parcours.dossier.show', $patient->id) }}" class="pt-icone" title="Dossier médical" aria-label="Dossier médical"><i class="fas fa-folder-open"></i></a>
                            @endcan
                            @canany(['patient.edit', 'patient.delete', 'patient.add_file'])
                                <details class="pt-plus">
                                    <summary class="pt-icone" title="Autres actions" aria-label="Autres actions"><i class="fas fa-ellipsis-h"></i></summary>
                                    <div class="pt-plus-menu">
                                        @can('patient.edit')
                                            <button type="button" class="edit-button" data-bs-toggle="modal" data-bs-target="#editRowModal"
                                                    data-patient='@json($patient)' data-phone="{{ $telephone }}">
                                                <i class="fas fa-pen"></i> Modifier
                                            </button>
                                        @endcan
                                        @can('patient.add_file')
                                            <button type="button" class="add-file-button" data-bs-toggle="modal" data-bs-target="#uploadFileModal"
                                                    data-patient='@json($patient)'>
                                                <i class="fas fa-paperclip"></i> Ajouter un document
                                            </button>
                                        @endcan
                                        @can('patient.delete')
                                            <hr>
                                            <button type="button" class="delete-button est-risque" data-bs-toggle="modal" data-bs-target="#deleteRowModal"
                                                    data-patient='@json($patient)'>
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        @endcan
                                    </div>
                                </details>
                            @endcanany
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hl-vide" id="aucunLocal" hidden>Aucun patient de cette page ne correspond. Appuyez sur Entrée pour chercher dans toute la clinique.</div>

            @if(method_exists($patients, 'hasPages') && $patients->hasPages())
                <div class="pt-pagination">{{ $patients->withQueryString()->links() }}</div>
            @endif
        @endif
    </section>

            <!-- MODAL AJOUT -->
                <div class="modal fade" id="addRowModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content modal-content-enhanced">
                            <div class="modal-header modal-header-enhanced">
                                <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Nouveau patient</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="addPatientForm" action="{{ route('patient.store') }}" method="POST">
                                @csrf
                                <div class="modal-body p-4">
                                    <!-- Informations Personnelles -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="fas fa-user"></i>Identité
                                        </div>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <span class="form-label-enhanced">Sexe <span class="text-danger">*</span></span>
                                                <div class="pt-genre" role="radiogroup" aria-label="Sexe">
                                                    <label><input type="radio" name="gender" value="Femme" required><span>Femme</span></label>
                                                    <label><input type="radio" name="gender" value="Homme" required><span>Homme</span></label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Prénom <span class="text-danger">*</span></label>
                                                <input type="text" name="first_name" class="form-control form-control-enhanced" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Nom <span class="text-danger">*</span></label>
                                                <input type="text" name="last_name" class="form-control form-control-enhanced" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Téléphone <span class="text-danger">*</span></label>
                                                <input type="tel" name="phone" inputmode="numeric" placeholder="622000000" pattern="[0-9]{9}" maxlength="9" title="9 chiffres, sans espace" class="form-control form-control-enhanced" required>
                                                <span class="form-text">9 chiffres, sans espace ni indicatif.</span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Âge <span class="text-danger">*</span></label>
                                                <input type="number" name="age" min="0" max="130" inputmode="numeric" class="form-control form-control-enhanced" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Informations Médicales -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="fas fa-heartbeat"></i>Informations médicales
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Situation matrimoniale</label>
                                                <select name="marital_status" class="form-control form-control-enhanced">
                                                    <option value="">-- Sélectionner --</option>
                                                    <option value="Marie">Marié(e)</option>
                                                    <option value="Celibataire">Célibataire</option>
                                                    <option value="Autre">Autre</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Groupe sanguin</label>
                                                <select name="blood_group" class="form-control form-control-enhanced">
                                                    <option value="">-- Sélectionner --</option>
                                                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                                                        <option value="{{ $group }}">{{ $group }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Contact et Localisation -->
                                    <div class="form-section">
                                        <div class="form-section-title">
                                            <i class="fas fa-map-marker-alt"></i>Contact et adresse
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Personne à prévenir</label>
                                                <input type="text" name="relative_name" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Téléphone de la personne à prévenir</label>
                                                <input type="tel" name="relative_phone" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Profession</label>
                                                <input type="text" name="occupation" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Adresse (quartier, commune)</label>
                                                <input type="text" name="location" class="form-control form-control-enhanced">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary" id="btnAddPatient">
                                        <i class="fas fa-save me-2"></i>Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MODAL MODIFICATION -->
                <div class="modal fade" id="editRowModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content modal-content-enhanced">
                            <div class="modal-header modal-header-enhanced">
                                <h5 class="modal-title"><i class="fas fa-user-edit me-2"></i>Modifier le patient</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="editPatientForm" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-body p-4">
                                    <div class="form-section">
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <span class="form-label-enhanced">Sexe</span>
                                                <div class="pt-genre" role="radiogroup" aria-label="Sexe">
                                                    <label><input type="radio" name="gender" value="Femme" id="edit_gender_femme"><span>Femme</span></label>
                                                    <label><input type="radio" name="gender" value="Homme" id="edit_gender_homme"><span>Homme</span></label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Prénom</label>
                                                <input type="text" name="first_name" id="edit_first_name" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Nom</label>
                                                <input type="text" name="last_name" id="edit_last_name" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Téléphone</label>
                                                <input type="tel" id="edit_phone" class="form-control form-control-enhanced" readonly aria-describedby="edit_phone_aide">
                                                <span class="form-text" id="edit_phone_aide">Le numéro est celui du compte du patient (connexion, SMS) : il ne se modifie pas ici.</span>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Âge</label>
                                                <input type="number" name="age" id="edit_age" class="form-control form-control-enhanced">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Situation matrimoniale</label>
                                                <select name="marital_status" id="edit_marital_status" class="form-control form-control-enhanced">
                                                    <option value="">-- Sélectionner --</option>
                                                    <option value="Marie">Marié(e)</option>
                                                    <option value="Celibataire">Célibataire</option>
                                                    <option value="Autre">Autre</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label-enhanced">Groupe sanguin</label>
                                                <select name="blood_group" id="edit_blood_group" class="form-control form-control-enhanced">
                                                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $group)
                                                        <option value="{{ $group }}">{{ $group }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label-enhanced">Adresse (quartier, commune)</label>
                                                <input type="text" name="location" id="edit_location" class="form-control form-control-enhanced">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary" id="btnEditPatient">
                                        <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MODAL SUPPRESSION -->
                <div class="modal fade" id="deleteRowModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content modal-content-enhanced">
                            <div class="modal-header">
                                <h5 class="modal-title" style="color: var(--hali-danger)"><i class="fas fa-exclamation-triangle me-2"></i>Supprimer le patient</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="deletePatientForm" method="POST">
                                @csrf
                                @method('DELETE')
                                <div class="modal-body text-center p-4">
                                    <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                                    <p class="h5 mb-3" id="delete_patient_name"></p>
                                    <p class="text-muted mb-0">Cette action est irréversible. Si le patient a des consultations ou des factures, préférez le conserver.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-danger" id="btnDeletePatient">
                                        <i class="fas fa-trash me-2"></i>Supprimer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- MODAL UPLOAD -->
                <div class="modal fade" id="uploadFileModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content modal-content-enhanced">
                            <div class="modal-header modal-header-enhanced">
                                <h5 class="modal-title"><i class="fas fa-paperclip me-2"></i>Ajouter un document</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form id="uploadFileForm" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="modal-body p-4">
                                    <div class="file-upload-zone" onclick="document.getElementById('fileInput').click()">
                                        <i class="fas fa-cloud-upload-alt text-primary fa-3x mb-3"></i>
                                        <h6>Glissez le fichier ici ou cliquez pour le choisir</h6>
                                        <p class="text-muted mb-0">PDF, JPG ou PNG · 10 Mo maximum</p>
                                        <input type="file" id="fileInput" name="file" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                                    </div>
                                    <div class="file-name-display" id="fileNameDisplay"></div>
                                    <div class="mt-3">
                                        <label class="form-label-enhanced">Description</label>
                                        <input type="text" name="name" class="form-control form-control-enhanced" placeholder="Ex: Radiographie du 02/06/2025">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-primary" id="btnUploadFile">
                                        <i class="fas fa-upload me-2"></i>Ajouter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>



</div></div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {

            // ============================================
            // RECHERCHE : filtre immédiat sur la page, Entrée cherche dans toute la clinique
            // ============================================
            $('#searchInput').on('input', function() {
                const terme = $(this).val().toLowerCase().trim();
                let visibles = 0;
                $('.patient-card').each(function() {
                    const texte = [$(this).data('patient-name'), $(this).data('patient-phone'), $(this).data('patient-id')].join(' ').toLowerCase();
                    const ok = terme === '' || texte.includes(terme);
                    $(this).toggle(ok);
                    if (ok) visibles++;
                });
                $('#aucunLocal').prop('hidden', visibles > 0 || $('.patient-card').length === 0);
            });

            // Un seul menu « … » ouvert, fermé par un clic ailleurs ou à l'ouverture d'une fenêtre
            $(document).on('click', function(e) {
                $('details.pt-plus[open]').each(function() {
                    if (!this.contains(e.target) || $(e.target).closest('[data-bs-toggle="modal"]').length) this.removeAttribute('open');
                });
            });

            // ============================================
            // MODIFIER
            // ============================================
            $('.edit-button').on('click', function() {
                const patient = $(this).data('patient');
                const phone = $(this).data('phone');

                $('#edit_first_name').val(patient.first_name);
                $('#edit_last_name').val(patient.last_name);
                $('#edit_phone').val(phone);
                $('#edit_age').val(patient.age);
                $('#edit_marital_status').val(patient.marital_status);
                $('#edit_blood_group').val(patient.blood_group);
                $('#edit_location').val(patient.location);
                $('#edit_gender_femme').prop('checked', patient.gender === 'Femme');
                $('#edit_gender_homme').prop('checked', patient.gender === 'Homme');

                $('#editPatientForm').attr('action', '/patient/' + patient.id);
            });

            // ============================================
            // SUPPRIMER
            // ============================================
            $('.delete-button').on('click', function() {
                const patient = $(this).data('patient');
                $('#delete_patient_name').text(`Supprimer ${patient.first_name} ${patient.last_name} ?`);
                $('#deletePatientForm').attr('action', '/patient/' + patient.id);
            });

            // ============================================
            // AJOUTER UN DOCUMENT
            // ============================================
            $('.add-file-button').on('click', function() {
                const patient = $(this).data('patient');
                $('#uploadFileForm').attr('action', '/patient/file/' + patient.id);
            });

            $('#fileInput').on('change', function() {
                const fileName = this.files[0]?.name;
                if (fileName) {
                    $('#fileNameDisplay').text(fileName).show();
                } else {
                    $('#fileNameDisplay').hide();
                }
            });

            // Glisser-déposer sur la zone
            const zone = document.querySelector('.file-upload-zone');
            if (zone) {
                ['dragenter', 'dragover'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('est-survole'); }));
                ['dragleave', 'drop'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.remove('est-survole'); }));
                zone.addEventListener('drop', function(e) {
                    const input = document.getElementById('fileInput');
                    if (e.dataTransfer.files.length) { input.files = e.dataTransfer.files; $(input).trigger('change'); }
                });
            }

            // ============================================
            // UN SEUL ENVOI PAR FORMULAIRE (réseau lent)
            // ============================================
            function setLoading(btn, isLoading) {
                $(btn).toggleClass('btn-loading', isLoading).prop('disabled', isLoading);
            }
            $('#addPatientForm').on('submit', function() { setLoading('#btnAddPatient', true); });
            $('#editPatientForm').on('submit', function() { setLoading('#btnEditPatient', true); });
            $('#deletePatientForm').on('submit', function() { setLoading('#btnDeletePatient', true); });
            $('#uploadFileForm').on('submit', function() { setLoading('#btnUploadFile', true); });
        });
    </script>
@endsection
