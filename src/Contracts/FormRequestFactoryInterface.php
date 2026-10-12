<?php declare(strict_types=1);

namespace Concept\Extensions\FormRequest\Contracts;

use Psr\Http\Message\ServerRequestInterface;

interface FormRequestFactoryInterface
{
    /**
     * @param class-string<FormRequestInterface> $className
     */
    public function make(string $className, ServerRequestInterface $request): FormRequestInterface;
}
