<?php

namespace App\Courts\Decisions;

use App\ErrorHandler;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

class ArchiveDecision
{
    private Connection $dbal;
    private ErrorHandler $errorHandler;

    public function __construct(Connection $dbal, ErrorHandler $errorHandler)
    {
        $this->dbal         = $dbal;
        $this->errorHandler = $errorHandler;
    }

    public function __invoke(int $id) : JsonResponse
    {
        $id = (int) $this->dbal->fetchOne('SELECT id FROM decisions WHERE id = ?', [$id]);

        if ($id === 0) {
            return new JsonResponse([]);
        }

        try {
            $this->dbal->transactional(function () use ($id) {
                $this->dbal->executeQuery(
                    'INSERT INTO decisions_archive SELECT * FROM decisions WHERE id = ?',
                    [$id]
                );
                $this->dbal->executeQuery(
                    'UPDATE attachment SET decision_id = null, decision_archived_id = ? WHERE decision_id = ?',
                    [$id, $id]
                );
                $this->dbal->executeQuery(
                    'DELETE FROM decisions WHERE id = ?',
                    [$id]
                );
            });
        } catch (UniqueConstraintViolationException $e) {

        } catch (Throwable $e) {
            $this->errorHandler->handleException($e);
            return new JsonResponse(['error' => 'Произошла ошибка']);
        }

        return new JsonResponse([]);
    }
}
