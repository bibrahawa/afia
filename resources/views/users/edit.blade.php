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
              <h5><i class="bi bi-files"></i> Modifier un utilisateur </h5>
          </div>
                  <form action="{{ route('users.update',$user->id) }}" method="POST"enctype="multipart/form-data" id="EditBtn">
                    @csrf
                    @method('PUT')

                    <div class="row mt-4">

                        <div class="col-md-6 mb-3">
                            <label for="imageUpload" class="form-label">
                                <i class="fas fa-camera"></i> Photo de profil
                            </label>
                            <input type="file" name="image" class="form-control" id="imageUpload" accept="image/*">

                            @if(isset($user) && $user->image)
                                <img id="previewImage" class="mt-2 rounded-circle" width="80" src="{{ asset('assets/img/' . $user->image) }}">
                            <button type="button" id="viewImageBtn" class="btn btn-info mt-2" onclick="toggleImage()">Voir</button>
                            @else
                                <img id="previewImage" class="mt-2 rounded-circle" width="80" style="display:none;">
                            @endif                        
                        </div>
                        
                      <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="bi bi-person"></i>Nom</label>
                        <input type="text" value="{{ $user->nom }}" name="nom" class="form-control" placeholder="Entrez votre nom" required>
                      </div>

                      <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="bi bi-person"></i>Prénom</label>
                        <input type="text" value="{{ $user->prenom }}" name="prenom" class="form-control" placeholder="Entrez votre prénom" required>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label"><i class="bi bi-envelope"></i>Email</label>
                      <input type="email" value="{{ $user->email }}" name="email" class="form-control" placeholder="exemple@email.com" required>
                        
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label"><i class="bi bi-telephone"></i>Contact</label>
                        <input type="text" value="{{ $user->contact }}" name="contact" class="form-control" placeholder="Numéro de téléphone" required>
                      </div>
                      <div class="col-md-6 mb-3">
                          <label class="form-label">Rôle</label>
                          <input type="text" value="{{ $user->role }}" name="role" class="form-control" placeholder="Entrez votre rôle">
                      </div>
                      <div class="col-md-6 mb-3">
                          <label class="form-label"><i class="bi bi-lock"></i>Mot de passe</label>
                              <input type="password" class="form-control" id="password" name="password" placeholder="••••••" required>
                              <span class="input-text toggle-password" onclick="togglePassword('password')"><i class="bi bi-eye-slash"></i></span>
                      </div>
                      <div class="col-md-6 mb-3">
                          <label class="form-label"><i class="bi bi-lock"></i>Confirmer le mot de passe</label>
                            <input type="password" class="form-control" id="passwordConfirm" name="password_confirmation" placeholder="••••••" required>
                            <span class="input-text toggle-password" onclick="togglePassword('passwordConfirm')"><i class="bi bi-eye-slash"></i></span>
                      </div>

                    </div>                      
                    <div class="row mb-3">
                        <div class="col-sm-10">
                          <button type="submit" class="btn btn-primary" id="UpdateBtn"><i class="bi bi-save"></i> Modifier</button>
                        </div>
                      </div>
                  </form>
              </div>
          </div>
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