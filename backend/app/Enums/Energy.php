<?php

namespace App\Enums;

enum Energy: string
{
    case PetrolBioethanol = 'FG';
    case PetrolNaturalGas = 'EN';
    case PetrolLpg = 'EG';
    case CompressedAir = 'AC';
    case Diesel = 'GO';
    case Electric = 'EL';
    case Ethanol = 'ET';
    case Petrol = 'ES';
    case DieselHybrid = 'GH';
    case PetrolHybrid = 'EH';
    case NaturalGasHybrid = 'NH';
    case Hydrogen = 'H2';
    case DieselPluginHybrid = 'GL';
    case PetrolPluginHybrid = 'EE';
    case NaturalGasPluginHybrid = 'NE';

    public function label(): string
    {
        return match ($this) {
            self::PetrolBioethanol => 'Essence / Bioéthanol',
            self::PetrolNaturalGas => 'Essence / GNV',
            self::PetrolLpg => 'Essence / GPL',
            self::CompressedAir => 'Air comprimé',
            self::Diesel => 'Diesel',
            self::Electric => 'Électrique',
            self::Ethanol => 'Éthanol',
            self::Petrol => 'Essence',
            self::DieselHybrid => 'Hybride diesel',
            self::PetrolHybrid => 'Hybride essence',
            self::NaturalGasHybrid => 'Hybride GNV',
            self::Hydrogen => 'Hydrogène',
            self::DieselPluginHybrid => 'Hybride rechargeable diesel',
            self::PetrolPluginHybrid => 'Hybride rechargeable essence',
            self::NaturalGasPluginHybrid => 'Hybride rechargeable GNV',
        };
    }
}
