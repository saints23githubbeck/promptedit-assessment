<?php

namespace App\Enums;
enum GenerationStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function isFinal(): bool
    {
        return in_array($this, [self::Succeeded, self::Failed], true);
    }
}
