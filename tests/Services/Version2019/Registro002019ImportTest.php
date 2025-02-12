<?php

namespace iEducar\Packages\Educacenso\Tests\Services\Version2019;

use App\Models\Educacenso\Registro00;
use App\Models\LegacySchool;
use App\Models\LegacySchoolAcademicYear;
use App\Models\SchoolInep;
use App\User;
use Database\Factories\LegacySchoolFactory;
use Database\Factories\LegacyUserFactory;
use Database\Factories\SchoolInepFactory;
use Faker\Factory;
use iEducar\Modules\Educacenso\Model\SituacaoFuncionamento;
use iEducar\Packages\Educacenso\Services\Version2019\Registro00Import;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class Registro002019ImportTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var User
     */
    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = LegacyUserFactory::new()->make();
    }

    /**
     * A importação não deve duplicar uma escola já existente
     */
    public function testExistingSchoolShouldNotDuplicate(): void
    {
        $model = $this->getImportModel();

        $school = LegacySchoolFactory::new()->create();
        $inep = SchoolInepFactory::new()->create([
            'cod_escola' => $school,
            'cod_escola_inep' => $model->codigoInep,
        ]);

        $service = new Registro00Import();
        $service->import($model, now()->year, $this->user);

        $schoolInepCount = SchoolInep::where('cod_escola_inep', $inep->cod_escola_inep)->count();

        $this->assertEquals(1, $schoolInepCount);
    }

    /**
     * Testa a criação da escola
     */
    public function testCreateSchool(): void
    {
        $model = $this->getImportModel();

        $service = new Registro00Import();
        $service->import($model, now()->year, $this->user);

        /** @var SchoolInep $schoolInep */
        $schoolInep = SchoolInep::where('cod_escola_inep', $model->codigoInep)->first();

        $this->assertInstanceOf(SchoolInep::class, $schoolInep);

        /** @var LegacySchool $school */
        $school = $schoolInep->school;

        $this->assertEquals($model->nome, $school->name);
    }

    /**
     * Testa a criação dos telefones da escola
     */
    public function testCreateSchoolPhone(): void
    {
        $faker = Factory::create();

        $model = $this->getImportModel();
        $model->ddd = $faker->numerify('##');
        $model->telefone = $faker->numerify('########');

        $service = new Registro00Import();
        $service->import($model, now()->year, $this->user);

        /** @var SchoolInep $schoolInep */
        $schoolInep = SchoolInep::where('cod_escola_inep', $model->codigoInep)->first();
        /** @var LegacySchool $school */
        $school = $schoolInep->school;
        $phones = $school->person->phones;

        $this->assertCount(1, $phones);
        $this->assertEquals((int) $model->telefone, (int) $phones->first()->fone);
    }

    /**
     * Para escola com situação de funcionamento diferente de EM FUNCIONAMENTO
     * não deve ser criado ano letivo
     */
    public function testClosedShoolShouldNotCreateAcademicYear(): void
    {
        $model = $this->getImportModel();
        $model->situacaoFuncionamento = SituacaoFuncionamento::EXTINTA;

        $service = new Registro00Import();
        $service->import($model, now()->year, $this->user);

        /** @var SchoolInep $schoolInep */
        $schoolInep = SchoolInep::where('cod_escola_inep', $model->codigoInep)->first();

        $this->assertFalse(LegacySchoolAcademicYear::where('ref_cod_escola', $schoolInep->cod_escola)->exists());
    }

    /**
     * Deve ser criado ano letivo para escolas em atividade
     */
    public function testCreateAcademicYear(): void
    {
        $faker = Factory::create();

        $model = $this->getImportModel();
        $model->situacaoFuncionamento = SituacaoFuncionamento::EM_ATIVIDADE;
        $model->inicioAnoLetivo = $faker->date('d/m/Y');
        $model->fimAnoLetivo = $faker->date('d/m/Y');

        $service = new Registro00Import();
        $service->import($model, now()->year, $this->user);

        /** @var SchoolInep $schoolInep */
        $schoolInep = SchoolInep::where('cod_escola_inep', $model->codigoInep)->first();

        $this->assertTrue(LegacySchoolAcademicYear::where('ref_cod_escola', $schoolInep->cod_escola)->exists());
    }

    /**
     * Retorna uma instância do model da importação com dados fake
     *
     * @return Registro00
     */
    private function getImportModel()
    {
        $faker = Factory::create();

        $model = new Registro00();

        $model->codigoInep = $faker->numerify('########');
        $model->nome = $faker->name();
        $model->codigoIbgeMunicipio = $faker->numerify('########');
        $model->codigoIbgeDistrito = $faker->numerify('########');
        $model->bairro = $faker->name();
        $model->numero = $faker->randomNumber(2);

        return $model;
    }
}
