<?php
namespace Figured\Resolvers\Exceptions;

/**
 * Thrown when a data is missing from the input.
 */
class MissingDataException extends \Exception
{
    public function __construct(string $path, string $level = null)
    {
        $message = $level ? "Missing input data for '$path' at '$level'"
                          : "Missing input data for '$path'";

        parent::__construct($message);
    }
}
