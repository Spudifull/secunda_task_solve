<?php
declare(strict_types=1);

namespace App\Queries;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

final readonly class OrganizationQueries
{
    public function __construct(private Connection $db) {}

    /**
     * @throws Exception
     */
    public function near(float $lat, float $lng, float $radiusM = 1000): array
    {
        $sql = <<<'SQL'
            select o.id, o.name, b.address, st_y(b.location)::float as lat, st_x(b.location)::float as lng
            from organizations as o
            join buildings as b on b.id = o.building_id
            where not o.deleted and not b.deleted and st_dwithin(
                b.location::geography,
                st_setsrid(st_point(:lng, :lat), 4326)::geography,
                :r
            )
            order by o.name
        SQL;

        return $this->db->fetchAllAssociative($sql, [
            'lat' => $lat,
            'lng' => $lng,
            'r'   => $radiusM,
        ]);
    }

    /**
     * @throws Exception
     */
    public function buildingsList(): array
    {
        $sql = <<<'SQL'
            select id, address
            from buildings
            where not deleted
            order by address
        SQL;

        return $this->db->fetchAllAssociative($sql);
    }

}
