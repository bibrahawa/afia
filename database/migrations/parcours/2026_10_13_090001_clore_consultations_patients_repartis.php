<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Consultations des patients repartis sans consulter.
 *
 * Avant ce correctif, « Reparti sans consulter » laissait la consultation
 * « en cours » (sans acte facturé, ou facture non annulée), ou la marquait
 * « terminée » alors qu'elle n'avait jamais été remplie. Elles passent au
 * statut « annulee ». Une consultation effectivement remplie (diagnostic
 * saisi) n'est pas touchée.
 *
 * Aucune modification de structure : consultations.statut est un texte.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('consultations')
            ->whereIn('visite_id', DB::table('visites')->where('statut', 'partie')->select('id'))
            ->where(function ($q) {
                $q->where('statut', 'en_cours')
                    ->orWhere(fn ($t) => $t->where('statut', 'terminee')->where(fn ($d) => $d->whereNull('diagnostic')->orWhere('diagnostic', '')));
            })
            ->update(['statut' => 'annulee', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Irréversible sans perte : l'ancien statut n'était pas fiable. Rien à défaire.
    }
};
