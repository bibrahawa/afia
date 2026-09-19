{{--
    Carte « Analyses de laboratoire » de la fiche consultation.
    À inclure dans resources/views/consultations/show.blade.php, juste après la carte
    « Diagnostic et Examens Cliniques » :
        @include('labo.partials.consultation-analyses', ['consultation' => $consultation])
    Le médecin y lit les résultats de SES analyses, même si le patient n'a pas encore payé.
--}}
@php
    $laboActif = \Illuminate\Support\Facades\Route::has('labo.demandes.show')
        && \App\Support\EtablissementContext::current()?->aModule('laboratoire');
@endphp

@if($laboActif && auth()->user()?->can('labo.demande.view'))
    @php
        $demandesLabo = \App\Models\Labo\LaboDemande::with(['examens.resultats', 'comptesRendus'])
            ->where('consultation_id', $consultation->id)->latest()->get();
        $testsNonRelies = $consultation->tests->isEmpty() ? collect()
            : $consultation->tests->whereNotIn('id', \App\Models\Labo\LaboExamen::whereIn('test_id', $consultation->tests->pluck('id'))->pluck('test_id'));
    @endphp

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                {{-- bg-primary : rendu sobre avec filet de couleur (admin-theme.css), comme les autres cartes de la fiche. --}}
                <div class="card-header bg-primary">
                    <h5 class="card-title mb-0"><i class="fas fa-flask me-2"></i>Analyses de laboratoire</h5>
                </div>
                <div class="card-body">
                    @forelse($demandesLabo as $d)
                        @php($cr = $d->comptesRendus->first())
                        <div class="p-3 mb-3" style="border:1px solid var(--hali-bordure, #e5e7eb); border-radius:10px; {{ $d->urgence ? 'border-left:4px solid var(--hali-danger, #b91c1c);' : '' }}">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <strong>{{ $d->numero }}</strong>
                                <span class="badge badge-{{ $d->statut->couleur() }}">{{ $d->statut->libelle() }}</span>
                                @if($d->urgence)<span class="hl-statut hl-s-danger"><i class="fas fa-bolt"></i> Urgent</span>@endif
                                <span class="small text-muted">{{ $d->created_at->format('d/m/Y H:i') }}</span>
                                <div class="ms-auto d-flex gap-2">
                                    @if($cr)
                                        @can('labo.compte_rendu.view')
                                            <a href="{{ route('labo.comptes-rendus.pdf', $cr) }}" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="fas fa-file-pdf me-1"></i>Compte rendu v{{ $cr->version }}@if($cr->est_rectificatif) (rectificatif)@endif
                                            </a>
                                        @endcan
                                    @endif
                                    <a href="{{ route('labo.demandes.show', $d) }}" class="btn btn-sm btn-outline-secondary">Détail</a>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($d->examens as $l)
                                    @php($anormaux = $l->estVerrouille() ? $l->resultats->filter(fn ($r) => $r->flag && $r->flag !== \App\Enums\Labo\FlagResultat::NORMAL) : collect())
                                    <span class="badge rounded-pill {{ $l->statut === \App\Enums\Labo\StatutExamen::ANNULE ? 'bg-light text-muted text-decoration-line-through' : 'bg-light text-dark border' }}">
                                        {{ $l->examen_nom }} · {{ $l->statut->libelle() }}
                                        @if($anormaux->contains(fn ($r) => $r->flag->estCritique()))
                                            <span class="text-danger fw-bold">· critique</span>
                                        @elseif($anormaux->isNotEmpty())
                                            <span class="text-warning fw-bold">· {{ $anormaux->count() }} anormal(aux)</span>
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-2">Aucune analyse demandée au laboratoire pour cette consultation.</p>
                    @endforelse

                    @if($testsNonRelies->isNotEmpty())
                        <div class="alert alert-warning small mb-0">
                            Examens prescrits sans correspondance dans le catalogue du laboratoire :
                            <strong>{{ $testsNonRelies->pluck('name')->implode(', ') }}</strong>.
                            @can('labo.catalogue.manage')
                                Reliez-les dans <a href="{{ route('labo.catalogue.index') }}">Laboratoire › Catalogue</a> (champ « Test de l'ancien catalogue »).
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
