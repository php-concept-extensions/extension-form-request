<?php declare(strict_types=1);

namespace Tests\Support;

use Concept\Extensions\CastingValinor\Contracts\DtoInterface;
use Concept\Extensions\CastingValinor\Dto\Dto;
use Concept\Extensions\FormRequest\Requests\FormRequest;

final class SampleUserDto extends Dto implements DtoInterface
{
    public function __construct(
        public readonly string $email,
    ) {}
}

final class SampleFormRequest extends FormRequest
{
    protected ?string $dtoClass = SampleUserDto::class;

    /** @var array<string> */
    protected array $except = ['_token'];

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'name' => 'required',
        ];
    }

    public function aliases(): array
    {
        return ['email' => 'E-mail'];
    }
}
