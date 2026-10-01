@extends('layouts.app')

@section('title')
    {{ $network->name }}
@endsection

@section('content')
<section class="events networks">
    <div class="container-fluid">
      <div class="events__header row align-content-top">
          <div class="col d-flex flex-column">

            <header>
                <div class="row">
                    <div class="col col-md-9">
                        <h1>Editing {{{ $network->name }}}</h1>
                    </div>
                </div>
            </header>

          </div>
      </div>
      <div class="row">
          <div class="col">
            <form action="/networks/{{ $network->id }}" method="post" enctype="multipart/form-data">
                @method('PUT')
                @csrf
                    <div class="form-group row">
                        <div class="col">
                            <label for="network_logo">@lang('networks.edit.label_logo'):</label><br/>
                            <input id="network_logo" name="network_logo" type="file" />
                        </div>
                    </div>

                    @if ($canEditReportingUrl)
                    <div class="form-group row">
                        <div class="col">
                            <label for="reporting_url">@lang('networks.edit.label_reporting_url'):</label>
                            <input id="reporting_url" name="reporting_url" type="url" class="form-control @error('reporting_url') is-invalid @enderror" value="{{ old('reporting_url', $network->reporting_url) }}" />
                            @error('reporting_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">@lang('networks.edit.help_reporting_url')</small>
                        </div>
                    </div>
                    @endif

                    <div class="button-group row row-compressed-xs">
                        <div class="col-lg-12 d-flex align-items-center justify-content-end">
                            <button type="submit" class="btn btn-primary btn-create">@lang('networks.edit.button_save')</button>
                        </div>
                    </div>
            </form>
          </div>
      </div>
    </div>
</section>

@endsection
