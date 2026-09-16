@extends('layouts.backend')

@section('content')
<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home"><a href="{{url('/')}}"><i class="icon-home"></i></a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item"><a href="{{ route('etablissement.index') }}">Établissements</a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item">{{ $etablissement->nom }} — Licence</li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header"><h4 class="card-title">Modules activés pour {{ $etablissement->nom }}</h4></div>
            <div class="card-body">
                <p class="small text-muted">
                    C'est cette licence, et non le type « {{ $etablissement->type }} », qui détermine
                    précisément ce que ce client voit dans son menu et peut atteindre — un type donne
                    des modules par défaut à la création, la licence réelle se gère ici.
                </p>
                <form action="{{ route('etablissement.modules.sync', $etablissement) }}" method="POST">
                    @csrf
                    @foreach($modules as $module)
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="modules[]" value="{{ $module->id }}"
                                id="module-{{ $module->id }}" {{ in_array($module->id, $actifs) ? 'checked' : '' }}>
                            <label class="form-check-label" for="module-{{ $module->id }}">
                                <strong>{{ $module->nom }}</strong> <span class="text-muted">({{ $module->code }})</span>
                                @if($module->description)
                                    <br><small class="text-muted">{{ $module->description }}</small>
                                @endif
                            </label>
                        </div>
                    @endforeach
                    <button type="submit" class="btn btn-primary mt-3">Enregistrer la licence</button>
                </form>
            </div>
          </div>
        </div>
      </div>
    </div>
</div>
@endsection
