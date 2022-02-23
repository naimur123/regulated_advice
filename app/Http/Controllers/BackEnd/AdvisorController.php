<?php

namespace App\Http\Controllers\BackEnd;

use App\AdvisorBillingInfo;
use App\AdvisorCompliance;
use App\AdvisorQuestion;
use App\AdvisorType;
use App\Events\Subscribe;
use App\FirmDetails;
use App\FundSize;
use App\Http\Components\Classes\Fetchify;
use App\Http\Controllers\Controller;
use App\Interview;
use App\PrimaryReason;
use App\Profession;
use App\PromotionalAdvisor;
use App\ServiceOffer;
use App\SubscribePrimaryReason;
use App\SubscriptionPlan;
use App\System;
use App\Testimonial;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;


class AdvisorController extends Controller
{

    /**
     * Get Table Column List
     */
    private function getColumns(){
        $columns = ['#', "date", "first_name", 'email', 'assign_number', "mobile_number", 'personal_fca_no', 'advisor_type', 'profile_name' ,'primaty_reason', 'area_covered', 'plan', 'subscribed', 'status', "live", 'email_verify', 'action',];
        return $columns;
    }

    /**
     * Get DataTable Column List
     */
    private function getDataTableColumns(){
        $columns = ['index', "date", 'name', 'email', 'telephone', "phone", 'personal_fca_number', 'profession', 'profile_name', 'primary_reason', 'area_covered', 'subscription', 'subscribe', 'status', "live", 'email_verify','action'];
        return $columns;
    }



    /**
     * Get Table Column List
     */
    private function getColumns2(){
        $columns = ['#', "date", "first_name", 'email', 'assign_number', "mobile_number", 'personal_fca_no', 'advisor_type', 'profile_name', 'primaty_reason', 'area_covered', 'subscribe_area_covered','plan', "non_specific_rating", 'subscribed',  'status', "live", 'email_verify', 'action',];
        return $columns;
    }

    

    /**
     * Get DataTable Column List
     */
    private function getDataTableColumns2(){
        $columns = ['index', "date", 'name', 'email', 'telephone', "phone", 'personal_fca_number', 'profession', 'profile_name', 'primary_reason', 'area_covered', 'subscribe_area_covered','subscription', "non_specific_rating", 'subscribe', 'status', "live", 'email_verify','action'];
        return $columns;
    }

    /**
     * Get Current Table Model
     */
    private function getModel(){
        return new User();
    }

    /**
     * Show advisor List  without Archive
     */
    public function index(Request $request){        
        if( $request->ajax() ){
            return $this->getDataTable($request, "list");
        }
        $this->saveActivity($request, "View Advisor List"); 
        $params = [
            'nav'               => 'advisor',
            'subNav'            => 'advisor.list',
            'tableColumns'      => $this->getColumns(),
            'dataTableColumns'  => $this->getDataTableColumns(),
            'tableColumns2'      => $this->getColumns2(),
            'dataTableColumns2'  => $this->getDataTableColumns2(),
            'dataTableUrl'      => URL::current().'?subscribe=0',
            'dataTableUrl2'     => URL::current().'?subscribe=1',
            'create'            => AccessController::checkAccess('advisor_create') ? route('advisor.create') : false,
            'pageTitle'         => 'New Advisors List',
            'pageTitle2'        => 'Subscribed Advisors List',
            'tableStyleClass'   => 'bg-success',
            'tableStyleClass2'  => 'bg-primary',
            'modalSizeClass'    => "modal-lg",
        ];
        return view('backEnd.advisor.table', $params);
    }

    /**
     * Show Deleted advisor List
     */
    public function deletedAdvisorList(Request $request){        
        if( $request->ajax() ){
            return $this->getDataTable($request, "deleted");
        }
        $this->saveActivity($request, "View Deleted Advisor List"); 
        $params = [
            'nav'               => 'advisor',
            'subNav'            => 'advisor.archived_list',
            'tableColumns'      => $this->getColumns(),
            'dataTableColumns'  => $this->getDataTableColumns(),
            'pageTitle'         => 'Deleted Advisor List',
            'tableStyleClass'   => 'bg-success',
        ];
        return view('backEnd.table', $params);
    }

    /**
     * Show Filter advisor List
     */
    public function filterAdvisor(Request $request){        
        if( $request->ajax() ){
            return $this->getDataTable($request, "list");
        }
        
        $params = [
            'nav'               => 'advisor',
            'subNav'            => 'advisor.list',
            'tableColumns'      => $this->getColumns(),
            'dataTableColumns'  => $this->getDataTableColumns(),
            'dataTableUrl'      => Null,           
            'pageTitle'         => 'Filter Advisor List',
            'tableStyleClass'   => 'bg-success',
            'tableStyleClass2'   => 'bg-primary'
        ];
        return view('backEnd.table', $params);
    }

    /**
     * Create New Admin
     */
    public function create(Request $request){
        $this->saveActivity($request, "Advisor Creation Page Open"); 
        $params = [
            'nav'               => 'advisor',
            'subNav'            => 'advisor.create',
            "title"             => "Create Advisor",
            "form_url"          => route('advisor.create'),
            "reasons"           => PrimaryReason::where('publication_status', true)->orderBy('position', 'ASC')->get(),
            "subscribe_reasons" => SubscribePrimaryReason::where('publication_status', true)->orderBy('position', 'ASC')->get(),
            "advisor_types"     => AdvisorType::where("publication_status", true)->orderBy("name", "ASC")->get(),
            "subscription_plans"=> SubscriptionPlan::where("office_manager", false)->get(),
            "professions"       => Profession::where("publication_status", true)->orderBy("name", "ASC")->get(),
            "service_offers"    => ServiceOffer::where("publication_status", true)->orderBy("id", "ASC")->get(),
            "fund_sizes"        => FundSize::where("publication_status", true)->orderBy("min_fund", "ASC")->get(),
            "edit"              => false, 
        ]; 
        return view('backEnd.advisor.create', $params);
    }

    /**
     * Store advisor Information
     */
    public function store(Request $request){
        $validator_data = [
            'profession_id'     => ['required','numeric','min:1'],
            "first_name"        => ['required','string','min:2', 'max:100'],
            "last_name"         => ['nullable','string','min:1', 'max:100'],
            "email"             => ['required','email', $request->id == 0 ? 'unique:advisors' : null],
            "password"          => [ $request->id == 0 ? 'required' : 'nullable','string','min:4', 'max:100'],
            "phone"             => ['required','string','min:8', 'max:16'],
            "telephone"         => ['nullable','string','min:8', 'max:16'],
            "personal_fca_number"=>['nullable','string','min:2', 'max:100'],
            "address_line_one"  => ['required','string','min:2', 'max:191'],
            "address_line_two"  => ['nullable','string','min:1', 'max:191'],
            "post_code"         => ['required','string','min:4', 'max:8'],
            "town"              => ['nullable','string','min:2', 'max:100'],
            "country"           => ['required','string','min:2', 'max:100'],
            "subscription_plan_id"=> ['required','numeric','min:1'],
            "fund_size_id"      => ['required','numeric','min:1'],
            "primary_region_id" => ['required','numeric','min:1'],
            "status"            => ["required", "string", "min:4", "max:20"],
            "advisor_type_id.*"   => ["required", "numeric"],
            "service_offered_id.*" => ["required", "numeric"],
            "location_postcode_id.*" => ["required", "numeric"],

        ];
        Validator::make($request->all(), $validator_data)->validate();
        try{
            $latitude = $longitude = null;
            $response =  (new Fetchify())->isValidEmail($request->email);
            if( !$response["status"] ){
                return back()->withInput()->withErrors( ["email" => $response["message"] ]);
            }
            $response =  (new Fetchify())->isValidPhone($request->phone);
            if( !$response["status"] ){
                return back()->withInput()->withErrors( ["phone" => $response["message"] ]);
            }
            $response =  (new Fetchify())->isValidPostCode($request->post_code);
            if( !$response["status"] ){
                return back()->withInput()->withErrors( ["post_code" => $response["message"] ]);
            }
            
            DB::beginTransaction();
            if( $request->id == 0 ){
                $data = $this->getModel();
                $data->created_by = $request->user()->id;
                $message = 'Advisor Information added Successfully';
            }else{
                $message = 'Advisor Information updated Successfully';
                $data = $this->getModel()->withTrashed()->find($request->id);
                $data->updated_by = $request->user()->id;
            }

            $data->profession_id = $request->profession_id;            
            $data->first_name = $request->first_name;            
            $data->last_name = $request->last_name;            
            $data->email = $request->email;
            $data->phone = $request->phone;
            $data->telephone = $request->telephone;
            $data->view_telephone_no = $request->view_telephone_no ?? 0;
            $data->personal_fca_number = $request->personal_fca_number;
            $data->fca_status_date = $request->fca_status_date;
            $data->password = !empty($request->password) ? bcrypt($request->password) : $data->password;
            $data->address_line_one = $request->address_line_one;
            $data->address_line_two = $request->address_line_two;
            $data->post_code = $request->post_code;
            $data->town = $request->town;
            $data->country = $request->country;
            $data->subscription_plan_id = $request->subscription_plan_id;
            $data->fund_size_id = $request->fund_size_id;
            $data->primary_region_id = $request->primary_region_id;
            $data->subscribe_primary_region_id = $request->subscribe_primary_region_id;
            $data->status = $request->status;
            $data->is_live = $request->is_live;
            $data->terms_and_condition_agree_date = $request->terms_and_condition_agree_date;
            $data->no_of_subscription_accounts = $request->no_of_subscription_accounts;
            $data->advisor_type_id = $request->advisor_type_id;
            $data->service_offered_id = $request->service_offered_id;
            $data->location_postcode_id = $request->location_postcode_id;
            $data->subscribe_location_postcode_id = $request->subscribe_location_postcode_id;
            $data->latitude = $request->latitude;
            $data->longitude = $request->longitude;
            $data->image = $this->uploadImage($request, 'image', $this->advisor_image, null, null, $data->image);
            $data->save();
            $this->saveFirmInfo($request, $data);
            $this->saveBillingInfo($request, $data);
            $this->success($message);
            DB::commit();
            try{
                if($request->id == 0){
                    event(new Registered($data));
                }
            }catch(Exception $e){
                //
            }
        }catch(Exception $e){
            DB::rollBack();
            return back()->with("error", $this->getError($e))->withInput();
        }

        $this->saveActivity($request, "Add New Advisor", $data); 
        return back()->with ("success",  $request->id == 0 ? "Advisor Basic Information Added Successfully" : "Advisor Basic Information Updated Successfully");
    }

    /**
     * Save Firm Info
     */
    public function saveFirmInfo($request, $advisor){
        $data = $advisor->firm_details;
        if(empty($data)){
            $data = new FirmDetails();            
            $data->created_by = $request->created_by;
        }else{
            $data->updated_by = $request->updated_by;
        }
        $data->advisor_id = $advisor->id;
        $data->profile_name = $request->profile_name;
        $data->profile_details = $request->profile_details;
        $data->firm_fca_number = $request->firm_fca_number;
        $data->firm_website_address = $request->firm_website_address;
        $data->linkedin_id = $request->linkedin_id;
        $data->save();
        $this->saveActivity($request, "Save Advisor Firm Info", $data); 
    }

    /**
     * Billing Info
     */
    public function saveBillingInfo($request, $advisor){
        $data = $advisor->billing_info;
        if(empty($data)){
            $data = new AdvisorBillingInfo();
            $data->created_by = $request->created_by;
        }else{
            $data->updated_by = $request->updated_by;
        }
        $data->advisor_id = $advisor->id;
        $data->contact_name = $request->contact_name ?? "";
        $data->billing_address_line_one = $request->billing_address_line_one;
        $data->billing_address_line_two = $request->billing_address_line_two;
        $data->billing_town = $request->billing_town;
        $data->billing_post_code = $request->billing_post_code;
        $data->billing_country = $request->billing_country;
        $data->billing_company_name = $request->billing_company_name;
        $data->billing_company_fca_number = $request->billing_company_fca_number;
        $data->save();
        $this->saveActivity($request, "Save Advisor Billing Info", $data); 
    }

    /**
     * Edit advisor Info
     */
    public function edit(Request $request){
        $advisor = $this->getModel()->withTrashed()->find($request->id);
        
        $params = [
            'nav'               => 'advisor',
            'subNav'            => 'advisor.create',
            "title"             => "Edit Advisor",
            "form_url"          => route('advisor.create'),
            "reasons"           => PrimaryReason::where('publication_status', true)->orderBy("position", "ASC")->get(),
            "subscribe_reasons" => SubscribePrimaryReason::where('publication_status', true)->orderBy('position', 'ASC')->get(),
            "advisor_types"     => AdvisorType::where("publication_status", true)->orderBy("name", "ASC")->get(),
            "subscription_plans"=> SubscriptionPlan::where("office_manager", false)->get(),
            "professions"       => Profession::where("publication_status", true)->orderBy("name", "ASC")->get(),
            "service_offers"    => ServiceOffer::where("publication_status", true)->orderBy("id", "ASC")->get(),
            "fund_sizes"        => FundSize::where("publication_status", true)->orderBy("min_fund", "ASC")->get(),
            "data"              => $advisor,
            "edit"              => true,
        ]; 
        $this->saveActivity($request, "Advisor Edit Page Open", $advisor);
        return view('backEnd.advisor.create', $params);
    }

    /**
     * Subscribe
     */
    public function subscribe(Request $request){
        try{
            $advisor = $this->getModel()->find($request->id);
            $advisor->subscribe = $request->subscribe;
            $advisor->save();
            if($request->subscribe){
                event(new Subscribe($advisor));
            }
            $this->saveActivity($request, "Advisor subscribed status changed", $advisor);
            $this->success('Advisor subscribed status changed successfully');
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Make the selected advisor As Archive
     */
    public function archive(Request $request){
        try{
            
            $data = $this->getModel()->withTrashed()->find($request->id);
            $data->delete();
            $this->success('Successfully Archived');
            $this->saveActivity($request, "Advisor Archived", $data);
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Make the selected advisor As Active from Archive
     */
    public function restore(Request $request){
        try{
            
            $data = $this->getModel()->withTrashed()->find($request->id);
            $data->restore();
            $this->success(' Advisor restored successfully');
            $this->saveActivity($request, "Advisor Restore", $data);
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Permanent Delete of an  advisor
     */
    public function delete(Request $request){
        try{
            $data = $this->getModel()->withTrashed()->find($request->id);
            AdvisorBillingInfo::where('advisor_id', $data->id)->delete();
            Testimonial::where('advisor_id', $data->id)->forceDelete();
            AdvisorQuestion::where('advisor_id', $data->id)->forceDelete();
            AdvisorCompliance::where('advisor_id', $data->id)->delete();           
            Interview::where('advisor_id', $data->id)->delete();
            
            $this->saveActivity($request, "Advisor deleted", $data);
            $data->forceDelete();
            $this->success(' Advisor deleted successfully');
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Show Archive  Advisor List
     */
    public function archiveList(Request $request){
        
        if( $request->ajax() ){
            return $this->getDataTable($request, 'archive');
        }
        $this->saveActivity($request, "View Advisor Archive List");
        $params = [
            'nav'               => 'advisor' ,
            'subNav'            => 'advisor.archive_list',
            'tableColumns'      => $this->getColumns(),
            'dataTableColumns'  => $this->getDataTableColumns(),
            'dataTableUrl'      => Null,
            'pageTitle'         => ' Advisor Archive List',
            'tableStyleClass'   => 'bg-success'
        ];
        return view('backEnd.advisor.table', $params);
    }

    /**
     * Send Verification Email
     */
    public function sendVerificationEmail(Request $request){
        try{
            $advisor = $this->getModel()->withTrashed()->find($request->id);
            $advisor->sendEmailVerificationNotification();
            $this->success("Verification email has sent successfully");
            $this->saveActivity($request, "Sent Email Verification Link", $advisor);
            $this->button = true;
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Make Email Verify
     */
    public function emailVerify(Request $request){
        try{
            $advisor = $this->getModel()->withTrashed()->find($request->id);
            $advisor->email_verified_at = Carbon::now();
            $advisor->save();
            $this->success("Email verified successfully");
            $this->saveActivity($request, "Make Email as Verified", $advisor);
            $this->button = true;
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Change Password Page
     */
    public function changePasswordPage(Request $request){
        $advisor = User::find($request->id);
        
        $params = [
            'title' => "Change Password",
            "form_url" => route('advisor.change_password', ['id' => $request->id]),
            'data'  => $advisor,
        ];
        return view('backEnd.advisor.change-password', $params );
    }

    /**
     * Password Changed
     */
    public function changePassword(Request $request){
        $validate = Validator::make($request->all(),[
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);
        if($validate->fails()){
            $this->message = $this->getValidationError($validate);
            return $this->output();
        }
        $advisor = User::find($request->id);
        $advisor->password = bcrypt($request->password);
        $advisor->save();
        $this->saveActivity($request, "Advisor Password Change", $advisor);
        $this->success("Password has been changed successfully");
        return $this->output();
    }

    /**
     * Assign Advisor under Office Manager Page Showw
     */
    public function assignOfficeManagerPage(Request $request){
        $advisor = User::find($request->id);
        $office_managers = User::whereHas("subscription_plan", function($qry){
            $qry->where("office_manager", true);
        })->where("office_manager_id", null)->get();
        
        $params = [
            'title'                 => "Assign Advisor Under Office Manager",
            "form_url"              => route('advisor.assign_office_manager', [$request->id]),
            "office_manager_list"   => $office_managers,
            'data'                  => $advisor,
        ];
        return view('backEnd.advisor.assign-advisor-under-office-manager', $params );
    }

    /**
     * Assign Advisor under Office Manager
     */
    public function assignOfficeManager(Request $request){
        $office_manager = User::find($request->office_manager_id);
        $max_advisor_allow = $office_manager->subscription_plan->max_advisor ?? 0;
        $current_advisor_profile = count($office_manager->advisor_profiles());
        if($max_advisor_allow <= $current_advisor_profile ){
            $this->message = "Your advisor add limit over. Your plan allow max ".$max_advisor_allow . ' advisor';
            return $this->output();
        }

        $advisor = User::find($request->id);
        $advisor->office_manager_id = $request->office_manager_id;
        $advisor->save();
        $this->saveActivity($request, "Assign Under Office Manager", $advisor);
        $this->success("Assigned Under Office Manager successfully");
        return $this->output();
    }


    /**
     * Get advisor DataTable
     * Type will be list & archive
     * Default Type is list
     */
    protected function getDataTable($request, $type = 'list'){
        $subscribe = true;
        if( isset($request->subscribe) ){
            $subscribe = $request->subscribe;
        }
        if( $type == "list" ){
            $data = $this->getModel();
        }else{
            $data = $this->getModel()->onlyTrashed();
        }
        
        // Type Filter
        if( $type == "list" ){
            if( isset($request->type) && $request->type == "filter"){          
                $data = $data->where('advisors.created_at', 'like', $request->type_value.'%');
            }
            if( isset($request->type) && $request->type == "plan"){
                $data = $data->whereHas('subscription_plan', function($qry) use($request){
                    $qry->where('name', $request->type_value);
                });
            }    
            if( !$request->type ){
                $data = $data->where('subscribe', $subscribe);
            }
        }

        $data = $data->leftjoin('primary_reasons', 'primary_reasons.id', '=','advisors.primary_region_id')
            ->where(function($qry){
                $qry->whereHas("subscription_plan", function($qry){
                    $qry->where("office_manager", false);
                })->orWhere("office_manager_id", "!=", null);
            })
            ->select('advisors.*', 'primary_reasons.name as primary_reason')
            ->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->get();

        $system = System::first();
        return DataTables::of($data)
            ->addColumn('index', function(){ return ++$this->index; })
            ->addColumn('subscription', function($row){ return $row->subscription_plan->name ?? "N/A"; })
            ->addColumn('name', function($row){ return ($row->first_name ?? "" . ' '. $row->last_name ?? ""); })
            ->addColumn('subscribe_primaty_reason', function($row){ return substr($row->subscribe_primary_reason(), 0, 60); })
            ->addColumn('date', function($row)use($system){ return Carbon::parse($row->created_at)->format($system->date_format); })
            ->addColumn('area_covered', function($row){
                if( !empty($row->location_postcode_id) && is_array($row->location_postcode_id)){
                    return substr($row->postcodesCovered(), 0, "70").'....';
                }
                return  "N/A";
            })
            ->addColumn('subscribe_area_covered', function($row){
                if( !empty($row->subscribe_location_postcode_id) && is_array($row->subscribe_location_postcode_id)){
                    /*return substr($row->postcodesCovered(null, true), 0, 60) . '.......';*/
                    return wordwrap($row->postcodesCovered(null, true),60,"<br>\n");
                }
                return  "N/A";
            })
            ->addColumn('action', function($row) use($type, $subscribe){  
                $li = "";
                if($type == 'list'){
                    $li = '<a href="'.route('advisor.view',['id' => $row->id]).'" class="btn btn-sm btn-primary" title="Advisor Profile" target="_blank"> <span class="fa fa-user fa-lg"></span> Advisor Admin</a> '; 
                    $li .= '<a href="'.route('advisor.view-postcode',['id' => $row->id]).'" class="btn btn-sm btn-info ajax-click-page" title="Advisor Postcode"> <span class="fa fa-eye fa-lg"></span> View Postcode</a> '; 
                }              
                $li .= '<a href="'.route('advisor.edit',['id' => $row->id]).'" class="btn btn-sm btn-warning" title="Edit" > <span class="fa fa-edit fa-lg"></span> Edit</a> ';
                $li .= '<a href="'.route('advisor.change_password',['id' => $row->id]).'" class="btn btn-sm btn-dark ajax-click-page" title="Edit" > <span class="fa fa-edit fa-lg"></span>Change Password</a> ';
                $li .= '<a href="'.route('advisor.assign_office_manager',['id' => $row->id]).'" class="btn btn-sm btn-primary ajax-click-page" title="Assign Under a Office Manager" > <i class="fa fa-clipboard-check"></i>Assign Office Manager</a> ';
                if(empty($row->email_verified_at)){
                    $li .= '<a href="'.route('advisor.make_email_verify',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-success" title="Make Email Verify" > <i class="far fa-check-square fa-lg"></i> Manually Verify</a> ';
                    $li .= '<a href="'.route('advisor.send_email_verification',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-warning" title="Send Verification Email" > <span class="far fa-envelope fa-lg"></span> Send verification email </a> ';
                }
                $li .= '<a href="'.route('advisor.view_march_rating',['id' => $row->id]).'" class="btn btn-sm btn-warning" title="View Match Rating" target="_blank"> <span class="fa fa-eye fa-lg"></span> Match Rating</a> ';
                if($type == 'list'){
                    if(AccessController::checkAccess("admin_delete")){
                        $li .= '<a href="'.route('advisor.archive',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-danger " > <span class="fa fa-trash fa-lg" title="Delete" ></span> Delete</a> ';
                    }
                }else{
                    if(AccessController::checkAccess("admin_restore")){
                        $li .= '<a href="'.route('advisor.restore',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-danger" > <i class="fas fa-redo fa-lg"></i> Restore</a> ';
                    }
                    if(AccessController::checkAccess("admin_delete")){
                        $li .= '<a href="'.route('advisor.delete',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-danger " > <span class="fa fa-trash fa-lg" title="Delete" ></span> Permanent Delete</a> ';
                    }
                }
                return $li;
            })
            ->editColumn('subscribe', function($row)use($subscribe){
                if( !$subscribe ){
                    return '<a href="'.route('advisor.subscribe',[$row->id, 1]).'" class="ajax-click btn btn-sm btn-warning" title="Unsubscribed" >Unsubscribed</a> '; 
                }else{
                    return '<a href="'.route('advisor.subscribe',[$row->id, 0]).'" class="ajax-click btn btn-sm btn-success" title="Subscribed" >Subscribed</a> '; 
                }                
            })
            ->addcolumn('profession', function($row){ return $row->profession->name ?? ""; })
            ->addcolumn('profile_name', function($row){ return $row->firm_details->profile_name; })
            ->editColumn('status', function($row){ return $this->getStatus($row->status); })
            ->addcolumn('live', function($row){ return $row->is_live == 1 ? '<input type="checkbox" checked>' : '<input type="checkbox">'; })
            ->addColumn("email_verify", function($row){ return !empty($row->email_verified_at) ? $this->getStatus('verified') : $this->getStatus('not_verified') ; })
            ->editColumn("created_by", function($row){ return $row->createdBy->name ?? "N/A"; })
            ->editColumn("updated_by", function($row){ return $row->updatedBy->name ?? "N/A"; })
            ->rawColumns(['action', 'publication_status', 'email_verify', 'area_covered', "subscribe_area_covered",'subscribe', 'status','live'])
            ->make(true);
    }

    /**
     * Advisor Dashboard
     * First Login into Authentication guard
     * Then Redirect Advisor Panel
     */
    public function dashboard(Request $request){
        $advisor = User::withTrashed()->find($request->id);
        $office_manager = $advisor->subscription_plan->office_manager ?? false;
        if($office_manager){
            Auth::guard("office_manager")->login($advisor);
        }
        Auth::guard("web")->login($advisor);
        return redirect()->route('advisor.dashboard');
    }

    /**
     * View PostCodes
     */
    public function viewPostcodes(Request $request){
        $params = [
            "advisor"           => User::find($request->id),
        ];
        return view('backEnd.advisor.view-post-code', $params);
    }

    /**
     * View Or Show Advisor Match Rating
     */
    public function viewMatchRating(Request $request){
        $advisor = User::withTrashed()->find($request->id);
        Auth::guard("web")->login($advisor);
        return redirect()->route('advisor.match_rating');
    }

    /******************************************************************************
     * Advisor Billing Section
     */

     /**
     * Get Table Column List
     */
    private function getBillingColumns(){
        return ['#','billing_id', "advisor", 'contact_name', 'company_name', "company_number",'post_code','address','plan','terms_and_condition_agree_date', 'action'];

    }

    /**
     * Get DataTable Column List
     */
    private function getBillingDataTableColumns(){
        return ['index','id', "advisor", 'contact_name', 'billing_company_name', "billing_company_fca_number",'post_code', 'address','subscription','terms_and_condition_agree_date','action'];
    }

    /**
     * Advisor Billing List
     */
    public function advisorBillingList(Request $request){
        if( $request->ajax() ){
            return $this->getBillingDataTable();
        }
        
        $params = [
            'nav'               => 'advisor',
            'subNav'            => 'advisor.billing_list',
            'tableColumns'      => $this->getBillingColumns(),
            'dataTableColumns'  => $this->getBillingDataTableColumns(),
            'dataTableUrl'      => Null,
            'pageTitle'         => 'All Advisor Billings',
            'tableStyleClass'   => 'bg-success',
            "modalSizeClass"    => "modal-lg"
        ];
        return view('backEnd.table', $params);
    }

    /**
     * View Billing Details
     */
    public function advisorBillingView(Request $request){
        $params = [
            "data"      => AdvisorBillingInfo::find($request->id)
        ];
        $this->saveActivity($request, "View Advisor Billing Info");
        return view('backEnd.advisor.view-billing', $params)->render();
    }

    /**
     * Edit Billing Info
     */
    public function advisorBillingEdit(Request $request){
        $params = [
            "title"     => "Update Advisor Billng Information",
            "form_url"  => route('advisor.billing_edit',[$request->id]),
            "data"      => AdvisorBillingInfo::find($request->id),
        ];
        $this->saveActivity($request, "Edit Advisor Billing Info"); 
        return view('backEnd.advisor.edit-billing', $params);
    }

    /**
     * Save Updated Billing Info
     */
    public function advisorBillingSave(Request $request){
        try{
            $data_arr = $request->except(['_token', 'id']);
            AdvisorBillingInfo::where('id', $request->id)->update($data_arr);
            $this->success('Billing information saved successfully');
            $this->saveActivity($request, "Update Advisor Billing Info"); 
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Billing DataTable
     */
    protected function getBillingDataTable(){
        $datas = AdvisorBillingInfo::join('advisors', 'advisors.id', '=', 'advisor_billing_infos.advisor_id')->select('advisor_billing_infos.*', 'advisors.first_name', 'advisors.last_name','advisors.terms_and_condition_agree_date','advisors.subscription_plan_id')->orderBy('id', 'DESC')->get();
        return DataTables::of($datas)
            ->addColumn('index', function(){ return ++$this->index; })
            ->addColumn('advisor', function($row){ 
                return (($row->first_name ?? 'N/A'). ' ' .($row->last_name ?? null)); 
            })
            ->addColumn('post_code', function($row){ return $row->billing_post_code; })
            ->addColumn('address', function($row){ return $row->billing_address_line_one . ' ' . $row->billing_address_line_two; })
            ->addColumn('subscription', function($row){
                $subscription_plan = SubscriptionPlan::where('id',$row->subscription_plan_id)->pluck('name')->first();
                return  $subscription_plan ?? "N/A";
                // return $row->subscription_plan->name ?? "N/A";
             })
            ->addColumn('terms_and_condition_agree_date', function($row){ return $row->terms_and_condition_agree_date ?? 'N/A';})
            ->addColumn('action', function($row){                
                $li = '<a href="'.route('advisor.billing_view',[$row->id]).'" class="btn btn-sm btn-primary ajax-click-page" title="Advisor Profile" target="_blank"> <span class="fa fa-eye fa-lg"></span> </a> '; 
                $li .= '<a href="'.route('advisor.billing_edit',[$row->id]).'" class="btn btn-sm btn-info ajax-click-page" title="Edit" > <span class="fa fa-edit fa-lg"></span> </a> ';
                return $li;
            })
        ->make(true);
    }

    /*************************************************************************************************
     * Promotional Advisor List
     */
    /**
     * Advisor Billing List
     */
    public function advisorPromotionalList(Request $request){
        if( $request->ajax() ){
            return $this->getPromotionalDataTable();
        }
        $this->saveActivity($request, "View Advisor Promotional List"); 
        $params = [
            'nav'               => 'advisor',
            'subNav'            => 'advisor.promotional_list',
            'tableColumns'      => $this->getPromotionalColumns(),
            'dataTableColumns'  => $this->getPromotionalTableColumns(),
            'create'            => AccessController::checkAccess(['promotional_create']) ? route('advisor.promotional_create') : false,
            'pageTitle'         => 'All Promotional Advisor List',
            'tableStyleClass'   => 'bg-success',
            "modalSizeClass"    => "modal-lg"
        ];
        return view('backEnd.table', $params);
    }

    /**
     * Create Promotional List
     */
    public function advisorPromotionalCreate(Request $request){
        $promotional_advisors_arr = PromotionalAdvisor::select('advisor_id')->get()->toArray();
        $this->saveActivity($request, "Advisor promotional list add page open"); 
        $params = [
            "title"     => "Add Promotional Advisor",
            "form_url"  => route('advisor.promotional_create'),
            "advisors"  => User::whereNotIn('id', $promotional_advisors_arr)->orderBy('id', 'asc')->get(),
        ]; 
        return view('backEnd.advisor.promotional', $params);
    }

    /**
     * Edit Promotional List
     */
    public function advisorPromotionalEdit(Request $request){
        $promotional_advisors_arr = PromotionalAdvisor::where('advisor_id', $request->id)->select('advisor_id')->get()->toArray();
        $params = [
            "title"     => "Edit Promotional Advisor",
            "form_url"  => route('advisor.promotional_create'),
            "advisors"  => User::whereNotIn('id', $promotional_advisors_arr)->orderBy('id', 'asc')->get(),
            "data"      => PromotionalAdvisor::where('id', $request->id)->first(),
        ]; 
        $this->saveActivity($request, "Advisor promotional list Edit page open"); 
        return view('backEnd.advisor.promotional', $params);
    }

    /**
     * Save Promotional List
     */
    public function advisorPromotionalStore(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'advisor_id'    => ['required', 'numeric', 'min:1'],
                'position'      => ['required', 'numeric', 'min:1'],
                'publication_status' => ['required', 'boolean', 'min:1'],
            ]);
            if($validator->fails()){
                $this->message = $this->getValidationError($validator);
                return $this->output();
            }

            $data_arr = $request->except(['_token', 'id']);
            if($request->id == 0){
                PromotionalAdvisor::insert($data_arr);
                $this->saveActivity($request, "Add Advisor into promotional list"); 
            }else{
                $this->saveActivity($request, "Update Advisor into promotional list"); 
                PromotionalAdvisor::where('id', $request->id)->update($data_arr);
            }
            $this->success('Promotional advisor information saved successfully');
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Delete / Remove Advisor from Promotional Advisor List
     */
    public function advisorPromotionalDelete(Request $request){
        try{
            PromotionalAdvisor::where('id', $request->id)->delete();
            $this->saveActivity($request, "Delete Advisor from promotional list"); 
            $this->success('Promotional advisor information deleted successfully');
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Get Table Column List
     */
    private function getPromotionalColumns(){
        return ['#', "advisor", 'position', 'publication_status', 'action'];

    }

    /**
     * Get DataTable Column List
     */
    private function getPromotionalTableColumns(){
        return ['index', "advisor", 'position', 'publication_status', 'action'];
    }

    /**
     * Promotional Advisor List Datatable 
     */
    protected function getPromotionalDataTable(){
        $datas = PromotionalAdvisor::orderBy('position', 'ASC')->get();
        return DataTables::of($datas)
            ->addColumn('index', function(){ return ++$this->index; })
            ->addColumn('advisor', function($row){ return ($row->advisor->first_name . ' ' .$row->advisor->last_name); })
            ->editColumn('publication_status', function($row){ return $this->getStatus($row->publication_status); })
            ->addColumn('action', function($row){                
                $li = '<a href="'.route('advisor.promotional_edit',[$row->id]).'" class="btn btn-sm btn-info ajax-click-page" title="Edit" > <span class="fa fa-edit fa-lg"></span> </a> ';  
                $li .= '<a href="'.route('advisor.promotional_delete',[$row->id]).'" class="btn btn-sm btn-danger ajax-click" title="Delete" > <span class="fa fa-trash fa-lg"></span> </a> ';  
                return $li;
            })
        ->rawColumns(['publication_status', 'action'])->make(true);
    }
    
}
