<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest\Requests;

use Concept\Extensions\CastingValinor\Contracts\CasterInterface;
use Concept\Extensions\CastingValinor\Contracts\DtoInterface;
use Concept\Extensions\CastingValinor\Exceptions\CastingException;
use Concept\Extensions\FormRequest\Contracts\FormRequestInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidationInterface;
use Concept\Extensions\ValidationRakit\Contracts\ValidatorInterface;
use Concept\Extensions\ValidationRakit\ValidationLogger;
use Concept\Extensions\ValidationRakit\Exceptions\ValidationCastException;
use Concept\Extensions\ValidationRakit\Exceptions\ValidationLogicException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @template T of DtoInterface
 */
abstract class FormRequest implements FormRequestInterface
{
    /**
     * Fields that should be excluded from validation for all requests
     *
     * @var array<string>
     */
    protected array $globalExcept = [];

    /**
     * Fields that should be excluded from validation
     *
     * @var array<string>
     */
    protected array $except = [];

    /**
     * Fields that should be validated only
     *
     * @var array<string>
     */
    protected array $only = [];

    /**
     * Class name of DTO that may be created from validated data
     *
     * @var class-string<T>|null
     */
    protected ?string $dtoClass = null;

    protected ValidationInterface $validation;

    /**
     * @param array<string> $globalExcept
     */
    public function __construct(
        protected readonly ServerRequestInterface $request,
        protected readonly ValidatorInterface $validator,
        protected readonly ?CasterInterface $caster = null,
        protected readonly ?ValidationLogger $validationLogger = null,
        array $globalExcept = [],
    ) {
        if ($globalExcept !== []) {
            $this->globalExcept = $globalExcept;
        }
    }

    public function httpRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @return array<string, string>|array<string, string[]>
     */
    abstract public function rules(): array;

    /**
     * @return array<string, string>
     */
    public function aliases(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    public function validate(): bool
    {
        $this->validation = $this->validator->make($this->all(), $this->rules());

        if (!empty($this->aliases())) {
            $this->validation->setAliases($this->aliases());
        }

        if (!empty($this->messages())) {
            $this->validation->setMessages($this->messages());
        }

        $this->validation->validate();

        if ($this->validationLogger instanceof ValidationLogger) {
            $this->validationLogger->logResult(
                static::class,
                $this->validation->isValid(),
                $this->validation->getValidData(),
                $this->validation->getErrors(),
            );
        }

        return $this->validation->isValid();
    }

    /**
     * @return array<mixed>
     */
    public function validated(): array
    {
        if (!isset($this->validation)) {
            throw new ValidationLogicException(self::class);
        }

        // 1. Get the validated data
        $data = $this->validation->getValidData();

        // 2. Get the allowed keys from the rules - this is our "white list"
        // We use array_keys to get the keys, to get fieldset names
        $allowedKeys = array_keys($this->rules());

        // 3. Filter the data to only include the allowed keys
        $validatedData = array_intersect_key($data, array_flip($allowedKeys));

        // 4. If $this->only is not empty, only include the specified keys
        if (!empty($this->only)) {
            $validatedData = array_intersect_key($validatedData, array_flip($this->only));
        }

        // 5. Exclude technical fields (CSRF) and fields from $this->except
        $exclude = array_merge($this->globalExcept, $this->except);
        $validatedData = array_diff_key($validatedData, array_flip($exclude));

        return $validatedData;
    }

    /**
     * @return array<mixed>
     */
    public function errors(): array
    {
        return $this->validation->getErrors();
    }

    /**
     * @return array<mixed>
     */
    public function all(): array
    {
        $body = $this->request->getParsedBody();
        $parsedBody = is_array($body) ? $body : (is_object($body) ? (array) $body : []);
        $data = array_merge($this->request->getQueryParams(), $parsedBody);

        if ($this->validationLogger instanceof ValidationLogger) {
            $this->validationLogger->logIncoming(
                static::class,
                $this->request->getUri()->getPath(),
                $data,
            );
        }

        return $data;
    }

    /**
     * @return DtoInterface|null
     */
    public function toDto(): ?DtoInterface
    {
        if ($this->dtoClass === null || !($this->caster instanceof CasterInterface)) {
            return null;
        }

        if (class_exists($this->dtoClass)) {
            $dto = $this->castValue($this->validated(), $this->dtoClass);

            return $dto instanceof DtoInterface ? $dto : null;
        }

        return null;
    }

    protected function getRouteParam(string $key, mixed $default = null): mixed
    {
        return $this->request->getAttribute($key, $default);
    }

    private function castValue(mixed $value, ?string $type): mixed
    {
        if ($type === null || !($this->caster instanceof CasterInterface)) {
            return null;
        }

        try {
            return $this->caster->cast($value, $type);
        } catch (CastingException $e) {
            throw new ValidationCastException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
