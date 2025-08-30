<?php

if (!function_exists('format_money')) {
    function format_money($amount, $currency = 'USD')
    {
        return number_format($amount, 2, '.', ' ') . ' ' . $currency;
    }
}

// calcule du reste a payer par l'assurance
if (!function_exists('reste_a_payer')) {
    $resteAssurance = $invoice->insurance_amount - $this->getPaidAmount($transaction->id, 'remboursement');

    function reste_a_payer($montant_du, $montant_paye)
    {
        return $montant_du - $montant_paye;
    }
}

function getPaidAmount($transctionId, $type)
{
    return Paiement::where('transaction_id', $transctionId)
                    ->where('type', $type)->sum('montant');
}
