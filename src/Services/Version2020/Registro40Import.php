<?php

namespace iEducar\Packages\Educacenso\Services\Version2020;

use App\Models\Educacenso\Registro40;
use App\Models\Educacenso\RegistroEducacenso;
use App\Models\Employee;
use App\Models\EmployeeInep;
use App\Models\LegacyInstitution;
use App\Models\SchoolManager;
use iEducar\Packages\Educacenso\Services\Version2019\Registro40Import as Registro40Import2019;
use iEducar\Packages\Educacenso\Services\Version2020\Models\Registro40Model;

class Registro40Import extends Registro40Import2019
{
    /**
     * Faz a importação dos dados a partir da linha do arquivo
     *
     * @param int                $year
     * @return void
     */
    public function import(RegistroEducacenso $model, $year, $user): void
    {
        \Log::info('[REGISTRO40-V2020] Iniciando importação', [
            'cpf' => $model->codigoPessoa ?? 'sem_cpf',
            'inep' => $model->inepGestor ?? 'sem_inep',
        ]);
        
        $this->user = $user;
        $this->model = $model;
        $this->institution = app(LegacyInstitution::class);

        $employee = $this->getEmployee();
        if (empty($employee)) {
            \Log::warning('[REGISTRO40-V2020] Employee não encontrado');
            return;
        }
        
        \Log::info('[REGISTRO40-V2020] Employee encontrado, criando gestor');
        $this->createOrUpdateManager($employee);
    }

    /**
     * @return Registro40|RegistroEducacenso
     */
    public static function getModel($arrayColumns)
    {
        $registro = new Registro40Model();
        $registro->hydrateModel($arrayColumns);

        return $registro;
    }

    private function getEmployee(): ?Employee
    {
        $inepNumber = $this->model->inepGestor;
        
        \Log::info('[REGISTRO40-V2020] Buscando employee', [
            'inep' => $inepNumber ?? 'vazio',
            'cpf' => $this->model->codigoPessoa ?? 'vazio',
        ]);
        
        // Tentar buscar por INEP primeiro
        if ($inepNumber) {
            $employeeInep = EmployeeInep::where('cod_docente_inep', $inepNumber)->first();
            if ($employeeInep && $employeeInep->employee) {
                \Log::info('[REGISTRO40-V2020] Employee encontrado por INEP');
                return $employeeInep->employee;
            }
        }
        
        // Se não encontrou por INEP, buscar por CPF
        $cpf = preg_replace('/\D/', '', $this->model->codigoPessoa ?? '');
        if (!$cpf) {
            \Log::warning('[REGISTRO40-V2020] CPF vazio');
            return null;
        }
        
        \Log::info('[REGISTRO40-V2020] Buscando por CPF');
        $person = \App\Models\LegacyIndividual::where('cpf', $cpf)->first();
        
        if ($person) {
            \Log::info('[REGISTRO40-V2020] Pessoa encontrada, criando employee');
            $employee = Employee::firstOrCreate([
                'cod_servidor' => $person->idpes,
                'ref_cod_instituicao' => $this->institution->id,
            ], [
                'carga_horaria' => 0,
                'data_cadastro' => now(),
            ]);
            
            if ($inepNumber && !EmployeeInep::where('cod_docente_inep', $inepNumber)->exists()) {
                EmployeeInep::create([
                    'cod_servidor' => $employee->cod_servidor,
                    'cod_docente_inep' => $inepNumber,
                ]);
            }
            
            return $employee;
        }
        
        \Log::warning('[REGISTRO40-V2020] Pessoa não encontrada, não será criada');
        return null;
    }

    private function createOrUpdateManager(Employee $employee): void
    {
        $school = $this->getSchool();

        if (empty($school)) {
            return;
        }

        $manager = SchoolManager::firstOrNew([
            'employee_id' => $employee->id,
            'school_id' => $school->id,
        ]);

        $manager->role_id = $this->model->cargo;
        $manager->access_criteria_id = $this->model->criterioAcesso ?: null;
        $manager->link_type_id = $this->model->tipoVinculo;

        if (! $this->existsChiefSchoolManager($school)) {
            $manager->chief = true;
        }

        $manager->saveOrFail();
    }
}
