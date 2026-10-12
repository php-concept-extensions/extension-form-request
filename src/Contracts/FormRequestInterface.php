<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest\Contracts;

use Psr\Http\Message\ServerRequestInterface;

interface FormRequestInterface
{
    public function httpRequest(): ServerRequestInterface;

    /**
     * @return array<string, string|list<string>>
     */
    public function rules(): array;

    /**
     * @return array<string, string>
     */
    public function aliases(): array;

    /**
     * @return array<string, string>
     */
    public function messages(): array;

    public function validate(): bool;

    /**
     * @return array<string, mixed>
     */
    public function validated(): array;

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array;

    /**
     * @return array<string, mixed>
     */
    public function all(): array;
}
