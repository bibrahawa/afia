<div class="row">
    <div class="col-sm-12 mb-2">
        <label>Nom</label>
        <input type="text" name="nom" class="form-control" required>
    </div>
    <div class="col-sm-6 mb-2">
        <label>Type</label>
        <select name="type" class="form-control" required>
            <option value="clinique">Clinique</option>
            <option value="laboratoire">Laboratoire</option>
            <option value="pharmacie">Pharmacie</option>
            <option value="cabinet">Cabinet</option>
        </select>
    </div>
    <div class="col-sm-6 mb-2">
        <label>Statut</label>
        <select name="statut" class="form-control" required>
            <option value="essai">Essai</option>
            <option value="actif">Actif</option>
            <option value="suspendu">Suspendu</option>
            <option value="resilie">Résilié</option>
        </select>
    </div>
    <div class="col-sm-12 mb-2">
        <label>Adresse</label>
        <input type="text" name="adresse" class="form-control">
    </div>
    <div class="col-sm-6 mb-2">
        <label>Contact</label>
        <input type="text" name="contact" class="form-control">
    </div>
    <div class="col-sm-6 mb-2">
        <label>Email</label>
        <input type="email" name="email" class="form-control">
    </div>
    <div class="col-sm-12 mb-2">
        <label>Nom d'expéditeur SMS</label>
        <input type="text" name="sms_expediteur" class="form-control" maxlength="11" pattern="[A-Za-z0-9 \-]{1,11}" placeholder="{{ \App\Support\Marque::expediteurSms() }}" style="text-transform:uppercase; letter-spacing:.06em">
        <small class="text-muted">11 caractères, sans accent. À faire enregistrer chez Nimba AVANT de le saisir, sinon les SMS seront refusés. Vide : « {{ \App\Support\Marque::expediteurSms() }} ».</small>
    </div>
</div>
