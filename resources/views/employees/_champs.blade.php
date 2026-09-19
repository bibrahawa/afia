{{-- Champs d'une fiche employé. $employee : fiche ou null ; $departments. --}}
@php $employee = $employee ?? null; @endphp
<div class="em-trois">
    <div><label class="cat-l" for="first_name">Prénom</label><input id="first_name" name="first_name" type="text" class="form-control" value="{{ old('first_name', $employee?->first_name) }}" placeholder="Sans « Dr » : il est ajouté automatiquement" required></div>
    <div><label class="cat-l" for="middle_name">Deuxième prénom</label><input id="middle_name" name="middle_name" type="text" class="form-control" value="{{ old('middle_name', $employee?->middle_name) }}"></div>
    <div><label class="cat-l" for="last_name">Nom</label><input id="last_name" name="last_name" type="text" class="form-control" value="{{ old('last_name', $employee?->last_name) }}" required></div>
</div>

<div>
    <span class="cat-l">Fonction</span>
    <div class="em-types" role="radiogroup" aria-label="Fonction">
        @foreach(\App\Models\Employee::TYPES as $valeur => $libelle)
            <label><input type="radio" name="type" value="{{ $valeur }}" @checked(old('type', $employee ? ($employee->type ?: 'Other') : 'Doctor') === $valeur) required><span>{{ $libelle }}</span></label>
        @endforeach
    </div>
    <p class="cat-aide">Seuls les « Médecins » reçoivent des patients : file d'attente, rendez-vous, consultations.</p>
</div>

<div class="em-deux">
    <div><label class="cat-l" for="department_id">Département</label>
        <select name="department_id" id="department_id" class="form-control" required>
            <option value="">Choisir…</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected((int) old('department_id', $employee?->department_id) === $d->id)>{{ $d->name }}</option>@endforeach
        </select></div>
    <div><label class="cat-l" for="speciality">Spécialité</label><input id="speciality" name="speciality" type="text" class="form-control" value="{{ old('speciality', $employee?->speciality) }}" placeholder="Gynécologue-obstétricien, pédiatre…"></div>
</div>

<div><label class="cat-l" for="address">Adresse</label><input id="address" name="address" type="text" class="form-control" value="{{ old('address', $employee?->address) }}" placeholder="Quartier, commune"></div>

<details class="em-plus" @if(old('education', $employee?->education) || old('certificate', $employee?->certificate) || old('description', $employee?->description)) open @endif>
    <summary>Parcours et présentation <span>facultatif</span></summary>
    <div class="em-plus-corps">
        <div><label class="cat-l" for="education">Formation</label><textarea id="education" name="education" class="form-control" rows="2" placeholder="Doctorat en médecine, Université Gamal Abdel Nasser…">{{ old('education', $employee?->education) }}</textarea></div>
        <div><label class="cat-l" for="certificate">Diplômes et certificats</label><textarea id="certificate" name="certificate" class="form-control" rows="2">{{ old('certificate', $employee?->certificate) }}</textarea></div>
        <div><label class="cat-l" for="description">Présentation</label><textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $employee?->description) }}</textarea></div>
    </div>
</details>

@once
<style>
    .em-form { display: grid; gap: 16px; padding: 20px 22px; }
    .em-deux { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .em-trois { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    .cat-l { display: block; margin-bottom: 5px; color: var(--hali-encre); font-size: .83rem; font-weight: 650; }
    .cat-aide { margin: 5px 0 0; color: var(--hali-discret); font-size: .78rem; }
    .em-types { display: flex; flex-wrap: wrap; gap: 8px; }
    .em-types label { margin: 0; }
    .em-types input { position: absolute; opacity: 0; pointer-events: none; }
    .em-types span { display: inline-flex; align-items: center; min-height: 38px; padding: 0 14px; border: 1px solid var(--hali-bordure); border-radius: 999px; background: #fff; font-size: .86rem; font-weight: 600; cursor: pointer; }
    .em-types input:checked + span { background: var(--hali-primaire); border-color: var(--hali-primaire); color: #fff; }
    .em-types input:focus-visible + span { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
    .em-plus { border: 1px solid var(--hali-bordure); border-radius: 12px; }
    .em-plus summary { padding: 12px 14px; color: var(--hali-encre); font-weight: 650; cursor: pointer; }
    .em-plus summary span { color: var(--hali-discret); font-size: .78rem; font-weight: 500; }
    .em-plus-corps { display: grid; gap: 12px; padding: 0 14px 14px; }
    .em-pied { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; padding: 0 22px 20px; }
    @media (max-width: 767.98px) { .em-deux, .em-trois { grid-template-columns: 1fr; } }
</style>
@endonce
