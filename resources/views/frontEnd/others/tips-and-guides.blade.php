@extends('frontEnd.masterPage')
@section('title')
    Tips and guides ||
@stop
@section('mainPart')
    <section style="background-size: cover;background-repeat: no-repeat;background-position: center; background-image: url('{{ asset('image/financial.jpg') }}')">
        <div class="container-fluid">
            <div class="row justify-content-center" style="min-height: 250px">
            </div>
        </div>
    </section>
    <div class="container-fluid pb-5" style="background: #eee;">
        <div class="row justify-content-center" >
            <div class="col-12 col-md-11">
                <p class="font-13 m-0 mt-5 text-theme">HOW TO GET THE MOST OUT OF YOUR FREE ONE HOUR CONSULTATION</p>
                <h3 class="m-0 text-theme">Tips and guides</h3>
                <div class="mt-3">We’ve set out all the information you need to make an informed decision when choosing a Financial Advisor and to ensure you get the most out of your free one hour consultation.</div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-md-11">
                <div class="row">
                    <div class="col-12 mt-4">
                        <div class="row">
                            @foreach($others as $data)
                                <div class="col-sm-6 col-md-3 mt-2">
                                    <div class="card">
                                        <img src="{{ asset(file_exists($data->image) ? $data->image : 'image/tea-cup.png') }}" class="img-fluid" alt="Image" style="height: 160px">
                                        <div class="card-body">
                                            <h4 class="card-title text-theme font-20 mb-0 pb-0">{{ $data->title ?? "" }}</h4>
                                            <div class="card-text" style="height: 140px;"> 
                                                {!! strip_tags(substr($data->description, 0, strpos($data->description, ' ', 140))) !!}... 
                                            </div>
                                            <a href="{{ route('view_tips_and_guides',[$data->slug]) }}" class="btn btn-link pl-0 float-right">Read More</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12 mt-4">                            
                        <div class="row">
                            <div class="col-sm-12 mt-4 mb-2">
                                <p class="m-0 text-theme font-13 text-uppercase">Here Are {{ count($area_of_advices) }} of the Best</p>
                                <h3 class="m-0 text-theme">Areas of advice</h3>
                            </div>
                            @foreach($area_of_advices as $data)
                                <div class="col-sm-6 col-md-3 mt-2">
                                    <div class="card">
                                        <img src="{{ asset(file_exists($data->image) ? $data->image : 'image/tea-cup.png') }}" class="img-fluid" alt="Image" style="height: 160px">
                                        <div class="card-body">
                                            <div style="height: 55px;">
                                                <h4 class="card-title text-theme font-18 mb-0 pb-0">{!! $data->title ?? "" !!}</h4>
                                            </div>
                                            <div class="card-text" style="height: 140px;"> 
                                                {!! strip_tags(substr($data->description, 0, strpos($data->description, ' ', 140))) !!}...
                                            </div>
                                            <a href="{{ route('view_tips_and_guides',[$data->slug]) }}" class="btn btn-link pl-0 float-right">Read More</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Popup -->
    @if( !empty($dynamic_popup) )
        <div class="custom-popup d-none center" style="text-align:center">
            <div class="row">
                <div class="col-12 text-right">
                    <button class="btn btn-default close-popup" title="Close"><i class="far fa-times-circle" style="font-size:36px;color:red;"></i></button>
                </div>                
            </div>
            <div class="container-md">
                <div class="row">
                    <div class="col-12 mt-0 mb-5">
                        {!! $dynamic_popup !!}
                        <a href="{{ route('search_advisor', ['Financial-Advisor']) }}" class="btn btn-warning btn-md no-radius">Search</a>
                    </div>
                </div>
            </div>
        </div>
    @endif

@endsection