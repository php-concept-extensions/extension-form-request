<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest;

use Closure;
use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\Event\Events\ExtensionAwakened;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use Concept\Extensions\FormRequest\Contracts\FormRequestFactoryInterface;
use Concept\Extensions\FormRequest\Factory\FormRequestFactory;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use Concept\Extensions\ValidationRakit\ValidationLogger;
use Concept\Support\FactoryResolver;
use League\Container\ServiceProvider\AbstractServiceProvider;

final class FormRequestServiceProvider extends AbstractServiceProvider
{
    private const string EXTENSION_NAME = 'form-request';

    /**
     * @param array<string> $globalExcept
     * @param Closure(): mixed $validatorFactory
     * @param Closure(): mixed|null $casterFactory
     * @param Closure(): mixed|null $validationLoggerFactory
     */
    public function __construct(
        private readonly Closure $validatorFactory,
        private readonly array $globalExcept = [],
        private readonly ?Closure $casterFactory = null,
        private readonly ?Closure $validationLoggerFactory = null,
    ) {}

    public function provides(string $id): bool
    {
        return $id === FormRequestFactoryInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(FormRequestFactoryInterface::class, function() use ($container): FormRequestFactory {
            EventDispatcherResolver::optional($container)?->dispatch(new ExtensionAwakened(
                extensionName: self::EXTENSION_NAME,
                anchorId: FormRequestFactoryInterface::class,
            ));

            $validator = FactoryResolver::required(
                $this->validatorFactory,
                ValidatorInterface::class,
                'Validator factory result',
            );

            $caster = $this->casterFactory !== null ? ($this->casterFactory)() : null;
            $validationLogger = $this->validationLoggerFactory !== null
                ? ($this->validationLoggerFactory)()
                : null;

            return new FormRequestFactory(
                validator: $validator,
                globalExcept: $this->globalExcept,
                caster: $caster instanceof CasterInterface ? $caster : null,
                validationLogger: $validationLogger instanceof ValidationLogger ? $validationLogger : null,
            );
        })->setShared(true);
    }
}
