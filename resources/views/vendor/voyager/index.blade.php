@extends('voyager::master')

@section('page_title', 'Dashboard - Acme Aptitude')

@section('content')
    <div class="page-content container-fluid">
        <!-- Your custom dashboard content -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Welcome to Acme Aptitude Admin</h3>
                    </div>
                    <div class="panel-body">
                        <p>Hello {{ auth()->user()->name ?? 'Admin' }}!</p>
                        <p>This is your Acme Aptitude dashboard. Manage candidates, assessments, results, and more.</p>
                        
                        <!-- Quick stats / cards example -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="panel panel-primary text-center">
                                    <div class="panel-heading">Total Candidates</div>
                                    <div class="panel-body">
                                        <h2>{{ \App\Models\User::where('role_id', 6)->count() }}</h2>
                                    </div>
                                </div>
                            </div>
                            <!-- Add more cards -->
                        </div>

                        <!-- Or keep some original widgets if you want -->
                        @if(isset($widgets) && count($widgets) > 0)
                            @foreach($widgets as $widget)
                                {!! $widget->render() !!}
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection