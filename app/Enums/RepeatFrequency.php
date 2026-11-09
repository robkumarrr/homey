<?php

namespace App\Enums;

/**
 * The unit a schedule repeats in. Together with an interval it covers
 * "every 2 weeks" or "every 3 months"; Once never repeats.
 */
enum RepeatFrequency: string
{
    case Once = 'once';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
