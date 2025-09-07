<?php
declare(strict_types=1);

namespace App\Services\Support;

use App\Domain\Entities\BaseEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;

final readonly class EntityPatcher
{
    public function __construct(private EntityManagerInterface $emi) {}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws ReflectionException
     */
    public function apply(BaseEntity $obj, array $data): void
    {
        unset($data['id'], $data['deleted']);

        foreach ($data as $key => $value) {
            if ($key === 'name' && method_exists($obj, 'rename')) {
                $obj->rename((string) $value);
                continue;
            }

            if (str_ends_with($key, '_id')) {
                $prop   = rtrim(substr($key, 0, -3), '_');
                $method = 'set' . ucfirst($prop);

                if ($prop === 'parent' && method_exists($obj, 'reparent')) {
                    $selfClass = $obj::class;
                    $parent = $value ? $this->emi->find($selfClass, (int)$value) : null;
                    if ($value && !$parent) throw new \RuntimeException('Parent not found');
                    $obj->reparent($parent);
                    continue;
                }

                if (method_exists($obj, $method)) {
                    $target = $this->firstParamClass($obj::class, $method);
                    if ($target && is_subclass_of($target, BaseEntity::class)) {
                        $refEntity = $this->emi->find($target, (int)$value)
                            ?? throw new \RuntimeException("$target#$value not found");
                        $obj->{$method}($refEntity);
                        continue;
                    }
                }
            }

            $method = 'set' . ucfirst($key);
            if (method_exists($obj, $method)) {
                $obj->{$method}($value);
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function firstParamClass(string $class, string $method): ?string
    {
        $rm = new ReflectionMethod($class, $method);
        $params = $rm->getParameters();
        if (!$params) return null;
        $t = $params[0]->getType();
        if (!$t || $t->isBuiltin()) return null;
        /** @var ReflectionNamedType $t */
        return ltrim($t->getName(), '?');
    }
}

