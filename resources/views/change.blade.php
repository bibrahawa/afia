<!-- Modal de changement de mot de passe -->
<div class="modal fade" id="change_password" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="passwordModalLabel">Changer le mot de passe</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <form action="{{ route('change.password') }}" method="POST">
          @csrf
          <div class="modal-body">
            <div class="mb-3">
              <label for="password" class="form-label">Nouveau mot de passe :</label>
              <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="password_confirmation" class="form-label">Confirmer le mot de passe :</label>
              <input type="password" id="password_confirmation" class="form-control" name="password_confirmation" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
              <i class="bi bi-x-circle"></i> Annuler
            </button>
            <button type="submit" class="btn btn-danger">
              <i class="bi bi-check-circle"></i> Changer le mot de passe
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Gestionnaire d'événement pour ouvrir la modal
      document.getElementById('password_change').addEventListener('click', function() {
        const passwordModal = new bootstrap.Modal(document.getElementById('change_password'));
        passwordModal.show();
      });
    });
  </script>
