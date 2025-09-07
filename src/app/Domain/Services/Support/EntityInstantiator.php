<?php

namespace App\Services\Support;

use App\Domain\Entities\BaseEntity;
use App\Support\Factory\Factory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionClass;
use ReflectionNamedType;

final readonly class EntityInstantiator
{
    public function __construct(private EntityManagerInterface $emi) {}

    /**
     * @throws Exception
     */
    public function instantiate(string $entityKey, string $fqcn, array $data): BaseEntity
    {
        try {
            $args = $this->resolveCtorArgs($fqcn, $data);
        } catch (BindingResolutionException|\ReflectionException $e) {
            throw new Exception();
        }
        /** @var BaseEntity $obj */
        $obj = Factory::createObject($entityKey, $args);
        return $obj;
    }

    /** @return array<string,mixed>
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    private function resolveCtorArgs(string $fqcn, array $data): array
    {
        $ref  = new ReflectionClass($fqcn);
        $ctor = $ref->getConstructor();
        if (!$ctor) return [];

        $out = [];
        foreach ($ctor->getParameters() as $p) {
            $name = $p->getName();
            $type = $p->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $tName = ltrim($type->getName(), '?');

                if (is_subclass_of($tName, BaseEntity::class)) {
                    $val = $data[$name.'_id'] ?? $data[$name] ?? null;
                    if ($val === null && $p->allowsNull()) { $out[$name] = null; continue; }

                    try {
                        $refEntity = $this->emi->find($tName, (int)$val)
                            ?? throw new \RuntimeException("$tName#$val not found");
                    } catch (OptimisticLockException|ORMException $e) {
                        throw new Exception();
                    }

                    $out[$name] = $refEntity;
                    continue;
                }

                $out[$name] = app()->make($tName);
                continue;
            }

            if (array_key_exists($name, $data)) {
                $out[$name] = $data[$name];
            } elseif ($p->isDefaultValueAvailable()) {
                $out[$name] = $p->getDefaultValue();
            } elseif ($p->allowsNull()) {
                $out[$name] = null;
            } else {
                throw new \InvalidArgumentException("Missing constructor field '$name' for $fqcn");
            }
        }
        return $out;
    }
}
