<?php

namespace iEducar\Packages\Educacenso\Services\Version2025;

use App\Models\Educacenso\Registro50;
use App\Models\Educacenso\RegistroEducacenso;
use App\Models\Employee;
use App\Models\EmployeeInep;
use App\Models\LegacyInstitution;
use App\Models\LegacySchoolClass;
use App\Models\LegacySchoolClassTeacher;
use App\Models\SchoolClassInep;
use iEducar\Packages\Educacenso\Services\Version2019\Registro50Import as Registro50Import2019;
use iEducar\Packages\Educacenso\Services\Version2025\Models\Registro50Model;
use Illuminate\Support\Facades\Log;

class Registro50Import extends Registro50Import2019
{
    public function import(RegistroEducacenso $model, $year, $user): void
    {
        $schoolClass = $this->findSchoolClass($model->inepTurma);

        if (!$schoolClass) {
            $schoolClassInep = SchoolClassInep::where('cod_turma_inep', $model->inepTurma)->first();
            Log::channel('educacenso_skipped')->warning('Registro50: Turma não encontrada', [
                'inep_escola' => $model->inepEscola,
                'inep_turma' => $model->inepTurma,
                'inep_turma_empty' => empty($model->inepTurma),
                'school_class_inep_exists' => $schoolClassInep !== null,
                'cod_turma' => $schoolClassInep?->cod_turma,
                'school_class_exists' => $schoolClassInep ? LegacySchoolClass::find($schoolClassInep->cod_turma) !== null : false,
            ]);
            return;
        }

        $employee = $this->findEmployee($model->inepDocente);

        if (!$employee) {
            Log::channel('educacenso_skipped')->warning('Registro50: Servidor não encontrado', [
                'inep_escola'        => $model->inepEscola,
                'nome_escola'        => $schoolClass->school->person->name ?? null,
                'inep_turma'         => $model->inepTurma,
                'nome_turma'         => $schoolClass->nm_turma ?? null,
                'turma_id'           => $schoolClass->getKey(),
                'inep_docente'       => $model->inepDocente,
                'codigo_pessoa_inep' => $model->codigoPessoa,
                'funcao_docente'     => $model->funcaoDocente,
                'tipo_vinculo'       => $model->tipoVinculo,
                'componentes'        => array_values(array_filter($model->componentes ?? [])),
                'acao_necessaria'    => 'Cadastrar servidor no Ensinus com este código INEP de docente',
            ]);
            return;
        }

        parent::import($model, $year, $user);

        $institution = app(LegacyInstitution::class);

        $schoolClassTeacher = LegacySchoolClassTeacher::where([
            'ano' => $year,
            'instituicao_id' => $institution->id,
            'turma_id' => $schoolClass->getKey(),
            'servidor_id' => $employee->getKey(),
        ])->first();

        if (!$schoolClassTeacher) {
            Log::channel('educacenso_skipped')->warning('Registro50: Vínculo servidor-turma não encontrado após import', [
                'inep_escola' => $model->inepEscola,
                'inep_turma' => $model->inepTurma,
                'turma_id' => $schoolClass->getKey(),
                'servidor_id' => $employee->getKey(),
            ]);
            return;
        }

        $schoolClassTeacher->unidades_curriculares = transformDBArrayInString($model->unidadesCurriculares) ?: null;

        $employee = parent::getEmployee();
        $schoolClass = $this->getSchoolClass();
        
        if (!$employee || !$schoolClass) {
            return;
        }
        
        $schoolClassTeacher = LegacySchoolClassTeacher::where('turma_id', $schoolClass->getKey())
            ->where('servidor_id', $employee->getKey())
            ->first();
            
        if (!$schoolClassTeacher) {
            return;
        }
        
        if (is_array($model->areaItinerario) && count($model->areaItinerario) > 0) {
            $schoolClassTeacher->area_itinerario = $this->getPostgresIntegerArray($model->areaItinerario);
        }
        $schoolClassTeacher->leciona_itinerario_tecnico_profissional = $model->lecionaItinerarioTecnicoProfissional ?: null;

        $schoolClassTeacher->save();
    }

    private function findSchoolClass(?string $inepTurma): ?LegacySchoolClass
    {
        if (empty($inepTurma)) {
            return null;
        }

        return SchoolClassInep::where('cod_turma_inep', $inepTurma)->first()?->schoolClass ?? null;
    }

    private function findEmployee(?string $inepDocente): ?Employee
    {
        if (!$inepDocente) {
            return null;
        }

        return EmployeeInep::where('cod_docente_inep', $inepDocente)->first()?->employee ?? null;
    }
  
    private function getPostgresIntegerArray($array)
    {
        return '{' . implode(',', $array) . '}';
    }

    /**
     * @return Registro50|RegistroEducacenso
     */
    public static function getModel($arrayColumns)
    {
        $registro = new Registro50Model();
        $registro->hydrateModel($arrayColumns);

        return $registro;
    }
}
