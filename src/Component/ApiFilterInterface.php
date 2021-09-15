<?php

namespace SkriptManufaktur\SimpleRestBundle\Component;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Interface ApiFilterInterface
 */
interface ApiFilterInterface
{
    /**
     * This method gets an OptionResolver and has to define the default parameters for filtering
     * if any GET collection is requested.
     *
     * @param OptionsResolver $options
     */
    public function setFilterAttributes(OptionsResolver $options): void;

    /**
     * This method gets an OptionResolver and has to define the default parameters for sorting
     * if any GET collection is requested.
     *
     * @param OptionsResolver $options
     */
    public function setOrderAttributes(OptionsResolver $options): void;
}
