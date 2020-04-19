@extends('layouts.app')

@section('content')
    <section class="admin">
        <div class="container">
            <div class="row">
                <div class="col">

                    <h1>Network: {{ $network->name }}</h1>

                    <h2>Coordinators</h2>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($network->coordinators as $coordinator)
                                <tr>
                                    <td><a href="/profile/edit/{{ $coordinator->id}}">{{ $coordinator->name }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <h2>Groups</h2>

                    <p>There are currently {{ $network->groups->count() }} groups in the {{ $network->name }} network.

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                            </tr>
                        </thead>
                        <tbody>
                    @foreach ($network->groups as $group)
                        <tr>
                            <td><a href="/group/view/{{ $group->idgroups }}">{{ $group->name }}</a></td>
                        </tr>
                    @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

@endsection
