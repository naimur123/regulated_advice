<?php

namespace App\Http\Controllers\Auth;

use App\AdvisorType;
use App\FirmDetails;
use App\Http\Components\Classes\Fetchify;
use App\Http\Controllers\Controller;
use App\Profession;
use App\Providers\RouteServiceProvider;
use App\SubscriptionPlan;
use App\TremsAndCondition;
use App\User;
use Exception;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\View\View
     */
    public function showRegistrationForm(Request $request)
    {
        if( Session::has('subscription_plan_id') && $request->plan){
            $plan_id = $request->plan ? Session::get('subscription_plan_id') :Session::pull('subscription_plan_id');
            $subscription_plan = SubscriptionPlan::where('id', $plan_id)->first();
            $params = [
                "form_url"          => route('register'),
                "advisor_types"     => AdvisorType::where("publication_status", true)->orderBy("name", "ASC")->get(),
                "subscription_plan" => $subscription_plan,
                "professions"       => Profession::where("publication_status", true)->orderBy("name", "ASC")->get(),
                "trems_and_condition" => TremsAndCondition::where('type', 'signup')->orWhere('type', 'Sign up')->first(),
            ];
            if($subscription_plan->office_manager){
                return view('frontEnd.office-manager.office-manager-register', $params);
            }else{
                return view('frontEnd.advisor.register', $params);
            }
        }
        return redirect()->route('advisor.subscription_plan');  
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        if( empty($request->office_manager_id) ){
            $this->validator($request->all())->validate();
        }else{
            $this->validatorForOfficeManager($request->all())->validate();
        }
        try{
            $fetchify = new Fetchify();
            $response = $fetchify->isValidPhone($request->phone);
            if(!$response["status"]){
                return redirect()->back()->withInput()->withErrors( ["phone" => $response["message"] ]);
            }
            $response = $fetchify->isValidEmail($request->email);
            if(!$response["status"]){
                return redirect()->back()->withInput()->withErrors(["email" => $response["message"]]);
            }
            $response = $fetchify->isValidPostCode($request->post_code);
            if($response["status"]){
                $latitude = "51.509865";
                $longitude  = "-0.118092";
            }else{
                return redirect()->back()->withInput()->withErrors(["post_code", $response["message"]]);
            }
            
            $input_data = $request->except('_token');
            $input_data['latitude'] = $latitude;
            $input_data['longitude'] = $longitude;
            Session::put("signup_step1", $input_data);
            return redirect()->route('advisor.register_setp2');

        }catch(Exception $e){
            return back()->with('error', $this->getError($e))->withInput();
        }
    }
    

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name'        => ['required', 'string', 'min:2', 'max:191'],
            'last_name'         => ['nullable', 'string', 'min:1', 'max:191'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:advisors'],
            'password'          => ['required', 'string', 'min:4'],
            "phone"             => ['required', 'string', "min:8", "max:16"],
            'telephone'         => ['nullable', 'string', "min:8", "max:16"],
            "personal_fca_number"=> ['nullable', 'string', "min:2", "max:30"],
            "fca_status_date"   => ['nullable', 'date'],
            "profession_id"     => ['required', 'numeric', 'min:1'],
            "subscription_plan_id"=> ['required', 'numeric', 'min:1'],
            "advisor_type_id.*" => ["required", "numeric"],
            "address_line_one"  => ['required', 'string', 'min:4', 'max:191'],
            "address_line_two"  => ['nullable', 'string', 'min:4', 'max:191'],
            "town"              => ['required', 'string', 'min:2', 'max:191'],
            "post_code"         => ['required', 'string', 'min:4', 'max:8'],
            "country"           => ['required', 'string', 'min:2', 'max:100'],
            "firm_name"         => ['nullable', 'string', 'min:2', 'max:191'],
            "firm_details"      => ['nullable', 'string', 'min:2', 'max:191'],
            "firm_fca_number"   => ['required', 'string', 'min:2', 'max:191'],
            "firm_website_address"=> ['required', 'string', 'min:2', 'max:191'],
            "linkedin_id"       => ['nullable', 'string', 'min:2', 'max:191'],
        ]);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validatorForOfficeManager(array $data)
    {
        return Validator::make($data, [
            'first_name'        => ['required', 'string', 'min:2', 'max:191'],
            'last_name'         => ['nullable', 'string', 'min:1', 'max:191'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:advisors'],
            'password'          => ['required', 'string', 'min:4'],
            "phone"             => ['required', 'string', "min:8", "max:16"],
            "subscription_plan_id"=> ['required', 'numeric', 'min:1', "exists:subscription_plans,id"],
            
            "address_line_one"  => ['required', 'string', 'min:4', 'max:191'],
            "address_line_two"  => ['nullable', 'string', 'min:4', 'max:191'],
            "town"              => ['required', 'string', 'min:2', 'max:191'],
            "country"           => ['required', 'string', 'min:2', 'max:100'],
            "post_code"         => ['required', 'string', 'min:4', 'max:8'],

            "firm_name"         => ['required', 'string', 'min:2', 'max:191'],
            "firm_fca_number"   => ['required', 'string', 'min:2', 'max:191'],
            "firm_website_address"=> ['required', 'string', 'min:2', 'max:191'],
            "linkedin_id"       => ['nullable', 'string', 'min:2', 'max:191'],
        ]);
    }

    

}
