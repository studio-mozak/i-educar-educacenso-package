<?php

namespace iEducar\Packages\Educacenso\Services\Version2019;

use App\Models\Educacenso\Registro40;
use App\Models\Educacenso\RegistroEducacenso;
use App\Models\Employee;
use App\Models\EmployeeInep;
use App\Models\LegacyEmployeeRole;
use App\Models\LegacyInstitution;
use App\Models\LegacyRole;
use App\Models\LegacySchool;
use App\Models\SchoolInep;
use App\Models\SchoolManager;
use App\User;
use iEducar\Packages\Educacenso\Services\RegistroImportInterface;
use iEducar\Packages\Educacenso\Services\Version2019\Models\Registro40Model;

class Registro40Import implements RegistroImportInterface
{
    /**
     * @var Registro40
     */
    protected $model;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var LegacyInstitution
     */
    protected $institution;

    /**
     * Faz a importação dos dados a partir da linha do arquivo
     *
     * @param int                $year
     * @return void
     */
    public function import(RegistroEducacenso $model, $year, $user): void
    {
        \Log::info('[REGISTRO40] Iniciando importação', [
            'cpf' => $model->cpf ?? 'sem_cpf',
            'inep' => $model->inepGestor ?? 'sem_inep',
            'cargo' => $model->cargo ?? 'sem_cargo',
        ]);
        
        $this->user = $user;
        $this->model = $model;
        $this->institution = app(LegacyInstitution::class);

        $employee = $this->getEmployee();
        if (empty($employee)) {
            \Log::warning('[REGISTRO40] Employee não encontrado', [
                'cpf' => $model->cpf ?? 'sem_cpf',
                'inep' => $model->inepGestor ?? 'sem_inep',
            ]);
            return;
        }
        
        \Log::info('[REGISTRO40] Employee encontrado', ['employee_id' => $employee->cod_servidor]);

        $this->createOrUpdateManager($employee);
        
        \Log::info('[REGISTRO40] Gestor criado/atualizado com sucesso');
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
        
        \Log::info('[REGISTRO40] Buscando employee', [
            'inep' => $inepNumber ?? 'vazio',
            'cpf' => $this->model->cpf ?? 'vazio',
        ]);
        
        // Tentar buscar por INEP primeiro
        if ($inepNumber) {
            $employeeInep = EmployeeInep::where('cod_docente_inep', $inepNumber)->first();
            if ($employeeInep && $employeeInep->employee) {
                \Log::info('[REGISTRO40] Employee encontrado por INEP');
                return $employeeInep->employee;
            }
        }
        
        // Se não encontrou por INEP, buscar por CPF
        $cpf = preg_replace('/\D/', '', $this->model->cpf ?? '');
        if (!$cpf) {
            \Log::warning('[REGISTRO40] CPF vazio, não pode buscar');
            return null;
        }
        
        \Log::info('[REGISTRO40] Buscando por CPF', ['cpf' => $cpf]);
        
        $person = \App\Models\LegacyIndividual::where('cpf', $cpf)->first();
        
        if ($person) {
            \Log::info('[REGISTRO40] Pessoa encontrada, criando/buscando employee');
            // Pessoa existe, buscar ou criar employee
            $employee = Employee::firstOrCreate([
                'cod_servidor' => $person->idpes,
                'ref_cod_instituicao' => $this->institution->id,
            ], [
                'carga_horaria' => 0,
                'data_cadastro' => now(),
            ]);
            
            // Criar INEP se tiver
            if ($inepNumber && !EmployeeInep::where('cod_docente_inep', $inepNumber)->exists()) {
                EmployeeInep::create([
                    'cod_servidor' => $employee->cod_servidor,
                    'cod_docente_inep' => $inepNumber,
                ]);
            }
            
            return $employee;
        }
        
        \Log::info('[REGISTRO40] Pessoa não existe, criando do zero');
        // Pessoa não existe, criar tudo do zero
        return $this->createEmployeeFromScratch($cpf, $inepNumber);
    }

    private function createOrUpdateManager(Employee $employee): void
    {
        $school = $this->getSchool();

        if (empty($school)) {
            return;
        }

        // Criar função de gestor se não existir
        $this->ensureManagerRole($employee);

        $manager = SchoolManager::firstOrNew([
            'employee_id' => $employee->id,
            'school_id' => $school->id,
        ]);

        $manager->role_id = $this->model->cargo;
        $manager->access_criteria_id = $this->model->criterioAcesso ?: null;
        $manager->access_criteria_description = $this->model->especificacaoCriterioAcesso;
        $manager->link_type_id = (int) $this->model->tipoVinculo ?: null;
        if (! $this->existsChiefSchoolManager($school)) {
            $manager->chief = true;
        }

        $manager->saveOrFail();
    }

    protected function existsChiefSchoolManager(LegacySchool $school): bool
    {
        return $school->schoolManagers()->where('chief', true)->exists();
    }

    /**
     * @return LegacySchool
     */
    protected function getSchool(): ?LegacySchool
    {
        $schoolInep = SchoolInep::where('cod_escola_inep', $this->model->inepEscola)->first();
        if ($schoolInep) {
            return $schoolInep->school;
        }

        return null;
    }

    private function ensureManagerRole(Employee $employee): void
    {
        // Verificar se já tem alguma função
        $hasRole = LegacyEmployeeRole::where('ref_cod_servidor', $employee->id)
            ->whereHas('role', function ($query): void {
                $query->ativo();
            })->exists();

        if ($hasRole) {
            return;
        }

        // Criar função de gestor baseado no cargo
        $rolesMap = [
            1 => ['nome' => 'Diretor', 'abrev' => 'Diretor'],
            2 => ['nome' => 'Vice-Diretor', 'abrev' => 'Vice-Dir.'],
            3 => ['nome' => 'Secretário Escolar', 'abrev' => 'Secretário'],
            4 => ['nome' => 'Auxiliar de Secretaria', 'abrev' => 'Aux. Secret.'],
            5 => ['nome' => 'Coordenador Pedagógico', 'abrev' => 'Coord. Ped.'],
        ];

        $roleData = $rolesMap[$this->model->cargo] ?? ['nome' => 'Gestor', 'abrev' => 'Gestor'];

        $role = LegacyRole::firstOrCreate(
            [
                'ref_cod_instituicao' => $this->institution->id,
                'nm_funcao' => $roleData['nome'],
                'ativo' => 1,
            ],
            [
                'ref_usuario_cad' => $this->user->id,
                'abreviatura' => $roleData['abrev'],
                'professor' => 0,
            ]
        );

        LegacyEmployeeRole::create([
            'ref_cod_funcao' => $role->id,
            'ref_cod_servidor' => $employee->id,
            'ref_ref_cod_instituicao' => $this->institution->id,
        ]);
    }
    
    private function createEmployeeFromScratch(string $cpf, ?string $inepNumber): Employee
    {
        // Criar pessoa
        $person = \App\Models\LegacyPerson::create([
            'nome' => 'Gestor Importado',
            'data_cad' => now(),
            'tipo' => 'F',
            'situacao' => 'P',
            'origem_gravacao' => 'U',
            'operacao' => 'I',
        ]);
        
        // Criar dados físicos
        \App\Models\LegacyIndividual::create([
            'idpes' => $person->idpes,
            'data_cad' => now(),
            'operacao' => 'I',
            'origem_gravacao' => 'U',
            'cpf' => $cpf,
        ]);
        
        // Criar employee
        $employee = Employee::create([
            'cod_servidor' => $person->idpes,
            'ref_cod_instituicao' => $this->institution->id,
            'carga_horaria' => 0,
            'data_cadastro' => now(),
        ]);
        
        // Criar INEP se tiver
        if ($inepNumber) {
            EmployeeInep::create([
                'cod_servidor' => $employee->cod_servidor,
                'cod_docente_inep' => $inepNumber,
            ]);
        }
        
        return $employee;
    }
}
