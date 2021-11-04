<?php
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace Icinga\Module\Manufaktura_elfov;

use DateTime;
use Generator;
use Icinga\File\Storage\TemporaryLocalFileStorage;
use RuntimeException;

class ExcelList extends PolitPrisonersList
{
    private string $url;
    private string $nameColumn;
    private ?string $bornColumn;

    public function __construct(string $uuid, string $url, string $nameColumn, ?string $bornColumn = null)
    {
        parent::__construct($uuid);

        $this->url = $url;
        $this->nameColumn = $nameColumn;
        $this->bornColumn = $bornColumn;
    }

    public function fetchAll(): Generator
    {
        $url = $this->url;
        $matches = [];
        $tlfs = new TemporaryLocalFileStorage;
        $tlfsd = dirname($tlfs->resolvePath('nosuchfile'));
        $officeOutput = [];
        $officeExit = null;

        if (preg_match('~^https://docs\.google\.com/spreadsheets/[^/]+/[^/]+/~', $url, $matches)) {
            $url = $matches[0] . 'export?format=xlsx';
        }

        $tlfs->create('xlsx2csv.sh', <<<EOF
set -e
cd '$tlfsd'
LC_CTYPE=en_US.UTF-8 soffice --headless --convert-to 'csv:Text - txt - csv (StarCalc):44,34,76' dl.xlsx 2>&1
EOF
        );

        $f = fopen($tlfs->resolvePath('dl.xlsx'), 'x');

        try {
            $wget = fopen($url, 'r');

            try {
                stream_copy_to_stream($wget, $f);
            } finally {
                fclose($wget);
            }
        } finally {
            fclose($f);
        }


        exec('sh ' . $tlfs->resolvePath('xlsx2csv.sh'), $officeOutput, $officeExit);

        if ($officeExit !== 0) {
            throw new RuntimeException(implode(PHP_EOL, $officeOutput));
        }

        $csv = $this->iterCsv($tlfs->resolvePath('dl.csv'));
        $csv->rewind();

        if ($csv->valid()) {
            $columns = array_flip($csv->current());

            if (isset($columns[$this->nameColumn])) {
                $nameIndex = $columns[$this->nameColumn];
                $bornIndex = $columns[$this->bornColumn] ?? null;

                $infoColumns = array_flip(array_diff(
                    array_flip($columns), array_filter([$this->nameColumn, $this->bornColumn], 'strlen')
                ));

                $csv->next();

                foreach ($this->remaining($csv) as $row) {
                    if (!isset($row[$nameIndex])) {
                        continue;
                    }

                    $pp = new PolitPrisoner;
                    $matches = [];

                    $pp->name = $row[$nameIndex];
                    $pp->source = $this->getUuid();

                    if ($bornIndex !== null && isset($row[$bornIndex])) {
                        if (preg_match('~^(\d+ \w+ \d+)~', $row[$bornIndex])) {
                            $pp->born = new DateTime($matches[1]);
                        }
                    }

                    foreach ($infoColumns as $column => $index) {
                        if (isset($row[$index])) {
                            $pp->info[$column] = $row[$index];
                        }
                    }

                    yield $pp;
                }
            }
        }
    }

    private function iterCsv(string $path): Generator
    {
        $f = fopen($path, 'r');

        try {
            for (; ;) {
                $row = fgetcsv($f);

                if ($row === false) {
                    break;
                }

                yield array_filter(array_map('trim', $row), 'strlen');
            }
        } finally {
            fclose($f);
        }
    }

    private function remaining(Generator $from): Generator
    {
        yield from $from;
    }
}
