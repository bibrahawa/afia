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
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="d-flex align-items-center">
                    <h4 class="card-title">Liste des users</h4>
                    @can('users.create')
                      <a
                        class="btn btn-primary btn-round ms-auto"
                        href="{{ url('users/create')}}"
                      >
                        <i class="fa fa-plus"></i> Ajouter un utilisateur
                      </a>
                    @endcan
                  </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="add-row" class="display table table-striped table-hover">
                            <thead class="bg-primary text-white"> <!-- Ajout de couleur d'entête -->
                                <tr>
                                    <th>N</th>
                                    <th style="text-align: center">Nom</th>
                                    <th style="text-align: center">Telephone</th>
                                    <th style="text-align: center">Status</th>
                                    <th style="text-align: center">Role</th>
                                    <th style="text-align: center">Action</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th>N</th>
                                    <th style="text-align: center">Nom</th>
                                    <th style="text-align: center">Telephone</th>
                                    <th style="text-align: center">Status</th>
                                    <th style="text-align: center">Role</th>
                                    <th style="text-align: center">Action</th>
                                </tr>
                            </tfoot>
                            <tbody>
                                <?php $indice = 1; ?>
                                @foreach ($users as $key=>$user )
                                  @if ($user->roles->first()?->name != 'patient')
                                    <tr>
                                        <td>{{ $indice++ }}</td>
                                        <td>{{ $user->name }} </td>
                                        <td>{{ $user->phone }}</td>
                                        <td style="text-align: center">
                                          @can('users.disable')
                                            
                                            <form id="#" action="{{ route('user.disable', $user->id) }}" method="GET">
                                                @csrf
                                                @method('PATCH')
                                                <div class="form-group">
                                                    <div class="form-check form-switch">
                                                        <input
                                                            class="form-check-input"
                                                            type="checkbox"
                                                            name="status"
                                                            onchange="this.form.submit()"
                                                            id="statusSwitch{{ $indice++ }}"
                                                            {{ $user->status ? 'checked' : '' }}>
                                                    </div>
                                                </div>
                                            </form>
                                          @endcan
                                        </td>
                                        <td>
                                          {{ $user->roles->first()?->name }}</td>
                                        <td>
                                            {{-- @can('modifier_utilisateur') --}}
                                            {{-- <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary">
                                                <i class="bi bi-pencil-square"></i>
                                            </a> --}}
                                            {{-- @endcan --}}
                                            @can('users.permissions')
                                              <a href="{{ route('users.listePermissions', $user->id) }}" type="button" class="btn btn-warning"><i class="fa fa-user-shield"></i></a>
                                            @endcan
                                            {{-- @can('afficher_utilisateur') --}}
                                                {{-- <a href="{{ route('users.show', $user->id) }}" type="button" class="btn btn-info"><i class="bi bi-eye-fill"></i></a> --}}
                                            {{-- @endcan --}}
                                        </td>
                                    </tr>
                                  @endif
                                    
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
          </div>
        </div>
    </div>


@endsection
