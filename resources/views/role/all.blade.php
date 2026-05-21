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
                        Roles
                    </h1>

                </div>
            </div>
        </div>

        <br>
        <div class="row">
            <div class="col-12">
                <div class="vue">
                    <roles-table :roles="{{ json_encode($tableData) }}"></roles-table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
