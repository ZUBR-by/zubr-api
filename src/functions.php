<?php

namespace App;

use Generator;
use LogicException;

function iterateCSV(string $path) : Generator
{
    $handle = fopen($path, "r");
    if ($handle === false) {
        throw new LogicException(sprintf('Not found file in path %s', $path));
    }

    if (fgets($handle, 4) !== "\xef\xbb\xbf") {
        rewind($handle);
    }
    fgets($handle);
    while (($row = fgetcsv($handle)) !== false) {
        yield $row;
    }
    fclose($handle);
}

function mapArraySQLFriendly(array $value) : array
{
    $result = [];
    foreach ($value as $key => $item) {
        if (is_string($item)) {
            if (strpos($item, '\'') !== false) {
                $result[] = '"' . $item . '"';
            } else {
                $result[] = '\'' . $item . '\'';
            }

        } elseif ($item === null) {
            $result[] = 'NULL';
        } else {
            $result[] = $item;
        }
    }

    return $result;
}

function courtsDatasetsContentHash() : string
{
    $overall = '';
    $paths   = [
        'court',
        'judge',
        'judge_tag',
        'history/01-brest',
        'history/02-vitebsk',
        'history/03-gomel',
        'history/04-grodno',
        'history/05-minsk',
        'history/06-mogilev',
        'history/07-capital',
        'history/removed',
    ];
    foreach ($paths as $file) {
        $overall .= sha1_file(__DIR__ . '/../datasets/courts/' . $file . '.csv');
    }

    return $overall;
}
