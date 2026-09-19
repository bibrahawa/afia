@extends('layouts.backend')

@section('style')
<style>
  .form-check {
      margin-bottom: 0.25rem;
  }

  .form-check-label {
      font-size: 0.9rem;
      cursor: pointer;
  }

  .card-header h6 {
      font-weight: 600;
  }

  .badge {
      font-size: 8px !important;
      padding: 0.2em 0.4em;
  }

  .btn-group .btn {
      font-size: 0.8rem;
      padding: 0.25rem 0.5rem;
  }

  .module-checkbox:indeterminate {
      opacity: 0.5;
  }
</style>
@endsection

@section('content')
<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs">
                <li class="nav-home"><a href="{{ url('/') }}"><i class="icon-home"></i></a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item"><a href="{{ route('users.index') }}">Users</a></li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item">Permissions - {{ $user->name }}</li>
            </ul>
        </div>

        <div class="row">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-shield-alt"></i> Gestion des Permissions - {{ $user->name }}</h5>
                    <span class="badge bg-light text-primary">
                        <span id="selectedCount">{{ $userPermissionsCount }}</span> / {{ $totalPermissions }} permissions
                    </span>
                </div>

                <div class="card-body">
                    <!-- Boutons rapides -->
                    <div class="mb-4">
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="selectAll()">Tout sélectionner</button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deselectAll()">Tout décocher</button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectByRole('admin')">Profil Admin</button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="selectByRole('medecin')">Profil Médecin</button>
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="selectByRole('comptable')">Profil Comptable</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="selectByRole('secretaire')">Profil Secrétaire</button>
                        </div>
                    </div>

                    <form action="{{ route('users.store_permissions', $user->id) }}" method="POST" id="permissionsForm">
                        @csrf
                        @foreach ($modules as $moduleTitle => $permissions)
                            @php 
                                $moduleClass = strtolower(str_replace([' ', '&'], ['_', '_'], $moduleTitle)); 
                            @endphp
                            <div class="card mb-3">
                                <div class="card-header bg-light d-flex justify-content-between">
                                    <h6 class="mb-0 text-primary">
                                        <i class="fas fa-folder"></i> {{ $moduleTitle }}
                                        <span class="badge bg-secondary ms-2">{{ $permissions->count() }}</span>
                                    </h6>
                                    <div class="form-check">
                                        <input class="form-check-input module-checkbox" type="checkbox" onchange="toggleModule('{{ $moduleClass }}')" id="module_{{ $moduleClass }}">
                                        <label class="form-check-label text-muted">Tout le module</label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach ($permissions as $permission)
                                        @php
                                            $action = explode('.', $permission->name)[1] ?? '';
                                            $badgeClass = match($action) {
                                                'view' => 'bg-info',
                                                'create' => 'bg-success',
                                                'edit', 'update' => 'bg-warning',
                                                'delete', 'destroy' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <div class="col-lg-3 col-md-4 col-sm-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input permission-checkbox module-{{ $moduleClass }}" 
                                                    type="checkbox" 
                                                    name="permissions[]" 
                                                    value="{{ $permission->name }}"
                                                    {{ in_array($permission->name, $userPermissions) ? 'checked' : '' }}>
                                                <label class="form-check-label">
                                                    <small>{{ $permission->name }}</small>
                                                    <span class="badge {{ $badgeClass }} ms-1" style="font-size:8px;">{{ $action }}</span>
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="text-center mt-4">
                            @can('users.permissions')
                                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Enregistrer</button>
                            @endcan
                            @can('users.view')
                                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-lg"><i class="fas fa-arrow-left"></i> Retour</a>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const rolePermissions = @json($rolePermissions);

    function selectAll() {
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
        updateModuleCheckboxes();
        updateCounter();
    }

    function deselectAll() {
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
        updateModuleCheckboxes();
        updateCounter();
    }

    function selectByRole(role) {
        if (!rolePermissions[role]) return;
        deselectAll();
        document.querySelectorAll('.permission-checkbox').forEach(cb => {
            if (rolePermissions[role].includes(cb.value)) cb.checked = true;
        });
        updateModuleCheckboxes();
        updateCounter();
    }

    function toggleModule(moduleClass) {
        const isChecked = document.getElementById('module_' + moduleClass).checked;
        document.querySelectorAll('.module-' + moduleClass).forEach(cb => cb.checked = isChecked);
        updateCounter();
    }

    function updateModuleCheckboxes() {
        document.querySelectorAll('.module-checkbox').forEach(moduleCheckbox => {
            const moduleClass = moduleCheckbox.id.replace('module_', '');
            const modulePermissions = document.querySelectorAll('.module-' + moduleClass);
            const checkedCount = Array.from(modulePermissions).filter(cb => cb.checked).length;
            moduleCheckbox.checked = (checkedCount === modulePermissions.length);
            moduleCheckbox.indeterminate = (checkedCount > 0 && checkedCount < modulePermissions.length);
        });
    }

    function updateCounter() {
        const count = document.querySelectorAll('.permission-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = count;
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.addEventListener('change', () => {
            updateModuleCheckboxes();
            updateCounter();
        }));
        updateModuleCheckboxes();
    });
</script>
@endsection