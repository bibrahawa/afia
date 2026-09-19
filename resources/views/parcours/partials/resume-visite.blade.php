{{--
    Bandeau patient : ce que l'accueil a déjà saisi, pour que le médecin ne le
    redemande pas. Un seul bandeau par écran.

    @include('parcours.partials.resume-visite', [
        'consultation' => $consultation,
        'collant' => true,                         // facultatif : reste visible au défilement
        'retour' => route('parcours.file.index'),  // facultatif : lien « Retour à la file »
    ])
--}}
@php
    $visite = $consultation->visite;
    $patient = $consultation->patient;
    $antecedents = $patient?->antecedant;
    $grossesseEnCours = $consultation->grossesse ?? null;
    $grossesseEnCours = $grossesseEnCours && $grossesseEnCours->estEnCours() ? $grossesseEnCours : null;
    $prochaineCpn = $grossesseEnCours?->prochainContact();

    $nomsPatient = preg_split('/\s+/', trim((string) $patient?->full_name));
    $initialesPatient = mb_strtoupper(mb_substr($nomsPatient[0] ?? '', 0, 1) . mb_substr(end($nomsPatient) ?: '', 0, 1));
@endphp

<div class="hl-patient {{ ($collant ?? false) ? 'est-collant' : '' }}" id="bandeauPatient">
    <div class="hl-patient-ligne">
        <span class="hl-avatar {{ $visite?->urgence ? 'est-urgent' : '' }}" aria-hidden="true">{{ $initialesPatient }}</span>

        <div class="hl-patient-nom">
            <strong>{{ $patient?->full_name }}</strong>
            <span>
                {{ $patient?->gender }}{{ $patient?->age !== null ? ', ' . $patient->age . ' ans' : '' }}
                @if($visite?->motif) · {{ $visite->motif }}@endif
                @if($visite?->arrivee_le) · arrivé{{ $patient?->gender === 'Femme' ? 'e' : '' }} à {{ $visite->arrivee_le->format('H:i') }}@endif
            </span>
        </div>

        <div class="hl-patient-pastilles">
            @if($visite?->urgence)
                <span class="hl-statut hl-s-danger">Urgence</span>
            @endif

            @if($antecedents?->allergies)
                <span class="hl-allergie" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Allergies : {{ $antecedents->allergies }}</span>
            @endif

            @if($grossesseEnCours)
                <a href="{{ route('parcours.grossesses.show', $grossesseEnCours) }}" target="_blank" class="hl-statut hl-s-rose"
                   title="Ouvrir le suivi de grossesse">
                    <i class="fas fa-baby" aria-hidden="true"></i>
                    {{ $grossesseEnCours->termeLisible() }} · DPA {{ $grossesseEnCours->dpa->format('d/m/Y') }}
                    @if($prochaineCpn && $prochaineCpn['statut'] === 'a_programmer') · CPN {{ $prochaineCpn['semaines'] }} SA à programmer @endif
                </a>
            @endif
        </div>

        <nav class="hl-patient-liens" aria-label="Liens du patient">
            @can('parcours.dossier')
                <a href="{{ route('parcours.dossier.show', $patient?->id) }}" target="_blank"><i class="fas fa-folder-open" aria-hidden="true"></i> Dossier</a>
            @endcan
            @can('assurance.referentiel.view')
                <a href="{{ route('assurance.droits.show', $patient?->id) }}" target="_blank"><i class="fas fa-shield-alt" aria-hidden="true"></i> Droits assurance</a>
            @endcan
            @isset($retour)
                <a href="{{ $retour }}" class="hl-bouton">Retour à la file</a>
            @endisset
        </nav>
    </div>

    @if($visite)
        <div class="hl-patient-bas">
            <span><i class="fas fa-heartbeat" aria-hidden="true"></i> @include('parcours.partials.constantes', ['c' => $visite->derniereConstante])</span>
            @if($visite->notes_accueil)
                <span class="hl-note-accueil">Accueil : {{ $visite->notes_accueil }}</span>
            @endif
        </div>
    @endif
</div>
