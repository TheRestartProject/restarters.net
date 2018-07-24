@extends('layouts.app')
@section('content')
<section class="reporting">
  <div class="container-fluid">

    <div class="row">
      <div class="col">
        <div class="d-flex justify-content-between align-content-center">

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">FIXOMETER</a></li>
                    <li class="breadcrumb-item"><a href="/reporting">@lang('events.reporting')</a></li>
                    <li class="breadcrumb-item active" aria-current="page">@lang('reporting.time_volunteered')</li>
                </ol>
            </nav>

          <div class="btn-group">
            <a class="btn btn-primary" href="/export/time-volunteered?{{ $_SERVER['QUERY_STRING'] }}">@lang('reporting.export_csv')</a>
          </div>

        </div>
      </div>
    </div>

    <br>

    <div class="row">
      @if (FixometerHelper::hasRole($user, 'Administrator') || FixometerHelper::hasRole($user, 'Host'))
          <div class="col-lg-3">

              <form id="filter-result" action="/reporting/time-volunteered/search" method="get">
                  <button class="btn btn-primary btn-volunteered" type="submit">@lang('reporting.search-all-time-volunteered')</button>

                  @if(isset($all_groups) && !empty($all_groups))
                    <aside class="edit-panel edit-panel__side">
                        <legend>@lang('devices.by_taxonomy')</legend>
                        <div class="form-group">
                            <label for="items_cat">@lang('groups.group'):</label>
                            <div class="form-control form-control__select">
                                <select id="items_cat" name="group" class="select2">
                                    <option value="">@lang('reporting.placeholder_group')</option>
                                    @foreach($all_groups as $g)
                                      @if(isset($group) && $group == $g->idgroups)
                                        <option value="{{ $g->idgroups }}" selected>{{ $g->name }}</option>
                                      @else
                                        <option value="{{ $g->idgroups }}">{{ $g->name }}</option>
                                      @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="items_group">@lang('groups.group_tag2'):</label>
                            <div class="form-control form-control__select">
                                <select id="items_group" name="tags[]" class="select2-tags" multiple data-live-search="true" title="Choose group tags...">
                                    @foreach($all_group_tags as $group_tag)
                                      @if(isset($selected_tags) && in_array($group_tag->id, $selected_tags))
                                        <option value="{{ $group_tag->id }}" selected>{{ $group_tag->tag_name }}</option>
                                      @else
                                        <option value="{{ $group_tag->id }}">{{ $group_tag->tag_name }}</option>
                                      @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </aside>
                  @endif

                  <aside class="edit-panel edit-panel__side">
                      <legend>@lang('reporting.by_users')</legend>
                      <div class="form-group">
                          <label for="name">@lang('admin.name'):</label>
                          <input type="text" id="name" name="name" class="field form-control" placeholder="@lang('reporting.placeholder_name')" value="{{ $name }}">
                      </div>
                      <div class="form-group">
                          <label for="age_range">@lang('reporting.birth_year'):</label>
                          <div class="form-control form-control__select">
                              <select id="age_range" name="year" class="select2">
                                  <option value="">@lang('reporting.placeholder_birth_year')</option>
                                  @foreach(FixometerHelper::allAges() as $year)
                                    @if(isset($age) && $age == $year)
                                      <option value="{{ $year }}" selected>{{ $year }}</option>
                                    @else
                                      <option value="{{ $year }}">{{ $year }}</option>
                                    @endif
                                  @endforeach
                              </select>
                          </div>
                      </div>
                      <div class="form-group">
                          <label for="gender">@lang('reporting.gender'):</label>
                          <!-- <div class="form-control form-control__select">
                              <select id="gender" class="select2">
                                  <option value="">@lang('reporting.placeholder_gender')</option>

                              </select>
                          </div> -->
                          <input type="text" id="gender" name="gender" class="field form-control" placeholder="@lang('reporting.placeholder_gender_text')" value="{{ $gender }}">
                      </div>
                  </aside>

                  <aside class="edit-panel edit-panel__side">
                      <legend>@lang('devices.by_date')</legend>
                      <div class="form-group">
                          <label for="from_date">@lang('devices.from_date'):</label>
                          <input type="date" id="from_date" name="from_date" class="field form-control" value="{{ $from_date }}">
                      </div>
                      <div class="form-group">
                          <label for="to_date">@lang('devices.to_date'):</label>
                          <input type="date" id="to_date" name="to_date" class="field form-control" value="{{ $to_date }}">
                      </div>

                  </aside>

                  <aside class="edit-panel edit-panel__side">
                      <legend>@lang('reporting.by_location')</legend>
                      <div class="form-group">
                          <label for="country">@lang('reporting.country'):</label>
                          <div class="form-control form-control__select">
                              <select id="country" name="country" class="field select2">
                                  <option value="">Choose country</option>
                                  @foreach (FixometerHelper::getAllCountries() as $country_code => $country_name)
                                    @if (isset($country) && $country_code == $country)
                                      <option value="{{ $country_code }}" selected>{{ $country_name }}</option>
                                    @else
                                      <option value="{{ $country_code }}">{{ $country_name }}</option>
                                    @endif
                                  @endforeach
                              </select>
                          </div>
                      </div>
                  </aside>
                  <aside class="edit-panel edit-panel__side">
                      <legend>@lang('reporting.miscellaneous')</legend>
                      <div class="form-group">
                          <label for="miscellaneous">@lang('reporting.include_anonymous_users'):</label>
                          <div class="form-control form-control__select">
                              <select id="misc" name="misc" class="select2">
                                  <option value="0" @if(isset($misc) && $misc == 0) selected @endif>@lang('reporting.no')</option>
                                  <option value="1" @if(isset($misc) && $misc == 1) selected @endif>@lang('reporting.yes')</option>
                              </select>
                          </div>
                      </div>

                  </aside>
              </form>

          </div>

          <div class="col-lg-9">
        @else
          <div class="col-lg-12">
        @endif

            <ul class="properties properties__iconless">
                <li>
                    <div>
                    <h3>@lang('reporting.hours_volunteered')</h3>
                    {{ $hours_completed }}
                    </div>
                </li>
                <li>
                    <div>
                    <h3>@lang('reporting.average_age')</h3>
                    {{ $average_age }}
                    </div>
                </li>
                <li>
                    <div>
                    <h3>@lang('reporting.number_of_groups')</h3>
                    {{ $group_count }}
                    </div>
                </li>
                <li>
                    <div>
                    <h3>@lang('reporting.total_number_of_users')</h3>
                    {{ $total_users }}
                    </div>
                </li>
                <li>
                    <div>
                    <h3>@lang('reporting.number_of_anonymous_users')</h3>
                    {{ $anonymous_users }}
                    </div>
                </li>
            </ul>

            <div class="row">

                <div class="col-lg-6">

                    <h2 id="country-breakdown">@lang('reporting.breakdown_by_country') <sup>(<a role="button" data-toggle="modal" data-target="#time-reporting-modal-1" href="#time-reporting-modal-1">@lang('reporting.see_all_results')</a>)</sup></h2>

                    <table class="table table-striped" role="table">
                        <thead>
                            <tr>
                                <th>@lang('reporting.country_name')</th>
                                <th>@lang('reporting.total_hours')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($country_hours_completed as $country_hours)
                              <tr>
                                @if(!is_null($country_hours->country))
                                  <td>{{ $country_hours->country }}</td>
                                @else
                                  <td>N/A</td>
                                @endif
                                <td>{{ substr($country_hours->event_hours, 0, -4) }}</td>
                              </tr>
                            @endforeach
                        </tbody>
                    </table>

                </div>
                <div class="col-lg-6">

                    <h2 id="city-breakdown">@lang('reporting.breakdown_by_city') <sup>(<a role="button" data-toggle="modal" data-target="#time-reporting-modal-2" href="#time-reporting-modal-2">@lang('reporting.see_all_results')</a>)</sup></h2>

                    <table class="table table-striped" role="table">
                        <thead>
                            <tr>
                                <th>@lang('reporting.town_city_name')</th>
                                <th>@lang('reporting.total_hours')</th>
                            </tr>
                        </thead>
                        <tbody>
                          @foreach($city_hours_completed as $city_hours)
                            <tr>
                              @if(!is_null($city_hours->location))
                                <td>{{ $city_hours->location }}</td>
                              @else
                                <td>N/A</td>
                              @endif
                              <td>{{ substr($city_hours->event_hours, 0, -4) }}</td>
                            </tr>
                          @endforeach
                        </tbody>
                    </table>
                </div>

            </div>

        <section class="table-section" id="list">

              <table class="table table-striped" role="table">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>@lang('reporting.restarter_name')</th>
                      <th>@lang('reporting.hours')</th>
                      <th>@lang('reporting.event_date')</th>
                      <th>@lang('reporting.restart_group')</th>
                      <th>@lang('reporting.location')</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($user_events as $ue)
                      <tr>
                          <td><a href="/party/view/{{ $ue->idevents }}">{{ $ue->idevents }}</a></td>
                          <td>{{ $ue->username }}</td>
                          @php
                            $start_time = new DateTime($ue->start);
                            $diff = $start_time->diff(new DateTime($ue->end));
                          @endphp
                          <td>{{ $diff->h.'.'.sprintf("%02d", $diff->i/60 * 100) }}</td>
                          <td>{{ date('d/m/Y', strtotime($ue->event_date)) }}</td>
                          <td>{{ $ue->groupname }}</td>
                          <td>{{ $ue->location }}</td>
                      </tr>
                    @endforeach
                  </tbody>
              </table>


        </section>


        <div class="d-flex justify-content-center">
          <nav aria-label="Page navigation example">
            <ul class="pagination">
              @if (!empty($_GET) || isset($group))
                {!! $user_events->appends(['group' => $group, 'selected_tags' => $selected_tags, 'name' => $name, 'age' => $age, 'gender' => $gender, 'from_date' => $from_date, 'to_date' => $to_date, 'country' => $country, 'misc' => $misc])->links() !!} <!-- 'selected_country' => $selected_country -->
              @else
                {{ $user_events->links() }}
              @endif
            </ul>
          </nav>
        </div>



        </div>
    </div>
  </div>
</section>

@include('includes.modals.time-reporting-1', ['all_country_hours_completed' => $all_country_hours_completed])
@include('includes.modals.time-reporting-2', ['all_city_hours_completed' => $all_city_hours_completed])

@endsection
