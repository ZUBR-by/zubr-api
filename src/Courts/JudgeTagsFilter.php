<?php

namespace App\Courts;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Courts\Entity\Judge;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;

final class JudgeTagsFilter extends AbstractContextAwareFilter
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

        if (! in_array($property, ['tag']) || empty($value) || ! is_array($value)) {
            return;
        }

        if (! is_array($value)) {
            $value = explode(',', (string) $value);
        }
        $expression = new Orx();
        foreach ($value as $index => $item) {
            $expression->add($queryBuilder->expr()->orX(
                "JSON_CONTAINS(o.tags, :str_{$index}) = 1",
            ));
            $queryBuilder->setParameter('str_' . $index, '"' . $index . '"', \PDO::PARAM_STR);
        }
        $queryBuilder->andWhere($expression);
    }

    public function getDescription(string $resourceClass) : array
    {
        $description['tags']            = [
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
