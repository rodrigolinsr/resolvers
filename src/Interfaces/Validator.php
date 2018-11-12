<?php
namespace Figured\Resolvers\Interfaces;

interface Validator
{
    public function isValid($value, int $types): bool;
}
