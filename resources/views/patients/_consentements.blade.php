@extends('layouts.backend')

@section('content')
<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home"><a href="{{url('/')}}"><i class="icon-home"></i></a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item"><a href="{{ route('patient.show', $patient) }}">{{ $patient->getFullName() }}</a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item">Accès & consentement</li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="card">
            <div class="card-header"><h5 class="card-title">Accès accordés</h5></div>
            <div class="card-body">
                <p class="small text-muted">
                    Le patient garde le contrôle : chaque accès peut être révoqué à tout moment,
                    et expire automatiquement s'il n'est pas renouvelé.
                </p>
                <ul class="list-group">
                    @forelse($consentements as $c)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span>
                                    <strong>{{ class_basename($c->beneficiaire_type) }} #{{ $c->beneficiaire_id }}</strong><br>
                                    <small class="text-muted">
                                        Portée : {{ implode(', ', $c->portee) }} —
                                        {{ $c->statut === 'actif' ? 'expire le ' . $c->expire_le?->format('d/m/Y') : ucfirst($c->statut) }}
                                    </small>
                                </span>
                                @if($c->estActif())
                                    <form action="{{ route('consentement.revoquer', $c) }}" method="POST" onsubmit="return confirm('Révoquer cet accès ?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Révoquer</button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Aucun accès accordé pour l'instant.</li>
                    @endforelse
                </ul>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card">
            <div class="card-header"><h5 class="card-title">Historique des demandes</h5></div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse($demandes as $d)
                        <li class="list-group-item">
                            <strong>{{ class_basename($d->demandeur_type) }} #{{ $d->demandeur_id }}</strong>
                            <span class="badge badge-{{ ['en_attente'=>'warning','acceptee'=>'success','refusee'=>'danger','expiree'=>'secondary'][$d->statut] }}">
                                {{ ucfirst(str_replace('_',' ', $d->statut)) }}
                            </span>
                            <br><small class="text-muted">{{ $d->created_at->format('d/m/Y H:i') }} — {{ implode(', ', $d->portee_demandee) }}</small>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">Aucune demande pour l'instant.</li>
                    @endforelse
                </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
</div>
@endsection
