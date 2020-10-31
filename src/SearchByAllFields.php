<?php

namespace App;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\AbstractContextAwareFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Courts\Entity\Court;
use App\Courts\Entity\Judge;
use App\Entity\Commission;
use App\Entity\Member;
use App\Entity\Organization;
use Doctrine\ORM\QueryBuilder;

class SearchByAllFields extends AbstractContextAwareFilter
{
    private const MAP = [
        Member::class       => ['fullName', 'workTitle', 'description'],
        Organization::class => ['name', 'description', 'location'],
        Commission::class   => ['name', 'description', 'location'],
        Court::class        => ['name', 'address', 'description'],
        Judge::class        => ['firstName', 'lastName', 'middleName', 'description'],
    ];

    protected function filterProperty(
        string $property,
        $value,
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ) {
        if (! in_array($property, ['search'])) {
            return;
        }
        if (empty($value)) {
            return;
        }
        if (! isset(self::MAP[$resourceClass])) {
            throw new \LogicException('Not supported resource for filter');
        }
        $value = trim($value);

        if ($resourceClass === Commission::class && preg_match('/(\d{1,2})\-(\d{1,3})\-(\d{1,4})/', $value, $parts)) {
            array_shift($parts);
            $normalized = [];
            foreach ($parts as $index => $part) {
                $normalized[] = str_pad((string) $part, $index + 2, "0", STR_PAD_LEFT);
            }
            $value = implode('-', $normalized);
            $queryBuilder->andWhere("o.code = :commission_code");
            $queryBuilder->setParameter(':commission_code', $value);
            return;
        }

        $fields = [];
        foreach (self::MAP[$resourceClass] as $fieldIndex => $field) {
            $fields[] = 'o.' . $field;
        }
        $words = explode(' ', $value);
        $terms = '';
        foreach ($words as $word) {
            if (! trim($word)) {
                continue;
            }
            $word  = str_replace('"', '', $word);
            $terms .= " +$word*";
        }
        $terms        = trim($terms);
        $fieldsString = implode(', ', $fields);
        $queryBuilder->andWhere("MATCH_AGAINST($fieldsString) AGAINST(:terms) > 0");
        $queryBuilder->addSelect("MATCH_AGAINST($fieldsString) AGAINST(:terms) as HIDDEN match_relevance");
        $queryBuilder->orderBy('match_relevance', 'DESC');
        $queryBuilder->setParameter(':terms', $terms);
    }

    public function getDescription(string $resourceClass) : array
    {
        $description['search'] = [
            'property'    => 'search',
            'type'        => 'string',
            'required'    => false,
            'description' => 'Search in ' . implode(',', self::MAP[$resourceClass]),
            'swagger'     => [
                'description' => 'Search in ' . implode(',', self::MAP[$resourceClass]),
                'name'        => 'search',
                'type'        => 'string',
            ],
        ];

        return $description;
    }
}
