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
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }

        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        .header h1 {
            font-size: 22px;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .header .info {
            font-size: 10px;
            opacity: 0.9;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
        }

        .info-box h3 {
            font-size: 12px;
            color: #3b82f6;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-item {
            display: table-cell;
            padding: 5px 10px;
            width: 25%;
        }

        .info-label {
            font-size: 9px;
            color: #6b7280;
            display: block;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
        }

        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .stat-card {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            background: #f8f9fa;
            border-radius: 6px;
            margin-right: 10px;
        }

        .stat-card:last-child {
            margin-right: 0;
        }

        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #3b82f6;
            display: block;
        }

        .stat-label {
            font-size: 9px;
            color: #6b7280;
            display: block;
            margin-top: 3px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background: #3b82f6;
            color: white;
        }

        thead th {
            padding: 10px 8px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9px;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tbody tr:hover {
            background-color: #f3f4f6;
        }

        .patient-name {
            font-weight: bold;
            color: #1f2937;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
            text-align: center;
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
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
        }

        .text-muted {
            color: #6b7280;
        }

        .text-truncate {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .icon {
            color: #9ca3af;
            margin-right: 3px;
        }

        .page-break {
            page-break-after: always;
        }

        @page {
            margin: 15mm;
        }
    </style>
</head>
<body>
    {{-- En-tête --}}
    <div class="header">
        <h1>📋 Liste des Rendez-vous</h1>
        <div class="info">
            {{-- Dr. {{ $medecin->user->name ?? 'Médecin' }} |  --}}
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
                <span class="info-value">{{ $stats['total'] }} rendez-vous</span>
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
                <th style="width: 5%;">#</th>
                <th style="width: 18%;">Patient</th>
                <th style="width: 13%;">Téléphone</th>
                <th style="width: 13%;">Date</th>
                <th style="width: 8%;">Heure</th>
                <th style="width: 23%;">Notes</th>
                <th style="width: 12%;">Statut</th>
                <th style="width: 8%;">Confirmation</th>
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
                        @if(isset($appointment->patient->user->phone))
                            <span class="icon">📞</span>{{ $appointment->patient->user->phone }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <span class="icon">📅</span>
                        {{ \Carbon\Carbon::parse($appointment->appointment_date)->locale('fr')->isoFormat('DD MMM YYYY') }}
                    </td>
                    <td>
                        <span class="icon">🕐</span>
                        {{ $appointment->appointment_time->format('H:i') }}
                    </td>
                    <td>
                        @if($appointment->notes)
                            <span class="text-truncate">{{ $appointment->notes }}</span>
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
                                ⏳ En attente
                            @elseif($appointment->status === 'confirmed')
                                ✓ Confirmé
                            @elseif($appointment->status === 'completed')
                                ✓✓ Terminé
                            @endif
                        </span>
                    </td>
                    <td class="text-muted">
                        @if($appointment->status === 'pending')
                            À confirmer
                        @elseif($appointment->status === 'confirmed')
                            À terminer
                        @else
                            Fait ✓
                        @endif
                    </td>
                </tr>

                {{-- Saut de page tous les 15 rendez-vous --}}
                @if(($index + 1) % 15 === 0 && !$loop->last)
                    </tbody>
                    </table>
                    <div class="page-break"></div>
                    
                    {{-- Répéter l'en-tête sur la nouvelle page --}}
                    <div class="header">
                        <h1>📋 Liste des Rendez-vous (suite)</h1>
                        <div class="info">
                            {{-- Dr. {{ $medecin->user->name ?? 'Médecin' }} |  --}}
                            Généré le {{ $generatedAt }}
                        </div>
                    </div>
                    
                    <table>
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 18%;">Patient</th>
                            <th style="width: 13%;">Téléphone</th>
                            <th style="width: 13%;">Date</th>
                            <th style="width: 8%;">Heure</th>
                            <th style="width: 23%;">Notes</th>
                            <th style="width: 12%;">Statut</th>
                            <th style="width: 8%;">Confirmation</th>
                        </tr>
                    </thead>
                    <tbody>
                @endif
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px;">
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