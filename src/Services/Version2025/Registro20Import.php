<?php

namespace iEducar\Packages\Educacenso\Services\Version2025;

use App\Models\Educacenso\Registro20;
use App\Models\Educacenso\RegistroEducacenso;
use App\Models\LegacySchoolClass;
use iEducar\Packages\Educacenso\Services\Version2023\Registro20Import as Registro20Import2023;
use iEducar\Packages\Educacenso\Services\Version2025\Models\Registro20Model;
use Illuminate\Support\Facades\Log;

class Registro20Import extends Registro20Import2023
{
    public function import(RegistroEducacenso $model, $year, $user): void
    {
        $this->user = $user;
        $this->model = $model;
        $this->year = $year;

        parent::import($model, $year, $user);

        $model = $this->model;

        $schoolClassInep = parent::getSchoolClass();

        if (empty($schoolClassInep)) {
            Log::channel('educacenso_skipped')->warning('Registro20 (2025): Turma não encontrada após import', [
                'inep_escola' => $model->codigoEscolaInep,
                'inep_turma' => $model->inepTurma,
            ]);
            return;
        }

        $schoolClass = LegacySchoolClass::find($schoolClassInep->cod_turma);

        if (empty($schoolClass)) {
            Log::channel('educacenso_skipped')->warning('Registro20 (2025): LegacySchoolClass não encontrada', [
                'inep_turma' => $model->inepTurma,
                'cod_turma' => $schoolClassInep->cod_turma,
            ]);
            return;
        }

        $schoolClass->etapa_agregada = $model->etapaAgregada ?: null;
        $schoolClass->classe_especial = $model->classeEspecial;
        $schoolClass->formacao_alternancia = $model->formacaoAlternancia;
        if (is_array($model->areaItinerario) && count($model->areaItinerario) > 0) {
            $schoolClass->area_itinerario = $this->getPostgresIntegerArray($model->areaItinerario);
        }
        $schoolClass->tipo_curso_intinerario = $model->tipoCursoIntinerario ?: null;
        $schoolClass->cod_curso_profissional_intinerario = $model->codCursoProfissionalIntinerario ?: null;

        $schoolClass->save();
    }

    /**
     * @return Registro20|RegistroEducacenso
     */
    public static function getModel($arrayColumns)
    {
        $registro = new Registro20Model();
        $registro->hydrateModel($arrayColumns);

        return $registro;
    }

    public static function getComponentes()
    {
        $componentes = parent::getComponentes();


        return $componentes;
    }
}
