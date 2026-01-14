<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Unit;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SkriptManufaktur\SimpleRestBundle\Validation\ValidationPreparationTrait;

class ValidationPreparationTest extends TestCase
{
    use ValidationPreparationTrait;


    #[DataProvider('validationProvider')]
    public function testCreation(string $propertyPath, string $finishedPropertyPath, array $result): void
    {
        $violations = [];
        $resultPath = $this->prepareValidation($propertyPath, $violations);

        static::assertIsArray($violations);
        static::assertIsString($resultPath);
        static::assertSame($result, $violations);
        static::assertSame($finishedPropertyPath, $resultPath);
    }

    public static function validationProvider(): Generator
    {
        yield [
            'data[email]',
            'email',
            [
                'email' => [],
            ],
        ];

        yield [
            'options[email]',
            'email',
            [
                'email' => [],
            ],
        ];

        yield [
            'diff[email]',
            'email',
            [
                'email' => [],
            ],
        ];

        yield [
            '',
            'root',
            [
                'root' => [],
            ],
        ];

        yield [
            'root',
            'root',
            [
                'root' => [],
            ],
        ];

        yield [
            'data[items][0][email]',
            'items[0][email]',
            [
                'items[0][email]' => [],
            ],
        ];

        yield [
            'options[items][fail][assert][string]',
            'items[fail][assert][string]',
            [
                'items[fail][assert][string]' => [],
            ],
        ];

        yield [
            'diff[email][test][assert]',
            'email[test][assert]',
            [
                'email[test][assert]' => [],
            ],
        ];

        yield [
            'data[items][0]',
            'items',
            [
                'items' => [],
            ],
        ];
    }
}
