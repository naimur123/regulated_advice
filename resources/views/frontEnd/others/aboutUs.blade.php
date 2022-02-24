@extends('frontEnd.masterPage')
@section('title')
    About Us ||
@stop
@section('mainPart')
    <section style="background-size: cover;background-repeat: no-repeat;background-position: center; background-image: url('{{ asset('image/financial.jpg') }}')">
        <div class="container-fluid">
            <div class="row justify-content-center" style="min-height: 200px">
            </div>
        </div>
    </section>

    <div class="container-lg bg-white mb-5" style="margin-top: -80px; position: relative;">
        <div class="row justify-content-center" >
            <div class="col-12 col-md-10">
                <br/>
                <p class="text-theme font-13 m-0">ABOUT REGULATED ADVICE</p>
                <h3 class="text-theme">We are Regulated Advice</h3>
            </div>
            <div class="col-12 col-md-10">
                <p class="m-0" >
                    When you find an advisor with us, you can be sure they are regulated. All the advisors listed with us are regulated by the relevant official bodies,  appropriately quali-fied and independent of product providers.  Ask a question or use our search to find ex-actly what you need in seconds –including independent financial advice, restricted ad-vice and best mortgage advice
                </p>
            </div>

            <div class="col-6 col-md-5 mt-2 text-center">
                <br/>
                <img src="{{ asset('image/ekomi-single.png') }}" class="img-fluid">
            </div>
            <div class="col-6 col-md-5 mt-2 text-center">
                <br/>
                <img src="{{ asset('image/five stars.png') }}" class="img-fluid">
            </div>
        </div>
        <div class="row justify-content-center" >
            <div class="col-6 col-md-5 mt-2">
                <p class="text-theme font-13 m-0" >POWERED BY EKOMI</p>
                <h3 class="m-0 text-theme">Unbiased reviews</h3>
                <div class="text-left">
                    <p class="m-0"> Receive peace of mind by  reading unbiased reviews from real people who have used this website.</p>
                </div>
            </div>
            <div class="col-6 col-md-5 mt-2">
                <p class="text-theme font-13 m-0" >SEARCH FOR 5 STAR RATED ADVISORS</p>
                <h3 class="m-0 text-theme">Use our unique Match Rating™</h3>
                <div class="text-left">
                    <p>Search for an advisor using our unique Match Rating™ tool. This matches your financial question to a selection of advisors who have answered a number of similar questions.</p>
                </div>
            </div>
        </div>
        <div class="row justify-content-center" >
            <div class="col-6 col-md-10 mt-5">
                <br/>
                <p class="text-theme font-13 m-0">3  WAYS TO FIND AN ADVISOR</p>
                <h3 class="text-theme m-0">How to find a Financial Advisor</h3>
            </div>
            <div class="col-6 col-md-10 text-center">
                <br/>
                <img src="{{ asset('image/aboutus.jpg') }}" class="img-fluid">
            </div>
            <div class="col-6 col-md-10 mt-5">
                <div class="text-theme font-13 m-0" >OUR SERVICE IS FREE</div>
                <h3 class="text-theme mt-0">How does Regulated Advice make money?</h3>
                <div>
                    <p class="m-0">We want the website to be free for the public to use, so therefore we charge advisers an annual subscription fee to be a member of Regulated Advice. We also charge advisers an enquiry fee for some enquiries submitted through the site.</p>
                </div>
            </div>
        </div>
        <br/><br/>
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
