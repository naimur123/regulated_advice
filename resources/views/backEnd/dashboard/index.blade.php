@extends('backEnd.masterPage')

@section('mainPart')


<!-- task, page, download counter  start -->
<div class="row">
    <!-- first Block Start-->
    <div class="col-xl-3 col-md-3 col-sm-6 ">
        <div class="card bg-c-yellow update-card">
            <div class="card-block">
                <div class="row align-items-end">
                    <div class="col-8">
                        <h4 class="text-white">{{ $today_register ?? 0 }}</h4>
                        <h6 class="text-white m-b-0">Today Registered</h6>
                    </div>
                    <div class="col-4 text-right"><iframe class="chartjs-hidden-iframe" style="display: block; overflow: hidden; border: 0px none; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;" tabindex="-1" __idm_frm__="10737418263"></iframe>
                        <canvas id="update-chart-1" height="50" style="display: block; width: 45px; height: 50px;" width="45"></canvas>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('advisor.filter_list', ['type' =>'filter', 'type_value' => date('Y-m-d')]) }}">Today Register</a>
            </div>
        </div>
    </div>
    <!-- first Block End-->
    <!-- Second Block Start-->
     
    <div class="col-xl-3 col-md-3 col-sm-6">
        <div class="card bg-c-green update-card">
            <div class="card-block">
                <div class="row align-items-end">
                    <div class="col-8">
                        <h4 class="text-white">{{ $total_register ?? 0 }}</h4>
                        <h6 class="text-white m-b-0">Total Advisor</h6>
                    </div>
                    <div class="col-4 text-right"><iframe class="chartjs-hidden-iframe" style="display: block; overflow: hidden; border: 0px none; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;" tabindex="-1" __idm_frm__="10737418265"></iframe>
                        <canvas id="update-chart-2" height="50" style="display: block; width: 45px; height: 50px;" width="45"></canvas>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('advisor.filter_list') }}"> Total Registered</a>
            </div>
        </div>
    </div>
    
    <!-- Second Block End-->
    <!-- Third Block End-->
    
    <div class="col-xl-3 col-md-3 col-sm-6">
        <div class="card bg-c-pink update-card">
            <div class="card-block">
                <div class="row align-items-end">
                    <div class="col-8">
                        <h4 class="text-white">{{ $today_visit ?? 0 }}</h4>
                        <h6 class="text-white m-b-0">Today Page Views</h6>
                    </div>
                    <div class="col-4 text-right"><iframe class="chartjs-hidden-iframe" style="display: block; overflow: hidden; border: 0px none; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;" tabindex="-1" __idm_frm__="10737418265"></iframe>
                        <canvas id="update-chart-2" height="50" style="display: block; width: 45px; height: 50px;" width="45"></canvas>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('visitor') }}"> Today Page Views</a>
            </div>
        </div>
    </div>
    
    <!-- Third Block End-->
    <!-- Fourth Block Start-->
    <div class="col-xl-3 col-md-3 col-sm-6">
        <div class="card update-card bg-c-lite-green">
            <div class="card-block">
                <div class="row align-items-end">
                    <div class="col-8">
                        <h4 class="text-white">{{ $total_visitor ?? 0 }}</h4>
                        <h6 class="text-white m-b-0">Total Visitor</h6>
                    </div>
                    <div class="col-4 text-right"><iframe class="chartjs-hidden-iframe" style="display: block; overflow: hidden; border: 0px none; margin: 0px; inset: 0px; height: 100%; width: 100%; position: absolute; pointer-events: none; z-index: -1;" tabindex="-1" __idm_frm__="10737418265"></iframe>
                        <canvas id="update-chart-2" height="50" style="display: block; width: 45px; height: 50px;" width="45"></canvas>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('visitor') }}">Total Visitor</a>

            </div>
        </div>
    </div> 

    <!-- Office Manager -->
    <div class="col-xl-3 col-md-3 col-sm-6">
        <div class="card update-card bg-c-pink">
            <div class="card-block">
                <div class="row align-items-end">
                    <div class="col-12">
                        <h4 class="text-white">{{ $total_office_manager ?? "" }}</h4>
                        <h6 class="text-white m-b-0">Total Office Manager</h6>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <p class="text-white m-b-0">                        
                    <a href="{{ route('office_manager.list') }}">Office Manager List</a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Plans Block Start-->
    @foreach($plans as $plan)
        @if(!$plan->office_manager)
            <div class="col-xl-3 col-md-3 col-sm-6">
                <div class="card update-card {{ $loop->iteration % 2 == 0 ? 'bg-c-yellow' : 'bg-c-green'}}">
                    <div class="card-block">
                        <div class="row align-items-end">
                            <div class="col-12">
                                <h4 class="text-white">{{ $plan->advisors->count() }}</h4>
                                <h6 class="text-white m-b-0">{{ $plan->name }}</h6>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-center">
                        <p class="text-white m-b-0">                        
                            <a href="{{ route('advisor.filter_list', ['type' =>'plan', 'type_value' => $plan->name]) }}">{{ $plan->name }}</a>
                        </p>
                    </div>
                </div>
            </div> 
        @endif
    @endforeach  

</div>

<!-- Modal -->
<div class="modal fade" keyboard="false" data-backdrop="static" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog  {{ isset($modalSizeClass) && !empty($modalSizeClass) ? $modalSizeClass : 'modal-lg'}}" role="document">
        <div class="modal-content">            
            <div class="modal-header">
                <h5 class="modal-title" > Loading... <img src="{{ asset('loading.svg') }}" width="50"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div> 
        </div>
    </div>
</div>

@endsection
