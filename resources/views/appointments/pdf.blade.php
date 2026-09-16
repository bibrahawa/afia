<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Rendez-vous</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.2;
        }

        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 12px 15px;
            margin-bottom: 12px;
            border-radius: 6px;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 4px;
            font-weight: bold;
        }

        .header .info {
            font-size: 9px;
            opacity: 0.9;
        }

        .info-box {
            background: #f8f9fa;
            padding: 10px 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            border-left: 3px solid #3b82f6;
        }

        .info-box h3 {
            font-size: 11px;
            color: #3b82f6;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-item {
            display: table-cell;
            padding: 3px 8px;
            width: 25%;
        }

        .info-label {
            font-size: 8px;
            color: #6b7280;
            display: block;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 10px;
            font-weight: bold;
            color: #1f2937;
        }

        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }

        .stat-card {
            display: table-cell;
            width: 25%;
            padding: 8px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .stat-number {
            font-size: 16px;
            font-weight: bold;
            color: #3b82f6;
            display: block;
            line-height: 1;
        }

        .stat-label {
            font-size: 8px;
            color: #6b7280;
            display: block;
            margin-top: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        thead {
            background: #3b82f6;
            color: white;
        }

        thead th {
            padding: 6px 4px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: none;
        }

        tbody td {
            padding: 5px 4px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 8px;
            vertical-align: middle;
            line-height: 1.1;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .patient-name {
            font-weight: bold;
            color: #1f2937;
            font-size: 9px;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 7px;
            font-weight: bold;
            display: inline-block;
            text-align: center;
            white-space: nowrap;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 7px;
            color: #6b7280;
        }

        .text-muted {
            color: #6b7280;
        }

        .text-truncate {
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @page {
            margin: 0mm 10mm;
        }
    </style>
</head>
<body>
    {{-- En-tête --}}
    <div class="header">
        <h1>📋 Liste des Rendez-vous</h1>
        <div class="info">
            Dr. {{ $medecin->user->name ?? 'Médecin' }} | 
            Généré le {{ $generatedAt }}
        </div>
    </div>

    {{-- Informations de filtrage --}}
    <div class="info-box">
        <h3>🔍 Critères de filtrage</h3>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Période</span>
                <span class="info-value">{{ $dateFilterLabel }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Statut</span>
                <span class="info-value">
                    @if($statusFilter === 'pending')
                        En attente
                    @elseif($statusFilter === 'confirmed')
                        Confirmé
                    @elseif($statusFilter === 'completed')
                        Terminé
                    @else
                        Tous
                    @endif
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">Recherche</span>
                <span class="info-value">{{ $searchQuery ?? '-' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Total</span>
                <span class="info-value">{{ $stats['total'] }} RDV</span>
            </div>
        </div>
    </div>

    {{-- Statistiques --}}
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-number">{{ $stats['total'] }}</span>
            <span class="stat-label">TOTAL</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" style="color: #f59e0b;">{{ $stats['pending'] }}</span>
            <span class="stat-label">EN ATTENTE</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" style="color: #3b82f6;">{{ $stats['confirmed'] }}</span>
            <span class="stat-label">CONFIRMÉS</span>
        </div>
        <div class="stat-card">
            <span class="stat-number" style="color: #10b981;">{{ $stats['completed'] }}</span>
            <span class="stat-label">TERMINÉS</span>
        </div>
    </div>

    {{-- Tableau des rendez-vous --}}
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">#</th>
                <th style="width: 18%;">Patient</th>
                <th style="width: 11%;">Téléphone</th>
                <th style="width: 13%;">Date</th>
                <th style="width: 7%;">Heure</th>
                <th style="width: 28%;">Notes</th>
                <th style="width: 11%;">Statut</th>
                <th style="width: 9%;">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $index => $appointment)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <span class="patient-name">
                            {{ $appointment->patient->first_name ?? '' }} 
                            {{ $appointment->patient->last_name ?? '' }}
                        </span>
                    </td>
                    <td>
                        @if(isset($patient->telephone))
                            {{ $patient->telephone }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        {{ \Carbon\Carbon::parse($appointment->appointment_date)->locale('fr')->isoFormat('DD MMM YY') }}
                    </td>
                    <td>
                        {{ $appointment->appointment_time->format('H:i') }}
                    </td>
                    <td>
                        @if($appointment->reason)
                            <span>{{ $appointment->reason }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="status-badge 
                            {{ $appointment->status === 'pending' ? 'status-pending' :
                            ($appointment->status === 'confirmed' ? 'status-confirmed' :
                            ($appointment->status === 'completed' ? 'status-completed' : '')) }}">
                            @if($appointment->status === 'pending')
                                En attente
                            @elseif($appointment->status === 'confirmed')
                                Confirmé
                            @elseif($appointment->status === 'completed')
                                Terminé
                            @endif
                        </span>
                    </td>
                    <td class="text-muted">
                        @if($appointment->status === 'pending')
                            À confirmer
                        @elseif($appointment->status === 'confirmed')
                            À terminer
                        @else
                            Fait
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">
                        <span class="text-muted">Aucun rendez-vous trouvé.</span>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pied de page --}}
    <div class="footer">
        <p>
            Document généré automatiquement le {{ $generatedAt }}<br>
            Ce document contient {{ $stats['total'] }} rendez-vous
            @if($statusFilter)
                (Filtré par statut: 
                @if($statusFilter === 'pending') En attente
                @elseif($statusFilter === 'confirmed') Confirmé
                @elseif($statusFilter === 'completed') Terminé
                @endif)
            @endif
        </p>
    </div>
</body>
</html>