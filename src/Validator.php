<?php
namespace Figured\Resolvers;

class Validator implements Interfaces\Validator
{
    public function isValid($value, int $types): bool
    {
        return Interfaces\Type::LOOKUP[gettype($value)] & $types;
    }
}
