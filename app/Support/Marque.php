<?php

namespace App\Support;

/**
 * Nom commercial de la plateforme, lu depuis config/marque.php.
 *
 * À ne pas confondre avec l'établissement courant : `IdentiteDocument` porte
 * le nom de la clinique ou du laboratoire, qui est ce qu'un patient doit voir
 * en haut d'une ordonnance. La marque, elle, apparaît en pied de page, dans
 * les écrans d'administration et dans l'expéditeur des SMS.
 */
class Marque
{
    public static function nom(): string
    {
        return (string) config('marque.nom', 'Hali');
    }

    public static function signature(): string
    {
        return (string) config('marque.signature', self::nom());
    }

    public static function expediteurSms(): string
    {
        return mb_substr((string) config('marque.sms_expediteur', 'HALI'), 0, 11);
    }

    public static function support(): string
    {
        return (string) config('marque.support', '');
    }

    /** Titre d'un écran : « Rapports — Hali ». */
    public static function titre(?string $ecran = null): string
    {
        return $ecran ? $ecran . ' — ' . self::nom() : self::nom();
    }

    /** Commande artisan préfixée par la marque (hali:comptes). */
    public static function commande(string $nom): string
    {
        return config('marque.prefixe_commandes', 'hali') . ':' . $nom;
    }
}
