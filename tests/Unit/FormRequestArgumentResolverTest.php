<?php declare(strict_types=1);

namespace Tests\Unit;

use Concept\Extensions\CastingValinor\Caster;
use Concept\Extensions\FormRequest\Events\FormRequestValidated;
use Concept\Extensions\FormRequest\Factory\FormRequestFactory;
use Concept\Extensions\FormRequest\Routing\FormRequestArgumentResolver;
use Concept\Extensions\ValidationRakit\Exceptions\ValidationException;
use Concept\Extensions\ValidationRakit\Validator;
use InvalidArgumentException;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use ReflectionFunction;
use ReflectionParameter;
use Tests\Support\RejectingFormRequest;
use Tests\Support\SampleFormRequest;

final class FormRequestArgumentResolverTest extends TestCase
{
    public function testSupportsReturnsTrueForFormRequestSubclass(): void
    {
        $resolver = new FormRequestArgumentResolver(
            formRequestFactory: static fn(): FormRequestFactory => new FormRequestFactory(new Validator()),
            container: $this->createMock(ContainerInterface::class),
        );
        $parameter = (new ReflectionFunction(function(SampleFormRequest $request): void {}))->getParameters()[0];

        $this->assertTrue($resolver->supports($parameter, []));
    }

    public function testSupportsReturnsFalseForBuiltinParameter(): void
    {
        $resolver = new FormRequestArgumentResolver(
            formRequestFactory: static fn(): FormRequestFactory => new FormRequestFactory(new Validator()),
            container: $this->createMock(ContainerInterface::class),
        );
        $parameter = (new ReflectionFunction(function(string $value): void {}))->getParameters()[0];

        $this->assertFalse($resolver->supports($parameter, []));
    }

    public function testResolveReturnsValidatedFormRequest(): void
    {
        $resolver = new FormRequestArgumentResolver(
            formRequestFactory: static fn(): FormRequestFactory => new FormRequestFactory(new Validator()),
            container: $this->createMock(ContainerInterface::class),
        );
        $request = (new ServerRequest())->withParsedBody([
            'email' => 'user@example.com',
            'name' => 'Alice',
        ]);
        $parameter = $this->parameter(SampleFormRequest::class);

        $formRequest = $resolver->resolve($parameter, $request, []);

        $this->assertInstanceOf(SampleFormRequest::class, $formRequest);
    }

    public function testResolveThrowsValidationExceptionWhenValidationFails(): void
    {
        $resolver = new FormRequestArgumentResolver(
            formRequestFactory: static fn(): FormRequestFactory => new FormRequestFactory(new Validator()),
            container: $this->createMock(ContainerInterface::class),
        );
        $request = (new ServerRequest())->withParsedBody(['email' => 'bad']);
        $parameter = $this->parameter(RejectingFormRequest::class);

        $this->expectException(ValidationException::class);
        $resolver->resolve($parameter, $request, []);
    }

    public function testResolveDispatchesFormRequestValidatedEvent(): void
    {
        $events = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            function(object $event) use (&$events): object {
                $events[] = $event;

                return $event;
            },
        );
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(EventDispatcherInterface::class)->willReturn(true);
        $container->method('get')->with(EventDispatcherInterface::class)->willReturn($dispatcher);

        $resolver = new FormRequestArgumentResolver(
            formRequestFactory: static fn(): FormRequestFactory => new FormRequestFactory(new Validator()),
            container: $container,
        );
        $request = (new ServerRequest())->withParsedBody([
            'email' => 'user@example.com',
            'name' => 'Alice',
        ]);
        $parameter = $this->parameter(SampleFormRequest::class);

        $resolver->resolve($parameter, $request, []);

        $this->assertInstanceOf(FormRequestValidated::class, $events[0]);
    }

    private function parameter(string $class): ReflectionParameter
    {
        return match ($class) {
            SampleFormRequest::class => (new ReflectionFunction(function(SampleFormRequest $request): void {}))->getParameters()[0],
            RejectingFormRequest::class => (new ReflectionFunction(function(RejectingFormRequest $request): void {}))->getParameters()[0],
            default => throw new InvalidArgumentException($class),
        };
    }
}
