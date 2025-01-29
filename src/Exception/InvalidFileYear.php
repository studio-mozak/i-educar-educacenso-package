<?php

namespace iEducar\Packages\Educacenso\Exception;

use Exception;

class InvalidFileYear extends Exception
{
    public function __construct($fileYear, $serviceYear)
    {
        parent::__construct("O ano do arquivo ($fileYear) não corresponde ao ano do serviço ($serviceYear).");
    }
}
