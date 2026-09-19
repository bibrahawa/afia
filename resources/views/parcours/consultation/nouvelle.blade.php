@extends('layouts.backend')

@php $gnf = fn ($m) => number_format((float) $m, 0, ',', ' '); @endphp

@section('style')
<style>
    .cd-carte { max-width: 640px; margin: 0 auto; }
    .cd-corps { display: grid; gap: 16px; padding: 20px 22px; }
    .cd-corps .form-label, .cd-libelle { display: block; margin-bottom: 6px; color: var(--hali-encre); font-size: .85rem; font-weight: 650; }
    .cd-corps .js-recherche { min-height: 48px; font-size: 1rem; }
    .cd-corps .js-choisi { font-weight: 600; }
    .cd-motifs { display: flex; flex-wrap: wrap; gap: 8px; }
    .cd-motifs label { margin: 0; }
    .cd-motifs input { position: absolute; opacity: 0; pointer-events: none; }
    .cd-motifs span { display: inline-flex; align-items: center; gap: 7px; min-height: 36px; padding: 0 13px; border: 1px solid var(--hali-bordure); border-radius: 999px; background: #fff; color: var(--hali-texte); font-size: .85rem; font-weight: 600; cursor: pointer; }
    .cd-motifs span i { width: 9px; height: 9px; border-radius: 3px; }
    .cd-motifs input:checked + span { background: var(--hali-primaire); border-color: var(--hali-primaire); color: #fff; }
    .cd-motifs input:focus-visible + span { outline: 2px solid var(--hali-primaire); outline-offset: 2px; }
    .cd-urgence { display: inline-flex; align-items: center; gap: 8px; margin: 0; color: var(--hali-danger); font-weight: 600; font-size: .88rem; cursor: pointer; }
    .cd-pied { display: grid; gap: 10px; padding: 16px 22px 20px; border-top: 1px solid var(--hali-bordure); background: #fafbfc; border-radius: 0 0 var(--hali-rayon) var(--hali-rayon); }
    .cd-pied p { margin: 0; color: var(--hali-discret); font-size: .82rem; }
    .cd-demarrer { min-height: 50px; font-size: 1rem; }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">

    <header class="hl-entete cd-carte" style="padding:0">
        <div>
            <h1>Recevoir un patient</h1>
            <p>{{ $medecin->nom_affiche }} · le patient passe directement en consultation avec vous.</p>
        </div>
        <div class="hl-entete-actions">
            <a href="{{ route('parcours.file.index') }}" class="hl-bouton">Ma file d'attente</a>
        </div>
    </header>

    <form method="POST" action="{{ route('parcours.consultation.directe') }}" class="hl-bloc cd-carte" id="cdForm">
        @csrf
        <div class="cd-corps">
            @include('assurance.partials.choix-patient', ['id' => 'directe', 'libelle' => 'Patient', 'url' => route('parcours.consultation.patients.recherche')])
            @if(Route::has('patient.index'))
                <a href="{{ route('patient.index') }}" style="margin-top:-10px; font-size:.82rem; font-weight:600">Nouveau patient ? Créez sa fiche, puis revenez ici.</a>
            @endif

            @if($motifs->isNotEmpty())
                <div>
                    <span class="cd-libelle">Motif</span>
                    <div class="cd-motifs" role="radiogroup" aria-label="Motif">
                        <label><input type="radio" name="motif_rdv_id" value="" data-service="" checked><span>Consultation</span></label>
                        @foreach($motifs as $m)
                            <label><input type="radio" name="motif_rdv_id" value="{{ $m->id }}" data-service="{{ $m->service_id }}"><span>@if($m->couleur)<i style="background:{{ $m->couleur }}"></i>@endif{{ $m->nom }}</span></label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <label class="cd-libelle" for="cdActe">Acte à facturer</label>
                <select name="service_id" id="cdActe" class="form-control">
                    <option value="">Aucun pour l'instant (à ajouter pendant la consultation)</option>
                    @foreach($services as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} — {{ $gnf($s->amount) }} GNF</option>
                    @endforeach
                </select>
            </div>

            <label class="cd-urgence"><input type="hidden" name="urgence" value="0"><input type="checkbox" name="urgence" value="1"> Urgence</label>
        </div>

        <div class="cd-pied">
            <button type="submit" class="hl-bouton hl-bouton-plein cd-demarrer" id="cdDemarrer"><i class="fas fa-stethoscope" aria-hidden="true"></i> Commencer la consultation</button>
            <p>Le patient est enregistré comme arrivé et pris en charge par vous : il apparaît dans la file, les statistiques et son dossier, et l'acte choisi est envoyé à la caisse.</p>
        </div>
    </form>
</div></div>
@endsection

@section('script')
    @include('assurance.partials.choix-patient-script')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var acte = document.getElementById('cdActe');
        // Le motif propose l'acte qui lui est associé, modifiable.
        document.querySelectorAll('input[name="motif_rdv_id"]').forEach(function (m) {
            m.addEventListener('change', function () { if (m.dataset.service) acte.value = m.dataset.service; });
        });
        // Un seul envoi, même si la connexion traîne.
        document.getElementById('cdForm').addEventListener('submit', function (e) {
            if (!document.querySelector('#cdForm .js-patient-id').value) {
                e.preventDefault();
                document.getElementById('directe-recherche').focus();
                var c = document.querySelector('#cdForm .js-choisi'); c.textContent = 'Choisissez le patient dans la liste.'; c.style.color = 'var(--hali-danger)';
                return;
            }
            var b = document.getElementById('cdDemarrer'); b.disabled = true; b.style.opacity = '.7';
        });
    });
    </script>
@endsection
