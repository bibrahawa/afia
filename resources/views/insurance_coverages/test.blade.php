<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Select2</title>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Select2 CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        body { font-family: Arial, sans-serif; padding: 30px; }
        .form-group { margin-bottom: 20px; }
    </style>
</head>
<body>

    <h2>Test de sélection d'actes groupés avec Select2</h2>

    <form method="POST" action="#">
        @csrf

        <div class="form-group">
            <label for="coverageable_select">Acte couvert</label>
            <select id="coverageable_select" class="form-control" style="width: 100%" multiple>
                <option></option> <!-- Pour le placeholder -->

                <optgroup label="Services">
                    <option value="1" data-type="Service">Consultation générale</option>
                    <option value="2" data-type="Service">Chirurgie mineure</option>
                </optgroup>

                <optgroup label="Médicaments">
                    <option value="3" data-type="Medicament">Paracétamol 500mg</option>
                    <option value="4" data-type="Medicament">Amoxicilline 1g</option>
                </optgroup>

                <optgroup label="Examens">
                    <option value="5" data-type="Examen">Radiographie thorax</option>
                    <option value="6" data-type="Examen">ECG</option>
                </optgroup>
            </select>
        </div>

        <!-- Champs cachés que tu soumets -->
        <input type="hidden" name="coverageable_id" id="coverageable_id" />
        <input type="hidden" name="coverageable_type" id="coverageable_type" />

        <button type="submit">Soumettre</button>
    </form>

    <script>
        $(document).ready(function () {
            $('#coverageable_select').select2({
                placeholder: "Sélectionner un acte",
                allowClear: true,
                width: '100%'
            });

            $('#coverageable_select').on('select2:select', function () {
                let selected = $(this).find('option:selected');
                $('#coverageable_id').val(selected.val());
                $('#coverageable_type').val(selected.data('type'));
            });

            $('#coverageable_select').on('select2:clear', function () {
                $('#coverageable_id').val('');
                $('#coverageable_type').val('');
            });
        });
    </script>

</body>
</html>
