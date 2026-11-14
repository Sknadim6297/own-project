<?php

require __DIR__.'/vendor/autoload.php';

$path = __DIR__.'/docs/client-requirements/YuktiOne_PFM_Developer_Logic_v2.xlsx';
$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$spreadsheet = $reader->load($path);

foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
    echo '===SHEET: '.$sheet->getTitle()."===\n";
    $rows = $sheet->toArray(null, true, true, false);
    $i = 0;
    foreach ($rows as $row) {
        $i++;
        $cells = array_map(static function ($c) {
            if ($c === null) {
                return '';
            }

            return str_replace(["\r", "\n", "\t"], [' ', ' ', ' '], (string) $c);
        }, $row);

        while (count($cells) && end($cells) === '') {
            array_pop($cells);
        }

        if ($cells === []) {
            continue;
        }

        echo 'R'.$i.'|'.implode(' | ', $cells)."\n";

        if ($i > 400) {
            echo "...TRUNCATED...\n";
            break;
        }
    }
    echo "\n";
}
