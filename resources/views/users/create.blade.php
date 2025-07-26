@extends('layouts.backend')
@section('content')



<style>
    .toggle-password {
      cursor: pointer;
      position: absolute;
      right: 15px;
      top: 80%;
      transform: translateY(-50%);
    }
    .password-item{
      cursor: pointer;
      position: absolute;
      left: 47%;
      top: 80%;
      transform: translateY(-50%);
    }

</style>

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
            <a href="{{ route('service.index') }}">Creer un utilisateur</a>
            </li>
        </ul>
        </div>

        <div class="row">
            <div class="card shadow-lg">
            <br>
            <div class="bg-primary text-white text-center">
                <h5><i class="bi bi-files"></i> Ajouter un employee </h5>
            </div>
            <br>
            <div class="modal-content">
                <div class="modal-body">
                    <form id="addEmployeForm" action="{{ route('users.store') }}" method="POST"  enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <div class="form-group form-group-default">
                                    <label class="form-label"><i class="bi bi-person"></i>Pseudo</label>
                                    <input type="text" name="name" class="form-control" placeholder="Entrez votre pseudo" required>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>First Name:</label>
                                    <input id="first_name" name="first_name" type="text" class="form-control" placeholder="Entrez first name" required/>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Last Name:</label>
                                    <input id="last_name" name="last_name" type="text" class="form-control" placeholder="Entrez last name" required/>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Email</label>
                                    <input id="email" name="email" type="email" class="form-control" placeholder="Entrez l'email" required/>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Telephone</label>
                                    <input name="phone" type="phone" class="form-control" placeholder="Entrez votre numero" required/>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>In-Time:</label>
                                    <input id="in_time" name="in_time" type="time" class="form-control timepicker" placeholder="Entrez votre numero" required/>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Out-Time:</label>
                                    <input id="out_time" name="out_time" type="time" class="form-control timepicker" placeholder="Entrez votre numero" required/>
                                </div>
                            </div>

                            <div class="col-sm-6 mb-3">
                                <div class="form-group form-group-default">
                                    <label class="form-label">Rôle</label>
                                    <select name="role_id" class="form-control" required>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Departement</label>
                                    <select name="department_id" class="form-control">
                                        <option disabled selected>Selectionnez un departement</option>
                                        @foreach ($departments as $department)
                                            <option value="{{$department->id}}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Address:</label>
                                    <textarea id="address" name="address" class="form-control" placeholder="Description"></textarea>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Education:</label>
                                    <textarea id="education" name="education" class="form-control" placeholder="Description"></textarea>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Certificate:</label>
                                    <textarea id="certificate" name="certificate" class="form-control" placeholder="Description"></textarea>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Speciality:</label>
                                    <textarea id="speciality" name="speciality" class="form-control" placeholder="Description"></textarea>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Descrption:</label>
                                    <textarea id="description" name="description" class="form-control" placeholder="Description"></textarea>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group form-group-default">
                                    <label>Jour ouvrable :</label>
                                    <select class="form-control" name="working_day[]" multiple>
                                        <option>Lundi</option>
                                        <option>Mardi</option>
                                        <option>Mercredi</option>
                                        <option>Jeudi</option>
                                        <option>Vendredi</option>
                                        <option>Samedi</option>
                                        <option>Dimanche</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-sm-6 mb-3">
                                <div class="form-group form-group-default">
                                    <label class="form-label"><i class="bi bi-lock"></i>Mot de passe</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••" required>
                                    <span class="input-text password-item" onclick="togglePassword('password')"><i class="bi bi-eye-slash"></i></span>
                                </div>
                            </div>

                            <div class="col-sm-6 mb-3">
                                <div class="form-group form-group-default">
                                    <label class="form-label"><i class="bi bi-lock"></i>Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" id="passwordConfirm" name="password_confirmation" placeholder="••••••" required>
                                    <span class="input-text toggle-password" onclick="togglePassword('passwordConfirm')"><i class="bi bi-eye-slash"></i></span>
                                </div>
                            </div>

                        </div>

                        <button type="submit" id="SaveBtn" class="btn btn-primary">
                            Ajouter
                            <div class="spinner-border spinner-border-sm text-light" role="status" id="addLoader" style="display: none;">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </button>

                    </form>
                </div>
            </div>
            </div>
        </div>

    </div>
</div>

@endsection

<script>
    document.getElementById('imageUpload').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImage').src = e.target.result;
                document.getElementById('previewImage').style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });

    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = field.nextElementSibling.querySelector('i');
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        } else {
            field.type = 'password';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        }
    }
</script>
