<?php

namespace App;

namespace App;

use App\Entity\Message;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class Normalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    private $decorated;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(NormalizerInterface $decorated, TokenStorageInterface $tokenStorage)
    {
        if (! $decorated instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException(
                sprintf('The decorated normalizer must implement the %s.', DenormalizerInterface::class)
            );
        }

        $this->decorated    = $decorated;
        $this->tokenStorage = $tokenStorage;
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $this->decorated->supportsNormalization($data, $format);
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $token        = $this->tokenStorage->getToken();
        $isAnon       = $token === null || $token instanceof AnonymousToken;
        $data         = $this->decorated->normalize($object, $format, $context);
        $isCollection = isset($context['collection_operation_name'])
            && $context['collection_operation_name'] === 'get_public';
        $isItem       = isset($context['item_operation_name'])
            && $context['item_operation_name'] === 'get';
        if (($isCollection || $isItem)
            && $object instanceof Message
            && $isAnon
            && isset($data['attachments']) && is_array($data['attachments'])
        ) {
            $data['attachments'] = array_filter(
                $data['attachments'],
                function ($item) {
                    if (! is_array($item)) {
                        return true;
                    }
                    $isHidden = isset($item['hide']) && $item['hide'] === true;
                    return ! $isHidden;
                }
            );
        }
        return $data;
    }

    public function supportsDenormalization($data, $type, string $format = null)
    {
        return $this->decorated->supportsDenormalization($data, $type, $format);
    }

    public function denormalize($data, $class, string $format = null, array $context = [])
    {
        return $this->decorated->denormalize($data, $class, $format, $context);
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        if ($this->decorated instanceof SerializerAwareInterface) {
            $this->decorated->setSerializer($serializer);
        }
    }
}
