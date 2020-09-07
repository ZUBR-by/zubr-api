<?php

namespace App\Commission;

use App\Entity\Commission;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GeneratePoster
{
    public function __invoke(PosterRenderer $posterRenderer, $id, EntityManagerInterface $em)
    {
        $data = $em->getRepository(Commission::class)->find($id);
        if($data === null) {
            throw new NotFoundHttpException();
        }
        return new BinaryFileResponse(
            $posterRenderer->render($data),
            Response::HTTP_OK, [
                'Content-Type'        => 'image/png',
                'Content-Disposition' => 'inline; filename="' . $data->getCode() . ' - ' . $data->getName() . '"',
            ]
        );
    }
}
