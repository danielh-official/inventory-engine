<?php

namespace App\Enums;

enum AdjustmentReason: string
{
    case CountCorrection = 'count_correction';
    case Damaged = 'damaged';
    case Found = 'found';
    case Expired = 'expired';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CountCorrection => __('Count correction'),
            self::Damaged => __('Damaged'),
            self::Found => __('Found'),
            self::Expired => __('Expired'),
            self::Other => __('Other'),
        };
    }
}
