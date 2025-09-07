<?php
declare(strict_types=1);

namespace App\Support\Factory;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionClass;

final class Factory
{
    public static array $stats = [];
    private static array $cache = [];

    /**
     * Магия: Factory::organization(...), Factory::organizationService(...), Factory::organizationController(...)
     * @throws BindingResolutionException
     */
    public static function __callStatic(string $method, array $parameters): object
    {
        return self::createObject($method, $parameters);
    }

    /**
     * @throws BindingResolutionException
     * @throws Exception
     */
    public static function createObject(string $method, array $parameters = []): object
    {
        $class = self::getClassByName($method, $parameters);
        self::$stats[$class] = (self::$stats[$class] ?? 0) + 1;

        if (self::shouldUseContainer($class)) {
            $named = self::namedArgsFor($class, $parameters);
            return app()->make($class, $named ?: $parameters);
        }

        return new $class(...$parameters);
    }

    /**
     * @throws Exception
     */
    public static function getClassByName(string $name, array &$parameters = []): string
    {
        if (isset(self::$cache[$name])) {
            return self::$cache[$name];
        }

        $cfg = config('factory') ?? [];
        $ns  = $cfg['namespaces'] ?? [
            'controller' => 'App\\Http\\Controllers\\%1$s',
            'service'    => 'App\\Services\\%1$s',
            'entity'     => 'App\\Domain\\Entities\\%1$s',
        ];

        $suffix = self::detectSuffix($name);
        $base   = self::baseName($name);

        $pattern = $ns[$suffix] ?? $ns['entity'];
        $candidate = sprintf($pattern, $base);
        if (class_exists($candidate)) {
            return self::$cache[$name] = $candidate;
        }

        throw new Exception("Class not found for method [$name] (base: $base, suffix: $suffix)");
    }

    private static function detectSuffix(string $name): string
    {
        $n = strtolower($name);
        return match (true) {
            str_ends_with($n, 'controller') => 'controller',
            str_ends_with($n, 'service')    => 'service',
            default                         => 'entity',
        };
    }

    private static function baseName(string $name): string
    {
        $parts  = explode('\\', $name);
        $method = array_pop($parts);

        foreach (['Controller','Service','Entity'] as $suf) {
            if (str_ends_with($method, $suf)) {
                $method = substr($method, 0, -strlen($suf));
                break;
            }
        }

        if ($method && $method[0] !== strtoupper($method[0])) {
            $method = ucfirst($method);
        }
        return $method;
    }

    private static function shouldUseContainer(string $class): bool
    {
        $cfg = config('factory') ?? [];
        $suffixes = array_map('strtolower', (array)($cfg['container_suffixes'] ?? ['controller','service','mapper','events','mail']));

        $lower = strtolower(class_basename($class));
        if (array_any($suffixes, fn($suf) => str_ends_with($lower, $suf))) {
            return true;
        }

        try {
            $ref = new ReflectionClass($class);
            $ctor = $ref->getConstructor();
            if (!$ctor) return false;
            if (array_any($ctor->getParameters(), fn($p) => $p->hasType() && !$p->getType()->isBuiltin())) {
                return true;
            }
        } catch (\Throwable) {}
        return false;
    }

    private static function namedArgsFor(string $class, array $parameters): array
    {
        if (!$parameters || !array_is_list($parameters)) return $parameters;
        try {
            $ref = new ReflectionClass($class);
            $ctor = $ref->getConstructor();
            if (!$ctor) return $parameters;
            $out = [];
            foreach ($ctor->getParameters() as $i => $param) {
                if (array_key_exists($i, $parameters)) {
                    $out[$param->getName()] = $parameters[$i];
                }
            }
            return $out ?: $parameters;
        } catch (\Throwable) {
            return $parameters;
        }
    }
}

