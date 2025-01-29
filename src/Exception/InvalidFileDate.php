<?php

namespace iEducar\Packages\Educacenso\Exception;

use Exception;

class InvalidFileDate extends Exception
{
    protected $message = 'A data do arquivo é inválida.';
}
