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
