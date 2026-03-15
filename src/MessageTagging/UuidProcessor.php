<?php

namespace SkriptManufaktur\SimpleRestBundle\MessageTagging;

use Monolog\LogRecord;
use Symfony\Component\Uid\UuidV7;

final readonly class UuidProcessor
{
    public function __construct(private UuidContextStack $context)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $record['extra'] ??= [];
        $uuids = $this->context->head();

        if (is_array($record['extra']) && !empty($uuids)) {
            $record['extra']['message_uuids'] = array_map(
                fn (UuidV7 $uuid): string => $uuid->toString(),
                $uuids
            );
        }

        return $record;
    }
}
