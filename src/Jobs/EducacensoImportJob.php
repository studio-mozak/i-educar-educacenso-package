<?php

namespace iEducar\Packages\Educacenso\Jobs;

use App\Models\EducacensoImport as EducacensoImportModel;
use DateTime;
use iEducar\Packages\Educacenso\Services\ImportServiceFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class EducacensoImportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var EducacensoImportModel
     */
    private $educacensoImport;

    /**
     * @var array
     */
    private $importArray;

    /**
     * @var string
     */
    private $databaseConnection;

    /**
     * @var DateTime
     */
    private $registrationDate;

    /**
     * Tempo máximo de execução do job
     */
    public $timeout = 3600;

    /**
     * Tempo que o job permanece único na fila
     */
    public $uniqueFor = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(EducacensoImportModel $educacensoImport, $importArray, $databaseConnection, $registrationDate)
    {
        $this->educacensoImport = $educacensoImport;
        $this->importArray = $importArray;
        $this->databaseConnection = $databaseConnection;
        $this->registrationDate = $registrationDate;
    }

    /**
     * Define o identificador único do job
     */
    public function uniqueId()
    {
        return 'educacenso-import-' . $this->educacensoImport->id;
    }

    /**
     * Middleware para evitar execução paralela
     */
    public function middleware()
    {
        return [
            (new WithoutOverlapping($this->educacensoImport->id))
                ->expireAfter(3600)
        ];
    }

    /**
     * Execute the job.
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        ini_set('memory_limit', '1G');

        DB::setDefaultConnection($this->databaseConnection);
        DB::beginTransaction();

        try {
            $importService = ImportServiceFactory::createImportService(
                $this->educacensoImport->year,
                $this->registrationDate
            );

            $importService->import(
                $this->importArray,
                $this->educacensoImport->user
            );

            $importService->adaptData();

        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $educacensoImport = $this->educacensoImport;
        $educacensoImport->finished = true;
        $educacensoImport->save();

        DB::commit();
    }

    /**
     * Tags para o Horizon
     */
    public function tags()
    {
        return [
            $this->databaseConnection,
            'educacenso-import',
        ];
    }
}
