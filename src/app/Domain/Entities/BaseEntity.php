<?php
declare(strict_types=1);

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class BaseEntity
{
    #[ORM\Id, ORM\Column(type: 'bigint'), ORM\GeneratedValue(strategy: 'IDENTITY')]
    public private(set) ?int $id = null;

    #[ORM\Column(name: 'created_dt', type: 'datetimetz_immutable')]
    protected \DateTimeImmutable $createdDt;

    #[ORM\Column(name: 'updated_dt', type: 'datetimetz_immutable')]
    protected \DateTimeImmutable $updatedDt;

    #[ORM\Column(type: 'boolean')]
    public private(set) bool $deleted = false;

    #[ORM\Column(name: 'deleted_dt', type: 'datetimetz_immutable', nullable: true)]
    public private(set) ?\DateTimeImmutable $deletedDt = null;

    protected function stampOnCreate(): void {
        $now = new \DateTimeImmutable();
        $this->createdDt = $now;
        $this->updatedDt = $now;
    }
}
