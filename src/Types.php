<?php
namespace Figured\Resolvers;

use Figured\Resolvers\Interfaces\Type;

class Types
{
    /**
     * @param int   $types
     * @param mixed $value
     *
     * @return bool TRUE if the given value matches the given types.
     */
    public static function isValid(int $types, $value): bool
    {
        return Type::LOOKUP[gettype($value)] & $types;
    }
}
