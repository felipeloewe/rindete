<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\CompletarLectura;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CompletarGastos extends BaseCommand
{
    protected $group       = 'Rindete';
    protected $name        = 'gastos:completar';
    protected $description = 'Completa datos vacíos de gastos ya guardados con lo leído del comprobante.';
    protected $usage       = 'gastos:completar';

    public function run(array $params)
    {
        $informe = (new CompletarLectura())->aplicar();
        CLI::write((string) json_encode($informe, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
