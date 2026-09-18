@extends('layouts.backend')

@section('content')
<div class="container"><div class="page-inner">
    <div class="page-header"><h3 class="fw-bold mb-3">Rapports</h3></div>

    <div class="card"><div class="card-body">
        <form method="GET" id="periode" class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label small">Du</label>
                <input type="date" name="debut" id="debut" class="form-control form-control-sm" value="{{ $periode['debut'] }}"></div>
            <div class="col-md-3"><label class="form-label small">Au</label>
                <input type="date" name="fin" id="fin" class="form-control form-control-sm" value="{{ $periode['fin'] }}"></div>
            <div class="col-md-6 small text-muted">La période s'applique au rapport que vous ouvrez. Certains rapports donnent l'état du jour et l'indiquent.</div>
        </form>
    </div></div>

    @foreach($catalogue as $famille => $rapports)
        <h4 class="mt-4 mb-2">{{ $famille }}</h4>
        <div class="row">
            @foreach($rapports as $cle => $rapport)
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="mb-1">{{ $rapport['titre'] }}</h5>
                            <p class="small text-muted">{{ $rapport['description'] }}</p>
                            <a href="{{ route('rapports.show', $cle) }}" class="btn btn-sm btn-primary js-ouvrir" data-cle="{{ $cle }}">Ouvrir</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</div></div>
@endsection

@section('script')
<script>
    // Les liens emportent la période choisie en haut de page.
    document.querySelectorAll('.js-ouvrir').forEach(function (lien) {
        lien.addEventListener('click', function (e) {
            e.preventDefault();
            const parametres = new URLSearchParams({
                debut: document.getElementById('debut').value,
                fin: document.getElementById('fin').value,
            });
            window.location = lien.getAttribute('href') + '?' + parametres.toString();
        });
    });
</script>
@endsection
