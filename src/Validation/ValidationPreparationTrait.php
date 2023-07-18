<?php

namespace SkriptManufaktur\SimpleRestBundle\Validation;

trait ValidationPreparationTrait
{
    protected function prepareValidation(string $propertyPath, array &$validationList, string $defaultRoot = 'root'): string
    {
        if ('' === trim($propertyPath)) {
            $propertyPath = $defaultRoot;
        }

        // matching, e.g. "data[email]" or "options[email]" in an object sub-array, resolve to "email"
        if (1 === preg_match('~(?:data|options)\[(.+)]~', $propertyPath, $match)) {
            $propertyPath = $match[1];
        }

        if (!array_key_exists($propertyPath, $validationList)) {
            $validationList[$propertyPath] = [];
        }

        return $propertyPath;
    }
}
