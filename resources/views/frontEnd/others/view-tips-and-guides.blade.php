@extends('frontEnd.masterPage')
@section('title')
{{ $data->title ?? "" }} ||
@stop
@section('mainPart')
    <section class="pt-0" style="background-size: cover;background-repeat: no-repeat;background-position: center; background-color:#eee">
        <div class="container-fluid">
            <div class="row justify-content-center" style="min-height: 300px">
                <div class="col-sm-12 text-center p-0">
                    <img src="{{ asset($data->image ?? 'image/financial.jpg') }}" style="height: 100%;">
                </div>
            </div>
        </div>
    </section>

    <div class="container-lg bg-white mb-5" style="margin-top: -80px; position: relative;">
        <div class="row justify-content-center" >
            <div class="col-12 col-md-10 mt-5">
                <div class="row">
                    <div class="col-12 col-md-9 mb-4">
                        <div>
                            <h3 class="p-0 m-0 text-theme font-24"><b>{{ $data->title }}</b></h3>      
                        </div>
                        <div class="mt-4">
                           {!! $data->description !!}
                        </div>
                    </div>
                
                    <div class="col-12 col-md-3">
                        <h3 class="text-theme">Quick links</h3>
                        <div class="list-group font-12 mt-4">                            
                            @foreach($quick_links as $link)
                                <a href="{{ route('view_quick_link',[$link->slug]) }}" class="list-group-item list-group-item-action">{{ $link->title }}</a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection