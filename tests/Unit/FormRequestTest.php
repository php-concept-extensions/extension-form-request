<?php declare(strict_types=1);

namespace Tests\Unit;

use Concept\Extensions\CastingValinor\Caster;
use Concept\Extensions\FormRequest\Factory\FormRequestFactory;
use Concept\Extensions\ValidationRakit\Validator;
use InvalidArgumentException;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Tests\Support\RejectingFormRequest;
use Tests\Support\SampleFormRequest;

final class FormRequestFactoryTest extends TestCase
{
    public function testMakeCreatesFormRequestInstance(): void
    {
        $factory = new FormRequestFactory(new Validator());
        $request = new ServerRequest();

        $formRequest = $factory->make(SampleFormRequest::class, $request);

        $this->assertInstanceOf(SampleFormRequest::class, $formRequest);
        $this->assertSame($request, $formRequest->httpRequest());
    }

    public function testMakeThrowsForInvalidClass(): void
    {
        $factory = new FormRequestFactory(new Validator());

        $this->expectException(InvalidArgumentException::class);
        $factory->make(\stdClass::class, new ServerRequest());
    }
}

final class FormRequestTest extends TestCase
{
    public function testAllMergesQueryAndParsedBody(): void
    {
        $request = (new ServerRequest())
            ->withQueryParams(['page' => '1'])
            ->withParsedBody(['email' => 'user@example.com', 'name' => 'Alice']);
        $formRequest = new SampleFormRequest($request, new Validator());

        $this->assertSame([
            'page' => '1',
            'email' => 'user@example.com',
            'name' => 'Alice',
        ], $formRequest->all());
    }

    public function testValidateReturnsTrueForValidData(): void
    {
        $request = (new ServerRequest())->withParsedBody([
            'email' => 'user@example.com',
            'name' => 'Alice',
            '_token' => 'csrf',
        ]);
        $formRequest = new SampleFormRequest($request, new Validator());

        $this->assertTrue($formRequest->validate());
        $this->assertSame([
            'email' => 'user@example.com',
            'name' => 'Alice',
        ], $formRequest->validated());
    }

    public function testValidateReturnsFalseForInvalidData(): void
    {
        $request = (new ServerRequest())->withParsedBody(['email' => 'bad']);
        $formRequest = new RejectingFormRequest($request, new Validator());

        $this->assertFalse($formRequest->validate());
        $this->assertArrayHasKey('email', $formRequest->errors());
    }

    public function testToDtoCastsValidatedData(): void
    {
        $request = (new ServerRequest())->withParsedBody([
            'email' => 'user@example.com',
            'name' => 'Alice',
        ]);
        $formRequest = new SampleFormRequest($request, new Validator(), new Caster());
        $formRequest->validate();

        $dto = $formRequest->toDto();

        $this->assertNotNull($dto);
        $this->assertSame('user@example.com', $dto->email);
    }
}
