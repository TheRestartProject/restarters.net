@extends('layouts.app')
@section('content')
  <section class="groups">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-12">
          <ul class="nav nav-tabs">
            <li class="nav-item">
              <a class="nav-link active" data-toggle="tab" href="#details">Group details</a>
            </li>
            @if( $audits && App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') )
              <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#log">Group log</a>
              </li>
            @endif
          </ul>
          <div class="edit-panel">
            <div class="tab-content">
              <div class="tab-pane active" id="details">
                <div class="vue">
                  <GroupAddEditPage :idgroups="{{ $id }}"
                                    :can-approve="{{ $can_approve ? "true": "false" }}"
                                    :can-network="{{ Auth::user()->hasRole('Administrator') ? "true" : "false" }}"
                  />
                </div>
              </div>
              @if( $audits && App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') )

                <div class="tab-pane" id="log">

                  <div class="row">
                    <div class="col">
                      <h4>Group changes</h4>
                      <p>Changes made on group <strong>{{ $name }}</strong></p>
                    </div>
                  </div>

                  @include('partials.log-accordion', ['type' => 'group-audits'])

                </div>

              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection
@section('scripts')
  @include('includes/gmap')
@endsection