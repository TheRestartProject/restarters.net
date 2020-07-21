<div class="col-12 mt-20 mb-30">
  <div class="d-flex align-items-center justify-content-between">
    <h2 class="mb-0">
      @lang('devices.global_impact')
    </h2>

    @if (isset($most_recent_finished_event) && ! empty($most_recent_finished_event))
      <div class="d-none d-md-block">
        <div class="call_to_action">
          <div class="mr-10">
            @include('svgs.fixometer.clap_doodle')
          </div>
          <a style="color:white; text-decoration:underline;" href="{{ route('group.show', ['id' => $most_recent_finished_event->theGroup->idgroups]) }}">{{ $most_recent_finished_event->theGroup->name }}</a>&nbsp;@lang('devices.group_prevented', ['amount' => number_format($most_recent_finished_event->WastePrevented, 0)])
        </div>
      </div>
    @endif
  </div>
</div>

<div class="col-12">
    <div class="row">
        <div class="col">
            <p><strong>@lang('devices.huge_impact')</strong> @lang('devices.impact_read_more')</p>
        </div>
    </div>
</div>

<div class="col-12">
  <div class="row">
    <div class="col d-none d-md-block mb-30 mb-lg-0">
      <div class="card card-summary">
        <div class="card-body">
          <div class="svg-wrapper">
            @include('svgs.fixometer.smile_doodle')
          </div>

          <h3>{{ $impact_data->participants }}</h3>
          <p>@lang('devices.participants')</p>
        </div>
      </div>
    </div>

    <div class="col-6 col-md mb-30 mb-lg-0">
      <div class="card card-summary">
        <div class="card-body">
          <div class="svg-wrapper">
            @include('svgs.fixometer.clock_doodle')
          </div>
          <h3>{{ $impact_data->hours_volunteered }}</h3>
          <p>@lang('devices.hours_volunteered')</p>
        </div>
      </div>
    </div>

    <div class="col-6 col-md mb-30 mb-lg-0">
      <div class="card card-summary">
        <div class="card-body">
          <div class="svg-wrapper">
            @include('svgs.fixometer.phone_doodle')
          </div>
          <h3>{{ $impact_data->items_fixed }}</h3>
          <p>@lang('devices.items_repaired')</p>
        </div>
      </div>
    </div>

    <div class="col mb-30 mb-lg-0">
      <div class="card card-summary">
        <div class="card-body">
          <div class="svg-wrapper">
            @include('svgs.fixometer.trash_doodle')
          </div>

          <h3>{{ number_format($impact_data->weights, 0) }} kg</h3>
          <p>@lang('devices.waste_prevented')</p>
        </div>
      </div>
    </div>

    <div class="col mb-30 mb-md-0">
      <div class="card card-summary">
        <div class="card-body">
          <div class="svg-wrapper">
            @include('svgs.fixometer.cloud_doodle')
          </div>

          <h3>{{ number_format($impact_data->emissions, 0) }} kg</h3>
          <p>@lang('devices.co2_prevented')</p>
        </div>
      </div>
    </div>
  </div>
</div>
