@extends('layouts.index')

@section('content')


<div class="pagetitle">
    <h1>Liste des utilisateurs</h1>
    <nav>
        <ol class="breadcrumb">
        <li class="breadcrumb-item active"> <a href="#">Listes des utilisateurs</a></li>
        </ol>
    </nav>
</div>


<style>
    .toggle-password {
      cursor: pointer;
      position: absolute;
      right: 15px;
      top: 77%;
      transform: translateY(-50%);
    }
</style>


<section class="section">
      
    <div class="row">  
        <div class="mb-3">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left-circle"></i> Retour à la liste
            </a>
        </div> 
        <div class="card shadow-lg">
          <div class="bg-primary text-white text-center">
              <h5><i class="bi bi-files"></i> detail d'un utilisateur </h5>
          </div>
            <form action="{{ route('users.update',$user->id) }}" method="POST"enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row mt-4">
                    <div class="col-md-6 mb-3">
                        <label for="imageUpload" class="form-label">
                            <i class="fas fa-camera"></i> Photo de profil
                        </label>
                        @if(isset($user) && $user->image)
                            <img id="previewImage" class="mt-2 rounded-circle" width="80" src="{{ asset('assets/img/' . $user->image) }}">
                            <button type="button" id="viewImageBtn" class="btn btn-info mt-2" onclick="toggleImage()">Voir</button>
                        @else
                            <img id="previewImage" class="mt-2 rounded-circle" width="80" style="display:none;">
                        @endif                        
                    </div>
                        
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="bi bi-person"></i>Nom</label>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="bi bi-person"></i>Prénom</label>
                        <p>{{ $user->prenom }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                    <label class="form-label"><i class="bi bi-envelope"></i>Email</label>
                    <p>{{ $user->email }}</p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="bi bi-telephone"></i>Contact</label>
                        <p>{{ $user->contact }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Rôle</label>
                        <p>{{ $user->role }}</p>
                    </div>
                </div>                      
                <div class="row mb-3">
                    <div class="col-sm-10">
                        <a href="{{ route('users.index')}}" type="submit" class="btn btn-primary"><i class="bi bi-save"></i> retour</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

@endsection

<script>
    function toggleImage() {
        const image = document.getElementById('previewImage');
        const button = document.getElementById('viewImageBtn');

        if (image.style.display === 'none' || image.style.display === '') {
            image.style.display = 'block';
            button.innerText = 'caher';
        } else {
            image.style.display = 'none';
            button.innerText = 'voir';
        }
    }
    document.getElementById('imageUpload').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImage').src = e.target.result;
                document.getElementById('previewImage').style.display = 'block';
                document.getElementById('viewImageBtn').innerText = 'Cacher';
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