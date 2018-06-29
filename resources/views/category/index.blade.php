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
            <a href="/" class="btn btn-primary btn-save">@lang('admin.create-new-category')</a>
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

                          <th>ID</th>
                          <th>Name</th>
                          <th>Weight [kg]</th>
                          <th>CO<sub>2</sub> Footprint [kg]</th>
                          <th width="145">Reliability</th>

                      </tr>
                  </thead>

                  <tbody>
                    @if(isset($list))
                      @foreach($list as $p)
                      <tr>
                          <td><a href="/category/edit/{{ $p->idcategories }}"><?php echo $p->idcategories; ?></a></td>
                          <td><?php echo $p->name; ?></td>
                          <td><?php echo $p->weight; ?></td>
                          <td><?php echo $p->footprint; ?></td>
                          @include('partials/footprint-reliability', ['reliability' => $p->footprint_reliability])
                      </tr>
                      @endforeach
                    @endif
                  </tbody>
              </table>
            </div>

        </div>
    </div>

    <div class="d-flex justify-content-center">
        <nav aria-label="Page navigation example">
        <ul class="pagination">
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
        </ul>
        </nav>
    </div>

  </div>
</section>
@endsection
