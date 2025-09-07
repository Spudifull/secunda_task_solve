<?php
declare(strict_types=1);

namespace App\Domain\Services;

use App\Domain\Entities\BaseEntity;
use App\Services\Support\EntityInstantiator;
use App\Services\Support\EntityPatcher;
use App\Support\Factory\Factory;
use App\Support\Helper;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final class EntityService
{
    public function __construct(
        private readonly EntityManagerInterface $emi,
        private readonly EntityInstantiator $instantiator,
        private readonly EntityPatcher $patcher,
    ) {}

    public function find(string $entity, int $id): ?BaseEntity
    {
        $class = Factory::getClassByName($entity);
        return $this->emi->find($class, $id);
    }

    public function save(string $entity, array $data): BaseEntity
    {
        $class = Factory::getClassByName($entity);
        $id = (int)($data['id'] ?? 0);

        if ($id > 0) {
            $obj = $this->emi->find($class, $id) ?? throw new RuntimeException('Not found');
            $isNew = false;
        } else {
            $obj = $this->instantiator->instantiate($entity, $class, $data);
            $isNew = true;
        }

        $wantDeleted = Helper::boolOrNull($data['deleted'] ?? null);
        if (property_exists($obj, 'deleted') && $obj->deleted && $wantDeleted !== false) {
            throw new RuntimeException('Элемент в архиве. Сначала deleted=false.');
        }

        $conn = $this->emi->getConnection();
        $conn->beginTransaction();
        try {
            $this->patcher->apply($obj, $data);

            if ($wantDeleted === true && method_exists($obj, 'softDelete')) {
                $obj->softDelete();
            } elseif ($wantDeleted === false) {
                if (property_exists($obj, 'deleted'))   $obj->deleted   = false;
                if (property_exists($obj, 'deletedDt')) $obj->deletedDt = null;
            }

            if ($isNew) $this->emi->persist($obj);
            $this->emi->flush();

            $conn->commit();
            return $obj;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    public function delete(string $entity, int $id): BaseEntity
    {
        $obj = $this->find($entity, $id) ?? throw new RuntimeException('Not found');
        if (method_exists($obj, 'softDelete')) $obj->softDelete();
        $this->emi->flush();
        return $obj;
    }

    public function restore(string $entity, int $id): BaseEntity
    {
        $obj = $this->find($entity, $id) ?? throw new RuntimeException('Not found');
        if (property_exists($obj, 'deleted'))   $obj->deleted   = false;
        if (property_exists($obj, 'deletedDt')) $obj->deletedDt = null;
        $this->emi->flush();
        return $obj;
    }
}
