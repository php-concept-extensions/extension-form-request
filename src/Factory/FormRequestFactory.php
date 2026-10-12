<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest\Factory;

use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\FormRequest\Contracts\FormRequestFactoryInterface;
use Concept\Extensions\FormRequest\Contracts\FormRequestInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use Concept\Extensions\ValidationRakit\ValidationLogger;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

final class FormRequestFactory implements FormRequestFactoryInterface
{
    private const string ERR_CLASS_MUST_IMPLEMENT_INTERFACE = 'Class %s must implement %s.';

    /**
     * @param array<string> $globalExcept
     */
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly array $globalExcept = [],
        private readonly ?CasterInterface $caster = null,
        private readonly ?ValidationLogger $validationLogger = null,
    ) {}

    public function make(string $className, ServerRequestInterface $request): FormRequestInterface
    {
        if (!is_subclass_of($className, FormRequestInterface::class)) {
            throw new InvalidArgumentException(sprintf(
                self::ERR_CLASS_MUST_IMPLEMENT_INTERFACE,
                $className,
                FormRequestInterface::class,
            ));
        }

        return new $className(
            $request,
            $this->validator,
            $this->caster,
            $this->validationLogger,
            $this->globalExcept,
        );
    }
}
