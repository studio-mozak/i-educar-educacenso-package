<?php

namespace iEducar\Packages\Educacenso\Services;

use App\Models\EducacensoImport;
use App\User;
use iEducar\Packages\Educacenso\Jobs\EducacensoImportJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use iEducar\Packages\Educacenso\Exception\InvalidFileDate;
use iEducar\Packages\Educacenso\Exception\InvalidFileYear;

class HandleFileService
{
    /**
     * @var EducacensoImportJob[]
     */
    private $jobs;

    public function __construct(
        private ImportService $yearImportService,
        private User $user
    ) {
    }

    /**
     * Processa o arquivo de importação do censo
     */
    public function handleFile(UploadedFile $file): void
    {
        $splitFileService = new SplitFileService($file);
        $schools = $splitFileService->getSplitedSchools();

        $this->validateFile($schools->current());

        foreach ($schools as $school) {
            $this->createImportProcess($school);
        }

        $this->dispatchJobs();
    }

    /**
     * Cria o processo de importação de uma escola
     *
     * @param  $year
     */
    public function createImportProcess($school): void
    {
        $import = new EducacensoImport();
        $import->year = $this->yearImportService->getYear();
        $import->school = mb_convert_encoding($this->yearImportService->getSchoolNameByFile($school), 'UTF-8');
        $import->user_id = $this->user->id;
        $import->registration_date = $this->yearImportService->registrationDate;
        $import->finished = false;
        $import->save();

        array_walk_recursive($school, static fn (&$item) => $item = mb_convert_encoding($item, 'HTML-ENTITIES', 'UTF-8'));

        $this->jobs[] = new EducacensoImportJob($import, $school, DB::getDefaultConnection(), $this->yearImportService->registrationDate);
    }

    private function dispatchJobs(): void
    {
        $firstJob = $this->jobs[0];
        unset($this->jobs[0]);

        $firstJob->chain($this->jobs);

        app(Dispatcher::class)->dispatch($firstJob);
    }

    private function validateFile($school): void
    {
        $serviceYear = $this->yearImportService->getYear();
        $line = explode($this->yearImportService::DELIMITER, $school[0]);

        if (is_bool($line[3])) {
            throw new InvalidFileDate();
        }

        $fileYear = \DateTime::createFromFormat('d/m/Y', $line[3])->format('Y');

        if ($serviceYear != $fileYear) {
            throw new InvalidFileYear($fileYear, $serviceYear);
        }
    }
}
