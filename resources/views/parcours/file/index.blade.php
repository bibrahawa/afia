@extends('layouts.backend')

@section('style')
<style>
    /* Une file d'attente se lit en diagonale : le suivant en haut, en grand,
       avec une seule action possible. Le reste s'efface. */
    .fa-section { margin-bottom: 16px; }
    .fa-section-titre { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; margin: 0; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); color: var(--hali-encre); font-size: .95rem; font-weight: 650; }
    .fa-section-titre small { color: var(--hali-discret); font-size: .8rem; font-weight: 500; }
    .fa-compte { display: inline-grid; place-items: center; min-width: 22px; height: 22px; padding: 0 6px; border-radius: 999px; background: #f3f4f6; color: var(--hali-texte); font-size: .75rem; font-weight: 700; }

    .fa-ligne { display: flex; align-items: center; gap: 14px; padding: 12px 18px; border-top: 1px solid #f3f4f6; }
    .fa-ligne:first-child { border-top: 0; }
    .fa-ligne-texte { flex: 1; min-width: 0; }
    .fa-nom { color: var(--hali-encre); font-weight: 650; }
    .fa-nom small { color: var(--hali-discret); font-weight: 500; font-size: .82rem; margin-left: 4px; }
    .fa-meta { color: var(--hali-discret); font-size: .84rem; }
    .fa-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .fa-actions form { margin: 0; }

    .fa-rang { width: 30px; height: 30px; flex: none; display: grid; place-items: center; border-radius: 50%; background: #f3f4f6; color: var(--hali-texte); font-size: .85rem; font-weight: 700; }
    .fa-rang.est-urgent { background: var(--hali-danger-pale); color: var(--hali-danger); }
    .fa-attente-longue { color: var(--hali-alerte); font-weight: 650; }
    .fa-encaisse { color: var(--hali-alerte); }

    /* En consultation : le patient qu'on a devant soi */
    .fa-en-cours .fa-ligne { background: var(--hali-primaire-pale); }
    .fa-en-cours .fa-ligne + .fa-ligne { border-top-color: var(--hali-primaire-clair); }
    .fa-pastille-cours { display: inline-flex; align-items: center; gap: 6px; color: var(--hali-primaire-fonce); font-size: .8rem; font-weight: 600; }
    .fa-pastille-cours::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: var(--hali-primaire); box-shadow: 0 0 0 3px var(--hali-primaire-clair); }

    /* Patient suivant : le seul bloc qui ressort */
    .fa-suivant { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; gap: 16px 20px; align-items: center; padding: 20px 22px; border: 1px solid var(--hali-primaire); border-left: 5px solid var(--hali-primaire); border-radius: var(--hali-rayon); background: #fff; box-shadow: 0 4px 14px rgba(15, 118, 110, .10); }
    .fa-suivant.est-urgent { border-color: var(--hali-danger); background: #fffafa; box-shadow: 0 4px 14px rgba(185, 28, 28, .10); }
    .fa-suivant .hl-avatar { width: 52px; height: 52px; flex-basis: 52px; font-size: 1.05rem; border-radius: 14px; }
    .fa-suivant-etiquette { color: var(--hali-primaire); font-size: .82rem; font-weight: 700; }
    .fa-suivant.est-urgent .fa-suivant-etiquette { color: var(--hali-danger); }
    .fa-suivant-nom { margin: 2px 0 4px; color: var(--hali-encre); font-size: 1.35rem; font-weight: 700; letter-spacing: -.01em; line-height: 1.25; }
    .fa-suivant-nom small { color: var(--hali-discret); font-size: .9rem; font-weight: 500; }
    .fa-suivant-constantes { margin-top: 8px; padding: 8px 12px; border-radius: 8px; background: #f9fafb; font-size: .86rem; }
    .fa-suivant-actions { display: grid; gap: 8px; justify-items: stretch; min-width: 170px; }
    .fa-appeler { min-height: 48px; font-size: 1rem; }

    .fa-vide { display: grid; justify-items: center; gap: 6px; padding: 36px 20px; text-align: center; }
    .fa-vide i { font-size: 1.6rem; color: #a7f3d0; }
    .fa-vide strong { color: var(--hali-encre); }
    .fa-vide span { color: var(--hali-discret); font-size: .88rem; }

    .fa-vus > summary { padding: 14px 18px; color: var(--hali-encre); font-size: .95rem; font-weight: 650; }
    .fa-vus[open] > summary { border-bottom: 1px solid var(--hali-bordure); }
    .fa-vus .fa-ligne { padding: 10px 18px; }
    .fa-heure { width: 44px; flex: none; color: var(--hali-discret); font-size: .85rem; font-variant-numeric: tabular-nums; }

    @media (max-width: 767.98px) {
        .fa-suivant { grid-template-columns: auto minmax(0, 1fr); }
        .fa-suivant-actions { grid-column: 1 / -1; }
        .fa-ligne { flex-wrap: wrap; }
    }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    @php
        $reglePassage = (\App\Support\EtablissementContext::current()?->ordre_file ?? 'arrivee') === 'rendez_vous'
            ? 'rendez-vous, puis ordre d\'arrivée'
            : 'ordre d\'arrivée';
        $initiales = function ($nom) {
            $noms = preg_split('/\s+/', trim((string) $nom));
            return mb_strtoupper(mb_substr($noms[0] ?? '', 0, 1) . mb_substr(end($noms) ?: '', 0, 1));
        };
        $identite = fn ($p) => $p->gender . ($p->age !== null ? ', ' . $p->age . ' ans' : '');
        $nonEncaisse = fn ($v) => ($t = $v->consultation?->transaction) && ! in_array($t->status, ['paid', 'approved'], true);
        $suivant = $enAttente->first();
    @endphp

    <header class="hl-entete">
        <div>
            <h1>Ma file d'attente</h1>
            <p>
                {{ $enAttente->count() }} en attente · {{ $enConsultation->count() }} en consultation · {{ $terminees->count() }} vu{{ $terminees->count() > 1 ? 's' : '' }} aujourd'hui
            </p>
        </div>
        <div class="hl-entete-actions">
            <span class="hl-maj" title="La page se met à jour toute seule chaque minute">Actualisé à {{ now()->format('H:i') }}</span>
            @if(Route::has('parcours.consultation.nouvelle'))
                <a href="{{ route('parcours.consultation.nouvelle') }}" class="hl-bouton"><i class="fa fa-plus" aria-hidden="true"></i> Recevoir un patient</a>
            @endif
        </div>
    </header>

    {{-- ------------------------------------------------ En consultation --}}
    @if($enConsultation->isNotEmpty())
        <section class="hl-bloc fa-section fa-en-cours">
            <h2 class="fa-section-titre">En consultation <span class="fa-compte">{{ $enConsultation->count() }}</span></h2>
            @foreach($enConsultation as $v)
                <div class="fa-ligne">
                    <span class="hl-avatar {{ $v->urgence ? 'est-urgent' : '' }}" aria-hidden="true">{{ $initiales($v->patient->full_name) }}</span>
                    <div class="fa-ligne-texte">
                        <div class="fa-nom">{{ $v->patient->full_name }} <small>{{ $identite($v->patient) }}</small></div>
                        <div class="fa-meta">
                            <span class="fa-pastille-cours">Appelé{{ $v->patient->gender === 'Femme' ? 'e' : '' }} à {{ $v->appele_le?->format('H:i') }}</span>
                            @if($v->motif) · {{ $v->motif }}@endif
                            @if($v->urgence) · <span class="hl-statut hl-s-danger">Urgence</span>@endif
                        </div>
                    </div>
                    <div class="fa-actions">
                        <a href="{{ route('parcours.consultation.show', $v->consultation) }}" class="hl-bouton hl-bouton-plein">Reprendre</a>
                        <form method="POST" action="{{ route('parcours.file.terminer', $v) }}">@csrf
                            <button class="hl-bouton" type="submit">Terminer</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    {{-- ------------------------------------------------ Le suivant --}}
    @if($suivant)
        <section class="fa-section fa-suivant {{ $suivant->urgence ? 'est-urgent' : '' }}" aria-label="Patient suivant">
            <span class="hl-avatar {{ $suivant->urgence ? 'est-urgent' : '' }}" aria-hidden="true">{{ $initiales($suivant->patient->full_name) }}</span>

            <div style="min-width:0">
                <div class="fa-suivant-etiquette">
                    {{ $suivant->urgence ? 'Urgence · à voir maintenant' : 'Patient suivant' }}
                </div>
                <h2 class="fa-suivant-nom">
                    {{ $suivant->patient->full_name }}
                    <small>{{ $identite($suivant->patient) }}</small>
                </h2>
                <div class="fa-meta">
                    {{ $suivant->motif }}
                    · <span class="{{ $suivant->minutesAttente() > 45 ? 'fa-attente-longue' : '' }}">attend depuis {{ $suivant->minutesAttente() }} min</span>
                    @if($suivant->appointment) · rendez-vous de {{ $suivant->appointment->appointment_time?->format('H:i') }}@endif
                    @if($nonEncaisse($suivant)) · <span class="fa-encaisse">acte non encaissé</span>@endif
                </div>
                <div class="fa-suivant-constantes">
                    <i class="fas fa-heartbeat text-danger" aria-hidden="true"></i>
                    @include('parcours.partials.constantes', ['c' => $suivant->derniereConstante])
                </div>
            </div>

            <div class="fa-suivant-actions">
                <form method="POST" action="{{ route('parcours.file.appeler', $suivant) }}">@csrf
                    <button class="hl-bouton hl-bouton-plein fa-appeler w-100" type="submit"><i class="fa fa-bullhorn" aria-hidden="true"></i> Appeler</button>
                </form>
                @can('parcours.dossier')
                    <a href="{{ route('parcours.dossier.show', $suivant->patient_id) }}" target="_blank" class="hl-bouton">Voir le dossier</a>
                @endcan
            </div>
        </section>
    @endif

    {{-- ------------------------------------------------ Le reste de la file --}}
    <section class="hl-bloc fa-section">
        <h2 class="fa-section-titre">
            {{ $suivant ? 'Ensuite' : 'En attente' }}
            <span class="fa-compte">{{ max(0, $enAttente->count() - ($suivant ? 1 : 0)) }}</span>
            <small class="ms-auto">Ordre fixé par l'accueil : urgences, puis {{ $reglePassage }}.</small>
        </h2>

        @forelse($enAttente->skip(1) as $v)
            <div class="fa-ligne">
                <span class="fa-rang {{ $v->urgence ? 'est-urgent' : '' }}">{{ $loop->iteration + 1 }}</span>
                <div class="fa-ligne-texte">
                    <div class="fa-nom">
                        {{ $v->patient->full_name }} <small>{{ $identite($v->patient) }}</small>
                        @if($v->urgence)<span class="hl-statut hl-s-danger">Urgence</span>@endif
                    </div>
                    <div class="fa-meta">
                        {{ $v->motif }}
                        · <span class="{{ $v->minutesAttente() > 45 ? 'fa-attente-longue' : '' }}">{{ $v->minutesAttente() }} min d'attente</span>
                        @if($nonEncaisse($v)) · <span class="fa-encaisse">acte non encaissé</span>@endif
                    </div>
                </div>
                <form method="POST" action="{{ route('parcours.file.appeler', $v) }}">@csrf
                    <button class="hl-bouton" type="submit" title="Appeler ce patient avant son tour">Appeler</button>
                </form>
            </div>
        @empty
            @if(! $suivant)
                <div class="fa-vide">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <strong>Personne n'attend. Vous êtes à jour.</strong>
                    <span>Les patients enregistrés par l'accueil apparaîtront ici automatiquement.</span>
                </div>
            @else
                <div class="fa-vide" style="padding:20px">
                    <span>Personne d'autre après {{ $suivant->patient->full_name }}.</span>
                </div>
            @endif
        @endforelse
    </section>

    {{-- ------------------------------------------------ Vus aujourd'hui --}}
    @if($terminees->isNotEmpty())
        <details class="hl-bloc fa-section fa-vus hl-repli">
            <summary>
                Vus aujourd'hui <span class="fa-compte">{{ $terminees->count() }}</span>
                <span class="hl-repli-aide">Afficher</span>
            </summary>
            @foreach($terminees as $v)
                <div class="fa-ligne">
                    <span class="fa-heure">{{ $v->terminee_le?->format('H:i') }}</span>
                    <div class="fa-ligne-texte">
                        <span class="fa-nom">{{ $v->patient->full_name }}</span>
                        <span class="fa-meta"> · {{ $v->motif }}</span>
                    </div>
                    <a href="{{ route('consultation.show', $v->consultation) }}" class="hl-bouton">Voir</a>
                </div>
            @endforeach
        </details>
    @endif

</div></div>

<script>
(function () {
    // Actualisation toutes les minutes, sauf si l'utilisateur est en train
    // d'agir (bloc ouvert, champ actif, onglet caché).
    setInterval(function () {
        if (document.hidden) return;
        if (document.querySelector('details[open], .modal.show')) return;
        var actif = document.activeElement;
        if (actif && /INPUT|TEXTAREA|SELECT/.test(actif.tagName)) return;
        window.location.reload();
    }, 60000);
})();
</script>
@endsection
