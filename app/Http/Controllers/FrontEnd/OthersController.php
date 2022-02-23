<?php

namespace App\Http\Controllers\FrontEnd;

use App\AdvisorQuestion;
use App\AdvisorQuickLink;
use App\ContactUs;
use App\Http\Components\Classes\Fetchify;
use App\Http\Controllers\Controller;
use App\Pages;
use App\QuickLinks;
use App\ServiceOffer;
use App\TipsAndGuides;
use App\TremsAndCondition;
use App\User;
use Exception;
use Illuminate\Http\Request;

class OthersController extends Controller
{
    /**
     * About Us
     */
    public function aboutUs(){
        $dynamic_page= TremsAndCondition::where("type", "Popup Page")->first();
        $params = [
            "dynamic_popup"  => $dynamic_page->trems_and_condition ?? "",
        ];
        return view('frontEnd.others.aboutUs', $params);
    }

    /**
     * Tips And Guides
     */
    public function tipsAndGuides(){
        $dynamic_page= TremsAndCondition::where("type", "Popup Page")->first();
        $params = [
            "title"          => "Tips and Guides",
            "others"         => TipsAndGuides::where('publication_status', true)->where('type', 'others')->get(),
            "area_of_advices"=> TipsAndGuides::where('publication_status', true)->where('type', '!=','others')->paginate(6),
            "dynamic_popup"  => $dynamic_page->trems_and_condition ?? "",
        ];
        return view('frontEnd.others.tips-and-guides', $params);
    }

    /**
     * View Tips & Guides
     */
    protected function viewTipsAndGuides(Request $request){
        $params = [
            'data'          => TipsAndGuides::where('slug', $request->slug)->first(),
            'quick_links'    => QuickLinks::where('publication_status', 1)->orderBy('id', 'ASC')->get(),
        ];
        return view('frontEnd.others.view-tips-and-guides', $params);
    }

    /**
     * Contact Us
     */
    public function contactUs(){
        $dynamic_page= TremsAndCondition::where("type", "Popup Page")->first();
        $params = [
            "dynamic_popup"  => $dynamic_page->trems_and_condition ?? "",
            "page"          => Pages::getPage("contact_page"),
        ];
        return view('frontEnd.others.contactUs', $params);
    }

    /**
     * Save Contact Us Enquire form
     */
    public function saveContactUs(Request $request){
        $this->validate($request, [
            'service_interest'  => ['required', 'string', 'min:4'],
            'first_name'        => ['required', 'string', 'min:2'],
            'last_name'         => ['nullable', 'string', 'min:2'],
            'company_name'      => ['required', 'string', 'min:2'],
            'phone_number'      => ['required', 'string', 'min:10', 'max:14'],
            'email'             => ['required', 'email', 'min:4'],
            'post_code'         => ['required', 'string', 'min:4', 'max:10'],
        ]);
        try{
            $response =  (new Fetchify())->isValidEmail($request->email);
            if( !$response["status"] ){
                return back()->withInput()->withErrors( ["email" => $response["message"] ]);
            }
            $response =  (new Fetchify())->isValidPhone($request->phone_number);
            if( !$response["status"] ){
                return back()->withInput()->withErrors( ["phone_number" => $response["message"] ]);
            }
            $response =  (new Fetchify())->isValidPostCode($request->post_code);
            if( !$response["status"] ){
                return back()->withInput()->withErrors( ["post_code" => $response["message"] ]);
            }
            $data = $request->except('_token');
            $data['created_at'] = now();
            $data = ContactUs::insert($data);
            // Sent Notification Email
            return back()->with('success', 'Enquire message sent successfully');
        }catch(Exception $e){
            return back()->with('error', $this->getError($e))->withInput();
        }
    }

    /**
     * Campain
     */
    public function campain(){
        $specific_footer    = TremsAndCondition::where("type", "Campaign Page Footer")->first();
        $dynamic_page       = TremsAndCondition::where("type", "Popup Page")->first();

        $params = [
            "page"                  => Pages::getPage("campain_page"),
            "specific_footer_text"  => $specific_footer->trems_and_condition ?? "",
            "dynamic_popup"         => $dynamic_page->trems_and_condition ?? "",
        ];

        return view('frontEnd.others.campain', $params);
    }

    /**
     * Legal Stuff
     */
    public function legalStuff(){
        $params = [
            'quick_links'    => QuickLinks::where('publication_status', 1)->orderBy('id', 'ASC')->get(),
            "disclaimer"     => TremsAndCondition::where("type", "Disclaimer")->first()->trems_and_condition,
        ];
        return view('frontEnd.others.legal-stuff', $params);
    }

    /**
     * Privacy Policy
     */
    public function privacyPolicy(){
        $params = [
            'quick_links'    => QuickLinks::where('publication_status', 1)->orderBy('id', 'ASC')->get(),
            "data"           => TremsAndCondition::where('type', "Privacy Policy")->first(),
        ];
        return view('frontEnd.others.privacy-policy', $params);
    }

    /**
     * Need Finalcial Advisor Page
     */
    public function needFinalCialAdvisor(Request $request){
        $params = [
            'quick_links'    => QuickLinks::where('publication_status', 1)->orderBy('id', 'ASC')->get(),
        ];
        return view('frontEnd.others.need-finalcial-advisor', $params);
    }

    /**
     * Trams &n Condition
     */
    public function termsAndCondition(){
        $params = [
            'quick_links'    => QuickLinks::where('publication_status', 1)->orderBy('id', 'ASC')->get(),
            "data"          => TremsAndCondition::where('type', 'Terms & Conditions')->first(),
        ];
        return view('frontEnd.others.terms-and-condition', $params);
    }

    /**
     * View Quick Link
     */
    public function viewQuickLink(Request $request){
        $params = [
            'data'          => QuickLinks::where('slug', $request->slug)->first(),
            'quick_links'    => QuickLinks::where('publication_status', 1)->orderBy('id', 'ASC')->get(),
        ];
        return view('frontEnd.others.view-quick-link', $params);
    }

    /**
     * View Advisor Quick Link
     */
    public function viewAdvisorQuickLink(Request $request){
        $params = [
            'data'          => AdvisorQuickLink::where('slug', $request->slug)->first(),
            'quick_links'    => AdvisorQuickLink::where('publication_status', 1)->orderBy('id', 'ASC')->get(),
        ];
        return view('frontEnd.others.view-advisor-quick-link', $params);
    }

    public function viewAllQuestions(Request $request){
        $specific_footer    = TremsAndCondition::where("type", "Questions & Answers Page Footer")->first();
        $service_offer = ServiceOffer::where('id', $request->id)->firstOrFail();
        $all_questions = AdvisorQuestion::where('id','!=', $request->question_id)
            ->where('visibility', 'public')
            ->where('service_offer_id', $service_offer->id)
            ->orderBy('id', 'DESC')->paginate(25);
        $params = [
            'service_offer'     => $service_offer,
            "service_offers"    => ServiceOffer::where('publication_status', 1)->get(),
            'all_questions'     => $all_questions,
            'active_advisor'    => User::where('status', 'active')->count(),
            "disclaimer"        => TremsAndCondition::where("type", "Disclaimer")->first()->trems_and_condition,
            "specific_footer"   => $specific_footer->trems_and_condition ?? "",

        ];
        return view('frontEnd.others.view-question', $params);
    }

    public function viewQuestions(Request $request){
        $specific_footer    = TremsAndCondition::where("type", "Questions & Answers Page Footer")->first();
        $question = AdvisorQuestion::where('id', $request->question_id)->firstOrFail();
        $service_offer = $question->service_offer;
        $all_questions = AdvisorQuestion::where('id','!=', $request->question_id)
            ->where('visibility', 'public')
            ->where('service_offer_id', $service_offer->id)
            ->orderBy('id', 'DESC')->paginate(25);
        $params = [
            'service_offer'     => $question->service_offer,
            "service_offers"    => ServiceOffer::where('publication_status', 1)->get(),
            'all_questions'     => $all_questions,
            'active_advisor'    =>  User::where('status', 'active')->count(),
            'open_question'     => $question,
            "disclaimer"        => TremsAndCondition::where("type", "Disclaimer")->first()->trems_and_condition,
            "specific_footer"   => $specific_footer->trems_and_condition ?? "",
        ];
        return view('frontEnd.others.view-question', $params);
    }


}
