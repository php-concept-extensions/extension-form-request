<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest\Routing;

use Concept\Core\Http\Contracts\ArgumentResolverInterface;
use Concept\Extensions\Event\Support\EventDispatcherResolver;
use Concept\Extensions\FormRequest\Contracts\FormRequestFactoryInterface;
use Concept\Extensions\FormRequest\Contracts\FormRequestInterface;
use Concept\Extensions\FormRequest\Events\FormRequestValidated;
use Concept\Extensions\ValidationRakit\Exceptions\ValidationException;
use Concept\Support\FactoryResolver;
use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;

final class FormRequestArgumentResolver implements ArgumentResolverInterface
{
    private const string ERR_PARAMETER_MUST_HAVE_NAMED_TYPE = 'Form request parameter must have a named type.';
    private const string ERR_CLASS_IS_NOT_FORM_REQUEST = 'Class %s is not a form request.';

    private ?FormRequestFactoryInterface $factory = null;

    /**
     * @param Closure(): mixed $formRequestFactory
     */
    public function __construct(
        private readonly Closure $formRequestFactory,
        private readonly ContainerInterface $container,
    ) {}

    public function supports(ReflectionParameter $parameter, array $vars): bool
    {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        return is_subclass_of($type->getName(), FormRequestInterface::class);
    }

    public function resolve(ReflectionParameter $parameter, ServerRequestInterface $request, array $vars): mixed
    {
        $type = $parameter->getType();
        if (!$type instanceof ReflectionNamedType) {
            throw new RuntimeException(self::ERR_PARAMETER_MUST_HAVE_NAMED_TYPE);
        }

        $className = $type->getName();
        if (!is_subclass_of($className, FormRequestInterface::class)) {
            throw new RuntimeException(sprintf(self::ERR_CLASS_IS_NOT_FORM_REQUEST, $className));
        }

        $formRequest = $this->factory()->make($className, $request);

        $startedAt = microtime(true);
        try {
            if (!$formRequest->validate()) {
                throw new ValidationException($formRequest->errors(), $formRequest->all());
            }
        } finally {
            EventDispatcherResolver::optional($this->container)?->dispatch(new FormRequestValidated(
                formRequestClass: $className,
                startedAt: $startedAt,
                duration: microtime(true) - $startedAt,
            ));
        }

        return $formRequest;
    }

    private function factory(): FormRequestFactoryInterface
    {
        if ($this->factory === null) {
            $formRequestFactory = $this->formRequestFactory;
            $this->factory = FactoryResolver::required(
                $formRequestFactory,
                FormRequestFactoryInterface::class,
                'Form request factory result',
            );
        }

        return $this->factory;
    }
}
