@extends('layouts.backend')

@php
    $envoyes = (int) ($totaux[\App\Models\SmsJournal::ENVOYE] ?? 0);
    $echecs = (int) ($totaux[\App\Models\SmsJournal::ECHEC] ?? 0);
    $total = $envoyes + $echecs;
    $taux = $total ? round($envoyes * 100 / $total) : null;
    $filtre = fn (array $changes) => route('sms.journal.index', array_filter(array_merge(request()->only(['periode', 'statut', 'type', 'q']), $changes), fn ($v) => $v !== null && $v !== ''));
    $icones = ['rdv' => 'fa-calendar-check', 'code' => 'fa-key', 'resultats' => 'fa-vial', 'labo' => 'fa-flask', 'cpn' => 'fa-baby', 'consentement' => 'fa-user-shield'];
    $icone = fn ($type) => collect($icones)->first(fn ($i, $prefixe) => str_starts_with($type, $prefixe)) ?? 'fa-sms';
    // Numéro affiché lisiblement : 622 12 34 56
    $numero = fn ($t) => preg_replace('/^(?:\+?224)?(\d{3})(\d{2})(\d{2})(\d{2})$/', '$1 $2 $3 $4', preg_replace('/\s/', '', (string) $t));
@endphp

@section('style')
<style>
    .sj-expediteur { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-bottom: 16px; padding: 14px 18px; border: 1px solid var(--hali-bordure); border-radius: var(--hali-rayon); background: #fff; }
    .sj-expediteur b { padding: 4px 12px; border-radius: 8px; background: var(--hali-encre); color: #fff; font-family: "SF Mono", Consolas, monospace; letter-spacing: .06em; }
    .sj-expediteur span { color: var(--hali-discret); font-size: .84rem; }
    .sj-filtres { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid var(--hali-bordure); }
    .sj-filtres form { display: flex; gap: 8px; margin-left: auto; }
    .sj-filtres select, .sj-filtres input { min-height: 38px; width: auto; }
    .sj-ligne { display: grid; grid-template-columns: 40px minmax(0, 1fr) 150px 130px auto; align-items: start; gap: 14px; padding: 14px 18px; border-top: 1px solid #f3f4f6; }
    .sj-ligne:first-child { border-top: 0; }
    .sj-ligne.est-echec { background: #fffafa; }
    .sj-icone { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 11px; background: var(--hali-primaire-pale); color: var(--hali-primaire); }
    .sj-ligne.est-echec .sj-icone { background: var(--hali-danger-pale); color: var(--hali-danger); }
    .sj-type { color: var(--hali-encre); font-weight: 650; font-size: .9rem; }
    .sj-message { margin: 4px 0 0; color: var(--hali-texte); font-size: .84rem; line-height: 1.45; word-break: break-word; }
    .sj-erreur { margin: 6px 0 0; padding: 6px 10px; border-radius: 8px; background: var(--hali-danger-pale); color: #7f1d1d; font-size: .78rem; word-break: break-word; }
    .sj-sous { display: block; color: var(--hali-discret); font-size: .78rem; }
    .sj-tel { color: var(--hali-encre); font-weight: 650; font-variant-numeric: tabular-nums; white-space: nowrap; }
    .sj-quand { color: var(--hali-texte); font-size: .84rem; white-space: nowrap; }
    .sj-actions form { margin: 0; }
    @media (max-width: 991.98px) { .sj-ligne { grid-template-columns: 40px 1fr; } .sj-ligne > :nth-child(n+3) { grid-column: 2; } .sj-filtres form { margin-left: 0; width: 100%; } }
</style>
@endsection

@section('content')
<div class="container"><div class="page-inner hl">
    <header class="hl-entete">
        <div>
            <h1>Journal des SMS</h1>
            <p>Tous les SMS envoyés par la clinique : rappels, confirmations, résultats, codes. Un patient dit ne rien avoir reçu ? Vérifiez ici et renvoyez.</p>
        </div>
    </header>

    <div class="sj-expediteur">
        <i class="fas fa-paper-plane" style="color:var(--hali-primaire)" aria-hidden="true"></i>
        Vos patients voient les SMS arriver de <b>{{ $expediteur }}</b>
        <span>Nom d'expéditeur enregistré chez l'opérateur. Pour le changer, contactez l'équipe {{ \App\Support\Marque::nom() }}.</span>
    </div>

    <div class="hl-kpis">
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">SMS · {{ mb_strtolower(\App\Http\Controllers\Sms\JournalSmsController::PERIODES[$periode]) }}</span><span class="hl-kpi-valeur">{{ number_format($total, 0, ',', ' ') }}</span></div>
        <div class="hl-bloc hl-kpi"><span class="hl-kpi-libelle">Bien partis</span><span class="hl-kpi-valeur" style="color:var(--hali-succes)">{{ number_format($envoyes, 0, ',', ' ') }}</span>@if($taux !== null)<span class="hl-kpi-detail">{{ $taux }} % des envois</span>@endif</div>
        <a href="{{ $filtre(['statut' => 'echec']) }}" class="hl-bloc hl-kpi" style="text-decoration:none"><span class="hl-kpi-libelle">Échecs</span><span class="hl-kpi-valeur" style="color:{{ $echecs ? 'var(--hali-danger)' : 'inherit' }}">{{ $echecs }}</span><span class="hl-kpi-detail">{{ $echecs ? 'à vérifier →' : 'aucun' }}</span></a>
    </div>

    <section class="hl-bloc">
        <div class="sj-filtres">
            <div class="hl-puces" role="group" aria-label="Période">
                @foreach(\App\Http\Controllers\Sms\JournalSmsController::PERIODES as $cle => $libelle)
                    <a href="{{ $filtre(['periode' => $cle]) }}" class="hl-puce {{ $periode === $cle ? 'est-actif' : '' }}">{{ $libelle }}</a>
                @endforeach
            </div>
            <form method="GET" action="{{ route('sms.journal.index') }}">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <select name="statut" class="form-control" aria-label="Statut" onchange="this.form.submit()">
                    <option value="">Tous statuts</option>
                    <option value="envoye" @selected($statut === 'envoye')>Envoyés</option>
                    <option value="echec" @selected($statut === 'echec')>Échecs</option>
                </select>
                <select name="type" class="form-control" aria-label="Type" onchange="this.form.submit()">
                    <option value="">Tous les types</option>
                    @foreach($typesPresents as $t)<option value="{{ $t }}" @selected($type === $t)>{{ \App\Models\SmsJournal::TYPES[$t] ?? $t }}</option>@endforeach
                </select>
                <input type="search" name="q" value="{{ $recherche }}" class="form-control" placeholder="Numéro…" inputmode="numeric" aria-label="Numéro de téléphone">
                <button type="submit" class="hl-bouton" aria-label="Rechercher"><i class="fas fa-search"></i></button>
            </form>
        </div>

        @forelse($envois as $sms)
            @php
                $sujet = $sms->sujet;
                $patient = $sujet?->patient ?? null;
            @endphp
            <div class="sj-ligne {{ $sms->estEnvoye() ? '' : 'est-echec' }}">
                <span class="sj-icone" aria-hidden="true"><i class="fas {{ $icone($sms->type) }}"></i></span>
                <div style="min-width:0">
                    <span class="sj-type">{{ $sms->type_libelle }}</span>
                    @if($sms->estEnvoye())<span class="hl-statut hl-s-succes">Parti</span>@else<span class="hl-statut hl-s-danger">Échec</span>@endif
                    @if($sms->renvoi_de_id)<span class="hl-statut hl-s-neutre">Renvoi</span>@endif
                    <p class="sj-message">{{ $sms->message }}</p>
                    @if(! $sms->estEnvoye() && $sms->erreur)<p class="sj-erreur">{{ \Illuminate\Support\Str::limit($sms->erreur, 220) }}</p>@endif
                </div>
                <div>
                    <span class="sj-tel">{{ $numero($sms->telephone) }}</span>
                    @if($patient)<span class="sj-sous">{{ $patient->full_name ?? '' }}</span>@endif
                    @if($sujet instanceof \App\Models\Appointment)
                        @can('appointment.view')<a href="{{ route('appointment.show', $sujet) }}" class="sj-sous">Voir le rendez-vous</a>@endcan
                    @endif
                </div>
                <div class="sj-quand">
                    {{ $sms->created_at->isToday() ? "Aujourd'hui" : ($sms->created_at->isYesterday() ? 'Hier' : $sms->created_at->translatedFormat('d M')) }} · {{ $sms->created_at->format('H:i') }}
                    @if($sms->auteur)<span class="sj-sous">par {{ $sms->auteur->name }}</span>@endif
                </div>
                <div class="sj-actions">
                    @can('sms.renvoyer')
                        @if($sms->renvoyable)
                            <form method="POST" action="{{ route('sms.journal.renvoyer', $sms->id) }}" onsubmit="return confirm('Renvoyer ce SMS au {{ $numero($sms->telephone) }} ? Il sera facturé à nouveau.');">
                                @csrf
                                <button type="submit" class="hl-bouton" style="min-height:34px; {{ $sms->estEnvoye() ? '' : 'border-color:var(--hali-primaire); color:var(--hali-primaire-fonce)' }}"><i class="fas fa-redo" aria-hidden="true"></i> Renvoyer</button>
                            </form>
                        @else
                            <span class="sj-sous" title="Code à usage unique : le patient doit en redemander un.">Code masqué</span>
                        @endif
                    @endcan
                </div>
            </div>
        @empty
            <div class="hl-vide"><i class="fas fa-sms" aria-hidden="true"></i>Aucun SMS sur cette période{{ $statut || $type || $recherche ? ' avec ces filtres' : '' }}.</div>
        @endforelse

        @if($envois->hasPages())<div style="padding:14px 18px; border-top:1px solid var(--hali-bordure)">{{ $envois->links() }}</div>@endif
    </section>

    <p class="hl-note hl-note-info mt-3"><i class="fas fa-info-circle" aria-hidden="true"></i> <span>« Parti » signifie que l'opérateur a accepté le SMS. Un téléphone éteint pendant plusieurs jours peut ne jamais le recevoir. Les SMS sont conservés 90 jours.</span></p>
</div></div>
@endsection
