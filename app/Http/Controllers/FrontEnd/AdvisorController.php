<?php

namespace App\Http\Controllers\FrontEnd;

use App\AdvisorBillingInfo;
use App\AdvisorBlog;
use App\AdvisorFaq;
use App\AdvisorMarketing;
use App\AdvisorQuickLink;
use App\AdvisorType;
use App\Auction;
use App\Events\LeadAssign;
use App\FirmDetails;
use App\FundSize;
use App\Http\Components\Classes\Fetchify;
use App\Http\Components\Classes\MatchRating;
use App\Http\Components\Traits\AdvisorProfileVisitor;
use App\Http\Components\Traits\Communication;
use App\Http\Controllers\Controller;
use App\Leads;
use App\Notifications\EmailVerification;
use App\PrimaryReason;
use App\Profession;
use App\ServiceOffer;
use App\SubscriptionPlan;
use App\System;
use App\TremsAndCondition;
use App\User;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class AdvisorController extends Controller
{
    use Communication, RegistersUsers, AdvisorProfileVisitor;
    /**
     * Open Step 2 page
     */
    public function registerStep2(Request $request){
        $prev_page_data = Session::has('signup_step1') ? Session::get('signup_step1') : null;
        if( empty($prev_page_data) || count($prev_page_data) == 0){
            return redirect()->route('advisor.subscription_plan');
        }
        $prev_page_data = json_decode(json_encode($prev_page_data));
        
        $params = [
            "form_url"          => route('advisor.register_setp2'),
            "reasons"           => PrimaryReason::where('publication_status', true)->orderBy("position", "ASC")->get(),
            "service_offers"    => ServiceOffer::where("publication_status", true)->orderBy("id", "ASC")->get(),
            "fund_sizes"        => FundSize::where("publication_status", true)->orderBy("min_fund", "ASC")->get(),
            "advisor"           => $prev_page_data,
        ];
        
        if(isset($prev_page_data->office_manager_id) && !empty($prev_page_data->office_manager_id)){
            return view('frontEnd.office-manager.office-manager-register-page2', $params);    
        }
        return view('frontEnd.advisor.register-page2', $params);
    }

    /**
     * Save Step 2 Data
     */
    public function storeStep2(Request $request){
        $prev_page_data = Session::has('signup_step1') ? Session::get('signup_step1') : null;
        if( empty($prev_page_data) || count($prev_page_data) == 0){
            return redirect()->route('advisor.subscription_plan');
        }
        if( isset($request->office_manager_id) && !empty($request->office_manager_id) ){
            $this->validateStep2OfficeManagerData($request);
        }else{
            $this->validateStep2Data($request);
        }
        
        try{
            
            DB::beginTransaction();
            $user = $this->createAdvisor($prev_page_data);
            // $user->primary_region_id = $request->primary_region_id;
            $user->location_postcode_id = $request->location_postcode_id;
            $user->service_offered_id = $request->service_offered_id;
            $user->fund_size_id = $request->fund_size_id;
            $user->save();

            $billing = $user->billing_info;
            if( empty($billing) ){
                $billing = new AdvisorBillingInfo();
            }
            $billing->advisor_id = $user->id;
            $billing->contact_name = $request->contact_name;
            $billing->billing_address_line_one = $request->billing_address_line_one;
            $billing->billing_address_line_two = $request->billing_address_line_two;
            $billing->billing_town = $request->billing_town;
            $billing->billing_post_code = $request->billing_post_code;
            $billing->billing_country = $request->billing_country;
            $billing->billing_company_name = $request->billing_company_name;
            $billing->billing_company_fca_number = $request->billing_company_fca_number;
            $billing->save();
            
            $this->guard()->login($user);
            if( !isset($request->office_manager_id)){
                (new MatchRating($user))->handel();
            }else{
                $this->guard("office_manager")->login($user);
            }
            $error_message = "";
            try{
                event(new Registered($user));
            }catch(Exception $e){
                $error_message = $this->getError($e);
            }
            DB::commit();
            Session::forget('signup_step1');
            return redirect()->route('advisor.email.not_verify')->with("message", "Congratulation! Your signup process has completed successfully.". $error_message ?? "");
        }catch(Exception $e){
            DB::rollBack();
            return back()->with('error', $this->getError($e))->withInput();
        }
    }

    /**
     * Validate Step2 Data For Advisor
     */
    protected function validateStep2Data($request){
        $this->validate($request, [
            // 'primary_region_id'             => ["required", "numeric", "min:1"],
            "location_postcode_id.*"        => ["required", "numeric"],
            "service_offered_id.*"          => ["required", "numeric", "min:1"],
            "fund_size_id"                  => ["required", "numeric", "min:1"],
            // Billing Info
            "contact_name"                  => ["required", "string", "min:2", "max:191"],
            "billing_company_name"          => ["nullable", "string", "min:2", "max:191"],
            "billing_company_fca_number"    => ["nullable", "string", "min:2", "max:191"],
            "billing_address_line_one"      => ["required", "string", "min:2", "max:191"],
            "billing_address_line_two"      => ["nullable", "string", "min:2", "max:191"],
            "billing_post_code"             => ['required', 'string', 'min:4', 'max:8'],
            "billing_town"                  => ['required', 'string', 'min:2', 'max:191'],
            "billing_country"               => ['required', 'string', 'min:2', 'max:191'],
        ]);
    }

    /**
     * Validate Step2 Office Manager Data
     */
    protected function validateStep2OfficeManagerData($request){
        $this->validate($request, [
            // Billing Info
            "contact_name"                  => ["required", "string", "min:2", "max:191"],
            "billing_company_name"          => ["nullable", "string", "min:2", "max:191"],
            "billing_company_fca_number"    => ["nullable", "string", "min:2", "max:191"],
            "billing_address_line_one"      => ["required", "string", "min:2", "max:191"],
            "billing_address_line_two"      => ["nullable", "string", "min:2", "max:191"],
            "billing_post_code"             => ['required', 'string', 'min:4', 'max:8'],
            "billing_town"                  => ['required', 'string', 'min:2', 'max:191'],
            "billing_country"               => ['required', 'string', 'min:2', 'max:191'],
        ]);
    }

    /**
     * Create Advisor
     * From Step 1 Data
     * Setp1 data get from Session
     */
    protected function createAdvisor(array $data){
        $user = new User();
        $user->first_name           = $data["first_name"] ?? null ;
        $user->last_name            = $data["last_name"] ?? null;
        $user->email                = $data["email"] ?? null;
        $user->password             = bcrypt($data["password"]);
        $user->telephone            = $data["telephone"] ?? null;
        $user->phone                = $data["phone"] ?? null;
        $user->personal_fca_number  = $data["personal_fca_number"] ?? null;
        $user->fca_status_date      = $data["fca_status_date"] ?? null;
        $user->profession_id        = $data["profession_id"] ?? null;
        $user->subscription_plan_id = $data["subscription_plan_id"] ?? null;
        $user->advisor_type_id      = $data["advisor_type_id"] ?? null;
        $user->address_line_one     = $data["address_line_one"] ?? null;
        $user->address_line_two     = $data["address_line_two"] ?? null;
        $user->town                 = $data["town"] ?? null;
        $user->post_code            = $data["post_code"] ?? null;
        $user->latitude             = $data['latitude'] ?? null;
        $user->longitude            = $data['longitude'] ?? null;
        $user->country              = $data["country"] ?? null;
        $user->save();

        $firm = new FirmDetails();
        $firm->advisor_id           = $user->id;
        $firm->profile_name         = $data["firm_name"] ?? null;
        $firm->profile_details      = $data["firm_details"] ?? null;
        $firm->firm_fca_number      = $data["firm_fca_number"] ?? null;
        $firm->firm_website_address = $data["firm_website_address"] ?? null;
        $firm->linkedin_id          = $data["linkedin_id"] ?? null;
        $firm->save();
        return $user; 
    }

    /**
     * Advisor Dashboard
     */
    public function dashboard(Request $request){
        $this->saveActivity($request, "Load Advisor Admin Panel");
        $advisor = $request->user();
        $office_namager_feature = $advisor->subscription_plan->office_manager ?? false;

        $dashboard_profile_text = TremsAndCondition::where("type", "Advisor Profile Page")->orderBy("id", "DESC")->first();
        $profile_url = "#";
        if(!$office_namager_feature){
            $profile_url = route('advisor.profile', ['profession' => Str::slug($advisor->profession->name), 'location' => str::slug($advisor->town) ?? "None", "id" => $advisor->id . '-' . Str::slug($advisor->first_name) . '-' . Str::slug($advisor->last_name) ]);
        }
        $params = [
            "nav"               => "dashboard",
            "profile_link"      => $profile_url,
            "monthly_profile_visit" => count($advisor->profile_visitor()),
            "total_profile_visit"   => count($advisor->profile_visitor("All")),
            "total_question"        => count($advisor->approve_advisor_questions),
            "total_testimonial"     => count($advisor->published_testimonials),
            "total_leads"           => count($advisor->leads),
            "no_of_auction"         => Auction::count(), 
            "dashboard_profile_text"=> $dashboard_profile_text->trems_and_condition ?? "",
            "office_namager_feature"=> $office_namager_feature,
            "my_advisors"           => User::where('office_manager_id', $advisor->id)->get(),
        ];
        return view("frontEnd.advisor.dashboard.index", $params);
    }

    /**
     * Advisor Profile
     */
    public function profile(Request $request){
        $advisor = $request->user();
        // Save Profile info
        $this->profileView($request, $advisor->id);
        $this->saveActivity($request, "View Profile");
        return $this->loadProfile($advisor);
    }

    /**
     * Advisor Profile
     */
    public function advisorProfile(Request $request){
        $id = explode("-", $request->name_id )[0];
        $advisor = User::leftJoin('advisor_questions as aq', "aq.advisor_id", "advisors.id")
        ->leftJoin('subscription_plans as sp', 'sp.id', '=', 'advisors.subscription_plan_id')
        ->where('advisors.id', $id)
        ->select('advisors.*', DB::raw(" IF(IF(aq.publication_status = 1, count(aq.id), 0) > profile_listing_star, profile_listing_star,  IF(aq.publication_status = 1, count(aq.id), 0)) as rating"))->firstOrFail();
        // Save Profile info
        $this->profileView($request, $advisor->id);
        return $this->loadProfile($advisor);
    }

    
    /**
     * Load Profile
     */
    protected function loadProfile($advisor){
        $params = [
            "advisor"       => $advisor,
            "nav"           => "advisor",
            "subNav"       => "advisor.profile_self",
        ];
        return view("frontEnd.advisor.profile.profile", $params);
    }

    /**
     * Edit Advisor Profile
     */
    public function editProfile(Request $request){
        $params = [
            'nav'               => 'advisor',
            'subNav'            => 'advisor.profile_update',
            "title"             => "Edit Advisor ",
            "form_url"          => route('advisor.profile_update'),
            "reasons"           => PrimaryReason::where('publication_status', true)->orderBy("position", "ASC")->get(),
            "advisor_types"     => AdvisorType::where("publication_status", true)->orderBy("name", "ASC")->get(),
            "subscription_plans"=> SubscriptionPlan::get(),
            "professions"       => Profession::where("publication_status", true)->orderBy("name", "ASC")->get(),
            "service_offers"    => ServiceOffer::where("publication_status", true)->orderBy("id", "ASC")->get(),
            "fund_sizes"        => FundSize::where("publication_status", true)->orderBy("min_fund", "ASC")->get(),
            "data"              => Auth::user(),
        ];
        $this->saveActivity($request, "Profile Edit Page Show");
        return view('frontEnd.advisor.profile.profile-update', $params);
    }

    /**
     * Update Advisor Profile
     */
    public function updateProfile(Request $request){
        
        try{
            $validator = Validator::make($request->all(),[
                "profession_id"     => ['required', 'numeric', 'min:1'],
                'first_name'        => ['required', 'string', 'max:191'],
                'last_name'         => ['nullable', 'string', 'max:191'],
                "phone"             => ['required', 'string', "min:8", "max:13"],
                'telephone'         => ['nullable', 'string', "min:8", "max:13"],
                "personal_fca_number"=> ['nullable', 'string', "min:2", "max:30"],
                "fca_status_date"   => ['nullable', 'date'],            
                
                "advisor_type_id.*" => ["required", "numeric"],
                "address_line_one"  => ['required', 'string', 'min:4', 'max:191'],
                "address_line_two"  => ['nullable', 'string', 'min:4', 'max:191'],
                "town"              => ['required', 'string', 'min:2', 'max:191'],
                "post_code"         => ['required', 'string', 'min:4', 'max:8'],
                "country"           => ['required', 'string', 'min:2', 'max:100'],
    
                "service_offered_id.*"          => ["required", "numeric", "min:1"],
                'primary_region_id'             => ["nullable", "numeric", "min:1"],
                "location_postcode_id.*"        => ["nullable", "numeric"],
            ]);
            if($validator->fails()){
                return back()->with('error', $validator->errors()->first());
            }

            $response =  (new Fetchify())->isValidPhone($request->phone);
            if( !$response["status"] ){
                return back()->withInput()->withErrors( ["phone" => $response["message"] ]);
            }
            $response =  (new Fetchify())->isValidPostCode($request->post_code);
            if( !$response["status"] ){
                return back()->withInput()->withErrors( ["post_code" => $response["message"] ]);
            }

            $data = User::find(Auth::user()->id);
            $data->profession_id = $request->profession_id;            
            $data->first_name = $request->first_name;            
            $data->last_name = $request->last_name; 
            $data->phone = $request->phone;
            $data->telephone = $request->telephone;
            $data->personal_fca_number = $request->personal_fca_number;
            $data->fca_status_date = $request->fca_status_date;
            $data->address_line_one = $request->address_line_one;
            $data->address_line_two = $request->address_line_two;
            $data->post_code = $request->post_code;
            $data->longitude = $request->longitude ;
            $data->latitude  = $request->latitude;
            $data->town = $request->town;
            $data->country = $request->country;
            $data->fund_size_id = $request->fund_size_id;
            $data->primary_region_id = $request->primary_region_id;
            $data->profile_brief = $request->profile_brief;
            $data->advisor_type_id = $request->advisor_type_id;
            $data->service_offered_id = $request->service_offered_id;
            $data->location_postcode_id = $request->location_postcode_id;
            $data->is_live          = $request->is_live;
            $data->image = $this->uploadImage($request, 'image', $this->advisor_image, null, null, $data->image);
            $data->save();
            $this->saveActivity($request, "Update Profile Information");
            return back()->with("success","Advisor basic information updated successfully");
        }catch(Exception $e){
            return back()->with('error', $this->getError($e));
        }
    }

    /**
     * Show Profile Visitor Info
     */
    public function profileVisitor(Request $request){
        if($request->ajax()){
            return $this->loadVisitorDataTable($request);
        }
        
        $params = [
            "nav"           => "dashboard",
            "pageTitle"     => "Your Profile Visit Information",
            "tableColumns"  => ['#', 'date', 'ip', 'browser', 'device', 'os', 'county', 'city'],
            "dataTableColumns" => ['index', 'date', 'ip', 'browser', 'device', 'os', 'country_code', 'city'],
        ];
        return view("frontEnd.table", $params);
    }

    /**
     * Load Visitor DataTable
     */
    public function loadVisitorDataTable($request){
        $advisor = $request->user();
        $data = $advisor->profile_visitor($request->type ?? "monthly");
        $system = System::first();
        return DataTables::of($data)
            ->addColumn('index', function(){ return ++$this->index; })
            ->editColumn('date', function($row) use($system){ return Carbon::parse($row->date)->format($system->date_format); })
            ->make(true);
    }


    /**
     * Edit Firm Info
     */
    public function editFirm(Request $request){
        $advisor = $request->user();
        $params = [
            "firm_details"  => $advisor->firm_details,
            "advisor"       => $advisor,
            "nav"           => "advisor",
            "subNav"       => "advisor.firm",
            "form_url"      => route('advisor.firm'),
        ];
        $this->saveActivity($request, "Firm Information Edit Page Open");
        return view("frontEnd.advisor.profile.firm", $params);
    }

    /**
     * Update Firm Info
     */
    public function updateFirm(Request $request){
        $validator = Validator::make($request->all(),[
            'profile_name'      => ['required', 'string', 'min:2', 'max:191'],
            'profile_details'   => ['required', 'string', 'min:2'],
            'firm_fca_number'   => ['required', 'string', 'min:2', 'max:191'],
            "firm_website_address" => ['required', "string", 'min:4']
        ],[
            'profile_details.required' => "About firm field is required."
        ]);
        $validator->validate();
        try{
            $firm = $request->user()->firm_details;
            $firm->profile_name = $request->profile_name;
            $firm->profile_details = $request->profile_details;
            $firm->firm_fca_number = $request->firm_fca_number;
            $firm->firm_website_address = $request->firm_website_address;
            $firm->linkedin_id = $request->linkedin_id;
            $firm->save();
            $this->saveActivity($request, "Update Firm Information");
            return back()->with("success", "Firm information updated successfully");
        }catch(Exception $e){
            return back()->with('error', $this->getError($e));
        }
    }

    /**
     * Edit Billing Info
     */
    public function editBillingInfo(Request $request){
        $advisor = $request->user();
        $params = [
            "advisor"       => $advisor,
            "billing_info"  => $advisor->billing_info,
            "nav"           => "advisor",
            "subNav"       => "advisor.billing_info",
            "form_url"      => route('advisor.billing_info'),
        ];
        $this->saveActivity($request, "Edit Billing Information Page Open");
        return view("frontEnd.advisor.profile.billing-info", $params);
    }

    /**
     * Update Billing Info
     */
    public function updateBillingInfo(Request $request){
        $validator = Validator::make($request->all(),[
            'billing_address_line_one'      => ['required', 'string', 'min:2', 'max:191'],
            'billing_address_line_two'      => ['nullable', 'string', 'min:2', 'max:191'],
            'billing_town'                  => ['required', 'string', 'min:2', 'max:191'],
            'billing_post_code'             => ['required', 'string', 'min:2', 'max:191'],
            'billing_country'               => ['required', 'string', 'min:2', 'max:191'],
            'billing_company_name'          => ['nullable', 'string', 'min:2', 'max:191'],
            'billing_company_fca_number'    => ['required', 'string', 'min:2', 'max:191'],
        ]);
            
        try{
            if($validator->fails()){
                return back()->with('error', $validator->errors()->first());
            }
            
            $url = "http://api.postcodes.io/postcodes/$request->billing_post_code/validate";
            $client = new Client();
            $res = $client->get($url);
            $data = json_decode($res->getBody());
            
            if($data->result == true){
                $url = "http://api.postcodes.io/postcodes/$request->billing_post_code";
                $client = new Client();
                $res = $client->get($url);
                $data = json_decode($res->getBody());
                $latitude = $data->result->latitude;
                $longitude  = $data->result->longitude;            
            }else{
                return back()->with('error',"Invalid postcode")->withInput();
            }

            $data = $request->user()->billing_info;
            $data->billing_address_line_one = $request->billing_address_line_one;
            $data->billing_address_line_two = $request->billing_address_line_two;
            $data->billing_town = $request->billing_town;
            $data->billing_post_code = $request->billing_post_code;
            $data->billing_country = $request->billing_country;
            $data->billing_company_name = $request->billing_company_name;
            $data->billing_company_fca_number = $request->billing_company_fca_number;
            $data->save();
            $this->saveActivity($request, "Update Billing Information");
            return back()->with("Firm information updated successfully");
        }catch(Exception $e){
            return back()->with('error', $this->getError($e));
        }
    }

    /**
     * Advisor Subscription Controller
     */
    public function subscriptionPlans(Request $request){
        $params = [
            'plans'         => SubscriptionPlan::where('publication_status', true)->get(),
            "plan_types"    => SubscriptionPlan::join("professions", "professions.id", "=", "subscription_plans.profession_id")->where('subscription_plans.publication_status', true)->select("subscription_plans.*", "professions.name as profession_name")->groupBy("profession_id")->get(),
            "advisor_blogs" => AdvisorBlog::where("publication_status", 1)->orderBy('id', 'desc')->paginate(10),
            "advisor_quick_links" => AdvisorQuickLink::where("publication_status", 1)->orderBy('id', 'desc')->paginate(10),
            "faqs"          => AdvisorFaq::orderBy("faq_sl", "ASC")->get(),
        ]; 
        return view('frontEnd.plan.subscriptionPlan', $params);
    }

    /**
     * Choose Subscription 
     */
    public function subscriptionPlanChoose(Request $request){
        $plan = SubscriptionPlan::where('id', $request->id)->first();
        if( empty($plan) ){
            abort(401);
        }
        Session::put("subscription_plan_id", $request->id);
        return redirect()->to(route('register') . '?plan='.$plan->name);
    }

    /**
     * Contact With Advisor Page Show
     */
    public function showContactForm(Request $request){
        $this->profileView($request, $request->advisor_id);
        $params = [
            "service_offers"    => ServiceOffer::where('publication_status', 1)->get(),
            "fund_sizes"        => FundSize::where('publication_status', 1)->get(),
            "form_url"          => route('contact_advisor', [$request->advisor_id]),
            "total_advisor"     => User::where("status", 'active')->count(),
            "lead"              => Session::has("lead") ? Session::get("lead") : Null,
        ];
        // dd($params);
        return view('frontEnd.contact.advisor-contact', $params);
    }

    /**
     * Contact with Advisor 
     */
    public function contact(Request $request){   
        $leads = Session::has('lead') ? Session::pull('lead') : new Leads();
        if( empty($leads->name) ){
            $this->validate($request, [
                'name'      => ['required', 'string', 'min:2', 'max:191'],
                'last_name' => ['nullable', 'string', 'min:2', 'max:191'],
                'email'     => ['required', 'email', 'min:2', 'max:191'],
                'phone'     => ['required', 'string', 'min:8', 'max:13'],
                'post_code' => ['required', 'string', 'min:8', 'max:13'],
                'fund_size_id'=> ['required','numeric', 'min:1'],
                'post_code' => ['required', 'string', 'min:4', 'max:16'],
                'communication_type' => ['required', 'string', 'min:2', 'max:191']
            ]);
        }
        $leads->name = $leads->name ?? $request->name;
        $leads->last_name = $leads->last_name ?? $request->last_name;
        $leads->email = $leads->email ?? $request->email;
        $leads->phone = $leads->phone ?? $request->phone;
        $leads->question = $leads->question ?? $request->question;
        $leads->post_code = $leads->post_code ?? $request->post_code;
        $leads->date = $leads->date ?? date('Y-m-d');
        $leads->fund_size_id = $request->fund_size_id;
        $leads->invite_advisors = (array) $request->advisor_id;
        $leads->service_offer_id = $request->service_offer_id;
        $leads->communication_type = $request->communication_type;
        $leads->type = "search local";
        $leads->save();
        // Add Comunication Message
        event(new LeadAssign($leads, "search local"));
        $this->addCommunicationMessage($leads->toArray(), "Congratulations! You just received a new lead", $request->advisor_id);
        return redirect()->route('contact_successfully');
    }

    /**
     * Contact Successfully
     */
    public function contactSuccessfully(){
        return view('frontEnd.contact.successfully');
    }

    /**
     * Match Rating
     */
    public function matchRating(Request $request){
        $advisor = $request->user();
        $reason_specific_rating_list = User::where('primary_region_id', $advisor->primary_region_id)->select('specific_rating')->get();
        $specific_rating_arr = [];
        foreach($reason_specific_rating_list as $list_arr){
            foreach($list_arr->specific_rating as $service_offer => $rating){
                $specific_rating_arr[$service_offer] = ($specific_rating_arr[$service_offer] ?? 0) + $rating;
            }
        }
        foreach($specific_rating_arr as $key => $value){
            $specific_rating_arr[$key] = $value / count($reason_specific_rating_list);
        }

        $params = [
            'nav'           => 'match_rating',
            'advisor'       => $request->user(),
            'service_offers'=> ServiceOffer::where('publication_status', 1)->get(),
            "specific_rating_arr" => $specific_rating_arr,
        ];
        $this->saveActivity($request, "View Match Rating");
        return view('frontEnd.advisor.profile.match-rating', $params);
    }

    /**
     * Match Rating
     */
    public function marketingProfile(Request $request){
        $advisorMarketing = AdvisorMarketing::whereHas('serviceOffer', function($qry)use($request){
            if( !empty($request->service) ){
                $qry->where('name', $request->service);
            }else{
                $qry->where('name', 'Investment & Savings');
            }
        })->get();
        
        $params = [
            'nav'           => 'marketing_profile',
            'advisor'       => $request->user(),
            'service_offers'=> ServiceOffer::where('publication_status', 1)->get(),
            'advisor_marketings'=> $advisorMarketing,
        ];
        $this->saveActivity($request, "View Marketing Profile");
        return view('frontEnd.advisor.profile.marketing-profile', $params);
    }


    /**
     * Advisor Not Verified Page
     */
    public function showEmailNotVerifyPage(Request $request){
        if( $request->user()->hasVerifiedEmail() ){
            return redirect()->to(route('advisor.dashboard'));
        }
        return view('frontEnd.advisor.email.not-verify',['advisor' => $request->user()]);
    }

    // Sent Verification Email
    public function sendVerificationEmail(Request $request){
        
        try{
            $advisor = $request->user();
            $advisor->notify(new EmailVerification($advisor));
            return back()->with('message','Verification email sent successfully. Please check your email.');
        }catch(Exception $e){
            return back()->with('message','Sorry! Failed to sent verification email. Please contact with admin.');
        }
    }


    /**
     * Change Password Page
     */
    public function changePasswordPage(Request $request){
        $params = [
            'nav'           => "advisor",
            "subNav"        => "advisor.password_change",
            'data'          => $request->user(),
            "form_url"      => route('advisor.password_change'),
        ];
        $this->saveActivity($request, "Advisor Change Password Page Show");
        return view('frontEnd.advisor.profile.change-password', $params );
    }

    /**
     * Password Changed
     */
    public function changePassword(Request $request){
        $this->validate($request, [
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);
        $advisor = $request->user();
        $advisor->password = bcrypt($request->password);
        $advisor->save();
        $this->saveActivity($request, "Advisor Password Changed");
        return back()->with('success', "Password changed successfully");
    }

}
