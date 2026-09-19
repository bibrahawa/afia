@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('style')
    @include('labo.partials.styles')
    <style>
        .rc-recherche { display: flex; gap: 8px; padding: 12px 18px; border-bottom: 1px solid var(--hali-bordure); }
        .rc-recherche input { min-height: 40px; }
        .rc-catalogue { max-height: 520px; overflow-y: auto; }
        .rc-catalogue tr { cursor: pointer; }
        .rc-catalogue input[type=checkbox] { width: 18px; height: 18px; accent-color: var(--hali-primaire); }
        .rc-catalogue tr:has(input:checked) > td { background: var(--hali-primaire-pale); }
        .rc-choix { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    </style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Envoyer des analyses', 'fil' => [route('labo.reseau.index') => 'Analyses envoyées', 0 => 'Nouvelle demande']])

    @if(! $partenariat)
        <p class="lb-alerte lb-alerte-avert"><i class="fas fa-exclamation-circle mt-1" aria-hidden="true"></i><span>Aucun laboratoire partenaire actif.</span></p>
    @else
        {{-- Recherche dans le catalogue : formulaire SÉPARÉ, relié aux champs par l'attribut form=.
             Auparavant imbriqué dans le formulaire d'envoi : le navigateur ignore un formulaire
             imbriqué, et « Chercher » ENVOYAIT la demande au laboratoire. --}}
        <form method="GET" id="rechercheCatalogue" action="{{ route('labo.reseau.create') }}">
            <input type="hidden" name="partenariat_id" value="{{ $partenariat->id }}">
            @if($patient)<input type="hidden" name="patient_id" value="{{ $patient->id }}">@endif
            @if($consultation)<input type="hidden" name="consultation_id" value="{{ $consultation->id }}">@endif
        </form>

        <form method="POST" action="{{ route('labo.reseau.store') }}" id="envoiDemande">@csrf
            <input type="hidden" name="partenariat_id" value="{{ $partenariat->id }}">
            @if($consultation)<input type="hidden" name="consultation_id" value="{{ $consultation->id }}">@endif

            <div class="lb-grille">
                <section class="hl-bloc">
                    <h2 class="hl-bloc-titre">Patient et prescription</h2>
                    <div class="lb-form">
                        <div>
                            <label class="form-label">Laboratoire</label>
                            <select class="form-control" onchange="window.location = '{{ route('labo.reseau.create') }}?partenariat_id=' + this.value + '{{ $patient ? '&patient_id=' . $patient->id : '' }}'">
                                @foreach($partenaires as $p)<option value="{{ $p->id }}" @selected($p->id === $partenariat->id)>{{ $p->laboratoire?->nom }}</option>@endforeach
                            </select>
                            <p class="lb-aide">{{ $partenariat->mode_facturation_defaut === 'partenaire' ? 'Les analyses vous seront facturées par le laboratoire.' : 'Le laboratoire encaisse directement le patient.' }}</p>
                        </div>

                        <div>
                            @if($patient)
                                <label class="form-label">Patient</label>
                                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                <div class="lb-fort">{{ $patient->full_name }} <span class="lb-sous" style="display:inline">{{ $patient->gender }}{{ $patient->age !== null ? ', ' . $patient->age . ' ans' : '' }}</span></div>
                            @else
                                @include('assurance.partials.choix-patient', ['id' => 'labo-reseau', 'libelle' => 'Patient', 'url' => route('labo.reseau.patients.recherche')])
                            @endif
                        </div>

                        <div><label class="form-label">Médecin prescripteur</label>
                            <select name="prescripteur_employee_id" class="form-control">
                                <option value="">—</option>
                                @foreach($medecins as $m)<option value="{{ $m->id }}" @selected($consultation && $consultation->medecin_id === $m->id)>{{ $m->nom_affiche }}</option>@endforeach
                            </select></div>

                        <div><label class="form-label">Renseignements cliniques</label>
                            <textarea name="renseignements_cliniques" class="form-control" rows="3" placeholder="Utile au biologiste : symptômes, traitement en cours…">{{ $consultation?->diagnostic }}</textarea></div>

                        <div class="rc-choix">
                            <input type="hidden" name="urgence" value="0">
                            <label class="lb-coche est-danger"><input type="checkbox" name="urgence" value="1"><span><i class="fas fa-bolt" aria-hidden="true"></i> Urgent</span></label>
                            <input type="hidden" name="grossesse" value="0">
                            <label class="lb-coche"><input type="checkbox" name="grossesse" value="1"><span>Patiente enceinte</span></label>
                            <input type="number" min="1" max="45" name="semaines_amenorrhee" class="form-control" placeholder="SA" style="width:80px; min-height:36px" aria-label="Semaines d'aménorrhée">
                        </div>

                        <label class="d-flex gap-2 mb-0" style="font-weight:500; font-size:.84rem">
                            <input type="hidden" name="consentement_partage" value="0">
                            <input type="checkbox" name="consentement_partage" value="1" checked style="margin-top:3px">
                            Le patient a été informé que ses analyses sont confiées à ce laboratoire
                        </label>
                    </div>
                </section>

                <section class="hl-bloc">
                    <h2 class="hl-bloc-titre">Catalogue de {{ $partenariat->laboratoire?->nom }}</h2>
                    <div class="rc-recherche">
                        <input name="q" value="{{ $recherche }}" class="form-control" placeholder="Chercher un examen" form="rechercheCatalogue">
                        <button class="hl-bouton" form="rechercheCatalogue"><i class="fas fa-search" aria-hidden="true"></i> Chercher</button>
                    </div>
                    <div class="table-responsive rc-catalogue">
                        <table class="lb-table">
                            <thead><tr><th></th><th>Examen</th><th>Échantillon</th><th class="lb-n">Délai</th><th class="lb-n">Prix</th></tr></thead>
                            <tbody>
                            @forelse($catalogue as $examen)
                                <tr onclick="if (event.target.tagName !== 'INPUT') { var c = this.querySelector('input'); c.checked = !c.checked; }">
                                    <td style="width:40px"><input type="checkbox" name="examens[]" value="{{ $examen['id'] }}" aria-label="{{ $examen['nom'] }}"></td>
                                    <td><span class="lb-fort">{{ $examen['nom'] }}</span> <span class="lb-sous" style="display:inline">{{ $examen['code'] }}</span>@if($examen['a_jeun']) <span class="hl-statut hl-s-info">à jeun</span>@endif</td>
                                    <td style="font-size:.84rem">{{ $examen['type_echantillon'] }}</td>
                                    <td class="lb-n">{{ $examen['delai_heures'] }} h</td>
                                    <td class="lb-n">{{ $gnf($examen['prix']) }} GNF</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><div class="hl-vide" style="padding:18px">Aucun examen trouvé.</div></td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <div class="lb-barre-bas">
                <button class="hl-bouton hl-bouton-plein"><i class="fa fa-paper-plane" aria-hidden="true"></i> Envoyer au laboratoire</button>
                <span class="lb-droite"><a href="{{ route('labo.reseau.index') }}" class="hl-bouton">Annuler</a></span>
            </div>
        </form>
    @endif
</div></div>
@endsection

@section('script')
    @include('assurance.partials.choix-patient-script')
@endsection
