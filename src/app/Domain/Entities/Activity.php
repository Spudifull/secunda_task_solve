<?php
declare(strict_types=1);

namespace App\Domain\Entities;

use BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Domain\Repositories\ActivityRepository::class)]
#[ORM\Table(name: 'activities')]
final class Activity extends BaseEntity
{
    public function __construct(
        #[ORM\Column(length: 255)]
        public private(set) string $name, // читаем публично, меняем через доменные методы

        #[ORM\ManyToOne(targetEntity: Activity::class, inversedBy: 'children')]
        #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
        public private(set) readonly ?Activity $parent = null,
    ) {
        $this->stampOnCreate();
        $this->children = new ArrayCollection();
        self::assertNoSelfParent($this, $parent);
    }

    /** @var Collection<int,Activity> */
    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'parent')]
    private Collection $children;

    private static function assertNoSelfParent(Activity $self, ?Activity $parent): void {
        if ($parent && $parent === $self) {
            throw new \InvalidArgumentException('Activity cannot be its own parent');
        }
    }

    /** @return list<Activity> */
    public function children(): array { return $this->children->toArray(); }
}
