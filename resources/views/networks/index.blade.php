@extends('layouts.app')

@section('title')
    @lang('networks.networks')
@endsection

@section('content')

<section class="networks">
<div class="container">

<div class="row">
<div class="col">

<h1>@lang('networks.general.networks')</h1>

<section class="table-section" id="user-groups">
    <h2>@lang('networks.index.your_networks')</h2>
    <p>
        @lang('networks.index.your_networks_explainer')
    </p>
    <div class="table-responsive">
        <table role="table" class="table table-striped table-hover table-layout-fixed">
            <thead>
                <tr><th>@lang('networks.general.network')</th></tr>
            </thead>
            <tbody>
                @if( !$yourNetworks->isEmpty() )
                    @foreach($yourNetworks as $network)
                        <tr>
                            <td>
                                <a href="/networks/{{$network->id}}">{{ $network->name }}</a>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="13" align="center" class="p-3">
                            @lang('networks.index.your_networks_no_networks')
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</section>

@if ($showAllNetworks)
<section class="table-section" id="user-groups">
    <h2>@lang('networks.index.all_networks')</h2>
    <p>
        @lang('networks.index.all_networks_explainer')
    </p>
    <div class="table-responsive">
        <table role="table" class="table table-striped table-hover table-layout-fixed">
            <thead>
                <tr><th>@lang('networks.network')</th></tr>
            </thead>
            <tbody>
                @if( !$allNetworks->isEmpty() )
                    @foreach($allNetworks as $network)
                        <tr>
                            <td>
                                <a href="/networks/{{$network->id}}">{{ $network->name }}</a>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="13" align="center" class="p-3">
                            @lang('networks.index.all_networks_no_networks')
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</section>
@endif

</div>
</div>
</div>
</section>

@endsection
