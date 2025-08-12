@extends('layout.default')

@push('styles')
    <link rel="stylesheet" type="text/css" href="{{ Asset::get('css/ieducar.css') }}"/>
    <link id="pagestyle" href="{{ asset('assets/css/argon-dashboard.css?v=2.1.0') }}" rel="stylesheet" />
     <link rel="stylesheet" type="text/css" href="{{ Asset::get('css/style_ensinus.css') }}" />
@endpush

@section('content')
    <h1 class="title_ensinus">Importações - <strong>Listagem</strong></h1>
    <div class="card" style="padding: 22px; margin-top: 10px;">
        <div class="card-body px-0 pt-0 pb-2">
            <div class="table-responsive p-0">
                <table class="table align-items-center mb-0"
                style="background-color: #fff;">
                    <tr class="text-uppercase text-theader text-xxs font-weight-bolder opacity-7">
                        <td style="font-weight:bold;">Ano</td>
                        <td style="font-weight:bold;">Escola</td>
                        <td style="font-weight:bold;">Usuário</td>
                        <td style="font-weight:bold;">Data</td>
                        <td style="font-weight:bold;">Situação</td>
                    </tr>
                    @forelse($imports as $import)
                        <tr>
                            <td>
                                <a href="">{{ $import->year }}</a>
                            </td>
                            <td>
                                {{ $import->school_name }}
                            </td>
                            <td>
                                {{ $import->user->realName }}
                            </td>
                            <td>
                                {{ $import->created_at->format('d/m/y H:i') }}
                            </td>
                            <td>
                                {{ $import->status }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" align=center>Não há informação para ser apresentada</td>
                        </tr>
                    @endforelse
                </table>
            </div>
            <div style="text-align: center">
                {{ $imports->links() }}
            </div>

            <div style="text-align: center; margin-top: 30px; margin-bottom: 30px">
                <a href="{{ route('educacenso.import.inep.create') }}" class="new-button">Nova Importação</a>
            </div>
        </div>
    </div>
@endsection
