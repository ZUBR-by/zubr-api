<?php

namespace App\Commission;

use ApiPlatform\Core\Serializer\SerializerContextBuilderInterface;
use App\Entity\Commission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ContextBuilder implements SerializerContextBuilderInterface
{
    private $decorated;
    private $authorizationChecker;

    public function __construct(
        SerializerContextBuilderInterface $decorated,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->decorated            = $decorated;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null) : array
    {
        $context       = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        $resourceClass = $context['resource_class'] ?? null;

        if ($resourceClass === Commission::class
            && isset($context['groups'])
            && $normalization
            && $request->query->get('map_view') === 'true'
        ) {
            $context['groups'] = ['map_view'];
        }

        return $context;
    }
}
