<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Aprosafe</title>
  <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    body {
      background: linear-gradient(to right, #4facfe, #00f2fe);
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-container {
      background: rgba(255, 255, 255, 0.9);
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      animation: fadeIn 1s ease-in-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .form-control {
      border-radius: 30px;
    }
    .btn-primary {
      border-radius: 30px;
      transition: 0.3s;
    }
    .btn-primary:hover {
      background: #00d4ff;
      transform: scale(1.05);
    }
    .toggle-password {
      cursor: pointer;
      position: absolute;
      right: 15px;
      top: 77%;
      transform: translateY(-50%);
    }
    .form-label {
      display: block;
      text-align: left;
      font-weight: 500;
    }
  </style>
</head>
<body>

  <div class="login-container text-center">
    <img src="{{ asset('assets/img/logo.jpeg') }}" alt="Logo" class="mb-3" width="100">
    <h3 class="mb-3">Connexion</h3>

    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif


    <form method="POST" action="{{ route('login') }}">
        @csrf
        @method('POST')
      <div class="mb-3 position-relative text-start">
        <label for="phone" class="form-label">Téléphone</label>
        <input type="text" name="phone" :value="old('phone')" class="form-control" id="phone" placeholder="Téléphone" required>
      </div>
      <div class="mb-3 position-relative text-start">
        <label for="password" class="form-label">Mot de passe</label>
        <input type="password" name="password" class="form-control" id="password" placeholder="Mot de passe" required>
        <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
      </div>
      <button type="submit" class="btn btn-primary w-100">Se connecter</button>
    </form>
  </div>
  <script>
    document.getElementById('togglePassword').addEventListener('click', function () {
      const passwordInput = document.getElementById('password');
      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        this.classList.replace('bi-eye-slash', 'bi-eye');
      } else {
        passwordInput.type = 'password';
        this.classList.replace('bi-eye', 'bi-eye-slash');
      }
    });
  </script>
</body>
</html>
