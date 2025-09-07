<?php
declare(strict_types=1);

namespace App\Application\Crud;

use App\Application\Crud\Support\EntityInstantiator;
use App\Application\Crud\Support\EntityPatcher;
use App\Domain\Entities\BaseEntity;
use App\Support\Factory\Factory;
use App\Support\Helper;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use RuntimeException;

class EntityService
{
    public function __construct(
        private readonly EntityManagerInterface $emi,
        private readonly EntityInstantiator     $inst,
        private readonly EntityPatcher          $patch,
    ) {}

    public function find(string $entity, int $id): ?BaseEntity
    {
        $class = Factory::getClassByName($entity);
        return $this->emi->find($class, $id);
    }

    /**
     * @throws OptimisticLockException
     * @throws \Throwable
     * @throws ORMException
     * @throws Exception
     */
    public function save(string $entity, array $data): BaseEntity
    {
        $class = Factory::getClassByName($entity);
        $id = (int)($data['id'] ?? 0);

        if ($id > 0) {
            $obj = $this->emi->find($class, $id) ?? throw new RuntimeException('Not found');
            $isNew = false;
        } else {
            $obj = $this->inst->instantiate($entity, $class, $data);
            $isNew = true;
        }

        $wantDeleted = Helper::toBoolOrNull($data['deleted'] ?? null);
        if (property_exists($obj, 'deleted') && $obj->deleted && $wantDeleted !== false) {
            throw new RuntimeException('Элемент в архиве. Восстановите его');
        }

        $cx = $this->emi->getConnection();
        $cx->beginTransaction();
        try {
            $this->patch->apply($obj, $data);

            if ($wantDeleted === true && method_exists($obj, 'softDelete')) {
                $obj->softDelete();
            } elseif ($wantDeleted === false) {
                if (property_exists($obj, 'deleted'))   $obj->deleted   = false;
                if (property_exists($obj, 'deletedDt')) $obj->deletedDt = null;
            }

            if ($isNew) $this->emi->persist($obj);
            $this->emi->flush();

            $cx->commit();
            return $obj;
        } catch (\Throwable $e) {
            $cx->rollBack(); throw $e;
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
