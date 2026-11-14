<?php

declare(strict_types=1);

namespace Forouzandea\Bluwp\UI\Forms;

final class FormProcessor
{
    public function process(FormSchema $schema, array $input): FormResult
    {
        $result = (new FieldSetProcessor())->process($schema->resolvedFields(), $input, $schema->normalizeData(...));
        $data = $result->data;
        $errors = $result->errors;
        if ($errors === []) {
            $errors = $schema->validateForm($data);
        }

        return new FormResult($errors === [] ? $data : [], $errors);
    }
}
