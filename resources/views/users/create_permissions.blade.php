@extends('layouts.backend')

@section('content')

<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home">
            <a href="{{url('/')}}">
              <i class="icon-home"></i>
            </a>
          </li>
          <li class="separator">
            <i class="icon-arrow-right"></i>
          </li>
          <li class="nav-item">
            <a href="{{ url('/') }}">Admin</a>
          </li>
          <li class="separator">
            <i class="icon-arrow-right"></i>
          </li>
          <li class="nav-item">
            <a href="{{ route('service.index') }}">Users</a>
          </li>
        </ul>
      </div>
        <div class="row">
            <div class="card shadow-lg">
                <div class="bg-primary text-white text-center">
                    <h5><i class="bi bi-files"></i> Permissions </h5>
                </div>
                <form action="{{ route('users.store_permissions', $user->id) }}" method="POST" id="AddBtn">
                    @csrf
                    <div class="row">
                        @foreach ($permissions as $permission)
                            <div class="col-sm-3">
                                <label class="form-label" for="tableau_bord">{{ $permission->name }}</label>
                                <input type="checkbox" name="{{ $permission->name }}" {{$user->hasPermissionTo($permission->name) ? 'checked' :''}}>
                            </div>
                        @endforeach
                        <div class="row mb-3">
                            <div class="col-sm-10">
                                <button type="submit" class="btn btn-primary" id="SaveBtn"><i class="bi bi-save"></i>Enregistrer</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
