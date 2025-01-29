<?php

namespace iEducar\Packages\Educacenso\Services\Version2024;

use App\Models\Educacenso\Registro60;
use App\Models\Educacenso\RegistroEducacenso;
use App\Models\LegacyEnrollment;
use App\Models\LegacyRegistration;
use iEducar\Modules\Educacenso\Model\TipoItinerarioFormativo;
use iEducar\Packages\Educacenso\Services\Version2019\Registro60Import as Registro60Import2019;
use iEducar\Packages\Educacenso\Services\Version2024\Models\Registro60Model;

class Registro60Import extends Registro60Import2019
{
    /**
     * Faz a importação dos dados a partir da linha do arquivo
     *
     * @param int                $year
     * @return void
     */
    public function import(RegistroEducacenso $model, $year, $user): void
    {
        $this->year = $year;
        $this->user = $user;
        $this->model = $model;

        parent::import($model, $year, $user);

        $arrayItineraryType = $this->getArrayItineraryType();
        $arrayItineraryComposition = $this->getArrayItineraryComposition();

        if (count($arrayItineraryType) > 0) {
            $schoolClass = parent::getSchoolClass();
            $student = parent::getStudent();
            $registration = LegacyRegistration::where('ref_ref_cod_serie', $schoolClass->grade->getKey())
                ->where('ref_cod_aluno', $student->getKey())
                ->where('ano', $this->year)
                ->first();

            $enrollment = LegacyEnrollment::where('ref_cod_matricula', $registration->getKey())
                ->where('ref_cod_turma', $schoolClass->getKey())
                ->first();

            $enrollment->tipo_itinerario = $arrayItineraryType;
            $enrollment->composicao_itinerario = $arrayItineraryComposition;
            $enrollment->curso_itinerario = $this->model->cursoItinerario ?: null;
            $enrollment->itinerario_concomitante = $this->model->cursoItinerario ? (bool) $this->model->itinerarioConcomitante : null;

            $enrollment->save();
        }
    }

    protected function getArrayItineraryType()
    {
        $arrayItineraryType = [];

        if ($this->model->tipoItinerarioLinguagens) {
            $arrayItineraryType[] = TipoItinerarioFormativo::LINGUANGENS;
        }

        if ($this->model->tipoItinerarioMatematica) {
            $arrayItineraryType[] = TipoItinerarioFormativo::MATEMATICA;
        }

        if ($this->model->tipoItinerarioCienciasNatureza) {
            $arrayItineraryType[] = TipoItinerarioFormativo::CIENCIAS_NATUREZA;
        }

        if ($this->model->tipoItinerarioCienciasHumanas) {
            $arrayItineraryType[] = TipoItinerarioFormativo::CIENCIAS_HUMANAS;
        }

        if ($this->model->tipoItinerarioFormacaoTecnica) {
            $arrayItineraryType[] = TipoItinerarioFormativo::FORMACAO_TECNICA;
        }

        if ($this->model->tipoItinerarioIntegrado) {
            $arrayItineraryType[] = TipoItinerarioFormativo::ITINERARIO_INTEGRADO;
        }

        return $arrayItineraryType;
    }

    protected function getArrayItineraryComposition()
    {
        $arrayItineraryComposition = [];

        if ($this->model->composicaoItinerarioLinguagens) {
            $arrayItineraryComposition[] = TipoItinerarioFormativo::LINGUANGENS;
        }

        if ($this->model->composicaoItinerarioMatematica) {
            $arrayItineraryComposition[] = TipoItinerarioFormativo::MATEMATICA;
        }

        if ($this->model->composicaoItinerarioCienciasNatureza) {
            $arrayItineraryComposition[] = TipoItinerarioFormativo::CIENCIAS_NATUREZA;
        }

        if ($this->model->composicaoItinerarioCienciasHumanas) {
            $arrayItineraryComposition[] = TipoItinerarioFormativo::CIENCIAS_HUMANAS;
        }

        if ($this->model->composicaoItinerarioFormacaoTecnica) {
            $arrayItineraryComposition[] = TipoItinerarioFormativo::FORMACAO_TECNICA;
        }

        return $arrayItineraryComposition;
    }

    /**
     * @return Registro60|RegistroEducacenso
     */
    public static function getModel($arrayColumns)
    {
        $registro = new Registro60Model();
        $registro->hydrateModel($arrayColumns);

        return $registro;
    }
}
