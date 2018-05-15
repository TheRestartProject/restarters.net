@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>
            @if($response)
              @php( printResponse(parseResponse($response)) )
            @endif
            <a class="btn btn-primary" href="/group/create"><i class="fa fa-plus"></i>New Group</a>
            <table class="table table-hover table-responsive sortable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Group</th>
                        <th>Location</th>
                        <th>Frequency</th>
                        <th>Restarters</th>
                        @if(FixometerHelper::hasRole($user, 'Administrator'))
                        <th><i class="fa fa-pencil"></i></th>
                        <th><i class="fa fa-trash"></i></th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @foreach($list as $g)
                    <tr>
                        <td><?php echo $g->id; ?></td>
                        <td><a href="/group/edit/<?php echo $g->id; ?>" title="edit group"><?php echo $g->name; ?></a></td>
                        <td><?php echo $g->location . ', ' . $g->area; ?></td>
                        <td><?php echo $g->frequency; ?>Parties/Year</td>
                        <td><?php echo $g->user_list; ?></td>
                        @if(FixometerHelper::hasRole($user, 'Administrator'))
                        <td><a href="/group/edit/<?php echo $g->id; ?>"><i class="fa fa-pencil"></i></a></td>
                        <td><a href="/group/delete/<?php echo $g->id; ?>" class="delete-control"><i class="fa fa-trash"></i></a></td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
