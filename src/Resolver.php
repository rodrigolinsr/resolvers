<?php
namespace Figured\Resolvers;

use Figured\Resolvers\Interfaces\Type;

class Resolver implements Interfaces\Resolver
{
    /**
     * @var array The input data to be resolved.
     */
    protected $input;

    /**
     * @var Interfaces\Cache Internal cache used to store resolved values.
     */
    protected $cache;

    /**
     * @var Interfaces\Validator Internal validator used to check input against expected types.
     */
    protected static $validator;

    /**
     * @param array $input
     * @param mixed ...$args
     *
     * @return Resolver
     */
    public static function resolve(array $input = []): Interfaces\Resolver
    {
        return new static($input);
    }

    /**
     * Resolver constructor.
     *
     * @param array $input The input data to be resolved.
     */
    final public function __construct(array $input)
    {
        /* */
        $this->input = $input;
        $this->cache = $this->cache();

        /* */
        static::$validator = static::$validator ?? static::validator();
    }

    /**
     * @return Interfaces\Cache The cache instance to be used by this resolver.
     */
    protected function cache(): Interfaces\Cache
    {
        return new Cache();
    }

    /**
     * @return Interfaces\Validator
     */
    protected static function validator(): Interfaces\Validator
    {
        return new Validator();
    }

    /**
     * Returns a copy of this resolver's input data merged with given data.
     *
     * @param array $input
     *
     * @return static
     */
    public function with(array $input): Interfaces\Resolver
    {
        return new static(array_replace($this->input, $input));
    }

    /**
     * @param string $path
     * @param mixed  $value
     * @param int    $types
     *
     * @throws Exceptions\ValidationException
     */
    protected function validate(string $path, $value, int $types): void
    {
        if ( ! static::$validator->isValid($value, $types)) {
            throw new Exceptions\ValidationException("Unexpected value at '$path'");
        }
    }

    /**
     * @param string $path
     * @param mixed  $value
     *
     * @return mixed The cached or otherwise produced value.
     */
    private function remember(string $path, callable $producer)
    {
        if ($this->cache->has($path) === false) {
            $this->cache->set($path, $producer());
        }

        return $this->cache->get($path);
    }

    /**
     * @param string $path
     *
     * @return mixed
     *
     * @throws Exceptions\MissingDataException
     */
    private function evaluateDotPath(string $path)
    {
        $input = $this->input;

        foreach (explode(".", $path) as $level) {

            /* Check if data is missing at the current level in the path. */
            if ( ! is_array($input) || ! array_key_exists($level, $input)) {
                throw new Exceptions\MissingDataException($path, $level);
            }

            $input = $input[$level];
        }

        return $input;
    }

    /**
     * @param string $path
     *
     * @return mixed
     *
     * @throws Exceptions\MissingDataException
     */
    private function evaluatePath(string $path)
    {
        /* Check if we need to evaluate a dot-notation path. */
        if (strpos($path, ".") !== false) {
            return $this->evaluateDotPath($path);
        }

        if ( ! array_key_exists($path, $this->input)) {
            throw new Exceptions\MissingDataException($path, $path);
        }

        return $this->input[$path];
    }

    /**
     * @param string $path
     * @param int    $types
     *
     * @return array|mixed
     *
     * @throws Exceptions\MissingDataException
     * @throws Exceptions\ValidationException
     */
    private function evaluate(string $path, int $types)
    {
        $value = $this->evaluatePath($path);

        $this->validate($path, $value, $types);

        return $value;
    }

    /**
     * @param string $path
     * @param null   $default
     *
     * @return mixed|null
     *
     * @throws Exceptions\MissingDataException
     * @throws Exceptions\ValidationException
     */
    protected function get(string $path, int $types = Type::MIXED, $default = null)
    {
        /* Resolve the path and input data, which also caches the value. */
        try {
            return $this->remember($path, function() use ($path, $types) {
                return $this->evaluate($path, $types);
            });

        /* Allow the caller to specify a default if the data is missing. */
        } catch (Exceptions\MissingDataException $e) {
            if (func_num_args() === 2) {
                throw $e;
            }
        }

        return $default;
    }

    /**
     * Delegates the data at a given path to a new instance of the given resolver class.
     *
     * @param string $path
     * @param string $resolver
     * @param bool   $nullable
     *
     * @return Interfaces\Resolver|null
     *
     * @throws Exceptions\MissingDataException
     * @throws Exceptions\ValidationException
     */
    protected function hasOne(string $path, string $resolver, ...$args): ?Interfaces\Resolver
    {
        return $this->remember($path, function() use ($path, $resolver, $args) {
            return $resolver::resolve($this->get($path, Type::ARRAY));
        });
    }

    /**
     * Delegates the data at a given path to an array of new instances of the given resolver class.
     *
     * @param string $path
     * @param string $resolver
     * @param bool   $nullable
     *
     * @return array|null
     *
     * @throws Exceptions\MissingDataException
     * @throws Exceptions\ValidationException
     */
    protected function hasMany(string $path, string $resolver, ...$args): ?array
    {
        return $this->remember($path, function() use ($path, $resolver, $args) {
            $collection = [];

            /* */
            foreach ($this->get($path, Type::ARRAY) as $key => $input) {
                $collection[$key] = $resolver::resolve($input);
            }

            return $collection ?? [];
        });
    }
}
