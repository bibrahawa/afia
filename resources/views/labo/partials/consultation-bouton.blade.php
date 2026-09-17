{{--
    Bouton « Laboratoire » de la fiche consultation.
    À inclure dans resources/views/consultations/show.blade.php, dans la barre d'actions,
    juste avant le bouton « Retour » :
        @include('labo.partials.consultation-bouton', ['consultation' => $consultation])
    N'affiche rien si le module labo est inactif ou si l'utilisateur n'a pas les droits.
--}}
@php
    $laboActif = \Illuminate\Support\Facades\Route::has('labo.demandes.create')
        && \App\Support\EtablissementContext::current()?->aModule('laboratoire');
@endphp

@if($laboActif && auth()->user()?->canAny(['labo.demande.create', 'labo.demande.view']))
    @php
        $demandesLabo = \App\Models\Labo\LaboDemande::where('consultation_id', $consultation->id)
            ->where('statut', '!=', \App\Enums\Labo\StatutDemande::ANNULEE->value)->latest()->get();
        $testsRelies = $consultation->tests->isEmpty() ? 0
            : \App\Models\Labo\LaboExamen::where('actif', true)->whereIn('test_id', $consultation->tests->pluck('id'))->count();
    @endphp

    <div class="btn-group">
        <button type="button" class="btn btn-sm btn-outline-danger dropdown-toggle" data-bs-toggle="dropdown">
            <i class="fas fa-flask me-1"></i> Laboratoire
            @if($demandesLabo->isNotEmpty())<span class="badge bg-danger ms-1">{{ $demandesLabo->count() }}</span>@endif
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            @can('labo.demande.create')
                @if($testsRelies > 0 && $demandesLabo->isEmpty())
                    <li>
                        <form method="POST" action="{{ route('labo.demandes.depuis-consultation', $consultation) }}"
                              onsubmit="return confirm('Envoyer au laboratoire les {{ $testsRelies }} examen(s) prescrit(s) ? Ils sont déjà facturés avec la consultation.')">
                            @csrf
                            <button class="dropdown-item">
                                <i class="fas fa-paper-plane me-2 text-danger"></i>Envoyer les examens prescrits ({{ $testsRelies }})
                            </button>
                        </form>
                    </li>
                @endif
                <li>
                    <a class="dropdown-item" href="{{ route('labo.demandes.create', ['consultation' => $consultation->id]) }}">
                        <i class="fas fa-plus me-2 text-primary"></i>Nouvelle demande d'analyses
                    </a>
                </li>
            @endcan
            @foreach($demandesLabo as $d)
                @if($loop->first)<li><hr class="dropdown-divider"></li>@endif
                <li>
                    <a class="dropdown-item" href="{{ route('labo.demandes.show', $d) }}">
                        <i class="fas fa-vial me-2 text-secondary"></i>{{ $d->numero }}
                        <span class="badge badge-{{ $d->statut->couleur() }} ms-1">{{ $d->statut->libelle() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
