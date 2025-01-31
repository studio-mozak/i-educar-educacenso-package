<?php

namespace iEducar\Packages\Educacenso\Layout\Export\Situation\Layout2024;

use App\Models\LegacyEnrollment;
use App\Models\LegacyRegistration;
use App\Models\LegacySchool;
use App\Models\SchoolClassInep;
use App\Services\SchoolClass\SchoolClassService;
use App_Model_MatriculaSituacao;
use Carbon\Carbon;
use iEducar\Modules\Educacenso\Model\TipoAtendimentoTurma;
use iEducar\Modules\SchoolClass\Period;
use Illuminate\Support\Facades\Cache;

class SituationRepository extends \iEducar\Packages\Educacenso\Layout\Export\Contracts\SituationRepository
{
    private SchoolClassService $service;

    public function __construct()
    {
        $this->service = new SchoolClassService;
    }

    private array $ignoreRegistrationsRecord90 = [];

    public function getDataRecord89(
        int $year,
        int $schoolId
    ): array {
        $school = LegacySchool::query()
            ->select([
                'cod_escola',
                'ref_idpes_gestor'
            ])
            ->with([
                'inep',
                'schoolManagers',
            ])
            ->whereKey($schoolId)
            ->first();
        if ($school instanceof LegacySchool) {

            $schoolManager = $school->schoolManagers->sortBy('role_id')->first();

            return [
                '1' => 89,
                '2' => $school->inep->number,
                '3' => clearInt($schoolManager->individual->cpf),
                '4' => mb_strtoupper($schoolManager->individual->person->name),
                '5' => $schoolManager->role_id,
                '6' => $schoolManager->individual->person->email,
            ];
        }

        return [];
    }

    public function getDataRecord90(
        int $year,
        int $schoolId
    ): array {
        $enrollments = $this->getEnrollments90ToExport($year, $schoolId);

        $enrollments = $enrollments->map(function ($enrollment) {
            $situation = $enrollment->registration->situation?->cod_situacao;

            $schoolClassId = $enrollment->schoolClass->getKey();
            $schoolClassInep = $enrollment->schoolClass?->inep?->number ?: null;

            if ($enrollment->schoolClass->turma_turno_id === Period::FULLTIME) {
                $hasPeriods = $this->service->hasStudentsPartials($enrollment->schoolClass->getKey());
                if ($hasPeriods) {
                    $turnoId = $enrollment->turno_id ?? $enrollment->schoolClass->turma_turno_id;

                    $schoolClassId .= '-' . $turnoId;
                    $schoolClassInep = $this->getSchoolClassInep($enrollment->schoolClass->getKey(), $turnoId);
                }
            }

            if ($situation && in_array($enrollment->registration->situation->cod_situacao, [
                App_Model_MatriculaSituacao::ABANDONO,
                App_Model_MatriculaSituacao::TRANSFERIDO
            ], true)) {
                $dataBaseEducacenso = config('educacenso.data_base.' . $enrollment->registration->ano);

                $otherRegistration = LegacyRegistration::query()
                    ->whereStudent($enrollment->registration->student->getKey())
                    ->active()
                    ->whereSchool($enrollment->registration->ref_ref_cod_escola)
                    ->whereCourse($enrollment->registration->ref_cod_curso)
                    ->whereGrade($enrollment->registration->ref_ref_cod_serie)
                    ->whereYearEq($enrollment->registration->ano)
                    ->where('data_matricula', '>', $dataBaseEducacenso)
                    ->where('data_matricula', '>', $enrollment->registration->data_matricula)
                    ->where('cod_matricula', '<>', $enrollment->registration->getKey())
                    ->first();

                if ($otherRegistration) {
                    $situation = $otherRegistration->situation->cod_situacao;
                    $this->ignoreRegistrationsRecord90[] = $otherRegistration->getKey();
                }
            }

            return [
                '1' => 90,
                '2' => $enrollment->schoolClass->school->inep->number,
                '3' => $schoolClassId,
                '4' => $schoolClassInep,
                '5' => $enrollment->registration->student?->inep->number ?: null,
                '6' => $enrollment->registration->student->getKey(),
                '7' => $enrollment->inep?->matricula_inep ?: null,
                '8' => $situation ? convertSituationIEducarToEducacenso($situation, $enrollment->schoolClass->etapa_educacenso) : null,
            ];
        });

        return $enrollments->toArray();
    }

    public function getDataRecord91(
        int $year,
        int $schoolId
    ): array {
        $enrollments = $this->getEnrollments91ToExport($year, $schoolId);

        $enrollments = $enrollments->map(function ($enrollment) {
            if (! in_array($enrollment->registration->getKey(), $this->ignoreRegistrationsRecord90, true)) {
                $schoolClassId = $enrollment->schoolClass->getKey();
                $schoolClassInep = $enrollment->schoolClass?->inep?->number ?: null;

                if ($enrollment->schoolClass->turma_turno_id === Period::FULLTIME) {
                    $hasPeriods = $this->service->hasStudentsPartials($enrollment->schoolClass->getKey());
                    if ($hasPeriods) {
                        $turnoId = $enrollment->turno_id ?? $enrollment->schoolClass->turma_turno_id;

                        $schoolClassId .= '-' . $turnoId;
                        $schoolClassInep = $this->getSchoolClassInep($enrollment->schoolClass->getKey(), $turnoId);
                    }
                }

                return [
                    '1' => 91,
                    '2' => $enrollment->schoolClass->school->inep->number,
                    '3' => $schoolClassId,
                    '4' => $schoolClassInep,
                    '5' => $enrollment->registration->student?->inep?->number ?: null,
                    '6' => $enrollment->registration->student->getKey(),
                    '7' => null,
                    '8' => null,
                    '9' => null,
                    '10' => null,
                    '11' => convertSituationIEducarToEducacenso($enrollment->registration->situation->cod_situacao, $enrollment->schoolClass->etapa_educacenso),
                ];
            }
        });

        return $enrollments->toArray();
    }

    public function getEnrollments90ToExport($year, $schoolId): mixed
    {
        $dataBaseEducacenso = config('educacenso.data_base.' . $year);

        $idsRemanejados = LegacyEnrollment::query()
            ->select([
                'id',
                'ref_cod_matricula',
                'ref_cod_turma',
            ])
            ->where('data_enturmacao', '<=', $dataBaseEducacenso)
            ->whereNotNull('data_exclusao')
            ->where('data_exclusao', '<=', $dataBaseEducacenso . ' 23:59:59')
            ->where('remanejado', true)
            ->where('ativo', 0)
            ->whereHas('registration', function ($q) use ($year): void {
                $q->where('ano', $year);
            })
            ->whereHas('schoolClass', function ($q) use ($schoolId): void {
                $q->where('ref_ref_cod_escola', $schoolId);
                $q->where('tipo_atendimento', TipoAtendimentoTurma::ESCOLARIZACAO);
                $q->active();
            })
            ->pluck('id');

        return LegacyEnrollment::query()
            ->select([
                'ref_cod_matricula',
                'ref_cod_turma',
                'data_enturmacao',
                'id',
                'sequencial',
                'turno_id',
            ])
            ->with([
                'registration:cod_matricula,ref_cod_aluno,ano,ref_ref_cod_escola,ref_ref_cod_serie,ref_cod_curso,data_matricula',
                'registration.student:cod_aluno,ref_idpes',
                'registration.student.person:idpes,nome',
                'registration.student.inep:cod_aluno,cod_aluno_inep',
                'registration.situation:cod_matricula,cod_situacao',
                'inep:matricula_turma_id,matricula_inep',
                'schoolClass' => function ($q) use ($schoolId): void {
                    $q->select([
                        'cod_turma',
                        'ref_ref_cod_escola',
                        'tipo_atendimento',
                        'etapa_educacenso',
                        'nm_turma',
                        'nao_informar_educacenso',
                        'turma_turno_id',
                    ]);
                    $q->where('ref_ref_cod_escola', $schoolId);
                    $q->where('nao_informar_educacenso', '!=', 1);
                    $q->where('tipo_atendimento', TipoAtendimentoTurma::ESCOLARIZACAO);
                },
                'schoolClass.school:cod_escola',
                'schoolClass.school.inep:cod_escola,cod_escola_inep',
                'schoolClass.inep:cod_turma,cod_turma_inep',
            ])
            ->where('data_enturmacao', '<=', $dataBaseEducacenso)
            ->whereNotIn('id', $idsRemanejados)
            ->whereHas('registration', function ($q) use ($year, $dataBaseEducacenso): void {
                $q->where('ano', $year);
                $q->where(function ($q) use ($dataBaseEducacenso): void {
                    $q->whereNull('data_cancel');
                    $q->orWhere('data_cancel', '>=', $dataBaseEducacenso);
                });
            })
            ->whereHas('schoolClass', function ($q) use ($schoolId): void {
                $q->where('ref_ref_cod_escola', $schoolId);
                $q->where('nao_informar_educacenso', '!=', 1);
                $q->where('tipo_atendimento', TipoAtendimentoTurma::ESCOLARIZACAO);
                $q->active();
            })
            ->whereValid()
            ->get();
    }

    public function getEnrollments91ToExport($year, $schoolId): mixed
    {
        $dataBaseEducacenso = config('educacenso.data_base.' . $year);

        return LegacyEnrollment::query()
            ->select([
                'ref_cod_matricula',
                'ref_cod_turma',
                'data_enturmacao',
                'id',
                'sequencial',
                'desconsiderar_educacenso',
                'turno_id',
            ])
            ->with([
                'registration' => function ($q): void {
                    $q->select([
                        'cod_matricula',
                        'ref_cod_aluno',
                        'ano'
                    ]);
                    $q->whereNotIn('cod_matricula', $this->ignoreRegistrationsRecord90);
                },
                'registration:cod_matricula,ref_cod_aluno,ano,ref_ref_cod_escola,ref_ref_cod_serie,ref_cod_curso,data_matricula',
                'registration.student:cod_aluno,ref_idpes',
                'registration.student.person:idpes,nome',
                'registration.student.inep:cod_aluno,cod_aluno_inep',
                'registration.situation:cod_matricula,cod_situacao',
                'inep:matricula_turma_id,matricula_inep',
                'schoolClass' => function ($q) use ($schoolId): void {
                    $q->select([
                        'cod_turma',
                        'ref_ref_cod_escola',
                        'tipo_atendimento',
                        'etapa_educacenso',
                        'nm_turma',
                        'nao_informar_educacenso',
                        'turma_turno_id',
                    ]);
                    $q->where('ref_ref_cod_escola', $schoolId);
                    $q->where('nao_informar_educacenso', '!=', 1);
                    $q->where('tipo_atendimento', TipoAtendimentoTurma::ESCOLARIZACAO);
                },
                'schoolClass.school:cod_escola',
                'schoolClass.school.inep:cod_escola,cod_escola_inep',
                'schoolClass.inep:cod_turma,cod_turma_inep',
            ])
            ->where('data_enturmacao', '>', $dataBaseEducacenso)
            ->where('desconsiderar_educacenso', false)
            ->whereNotIn('ref_cod_matricula', $this->ignoreRegistrationsRecord90)
            ->whereHas('registration', function ($q) use ($year, $dataBaseEducacenso): void {
                $q->where('ano', $year);
                $q->where('data_matricula', '>', $dataBaseEducacenso);
                $q->active();
            })
            ->whereHas('schoolClass', function ($q) use ($schoolId): void {
                $q->where('ref_ref_cod_escola', $schoolId);
                $q->where('nao_informar_educacenso', '!=', 1);
                $q->where('tipo_atendimento', TipoAtendimentoTurma::ESCOLARIZACAO);
                $q->active();
            })
            ->whereValid()
            ->get();
    }

    private function getSchoolClassInep($schoolClassId, $periodId): mixed
    {
        return Cache::remember('inep_' . $schoolClassId . '_' . $periodId, Carbon::now()->addMinutes(5), function () use ($schoolClassId, $periodId) {
            return SchoolClassInep::query()
                ->where('cod_turma', $schoolClassId)
                ->where('turma_turno_id', $periodId)
                ->value('cod_turma_inep');
        });
    }
}
