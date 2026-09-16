
<div class="card">
    <div class="card-header"><h5 class="card-title">Liens familiaux</h5></div>
    <div class="card-body">
        <p class="small text-muted">
            Un lien familial ne donne, par lui-même, AUCUN accès aux données de santé de l'autre
            personne — sauf tutelle légale d'un mineur. Pour partager le dossier, utilise l'onglet
            « Accès & consentement ».
        </p>

        <ul class="list-group mb-3">
            @forelse($patient->relationsFamiliales as $r)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <strong>{{ ucfirst($r->type_relation->value) }}</strong> : {{ $r->personneLiee->getFullName() }}
                        <span class="text-muted">({{ $r->personneLiee->identifiant_national_sante }})</span>
                        @if($r->verifie_le)
                            <span class="badge badge-success ms-1">Vérifié</span>
                        @endif
                    </span>
                    @can('patient.edit')
                        <form action="{{ route('relations-familiales.delete', $r) }}" method="POST" onsubmit="return confirm('Supprimer ce lien ?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                        </form>
                    @endcan
                </li>
            @empty
                <li class="list-group-item text-muted">Aucun lien familial enregistré.</li>
            @endforelse
        </ul>

        @can('patient.edit')
        <form action="{{ route('relations-familiales.store', $patient) }}" method="POST" class="row g-2">
            @csrf
            <div class="col-sm-5">
                <input type="text" name="identifiant_national_sante" class="form-control" placeholder="Identifiant national santé de l'autre personne" required>
            </div>
            <div class="col-sm-4">
                <select name="type_relation" class="form-control" required>
                    <option value="pere">Père de ce patient</option>
                    <option value="mere">Mère de ce patient</option>
                    <option value="enfant">Enfant de ce patient</option>
                    <option value="epoux">Époux</option>
                    <option value="epouse">Épouse</option>
                    <option value="tuteur">Tuteur légal</option>
                    <option value="frere">Frère</option>
                    <option value="soeur">Sœur</option>
                </select>
            </div>
            <div class="col-sm-3">
                <button type="submit" class="btn btn-primary w-100">Ajouter</button>
            </div>
        </form>
        @endcan
    </div>
</div>
