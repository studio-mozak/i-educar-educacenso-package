<?php

namespace iEducar\Packages\Educacenso\Services\Version2025;

use App\Models\Educacenso\Registro30;
use App\Models\Educacenso\RegistroEducacenso;
use App\Models\EducacensoDegree;
use App\Models\EducacensoInstitution;
use App\Models\Employee;
use App\Models\EmployeeGraduation;
use App\Models\LegacyDocument;
use App\Services\EmployeePosgraduateService;
use iEducar\Modules\ValueObjects\EmployeePosgraduateValueObject;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use iEducar\Packages\Educacenso\Services\Version2023\Registro30Import as Registro30Import2023;
use iEducar\Packages\Educacenso\Services\Version2025\Models\Registro30Model;

class Registro30Import extends Registro30Import2023
{
    public function import(RegistroEducacenso $model, $year, $user): void
    {
        $this->user = $user;
        $this->model = $model;
        $this->year = $year;
        parent::import($model, $year, $user);
    }

    /**
     * @return Registro30|RegistroEducacenso
     */
    public static function getModel($arrayColumns)
    {
        $registro = new Registro30Model();
        $registro->hydrateModel($arrayColumns);

        return $registro;
    }

    protected function createEmployeeGraduations(Employee $employee): void
    {
        $arrayCursos = array_values(array_filter($this->model->formacaoCurso));
        $arrayInstituicoes = array_values(array_filter($this->model->formacaoInstituicao));
        $arrayAnosConclusao = array_values(array_filter($this->model->formacaoAnoConclusao));

        if (empty($arrayCursos) || $employee->graduations->count()) {
            return;
        }

        foreach ($arrayCursos as $key => $curso) {
            $iesId = $arrayInstituicoes[$key] ?? null;

            $degree = EducacensoDegree::where('curso_id', $curso)->first();

            if (empty($degree)) {
                Log::channel('educacenso_skipped')->warning('Registro30: Curso de graduação não encontrado', [
                    'inep_escola'      => $this->model->inepEscola,
                    'inep_docente'     => $this->model->inepPessoa,
                    'cpf'              => $this->model->cpf,
                    'nome_servidor'    => $this->model->nomePessoa,
                    'employee_id'      => $employee->getKey(),
                    'curso_id_inep'    => $curso,
                    'ies_id_inep'      => $iesId,
                    'ano_conclusao'    => $arrayAnosConclusao[$key] ?? null,
                    'acao_necessaria'  => 'Cadastrar manualmente a graduação do servidor no i-Educar',
                ]);
                continue;
            }

            $institution = null;
            if ($iesId && is_numeric($iesId) && $iesId <= 2147483647) {
                $institution = EducacensoInstitution::where('ies_id', (int) $iesId)->first();
            }

            EmployeeGraduation::create([
                'employee_id'     => $employee->getKey(),
                'course_id'       => $degree->getKey(),
                'completion_year' => $arrayAnosConclusao[$key] ?? null,
                'college_id'      => $institution?->getKey(),
            ]);
        }
    }

    protected function createCertidaoNascimento(\App\Models\LegacyStudent $student): void
    {
        if (empty($this->model->certidaoNascimento)) {
            return;
        }

        $idpes = $student->person->getKey();

        try {
            LegacyDocument::updateOrCreate(
                ['idpes' => $idpes],
                [
                    'certidao_nascimento' => $this->model->certidaoNascimento,
                    'origem_gravacao'     => 'U',
                    'operacao'            => 'I',
                    'data_cad'            => now(),
                ]
            );
        } catch (QueryException $e) {
            if ($e->getCode() !== '40P01') {
                throw $e;
            }

            Log::channel('educacenso_skipped')->warning('Registro30: Deadlock ao salvar certidão, ignorando', [
                'idpes'       => $idpes,
                'inep_escola' => $this->model->inepEscola ?? null,
            ]);
        }
    }

    protected function storePosgraduate($employee): void
    {
        if (empty($this->model->posGraduacoes)) {
            return;
        }

        /** @var EmployeePosgraduateService $employeePosgraduateService */
        $employeePosgraduateService = app(EmployeePosgraduateService::class);

        foreach ($this->model->posGraduacoes as $posgraducao) {
            $valueObject = new EmployeePosgraduateValueObject();
            $valueObject->employeeId = $employee->id;
            $valueObject->entityId = $this->institution->getKey();
            $valueObject->typeId = $posgraducao['tipo'] ?: null;
            $valueObject->areaId = $posgraducao['area'] ?: null;
            $valueObject->completionYear = $posgraducao['ano_conclusao'] ?: null;
            $employeePosgraduateService->storePosgraduate($valueObject);
        }
    }
}
