<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

use SkriptManufaktur\SimpleRestBundle\Component\ApiFilterInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DummyFilterObject implements ApiFilterInterface
{
    public function setFilterAttributes(OptionsResolver $options): void
    {
        $options->setDefaults([
            'name' => null,
            'date' => date('Y-m-d'),
            'rank' => null,
            'enabled' => true,
        ]);
    }

    public function setOrderAttributes(OptionsResolver $options): void
    {
        $options->setDefaults([
            'date' => 'DESC',
            'rank' => null,
        ]);
    }
}
