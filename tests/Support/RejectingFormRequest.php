<?php declare(strict_types=1);

namespace Tests\Support;

use Concept\Extensions\FormRequest\Requests\FormRequest;

final class RejectingFormRequest extends FormRequest
{
    public function rules(): array
    {
        return ['email' => 'required|email'];
    }
}
