@if( $event->hasFinished() )
<!-- Modal -->
<div class="modal fade" id="event-share-stats" tabindex="-1" role="dialog" aria-labelledby="eventShareStatsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="eventShareStatsLabel">@lang('events.share_stats_header')</h5>
        @include('partials.cross')

      </div>

      <div class="modal-body">

        <p>@lang('events.share_stats_message', ['date' => $event->getFormattedLocalStart(), 'event_name' => $formdata->venue, 'number_devices' => count($formdata->devices)])</p>

        <div id="accordionEvent" class="accordion__share mt-4">

          <div class="card">
            <div class="card-header p-0" id="headingEventHeadline">
              <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseEventHeadline" aria-expanded="false" aria-controls="collapseEventHeadline">
                @lang('events.headline_stats_dropdown')
                @include('partials.caret')
              </button>
            </div>
            <div id="collapseEventHeadline" class="collapse" aria-labelledby="headingEventHeadline" data-parent="#accordionEvent">
              <div class="card-body">

                  <div class="form-group">
                      <label for="event_headline_stats_embed">@lang('events.embed_code_header'):</label>
                      <input type="text" class="form-control field" id="event_headline_stats_embed" value='<iframe src="{{{ env('APP_URL') }}}/party/stats/{{{ $formdata->id }}}/wide" frameborder="0" width="100%" height="115"></iframe>'>
                  </div>
                  <small class="after-offset">@lang('events.headline_stats_message')</small>

                  <iframe src="{{{ env('APP_URL') }}}/party/stats/{{{ $formdata->id }}}/wide" frameborder="0" width="100%" height="115" id="headlineStats" class="form-control"></iframe>

              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header p-0" id="headingEventCO2">
              <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseEventCO2" aria-expanded="false" aria-controls="collapseEventCO2">
                @lang('events.co2_equivalence_visualisation_dropdown')
                @include('partials.caret')
              </button>
            </div>

            <div id="collapseEventCO2" class="collapse" aria-labelledby="headingEventCO2" data-parent="#accordionEvent">
              <div class="card-body">

                  <p>@lang('events.infographic_message')</p>

                  <div class="form-group">
                      <label for="event_co2_stats_embed">@lang('events.embed_code_header'):</label>
                      <input type="text" class="form-control field" id="event_co2_stats_embed" value='<iframe src="{{{ env('APP_URL') }}}/outbound/info/party/{{{ $formdata->id }}}/manufacture" frameborder="0" width="700" height="850"></iframe>'>
                  </div>

                  <div class="embed-responsive embed-responsive-21by9">
                    <iframe src="{{{ env('APP_URL') }}}/outbound/info/party/{{{ $formdata->id }}}/manufacture" frameborder="0" width="700" height="850" class="form-control embed-responsive-item"></iframe>
                  </div>

                  <div class="form-group">
                      <label for="event_co2_stats_embed">@lang('events.embed_code_header'):</label>
                      <input type="text" class="form-control field" id="event_co2_stats_embed" value='<iframe src="{{{ env('APP_URL') }}}/outbound/info/party/{{{ $formdata->id }}}/consume" frameborder="0" width="700" height="850"></iframe>'>
                  </div>

                  <div class="embed-responsive embed-responsive-21by9">
                    <iframe src="{{{ env('APP_URL') }}}/outbound/info/party/{{{ $formdata->id }}}/consume" frameborder="0" width="700" height="850" class="form-control embed-responsive-item"></iframe>
                  </div>

              </div>
            </div>
          </div>

        </div>

      </div>


    </div>
  </div>
</div>
@endif
