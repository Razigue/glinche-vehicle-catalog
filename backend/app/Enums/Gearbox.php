<?php

namespace App\Enums;

enum Gearbox: string
{
    case Automatic = 'AUT';
    case Manual = 'MAN';
    case None = 'NOG';
    case Pdk = 'PDK';
    case Robotised = 'ROB';
    case SemiAutomatic = 'SEM';
    case Sequential = 'SEQ';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Automatique',
            self::Manual => 'Manuelle',
            self::None => 'Sans boîte',
            self::Pdk => 'PDK',
            self::Robotised => 'Robotisée',
            self::SemiAutomatic => 'Semi-automatique',
            self::Sequential => 'Séquentielle',
        };
    }
}
