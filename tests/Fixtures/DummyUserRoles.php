<?php

namespace SkriptManufaktur\SimpleRestBundle\Tests\Fixtures;

enum DummyUserRoles: string
{
    case ADMIN = 'ROLE_ADMIN';
    case MANAGER = 'ROLE_MANAGER';
    case DIRECTOR = 'ROLE_DIRECTOR';
    case API = 'ROLE_API';
    case USER = 'ROLE_USER';
}
