@extends('layouts.app')

@section('content')
<section class="admin">
    <div class="container">

        @if (\Session::has('success'))
        <div class="alert alert-success">
            {!! \Session::get('success') !!}
        </div>
        @endif

        @if (\Session::has('danger'))
        <div class="alert alert-danger">
            {!! \Session::get('danger') !!}
        </div>
        @endif


        <div class="row mb-30">
            <div class="col-12 col-md-12">
                <div class="d-flex align-items-center">
                    <h1 class="mb-0 mr-30">
                        Categories
                    </h1>

                    <!-- <button data-toggle="modal" data-target="#add-new-category" class="btn btn-primary btn-save ml-auto">@lang('admin.create-new-category')</button> -->

                </div>
            </div>
        </div>

        <br>

        <div class="row">
            <div class="col-12">

                <div class="table-responsive table-section">
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
