<?php

namespace App;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class MapViewFilter extends AbstractContextAwareFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
    }

    public function getDescription(string $resourceClass) : array
    {
        $description['map_view'] = [
            'property' => 'map_view',
            'type'     => 'boolean',
            'required' => false,
            'swagger'  => [
                'description' => 'map_view',
                'name'        => 'map_view',
                'type'        => 'boolean',
            ],
        ];

        return $description;
    }
}
