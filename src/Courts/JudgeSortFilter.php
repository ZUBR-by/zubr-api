<?php

namespace App\Courts;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Courts\Entity\Judge;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

final class JudgeSortFilter extends AbstractContextAwareFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if ($resourceClass !== Judge::class) {
            return;
        }

        if (! in_array($property, ['sort']) || empty($value) || ! is_array($value)) {
            return;
        }

        foreach ($value as $field => $order) {
            switch ($field) {
                case 'fullName':
                    $queryBuilder->addOrderBy('o.fullName', $order);
                    break;
                case 'tags':
                    $queryBuilder->addSelect('JSON_LENGTH(o.tags) as HIDDEN tag_contains');
                    $queryBuilder->addOrderBy('tag_contains', $order);
                    break;
                case 'decisions':
                    $queryBuilder->addSelect('COUNT(d.id) as HIDDEN num');
                    $queryBuilder->leftJoin(
                        'o.decisions',
                        'd',
                        Join::WITH,
                        'd.category = \'administrative\' AND d.hiddenAt IS NULL'
                    );
                    $queryBuilder->groupBy('o.id');
                    $queryBuilder->addOrderBy('num', $order);
                    break;
            }
        }
    }

    public function getDescription(string $resourceClass) : array
    {
        $description['sort[tags]']      = [
            'property'    => 'sort',
            'type'        => 'string',
            'required'    => false,
            'description' => 'sort by tags',
            'swagger'     => [
                'description' => 'tag exists',
                'name'        => 'search',
                'type'        => 'string',
            ],
        ];
        $description['sort[decisions]'] = [
            'property'    => 'sort',
            'type'        => 'string',
            'required'    => false,
            'description' => 'sort by decisions',
            'swagger'     => [
                'description' => 'count decisions',
                'name'        => 'search',
                'type'        => 'string',
            ],
        ];

        return $description;
    }
}
