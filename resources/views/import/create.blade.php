@extends('layout.default')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ Asset::get('css/ieducar.css') }}" />
    <link id="pagestyle" href="{{ asset('assets/css/argon-dashboard.css?v=2.1.0') }}" rel="stylesheet" />
     <link rel="stylesheet" type="text/css" href="{{ Asset::get('css/style_ensinus.css') }}" />
@endpush

@section('content')
    <form id="formcadastro"
            action="{{ route('educacenso-import-registrations.store') }}"
            method="post"
            enctype="multipart/form-data"
    >
        <h1 class="title_ensinus"><strong>Importações</strong></h1>
        <div class="card" style="padding: 22px; margin-top: 10px;">
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive p-0">
                    <table class="tablecadastro" width="100%" border="0" cellpadding="2" cellspacing="0">
                        <tbody>
                            <tr>
                                <td class="formlttd" valign="top">
                                    <span class="form">
                                        Ano
                                    </span>
                                    <span class="campo_obrigatorio">*</span>
                                </td>
                                <td class="formlttd" valign="top">
                                    <span class="form">
                                        @include('form.select-year', ['required' => 'required'])
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="formmdtd" valign="top">
                                    <span class="form">
                                        Data de entrada das matrículas
                                    </span>
                                    <span class="campo_obrigatorio">*</span>
                                    <br/>
                                    <sub style="vertical-align: top;">
                                        dd/mm/aaaa
                                    </sub>
                                </td>
                                <td class="formmdtd" valign="top">
                                    <span class="form">
                                        @include('layout.input.date')
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="formlttd" valign="top">
                                    <span class="form">
                                        Arquivo
                                    </span>
                                    <span class="campo_obrigatorio">*</span>
                                </td>
                                <td class="formlttd" valign="top">
                                    <span class="form">
                                        @include('layout.input.file')
                                    </span>
                                    <br/>
                                    <span style="font-style: italic; font-size: 12px;">
                                        * Somente arquivos com formato txt serão aceitos
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>


                    <div style="text-align: center; margin-bottom: 10px">
                        <button id="export-button" class="new-button" type="submit">Importar</button>
                    </div>
                </div>
            </div>
        </div>

        <style>
            #export-button[disabled] {
                opacity: 0.7;
            }

            .flex {
                display: flex;
            }

            .gap-4 {
                gap: 1rem;
            }

            .justify-between {
                justify-content: space-between;
            }

            .items-center {
                align-items: center;
            }

            .border-l-8 {
                border-left-width: 8px !important;
            }

            .border-solid {
                border-style: solid !important;
            }

            .py-2 {
                padding-top: 0.5rem;
                padding-bottom: 0.5rem;
            }

            .px-4 {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .border-warning {
                border: 0;
                border-left-color: #dfc32e;
            }

            .bg-warning {
                background-color: #fff8d6;
            }

            .flex-grow {
                flex-grow: 1;
            }

            .x-alert-icon {
                color: #dfc32e;
            }

            .x-alert-icon::before {
                font-size: 1.5rem;
            }

            .text-warning {
                color: #958f73;
            }

            .tablecadastro {
                margin-bottom: 20px;
            }
        </style>
        <link type='text/css' rel='stylesheet' href='{{ Asset::get("/vendor/legacy/Portabilis/Assets/Plugins/Chosen/chosen.css") }}'>
        <script type='text/javascript' src='{{ Asset::get('/vendor/legacy/Portabilis/Assets/Plugins/Chosen/chosen.jquery.min.js') }}'></script>
        <script type="text/javascript" src="{{ Asset::get("/vendor/legacy/Portabilis/Assets/Javascripts/ClientApi.js") }}"></script>
        <script type="text/javascript" src="{{ Asset::get("/vendor/legacy/DynamicInput/Assets/Javascripts/DynamicInput.js") }}"></script>
        <script type="text/javascript" src="{{ Asset::get("/vendor/legacy/DynamicInput/Assets/Javascripts/Escola.js") }}"></script>
    </form>
@endsection