<?php

namespace SkriptManufaktur\SimpleRestBundle\Voter;

use Exception;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\UserInterface;

class RoleService extends RoleHierarchy
{
    const STRATEGY_AND = 'and';
    const STRATEGY_OR = 'or';

    /** @var string[]|null */
    private ?array $flatRoles = null;


    public function __construct(array $hierarchy)
    {
        parent::__construct($hierarchy);
    }

    /**
     * Get the map of roles, created by Symfony security component
     *
     * @return array
     */
    public function getRolesMap(): array
    {
        return $this->map;
    }

    /**
     * Get a flat list of main roles, defined in security.yaml
     *
     * @return array
     */
    public function getFlatRoles(): array
    {
        if (null === $this->flatRoles) {
            $this->flatRoles = array_keys($this->map);
        }

        return $this->flatRoles;
    }

    /**
     * Find out, if a user has one or more specific roles or at least one of them
     *
     * @param UserInterface $user
     * @param array         $roles
     * @param string        $strategy
     *
     * @return bool
     *
     * @throws Exception
     */
    public function userHasRoles(UserInterface $user, array $roles, string $strategy = self::STRATEGY_AND): bool
    {
        $strategy = strtolower($strategy);
        $possibleStrategies = [self::STRATEGY_AND, self::STRATEGY_OR];

        if (!in_array($strategy, $possibleStrategies)) {
            throw new Exception(sprintf(
                '"%s" is not a possible strategy! Possible strategies are %s',
                $strategy,
                implode(', ', $possibleStrategies)
            ));
        }

        // cast any role to string, in case the old Role class is used
        $userRoles = array_map(fn ($role) => (string) $role, $user->getRoles());

        // get a flat list of roles, the user really has
        $userRoles = $this->getUserRoleList($userRoles);

        // return the result by chosen strategy
        switch ($strategy) {
            case self::STRATEGY_OR:
                foreach ($roles as $r) {
                    if (in_array($r, $userRoles)) {
                        return true;
                    }
                }
                break;

            case self::STRATEGY_AND:
                $result = true;
                foreach ($roles as $r) {
                    $result = $result && in_array($r, $userRoles);
                }

                return $result;
        }

        return false;
    }

    /**
     * Find out, if a user has one or more specific roles or at least one of them
     *
     * @param TokenInterface $token
     * @param array          $roles
     * @param string         $strategy
     *
     * @return bool
     *
     * @throws Exception
     */
    public function tokenHasRoles(TokenInterface $token, array $roles, string $strategy = self::STRATEGY_AND): bool
    {
        /** @var UserInterface $user */
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            $exception = new AuthenticationException(sprintf('Invalid token was given to %s', 'RoleService::tokenHasRoles()'));
            $exception->setToken($token);

            throw $exception;
        }

        return $this->userHasRoles($user, $roles, $strategy);
    }

    /**
     * Make a flat list of roles, the user really owns
     *
     * @param string[] $roles
     *
     * @return string[]
     */
    private function getUserRoleList(array $roles): array
    {
        $userRoles = [];

        foreach ($this->map as $roleKey => $hierarchy) {
            if (in_array($roleKey, $roles)) {
                $userRoles[] = $roleKey;

                foreach ($hierarchy as $r) {
                    $userRoles[] = $r;
                }
            }
        }

        return array_unique($userRoles);
    }
}
