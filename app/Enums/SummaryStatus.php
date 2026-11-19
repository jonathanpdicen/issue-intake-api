<?php

namespace App\Enums;

enum SummaryStatus: string
{
    case PENDING = 'pending';
    case READY = 'ready';
    case FAILED = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
