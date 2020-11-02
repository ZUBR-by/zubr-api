<?php

namespace App\Elections;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Elections\Entity\Message;
use App\Elections\Entity\Staff;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;

final class MessageFilters extends AbstractContextAwareFilter
{
    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if (
            $resourceClass !== Message::class
        ) {
            return;
        }
        if (! in_array($property, ['staff_id', 'has_attachments', 'categories'])) {
            return;
        }
        if ($property === 'has_attachments' && $value === 'true') {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->neq(
                    'o.attachments',
                    "'[]'"
                )
            );
            return;
        }

        if ($property === 'categories') {
            if (empty($value)) {
                return;
            }
            if (! is_array($value)) {
                $value = explode(',', (string) $value);
            }
            $expression = new Orx();
            foreach ($value as $index => $item) {
                $expression->add($queryBuilder->expr()->orX(
                    "JSON_CONTAINS(o.categories, :str_{$index}) = 1",
                    "JSON_CONTAINS(o.categories, :int_{$index}) = 1"
                ));
                $queryBuilder->setParameter('str_' . $index, '"' . $item . '"', \PDO::PARAM_STR);
                $queryBuilder->setParameter('int_' . $index, (int) $item, \PDO::PARAM_INT);
            }
            $queryBuilder->andWhere($expression);
            return;
        }
        /** @var Staff $staff */
        $staff = $this->getManagerRegistry()->getRepository(Staff::class)->find($value);
        if (! $staff->isModerator()) {
            return;
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                'o.commissionCode',
                $staff->getCommissions()
            )
        );
    }

    public function getDescription(string $resourceClass) : array
    {
        return [];
    }
}
