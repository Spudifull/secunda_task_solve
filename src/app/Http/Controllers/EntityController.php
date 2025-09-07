<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Services\EntityService;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use ReflectionException;
use Throwable;

final readonly class EntityController
{
    public function __construct(private EntityService $svc) {}

    /**
     * @throws OptimisticLockException
     * @throws Throwable
     * @throws ORMException
     * @throws ReflectionException
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function save(string $entity, Request $r): JsonResponse
    {
        $obj = $this->svc->save($entity, $r->all());
        return response()->json(['id' => $obj->id]);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function show(string $entity, int $id): JsonResponse
    {
        $e = $this->svc->find($entity, $id);
        if (!$e) return response()->json(['message' => 'Not found'], 404);
        return response()->json(['id' => $e->id, 'deleted' => $e->deleted]);
    }

    public function delete(string $entity, int $id): JsonResponse
    {
        $e = $this->svc->delete($entity, $id);
        return response()->json(['id' => $e->id, 'deleted' => true]);
    }

    public function restore(string $entity, int $id): JsonResponse
    {
        $e = $this->svc->restore($entity, $id);
        return response()->json(['id' => $e->id, 'deleted' => false]);
    }
}
