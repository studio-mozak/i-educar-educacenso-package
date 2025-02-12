<?php

namespace iEducar\Packages\Educacenso\Services\Version2019;

use App\Models\City;
use App\Models\Educacenso\RegistroEducacenso;
use App\Models\LegacyInstitution;
use App\Models\LegacyOrganization;
use App\Models\LegacyPerson;
use App\Models\LegacyPhone;
use App\Models\LegacySchool;
use App\Models\LegacySchoolAcademicYear;
use App\Models\LegacyStageType;
use App\Models\PersonHasPlace;
use App\Models\Place;
use App\Models\SchoolInep;
use App\User;
use DateTime;
use iEducar\Modules\Educacenso\Model\EsferaAdministrativa;
use iEducar\Modules\Educacenso\Model\MantenedoraDaEscolaPrivada;
use iEducar\Modules\Educacenso\Model\OrgaoVinculadoEscola;
use iEducar\Modules\Educacenso\Model\SituacaoFuncionamento;
use iEducar\Packages\Educacenso\Services\RegistroImportInterface;
use iEducar\Packages\Educacenso\Services\Version2019\Models\Registro00Model;

class Registro00Import implements RegistroImportInterface
{
    /**
     * @var Registro00
     */
    private $model;

    /**
     * @var User
     */
    private $user;

    /**
     * @var int
     */
    private $year;
    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */

    /**
     * @var LegacyInstitution
     */
    private $institution;

    /**
     * Faz a importação dos dados a partir da linha do arquivo
     *
     * @param int                $year
     * @return void
     */
    public function import(RegistroEducacenso $model, $year, $user): void
    {
        $this->user = $user;
        $this->model = $model;
        $this->year = $year;
        $this->institution = app(LegacyInstitution::class);
        $this->getOrCreateSchool();
    }

    /**
     * Retorna uma escola existente ou cria uma nova
     *
     * @return LegacySchool
     */
    protected function getOrCreateSchool()
    {
        $schoolInep = $this->getSchool();

        if ($schoolInep) {
            return $schoolInep->school;
        }

        $institution = LegacyInstitution::whereNull('orgao_regional')->first();
        if ($institution instanceof LegacyInstitution) {
            $institution->orgao_regional = $this->model->orgaoRegional;
            $institution->save();
        }

        $person = LegacyPerson::create([
            'nome' => $this->model->nome,
            'tipo' => 'J',
            'email' => $this->model->email,
            'data_cad' => now(),
            'situacao' => 'P',
            'origem_gravacao' => 'U',
            'operacao' => 'I',
        ]);

        $organization = LegacyOrganization::create([
            'idpes' => $person->idpes,
            'cnpj' => $this->model->cnpjEscolaPrivada ?: rand(1, 99) . rand(1, 999) . rand(1, 999) . rand(1, 9999) . rand(1, 99),
            'origem_gravacao' => 'M',
            'idpes_cad' => $this->user->id,
            'data_cad' => now(),
            'operacao' => 'I',
            'fantasia' => $this->model->nome,
        ]);

        $school = LegacySchool::create([
            'situacao_funcionamento' => $this->model->situacaoFuncionamento,
            'sigla' => mb_substr($this->model->nome, 0, 5, 'UTF-8'),
            'data_cadastro' => now(),
            'ativo' => 1,
            'ref_idpes' => $organization->getKey(),
            'ref_usuario_cad' => $this->user->id,
            'ref_cod_instituicao' => $this->institution->id,
            'zona_localizacao' => $this->model->zonaLocalizacao,
            'localizacao_diferenciada' => $this->model->localizacaoDiferenciada,
            'dependencia_administrativa' => $this->model->dependenciaAdministrativa,
            'orgao_vinculado_escola' => $this->getArrayOrgaoVinculado(),
            'mantenedora_escola_privada' => $this->getArrayMantenedora(),
            'categoria_escola_privada' => $this->model->categoriaEscolaPrivada ?: null,
            'conveniada_com_poder_publico' => $this->model->conveniadaPoderPublico ?: null,
            'cnpj_mantenedora_principal' => $this->model->cnpjMantenedoraPrincipal ?: null,
            'regulamentacao' => $this->model->regulamentacao ?: null,
            'esfera_administrativa' => $this->getEsferaAdministrativa(),
            'unidade_vinculada_outra_instituicao' => $this->model->unidadeVinculada ?: null,
            'inep_escola_sede' => $this->model->inepEscolaSede ?: null,
            'codigo_ies' => $this->model->codigoIes ?: null,
        ]);

        if ($this->model->situacaoFuncionamento == SituacaoFuncionamento::EM_ATIVIDADE) {
            $this->createAcademicYear($school);
        }

        $this->createAddress($school);
        $this->createSchoolInep($school);
        $this->createPhones($school);
    }

    protected function getSchool()
    {
        return SchoolInep::where('cod_escola_inep', $this->model->codigoInep)->first();
    }

    private function createAddress($school): void
    {
        $personAddress = PersonHasPlace::where('person_id', $school->ref_idpes)->exists();
        if ($personAddress) {
            return;
        }

        $city = City::where('ibge_code', $this->model->codigoIbgeMunicipio)->first();
        if (! $city) {
            return;
        }

        $place = Place::create([
            'city_id' => $city->getKey(),
            'address' => $this->model->logradouro,
            'number' => (int) (is_numeric($this->model->numero) ? $this->model->numero : null),
            'complement' => $this->model->complemento,
            'neighborhood' => $this->model->bairro,
            'postal_code' => $this->model->cep,
        ]);

        PersonHasPlace::firstOrCreate([
            'person_id' => $school->ref_idpes,
            'place_id' => $place->getKey(),
            'type' => 1,
        ]);
    }

    private function createSchoolInep($school): void
    {
        SchoolInep::create([
            'cod_escola' => $school->getKey(),
            'cod_escola_inep' => $this->model->codigoInep,
            'nome_inep' => '-',
            'fonte' => 'importador',
            'created_at' => now(),
        ]);
    }

    private function createPhones($school): void
    {
        if ($this->model->telefone) {
            LegacyPhone::create([
                'idpes' => $school->ref_idpes,
                'tipo' => 1,
                'ddd' => $this->model->ddd,
                'fone' => $this->model->telefone,
                'idpes_cad' => $this->user->id,
                'origem_gravacao' => 'M',
                'operacao' => 'I',
                'data_cad' => now(),
            ]);
        }

        if ($this->model->telefoneOutro) {
            LegacyPhone::create([
                'idpes' => $school->ref_idpes,
                'tipo' => 3,
                'ddd' => $this->model->ddd,
                'fone' => $this->model->telefoneOutro,
                'idpes_cad' => $this->user->id,
                'origem_gravacao' => 'M',
                'operacao' => 'I',
                'data_cad' => now(),
            ]);
        }
    }

    /**
     * @param LegacySchool $school
     */
    private function createAcademicYear($school): void
    {
        $schoolAcademicYear = LegacySchoolAcademicYear::create([
            'ref_cod_escola' => $school->getKey(),
            'ano' => $this->year,
            'ref_usuario_cad' => $this->user->id,
            'andamento' => 1,
            'ativo' => 1,
        ]);

        $stageType = LegacyStageType::first();

        if (! $stageType) {
            $stageType = LegacyStageType::create([
                'ref_usuario_cad' => $this->user->id,
                'nm_tipo' => 'Módulo Importação',
                'data_cadastro' => now(),
                'ref_cod_instituicao' => $school->institution->getKey(),
                'num_etapas' => 1,
            ]);
        }

        $schoolAcademicYear->academicYearStages()->create([
            'ref_ano' => $this->year,
            'ref_ref_cod_escola' => $school->getKey(),
            'sequencial' => 1,
            'ref_cod_modulo' => $stageType->getKey(),
            'data_inicio' => DateTime::createFromFormat('d/m/Y', $this->model->inicioAnoLetivo),
            'data_fim' => DateTime::createFromFormat('d/m/Y', $this->model->fimAnoLetivo),
            'dias_letivos' => 200,
        ]);
    }

    private function getArrayOrgaoVinculado()
    {
        $arrayOrgaoVinculado = [];

        if ($this->model->orgaoEducacao) {
            $arrayOrgaoVinculado[] = OrgaoVinculadoEscola::EDUCACAO;
        }

        if ($this->model->orgaoSeguranca) {
            $arrayOrgaoVinculado[] = OrgaoVinculadoEscola::SEGURANCA;
        }

        if ($this->model->orgaoSaude) {
            $arrayOrgaoVinculado[] = OrgaoVinculadoEscola::SAUDE;
        }

        if ($this->model->orgaoOutro) {
            $arrayOrgaoVinculado[] = OrgaoVinculadoEscola::OUTRO;
        }

        return '{' . implode(',', $arrayOrgaoVinculado) . '}';
    }

    private function getArrayMantenedora()
    {
        $arrayMantenedora = [];

        if ($this->model->mantenedoraEmpresa) {
            $arrayMantenedora[] = MantenedoraDaEscolaPrivada::GRUPOS_EMPRESARIAIS;
        }

        if ($this->model->mantenedoraSindicato) {
            $arrayMantenedora[] = MantenedoraDaEscolaPrivada::SINDICATOS_TRABALHISTAS;
        }

        if ($this->model->mantenedoraOng) {
            $arrayMantenedora[] = MantenedoraDaEscolaPrivada::ORGANIZACOES_NAO_GOVERNAMENTAIS;
        }

        if ($this->model->mantenedoraInstituicoes) {
            $arrayMantenedora[] = MantenedoraDaEscolaPrivada::INSTITUICOES_SIM_FINS_LUCRATIVOS;
        }

        if ($this->model->mantenedoraSistemaS) {
            $arrayMantenedora[] = MantenedoraDaEscolaPrivada::SISTEMA_S;
        }

        if ($this->model->mantenedoraOscip) {
            $arrayMantenedora[] = MantenedoraDaEscolaPrivada::OSCIP;
        }

        return '{' . implode(',', $arrayMantenedora) . '}';
    }

    private function getEsferaAdministrativa()
    {
        if ($this->model->esferaFederal) {
            return EsferaAdministrativa::FEDERAL;
        }

        if ($this->model->esferaEstadual) {
            return EsferaAdministrativa::ESTADUAL;
        }

        if ($this->model->esferaMunicipal) {
            return EsferaAdministrativa::MUNICIPAL;
        }

        return;
    }

    public static function getModel($arrayColumns)
    {
        $registro = new Registro00Model();
        $registro->hydrateModel($arrayColumns);

        return $registro;
    }
}
