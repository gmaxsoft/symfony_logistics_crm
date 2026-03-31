<?php

declare(strict_types=1);

namespace App\Enum;

enum ParcelStatus: string
{
    case DRAFT = 'draft';
    case PICKED_UP = 'picked_up';
    case IN_SORTING_CENTER = 'in_sorting_center';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Wersja robocza',
            self::PICKED_UP => 'Odebrana od nadawcy',
            self::IN_SORTING_CENTER => 'W centrum sortowania',
            self::OUT_FOR_DELIVERY => 'W doręczeniu',
            self::DELIVERED => 'Dostarczona',
            self::FAILED => 'Nieudana dostawa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'grey',
            self::PICKED_UP => 'blue',
            self::IN_SORTING_CENTER => 'orange',
            self::OUT_FOR_DELIVERY => 'purple',
            self::DELIVERED => 'green',
            self::FAILED => 'red',
        };
    }
}
