<!-- Modal -->
<div class="modal fade" id="group-share-stats" tabindex="-1" role="dialog" aria-labelledby="groupShareStatsLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="groupShareStatsLabel">@lang('groups.share_stats_header')</h5>
        @include('partials.cross')

      </div>

      <div class="modal-body">

        <p>@lang('groups.share_stats_message', ['group' => $group->name])</p>

        <div id="accordionGroup" class="accordion__share mt-4">

          <div class="card">
            <div class="card-header p-0" id="headingGroupHeadline">
              <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseGroupHeadline" aria-expanded="true" aria-controls="collapseGroupHeadline">
                @lang('groups.headline_stats_dropdown')
                @include('partials.caret')
              </button>
            </div>
            <div id="collapseGroupHeadline" class="collapse" aria-labelledby="headingGroupHeadline" data-parent="#accordionGroup">
              <div class="card-body">

                  <div class="form-group">
                      <label for="group_headline_stats_embed">@lang('groups.embed_code_header'):</label>
                      <input type="text" class="form-control field" id="group_headline_stats_embed" value='<iframe src="{{{ env('APP_URL') }}}/group/stats/{{{ $group->idgroups }}}" frameborder="0" width="700" height="370"></iframe>'>
                  </div>
                  <small class="after-offset">@lang('groups.headline_stats_message')</small>

                  <iframe src="{{{ env('APP_URL') }}}/group/stats/{{{ $group->idgroups }}}" frameborder="0" width="700" height="370" id="headlineStats" class="form-control"></iframe>

              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-header p-0" id="headingGroupCO2">
              <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseGroupCO2" aria-expanded="false" aria-controls="collapseGroupCO2">
                @lang('groups.co2_equivalence_visualisation_dropdown')
                @include('partials.caret')
              </button>
            </div>

            <div id="collapseGroupCO2" class="collapse" aria-labelledby="headingGroupCO2" data-parent="#accordionGroup">
              <div class="card-body">

                  <p>@lang('groups.infographic_message')</p>

                  <div class="form-group">
                      <label for="group_co2_stats_embed">@lang('groups.embed_code_header'):</label>
                      <input type="text" class="form-control field" id="group_co2_stats_embed" value='<iframe src="{{{ env('APP_URL') }}}/outbound/info/group/{{{ $group->idgroups }}}/leaf" frameborder="0" width="700" height="370"></iframe>'>
                  </div>

                  <div class="embed-responsive embed-responsive-21by9">
                    <iframe src="{{{ env('APP_URL') }}}/outbound/info/group/{{{ $group->idgroups }}}/leaf" frameborder="0" width="700" height="370" class="form-control embed-responsive-item"></iframe>
                  </div>

              </div>
            </div>
          </div>

        </div>

      </div>


    </div>
  </div>
</div>
