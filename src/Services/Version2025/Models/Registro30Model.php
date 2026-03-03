<?php

namespace iEducar\Packages\Educacenso\Services\Version2025\Models;

use App\Models\Educacenso\Registro30;

class Registro30Model extends Registro30
{
    public function hydrateModel($arrayColumns): void
    {
        array_unshift($arrayColumns, null);
        unset($arrayColumns[0]);

        $this->inepEscola = $arrayColumns[2];
        $this->codigoPessoa = $arrayColumns[3];
        $this->inepPessoa = $arrayColumns[4];
        $this->cpf = $arrayColumns[5];
        $this->nomePessoa = $arrayColumns[6];
        $this->dataNascimento = $arrayColumns[7];
        $this->filiacao = $arrayColumns[8];
        $this->filiacao1 = $arrayColumns[9];
        $this->filiacao2 = $arrayColumns[10];
        $this->sexo = $arrayColumns[11];
        $this->raca = $arrayColumns[12];
        $this->povoIndigena = $arrayColumns[13];
        $this->nacionalidade = $arrayColumns[14];
        $this->paisNacionalidade = $arrayColumns[15];
        $this->municipioNascimento = $arrayColumns[16];
        $this->deficiencia = $arrayColumns[17];
        $this->deficienciaCegueira = $arrayColumns[18];
        $this->deficienciaBaixaVisao = $arrayColumns[19];
        $this->deficienciaVisaoMonocular = $arrayColumns[20];
        $this->deficienciaSurdez = $arrayColumns[21];
        $this->deficienciaAuditiva = $arrayColumns[22];
        $this->deficienciaSurdoCegueira = $arrayColumns[23];
        $this->deficienciaFisica = $arrayColumns[24];
        $this->deficienciaIntelectual = $arrayColumns[25];
        $this->deficienciaMultipla = $arrayColumns[26];
        $this->deficienciaAutismo = $arrayColumns[27];
        $this->deficienciaAltasHabilidades = $arrayColumns[28];
        $this->transtorno = $arrayColumns[29];
        $this->transtornoDiscalculia = $arrayColumns[30];
        $this->transtornoDisgrafia = $arrayColumns[31];
        $this->transtornoDislalia = $arrayColumns[32];
        $this->transtornoDislexia = $arrayColumns[33];
        $this->transtornoTdah = $arrayColumns[34];
        $this->transtornoTpac = $arrayColumns[35];
        $this->recursoLedor = $arrayColumns[36];
        $this->recursoTranscricao = $arrayColumns[37];
        $this->recursoGuia = $arrayColumns[38];
        $this->recursoTradutor = $arrayColumns[39];
        $this->recursoLeituraLabial = $arrayColumns[40];
        $this->recursoProvaAmpliada = $arrayColumns[41];
        $this->recursoProvaSuperampliada = $arrayColumns[42];
        $this->recursoAudio = $arrayColumns[43];
        $this->recursoLinguaPortuguesaSegundaLingua = $arrayColumns[44];
        $this->recursoVideoLibras = $arrayColumns[45];
        $this->recursoBraile = $arrayColumns[46];
        $this->provaBraile = $arrayColumns[47];
        $this->recursoTempoAdicional = $arrayColumns[48];
        $this->recursoNenhum = $arrayColumns[49];
        $this->certidaoNascimento = $arrayColumns[50];
        $this->paisResidencia = $arrayColumns[51];
        $this->cep = $arrayColumns[52];
        $this->municipioResidencia = $arrayColumns[53];
        $this->localizacaoResidencia = $arrayColumns[54];
        $this->localizacaoDiferenciada = $arrayColumns[55];
        $this->justificativaFaltaDocumentacao = $arrayColumns[56];
        $this->escolaridade = $arrayColumns[57];
        $this->tipoEnsinoMedioCursado = $arrayColumns[58];
        $this->formacaoCurso = [
            $arrayColumns[60],
            $arrayColumns[63],
            $arrayColumns[66],
        ];
        $this->formacaoAnoConclusao = [
            $arrayColumns[61],
            $arrayColumns[64],
            $arrayColumns[67],
        ];
        $this->formacaoInstituicao = [
            $arrayColumns[62],
            $arrayColumns[65],
            $arrayColumns[68],
        ];
        $this->complementacaoPedagogica = array_filter([
            $arrayColumns[68],
            $arrayColumns[69],
            $arrayColumns[70],
        ]);

        $this->posGraduacoes = [];
        if (! empty($arrayColumns[71])) {
            $this->posGraduacoes[] = [
                'tipo' => $arrayColumns[71],
                'area' => $arrayColumns[72],
                'ano_conclusao' => $arrayColumns[73],
            ];
        }

        if (! empty($arrayColumns[74])) {
            $this->posGraduacoes[] = [
                'tipo' => $arrayColumns[74],
                'area' => $arrayColumns[75],
                'ano_conclusao' => $arrayColumns[76],
            ];
        }

        if (! empty($arrayColumns[77])) {
            $this->posGraduacoes[] = [
                'tipo' => $arrayColumns[77],
                'area' => $arrayColumns[78],
                'ano_conclusao' => $arrayColumns[79],
            ];
        }

        if (! empty($arrayColumns[80])) {
            $this->posGraduacoes[] = [
                'tipo' => $arrayColumns[80],
                'area' => $arrayColumns[81],
                'ano_conclusao' => $arrayColumns[82],
            ];
        }

        if (! empty($arrayColumns[83])) {
            $this->posGraduacoes[] = [
                'tipo' => $arrayColumns[83],
                'area' => $arrayColumns[84],
                'ano_conclusao' => $arrayColumns[85],
            ];
        }

        if (! empty($arrayColumns[86])) {
            $this->posGraduacoes[] = [
                'tipo' => $arrayColumns[86],
                'area' => $arrayColumns[87],
                'ano_conclusao' => $arrayColumns[88],
            ];
        }

        $this->posGraduacaoNaoPossui = $arrayColumns[89];
        $this->formacaoContinuadaCreche = $arrayColumns[90];
        $this->formacaoContinuadaPreEscola = $arrayColumns[91];
        $this->formacaoContinuadaAnosIniciaisFundamental = $arrayColumns[92];
        $this->formacaoContinuadaAnosFinaisFundamental = $arrayColumns[93];
        $this->formacaoContinuadaEnsinoMedio = $arrayColumns[94];
        $this->formacaoContinuadaEducacaoJovensAdultos = $arrayColumns[95];
        $this->formacaoContinuadaEducacaoEspecial = $arrayColumns[96];
        $this->formacaoContinuadaEducacaoIndigena = $arrayColumns[97];
        $this->formacaoContinuadaEducacaoCampo = $arrayColumns[98];
        $this->formacaoContinuadaEducacaoAmbiental = $arrayColumns[99];
        $this->formacaoContinuadaEducacaoDireitosHumanos = $arrayColumns[100];
        $this->formacaoContinuadaEducacaoBilingueSurdos = $arrayColumns[101];
        $this->formacaoContinuadaEducacaoTecnologiaInformacaoComunicacao = $arrayColumns[102];
        $this->formacaoContinuadaGeneroDiversidadeSexual = $arrayColumns[103];
        $this->formacaoContinuadaDireitosCriancaAdolescente = $arrayColumns[104];
        $this->formacaoContinuadaEducacaoRelacoesEticoRaciais = $arrayColumns[105];
        $this->formacaoContinuadaEducacaoGestaoEscolar = $arrayColumns[106];
        $this->formacaoContinuadaEducacaoOutros = $arrayColumns[107];
        $this->formacaoContinuadaEducacaoNenhum = $arrayColumns[108];
        $this->email = $arrayColumns[109] ?? null;

        if ($this->escolaridade) {
            $this->tipos[self::TIPO_TEACHER] = true;
            $this->tipos[self::TIPO_MANAGER] = true;
        } else {
            $this->tipos[self::TIPO_STUDENT] = true;
        }
    }
}
