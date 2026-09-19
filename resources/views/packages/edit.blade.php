@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div><h1>Modifier le forfait</h1><p>{{ $package->name }}</p></div>
        <div class="hl-entete-actions"><a href="{{ route('package.index') }}" class="hl-bouton"><i class="fas fa-arrow-left" aria-hidden="true"></i> Forfaits</a></div>
    </header>

    @include('partials.catalogue')

    @if($errors->any())<div class="hl-note hl-note-danger mb-3" role="alert"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

    <form id="editPackageForm" action="{{ route('package.update', $package->id) }}" method="POST" class="hl-bloc cat-modal" style="max-width:980px">
        @csrf @method('PUT')
        <div class="modal-body" style="padding:20px 22px">@include('packages._formulaire', ['package' => $package])</div>
        <div style="display:flex; justify-content:flex-end; gap:8px; padding:0 22px 20px">
            <a href="{{ route('package.index') }}" class="hl-bouton">Annuler</a>
            <button type="submit" class="hl-bouton hl-bouton-plein" id="editRowButton">Enregistrer <span class="spinner-border spinner-border-sm" role="status" id="editLoader" style="display:none"></span></button>
        </div>
    </form>
</div></div>
@endsection

@section('script')
<script>
document.getElementById('editPackageForm').addEventListener('submit', function () {
    document.getElementById('editRowButton').disabled = true; document.getElementById('editLoader').style.display = 'inline-block';
});
</script>
@endsection
