<?php

namespace iEducar\Packages\Educacenso\Services\Version2025;

use iEducar\Packages\Educacenso\Services\Version2020\Registro40Import;
use iEducar\Packages\Educacenso\Services\Version2022\ImportService as ImportServiceVersion2022;
use iEducar\Packages\Educacenso\Services\Version2024\Registro00Import;

class ImportService extends ImportServiceVersion2022
{
    public static array $inepsServidores = [];
    public static array $inepsAlunos = [];

    public function import($importString, $user): void
    {
        foreach ($importString as $line) {
            $cols = explode(self::DELIMITER, $line);
            if ($cols[0] === '50' && !empty($cols[3])) {
                self::$inepsServidores[$cols[3]] = true;
            } elseif ($cols[0] === '40' && !empty($cols[4])) {
                self::$inepsServidores[$cols[4]] = true;
            } elseif ($cols[0] === '60' && !empty($cols[3])) {
                self::$inepsAlunos[$cols[3]] = true;
            }
        }

        parent::import($importString, $user);
    }

    public function getYear()
    {
        return 2025;
    }

    /**
     * Retorna a classe responsável por importar o registro da linha
     */
    public function getRegistroById($lineId)
    {
        $arrayRegistros = [
            '00' => Registro00Import::class,
            '10' => Registro10Import::class,
            '20' => Registro20Import::class,
            '30' => Registro30Import::class,
            '40' => Registro40Import::class,
            '50' => Registro50Import::class,
            '60' => Registro60Import::class,
        ];

        if (! isset($arrayRegistros[$lineId])) {
            return;
        }

        return new $arrayRegistros[$lineId]();
    }

    public function getSchoolNameByFile($school)
    {
        $columns = explode(self::DELIMITER, $school[0]);

        return $columns[5];
    }
}
