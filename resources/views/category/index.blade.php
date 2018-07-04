@extends('layouts.app')

@section('content')
<section class="admin">
  <div class="container">
    <div class="row">
      <div class="col">
        <div class="d-flex justify-content-between align-content-center">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">FIXOMETER</a></li>
              <li class="breadcrumb-item active" aria-current="page">@lang('admin.categories')</li>
            </ol>
          </nav>
          <div class="btn-group">
            <!-- <a href="/" class="btn btn-primary btn-save">@lang('admin.create-new-category')</a> -->
          </div>
        </div>
      </div>
    </div>

    <br>

    <div class="row">
        <div class="col-12">

            <div class="table-responsive">
              <table class="table table-hover table-striped table-categories sortable" role="table">
                  <thead>
                      <tr>

                          <th>Name</th>
                          <th>Category Cluster</th>
                          <th>Weight [kg]</th>
                          <th>CO<sub>2</sub> Footprint [kg]</th>
                          <th width="145">Reliability</th>

                      </tr>
                  </thead>

                  <tbody>
                    @if(isset($list))
                      @foreach($list as $p)
                      <tr>
                          <td><a href="/category/edit/{{ $p->idcategories }}">{{{ $p->name }}}</a></td>
                          @if( !empty($p->cluster) )
                            @foreach($categories as $cluster)
                              {!! $cluster->idclusters == $p->cluster ? '<td>'.$cluster->name.'</td>' : '' !!}
                            @endforeach
                          @else
                            <td>N/A</td>
                          @endif
                          <td>{{{ $p->weight }}}</td>
                          <td>{{{ $p->footprint }}}</td>
                          @include('partials/footprint-reliability', ['reliability' => $p->footprint_reliability])
                      </tr>
                      @endforeach
                    @endif
                  </tbody>
              </table>
            </div>

        </div>
    </div>

  </div>
</section>
@endsection
