<?php

namespace SkriptManufaktur\SimpleRestBundle\Validation;

trait ValidationPreparationTrait
{
    protected function prepareValidation(string $propertyPath, array &$validationList, string $defaultRoot = 'root'): string
    {
        if ('' === trim($propertyPath)) {
            $propertyPath = $defaultRoot;
        }

        // remove the first wrapper, e.g. "data[email]" or "options[email]" in an object sub-array, resolve to "email"
        // a "data[email][0]" will turn into "email[0]"
        if (1 === preg_match('~^\w+\[([^]]+)](.*)~', $propertyPath, $match)) {
            $propertyPath = $match[1];

            if (!empty($match[2])) {
                $propertyPath .= $match[2];
            }
        }

        $propertyPath = preg_replace('~\[\d+]$~', '', $propertyPath);

        if (!array_key_exists($propertyPath, $validationList)) {
            $validationList[$propertyPath] = [];
        }

        return $propertyPath;
    }
}
