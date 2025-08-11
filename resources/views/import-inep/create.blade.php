@extends('layout.default')

@section('content')
    <form id="formcadastro" action="{{ route('educacenso.import.inep.store') }}" method="post" enctype="multipart/form-data">
        <h1 class="title_ensinus"><strong>Nova importação</strong></h1>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0">
                        <tbody>
                        <tr id="tr_nm_ano">
                            <td class="formmdtd" valign="top">
                                <span class="form">Ano</span>
                                <span class="campo_obrigatorio">*</span>
                                <br>
                                <sub style="vertical-align:top;">somente números</sub>
                            </td>
                            <td class="formmdtd" valign="top">
                                <span class="form">
                                    <select name="ano" id="ano" required class="formcampo">
                                        @foreach($years as $year)
                                            <option value="{{ $year }}">{{ $year }}</option>
                                        @endforeach
                                    </select>
                                </span>
                            </td>
                        </tr>
                        <tr id="tr_nm_arquivo">
                            <td class="formmdtd" valign="top" style="padding-bottom: 30px">
                                <span class="form">Arquivos</span>
                                <span class="campo_obrigatorio">*</span>
                            </td>
                            <td class="formmdtd" valign="top" style="padding-top: 20px;padding-bottom: 20px">
                            <span class="form">
                                <input data-multiple-caption="{count} arquivos" class="inputfile inputfile-buttom" name="arquivos[]" id="arquivos" type="file" accept=".txt" multiple required>
                                <!-- <label for="arquivos"><span></span> <strong>Escolha um arquivo</strong></label>&nbsp;<br> -->
                                 <br>
                                <span style="font-style: italic; font-size: 10px;">* Somente arquivos com formato txt serão aceitos</span>
                            </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <div style="text-align: center">
                        <button id="importButton" class="btn-green" type="submit">Importar Ineps</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@prepend('scripts')
    <script>
        $j(document).ready(function () {
            $j('#formcadastro').submit(function (e) {
                $j('#importButton').prop('disabled', true);
            });
        });
    </script>
@endprepend

@prepend('styles')
    <link rel="stylesheet" type="text/css" href="{{ Asset::get('css/ieducar.css') }}"/>
    <style>
        .tablecadastro {
            margin-bottom: 0px;
        }
    </style>
@endprepend
