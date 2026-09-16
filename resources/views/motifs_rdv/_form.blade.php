<div class="mb-2">
    <label>Nom du motif</label>
    <input type="text" name="nom" class="form-control" required>
</div>
<div class="row">
    <div class="col-sm-6 mb-2">
        <label>Durée (minutes)</label>
        <input type="number" name="duree_minutes_defaut" class="form-control" list="paliers-duree" min="1" max="240" value="15" required>
        <datalist id="paliers-duree">
            <option value="5"><option value="10"><option value="15">
            <option value="20"><option value="30"><option value="45"><option value="60">
        </datalist>
        <small class="text-muted">Ex : 5 min pour une interprétation de résultat, 15-20 min pour une consultation.</small>
    </div>
    <div class="col-sm-6 mb-2">
        <label>Marge tampon après (minutes)</label>
        <input type="number" name="marge_tampon_minutes" class="form-control" min="0" max="60" value="5" required>
    </div>
</div>
<div class="mb-2">
    <label>Couleur (calendrier)</label>
    <input type="color" name="couleur" class="form-control form-control-color" value="#3B82F6">
</div>
