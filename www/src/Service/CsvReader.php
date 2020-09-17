<?php

namespace App\Service;

use Generator;

class CsvReader
{
    public function getContent(string $filePath, string $delimiter = ';'): Generator
    {
        /** @var resource $file */
        $file = fopen($filePath, 'rb');
        while ($line = fgetcsv($file, 0, $delimiter)) {
            yield $line;
        }
        fclose($file);
    }
}