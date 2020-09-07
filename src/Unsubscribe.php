<?php

namespace App;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Unsubscribe
{
    public function __invoke(Request $request, Connection $dbal, string $uid, string $hash, string $unsubscribeSecret)
    {
        $verify = sha1($uid . $unsubscribeSecret);
        if ($verify !== $hash) {
            return new Response('Успешно отписались от рассылки');
        }
        try {
            $dbal->insert('unsubscribed_observers', ['uid' => $uid]);
        } catch (UniqueConstraintViolationException $e) {

        }

        return new Response('Успешно отписались от рассылки');
    }
}
