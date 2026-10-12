<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest\Events;

final readonly class FormRequestValidated
{
    public function __construct(
        public string $formRequestClass,
        public float $startedAt,
        public float $duration,
    ) {}
}
