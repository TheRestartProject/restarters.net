@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>



            <div class="row">
                <div class="col-md-12">
                    <span>Legend</span>
                    <ul class="legend">
                        <li class="voice indicator indicator-1">Very poor</li>
                        <li class="voice indicator indicator-2">Poor</li>
                        <li class="voice indicator indicator-3">Fair</li>
                        <li class="voice indicator indicator-4">Good</li>
                        <li class="voice indicator indicator-5">Very good</li>
                    </ul>
                </div>
            </div>
            <table class="table table-hover table-responsive sortable">
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
                        <td><?php echo $p->idcategories; ?></td>
                        <td><?php echo $p->name; ?></td>
                        <td><?php echo $p->weight; ?></td>
                        <td><?php echo $p->footprint; ?></td>
                        <td><span class="indicator indicator-<?php echo $p->footprint_reliability; ?>"><?php echo $p->footprint_reliability; ?></span></td>

                    </tr>
                    @endforeach
                  @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
