@extends('layouts.backend')

@section('content')
<div class="container">
    <div class="page-inner">
      <div class="page-header">
        <ul class="breadcrumbs">
          <li class="nav-home"><a href="{{url('/')}}"><i class="icon-home"></i></a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item"><a href="{{ route('appointment.index') }}">Rendez-vous</a></li>
          <li class="separator"><i class="icon-arrow-right"></i></li>
          <li class="nav-item">{{ $appointment->appointment_datetime->format('d/m/Y H:i') }}</li>
        </ul>
      </div>

      <div class="row">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header"><h4 class="card-title">Détail du rendez-vous</h4></div>
            <div class="card-body">
                <table class="table">
                    <tr><th style="width:30%">Patient</th><td>
                        {{ $appointment->patient?->getFullName() }}
                        @if($appointment->patient)
                            <br><small class="text-muted">{{ $appointment->patient->identifiant_national_sante }}</small>
                        @endif
                    </td></tr>
                    <tr><th>Médecin</th><td>Dr. {{ $appointment->employee->full_name }} — {{ $appointment->employee->department->name ?? '' }}</td></tr>
                    <tr><th>Motif</th><td>{{ $appointment->motifRdv->nom ?? '—' }}</td></tr>
                    <tr><th>Date et heure</th><td>{{ $appointment->appointment_datetime->format('d/m/Y à H:i') }}</td></tr>
                    <tr><th>Durée prévue</th><td>{{ $appointment->duree_minutes }} min</td></tr>
                    <tr><th>Statut</th><td>
                        <span class="badge badge-{{ ['pending'=>'warning','confirmed'=>'success','completed'=>'primary','cancelled'=>'danger','no_show'=>'secondary'][$appointment->status] }}">
                            {{ ['pending'=>'En attente','confirmed'=>'Confirmé','completed'=>'Terminé','cancelled'=>'Annulé','no_show'=>'Absence'][$appointment->status] }}
                        </span>
                    </td></tr>
                    @if($appointment->description)
                        <tr><th>Notes du patient</th><td>{{ $appointment->description }}</td></tr>
                    @endif
                    @if($appointment->status === 'cancelled')
                        <tr><th>Motif d'annulation</th><td>{{ $appointment->cancellation_reason }}</td></tr>
                    @endif
                </table>

                @can('appointment.edit')
                    @if($appointment->canBeCancelled())
                        <div class="mb-4 border rounded p-3">
                            <h5>Reprogrammer</h5>
                            <p class="text-muted small">Même médecin, même motif — seule la date/heure change.</p>
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <select id="reprogDate" class="form-control"><option value="">Chargement des dates…</option></select>
                                </div>
                                <div class="col-md-4">
                                    <select id="reprogHeure" class="form-control" disabled><option value="">Choisir une date</option></select>
                                </div>
                                <div class="col-md-3">
                                    <button type="button" id="reprogBouton" class="btn btn-primary w-100" disabled>Reprogrammer</button>
                                </div>
                            </div>
                            <p class="text-danger small mt-1" id="reprogErreur"></p>
                        </div>
                    @endif

                    @if($appointment->canBeCancelled())
                        <form action="{{ route('appointment.cancel', $appointment) }}" method="POST" onsubmit="return confirm('Annuler ce rendez-vous ?');">
                            @csrf @method('DELETE')
                            <div class="mb-2">
                                <label>Motif d'annulation (facultatif)</label>
                                <input type="text" name="reason" class="form-control">
                            </div>
                            <button type="submit" class="btn btn-danger">Annuler ce rendez-vous</button>
                        </form>
                    @endif
                @endcan
            </div>
          </div>
        </div>
      </div>
    </div>
</div>

<script>
(function(){
    const employeeId = {{ $appointment->employee_id }};
    const motifId = {{ $appointment->motif_rdv_id ?? 'null' }};
    const appointmentId = {{ $appointment->id }};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    const dateSelect = document.getElementById('reprogDate');
    const heureSelect = document.getElementById('reprogHeure');
    const bouton = document.getElementById('reprogBouton');

    if (!dateSelect) return;

    fetch(`{{ url('/appointment-form/dates-disponibles') }}?employee_id=${employeeId}&motif_rdv_id=${motifId}`)
        .then(r => r.json()).then(jours => {
            dateSelect.innerHTML = '<option value="">Choisir une date</option>';
            jours.forEach(iso => {
                const opt = document.createElement('option');
                opt.value = iso;
                opt.textContent = new Date(iso + 'T00:00:00').toLocaleDateString('fr-FR', {weekday:'short', day:'numeric', month:'short'});
                dateSelect.appendChild(opt);
            });
        });

    dateSelect.addEventListener('change', function(){
        heureSelect.disabled = true;
        heureSelect.innerHTML = '<option value="">Chargement…</option>';
        bouton.disabled = true;
        if (!this.value) return;

        fetch(`{{ url('/appointment-form/creneaux') }}?employee_id=${employeeId}&motif_rdv_id=${motifId}&date=${this.value}`)
            .then(r => r.json()).then(creneaux => {
                heureSelect.innerHTML = '<option value="">Choisir une heure</option>';
                creneaux.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.debut; opt.textContent = c.debut;
                    heureSelect.appendChild(opt);
                });
                heureSelect.disabled = false;
            });
    });

    heureSelect.addEventListener('change', function(){
        bouton.disabled = !this.value;
    });

    bouton.addEventListener('click', function(){
        this.disabled = true;
        fetch(`{{ url('/appointment') }}/${appointmentId}/reprogrammer`, {
            method: 'PUT',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf},
            body: JSON.stringify({ appointment_date: dateSelect.value, appointment_time: heureSelect.value })
        }).then(r => { if(r.ok || r.redirected) { window.location.reload(); } else { r.json().then(d => document.getElementById('reprogErreur').textContent = d.error || 'Erreur.'); this.disabled = false; } });
    });
})();
</script>
@endsection
