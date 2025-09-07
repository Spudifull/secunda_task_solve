<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Queries\OrganizationQueries;
use Doctrine\DBAL\Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class QueryController
{
    public function __construct(private OrganizationQueries $q) {}

    /**
     * @throws Exception
     */
    public function near(Request $r): JsonResponse
    {
        $lat = (float)$r->query('lat');
        $lng = (float)$r->query('lng');
        $radius = (float)$r->query('radius_m', 1000);
        return response()->json($this->q->near($lat, $lng, $radius));
    }

    /**
     * @throws Exception
     */
    public function buildings(): JsonResponse
    {
        return response()->json($this->q->buildingsList());
    }
}
