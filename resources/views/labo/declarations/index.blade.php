@extends('layouts.backend')
@section('style') @include('labo.partials.styles') @endsection

@php use App\Models\Labo\LaboDeclarationMdo as MDO; @endphp

@section('content')
<div class="container"><div class="page-inner">
    @include('labo.partials.entete', ['titre' => 'Maladies à déclaration obligatoire', 'fil' => [route('labo.declarations.index') => 'Déclarations']])

    <div class="alert alert-info small">
        Le logiciel <strong>rappelle</strong> les déclarations et trace qui les a faites : la notification elle-même se fait
        auprès de l'autorité sanitaire selon la procédure en vigueur (fiche, téléphone, plateforme).
        Liste des maladies et délais à valider avec l'autorité sanitaire, puis à régler dans le catalogue.
    </div>

    <ul class="nav nav-pills mb-3">
        @foreach(MDO::STATUTS as $code => $libelle)
            <li class="nav-item">
                <a class="nav-link {{ $statut === $code ? 'active' : '' }}" href="{{ route('labo.declarations.index', ['statut' => $code]) }}">
                    {{ $libelle }} <span class="badge bg-light text-dark ms-1">{{ $compteurs[$code] ?? 0 }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card"><div class="card-body table-responsive">
        <table class="table align-middle">
            <thead><tr><th>Maladie</th><th>Patient</th><th>Demande / examen</th><th>Validé le</th><th>Prescripteur</th><th></th></tr></thead>
            <tbody>
            @forelse($declarations as $d)
                @php($ligne = $d->demandeExamen)
                <tr class="{{ $d->enRetard() ? 'labo-ligne-critique' : '' }}">
                    <td>
                        <strong>{{ $d->maladie }}</strong>
                        @if($d->immediate)<span class="badge bg-danger">Immédiate</span>@endif
                        @if($d->enRetard())<br><span class="small text-danger fw-bold">Délai indicatif dépassé</span>@endif
                    </td>
                    <td>{{ $ligne->demande->patient->full_name }}<br><span class="small text-muted">{{ $ligne->demande->patient->gender }} · {{ $ligne->demande->patient->age_texte ?? '' }}</span></td>
                    <td><a href="{{ route('labo.demandes.show', $ligne->demande) }}">{{ $ligne->demande->numero }}</a><br><span class="small">{{ $ligne->examen_nom }}</span></td>
                    <td class="small">{{ $ligne->valide_biologique_le?->format('d/m/Y H:i') }}</td>
                    <td class="small">{{ $ligne->demande->nomPrescripteur() }}</td>
                    <td class="text-end">
                        @if($d->statut === MDO::A_DECLARER)
                            <button class="btn btn-sm btn-warning btn-declarer" data-action="{{ route('labo.declarations.declaree', $d) }}" data-maladie="{{ $d->maladie }}">Marquer déclarée</button>
                        @elseif($d->statut === MDO::DECLAREE)
                            <span class="small">{{ $d->declaree_le?->format('d/m/Y') }} → {{ $d->destinataire }}<br>
                                @if($d->reference)Réf. {{ $d->reference }} · @endif{{ $d->declareePar?->name }}</span>
                        @else
                            <span class="small text-muted">{{ $d->commentaire }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">Aucune déclaration dans cette liste.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $declarations->links() }}
    </div></div>
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
    <div class="modal-footer"><button class="btn btn-warning">Enregistrer la déclaration</button></div>
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
