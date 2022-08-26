<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

interface GrantingStampInterface
{
    /**
     * Returns the voters attribute
     *
     * @return string
     */
    public function getAttribute(): string;

    /**
     * Holds the voters result, if possible
     *
     * @return bool|null
     */
    public function getVote(): bool|null;
}
