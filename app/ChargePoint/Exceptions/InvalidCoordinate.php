<?php

declare(strict_types=1);

namespace App\ChargePoint\Exceptions;

use Exception;

class InvalidCoordinate extends Exception
{
    private function __construct(
        string $message,
        public readonly ?CoordinateType $type = null,
    ) {
        parent::__construct($message);
    }

    public static function forAxis(CoordinateType $type): self
    {
        return new self(
            sprintf(
                'The given %s is out of bounds (min: %s, max: %s).',
                $type->label(),
                $type->min(),
                $type->max(),
            ),
            $type,
        );
    }

    public static function forChargePoint(?string $id): self
    {
        return new self(sprintf(
            'The coordinate given for charge point %s is not valid.',
            $id ?? 'unsaved',
        ));
    }
}
