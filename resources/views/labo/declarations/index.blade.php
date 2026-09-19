@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@php use App\Models\Labo\LaboDeclarationMdo as MDO; @endphp

@section('content')
<div class="container"><div class="page-inner hl">
    @include('labo.partials.entete', ['titre' => 'Maladies à déclaration obligatoire', 'fil' => [route('labo.declarations.index') => 'Déclarations']])

    <p class="lb-alerte lb-alerte-info"><i class="fas fa-info-circle mt-1" aria-hidden="true"></i><span>Le logiciel <strong>rappelle</strong> les déclarations et trace qui les a faites. La notification elle-même se fait auprès de l'autorité sanitaire, selon la procédure en vigueur (fiche, téléphone, plateforme). La liste des maladies et les délais se valident avec l'autorité sanitaire, puis se règlent dans le catalogue.</span></p>

    <section class="hl-bloc">
        <div style="padding:14px 18px; border-bottom:1px solid var(--hali-bordure)">
            <div class="hl-puces" role="group" aria-label="Statut">
                @foreach(MDO::STATUTS as $code => $libelle)
                    <a class="hl-puce {{ $statut === $code ? 'est-actif' : '' }}" href="{{ route('labo.declarations.index', ['statut' => $code]) }}">{{ $libelle }} <b>{{ $compteurs[$code] ?? 0 }}</b></a>
                @endforeach
            </div>
        </div>

        @if($declarations->isEmpty())
            <div class="hl-vide"><i class="fas fa-bullhorn" aria-hidden="true"></i>Aucune déclaration dans cette liste.</div>
        @else
            <div class="table-responsive">
                <table class="lb-table">
                    <thead><tr><th>Maladie</th><th>Patient</th><th>Demande</th><th>Validé le</th><th>Prescripteur</th><th></th></tr></thead>
                    <tbody>
                    @foreach($declarations as $d)
                        @php($ligne = $d->demandeExamen)
                        <tr class="{{ $d->enRetard() ? 'labo-ligne-critique' : '' }}">
                            <td>
                                <span class="lb-fort">{{ $d->maladie }}</span>
                                @if($d->immediate) <span class="hl-statut hl-s-danger">Immédiate</span>@endif
                                @if($d->enRetard())<span class="lb-sous" style="color:var(--hali-danger); font-weight:700">Délai indicatif dépassé</span>@endif
                            </td>
                            <td>{{ $ligne->demande->patient->full_name }}<span class="lb-sous">{{ $ligne->demande->patient->gender }} · {{ $ligne->demande->patient->age_texte ?? '' }}</span></td>
                            <td><a href="{{ route('labo.demandes.show', $ligne->demande) }}" class="lb-fort">{{ $ligne->demande->numero }}</a><span class="lb-sous">{{ $ligne->examen_nom }}</span></td>
                            <td style="font-size:.84rem">{{ $ligne->valide_biologique_le?->format('d/m/Y H:i') }}</td>
                            <td style="font-size:.84rem">{{ $ligne->demande->nomPrescripteur() }}</td>
                            <td class="lb-actions">
                                @if($d->statut === MDO::A_DECLARER)
                                    <button type="button" class="hl-bouton hl-bouton-plein lb-petit btn-declarer" data-action="{{ route('labo.declarations.declaree', $d) }}" data-maladie="{{ $d->maladie }}">Marquer déclarée</button>
                                @elseif($d->statut === MDO::DECLAREE)
                                    <span style="font-size:.82rem; white-space:normal">{{ $d->declaree_le?->format('d/m/Y') }} → {{ $d->destinataire }}
                                        <span class="lb-sous">@if($d->reference)Réf. {{ $d->reference }} · @endif{{ $d->declareePar?->name }}</span></span>
                                @else
                                    <span class="lb-sous">{{ $d->commentaire }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if($declarations->hasPages())
                <div style="padding:14px 18px; border-top:1px solid var(--hali-bordure); display:flex; justify-content:center">{{ $declarations->withQueryString()->links() }}</div>
            @endif
        @endif
    </section>
</div></div>

<div class="modal fade" id="modalDeclarer" tabindex="-1"><div class="modal-dialog"><form method="POST" id="formDeclarer" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Déclaration : <span id="declarerMaladie"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="form-label">Destinataire (service ou personne notifiée)</label>
        <input name="destinataire" class="form-control mb-2" required maxlength="255" placeholder="ex. Point focal surveillance de la DPS">
        <label class="form-label">Référence de la fiche de notification</label>
        <input name="reference" class="form-control mb-2" maxlength="100">
        <label class="form-label">Date de la déclaration</label>
        <input type="datetime-local" name="declaree_le" class="form-control mb-2" value="{{ now()->format('Y-m-d\TH:i') }}">
        <label class="form-label">Commentaire</label>
        <textarea name="commentaire" class="form-control" rows="2"></textarea>
    </div>
    <div class="modal-footer"><button class="hl-bouton hl-bouton-plein">Enregistrer la déclaration</button></div>
</form></div></div>
@endsection

@section('script')
<script>
document.querySelectorAll('.btn-declarer').forEach(b => b.addEventListener('click', function () {
    document.getElementById('formDeclarer').action = this.dataset.action;
    document.getElementById('declarerMaladie').textContent = this.dataset.maladie;
    new bootstrap.Modal(document.getElementById('modalDeclarer')).show();
}));
</script>
@endsection
