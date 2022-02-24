<?php

namespace App\Http\Controllers\BackEnd;

use App\AdvisorBillingInfo;
use App\AdvisorCompliance;
use App\AdvisorQuestion;
use App\Interview;
use App\Testimonial;
use App\Http\Controllers\Controller;
use App\SubscriptionPlan;
use App\System;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;


class OfficeManagerController extends Controller
{

    /**
     * Get Table Column List
     */
    private function getColumns(){
        return ['#', 'office_manager_name', 'email', 'phone', 'post_code',  'created_by', 'updated_by', 'action'];
    }

    /**
     * Get DataTable Column List
     */
    private function getDataTableColumns(){
        return ['index', 'office_manager_name', 'email', 'phone', 'post_code', 'created_by', 'updated_by', 'action'];
    }


    /**
     * Get Current Table Model
     */
    private function getModel(){
        return new User();
    }

    /**
     * Show Office Manager List  without Archive
     */
    public function index(Request $request){
        if( $request->ajax() ){
            return $this->getDataTable();
        }

        $params = [
            'nav'               => 'office_manager',
            'subNav'            => 'list',
            'tableColumns'      => $this->getColumns(),
            'dataTableColumns'  => $this->getDataTableColumns(),
            'dataTableUrl'      => Null,
            'create'            => route('office_manager.create'),
            'pageTitle'         => 'Office Manager List',
            'tableStyleClass'   => 'bg-success',
            'modalSizeClass'    => "modal-lg",
            'table_responsive'  => "table-responsive",

        ];
        return view('backEnd.table', $params);
    }

    /**
     * Create New Admin
     */
    public function create(Request $request){
        $params = [
            'nav'               => 'office_manager',
            'subNav'            => 'create',
            "title"     => "Create Office Manager",
            "form_url"  => route('office_manager.create'),
            "subscription_plans" => SubscriptionPlan::where("office_manager", true)->get(),
            "edit"      => false,
        ];
        $this->saveActivity($request, "Create Office Manager page open");
        return view('backEnd.office-manager.create', $params)->render();
    }

    /**
     * Store Office Manager Information
     */
    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                "first_name"        => ['required','string', "min:2"],
                "last_name"         => ['nullable','string','min:1'],
                'email'             => ['required','email', 'min:2', 'max:191'],
                "password"          => ["required", "min:3", "max:20"],
                'phone'             => ['required','string', 'min:11', 'max:13'],
                'address_line_one'  => ['required','string', 'min:2', 'max:191'],
                'address_line_two'  => ['nullable','string'],
                'town'              => ['nullable','string'],
                'country'           => ['required','string', 'min:2', 'max:191'],
                'post_code'         => ['required', "string", "min:4", "max:8"],

                "subscription_plan_id"              => ['required','numeric'],
                "terms_and_condition_agree_date"    =>['required', "date"],

                "profile_name"          => ['required', "string", "min:2", "max:191"],
                "firm_fca_number"       => ['nullable', "string", "min:2", "max:191"],
                "firm_website_address"  => ['nullable', "string", "min:2", "max:191"],
                "linkedin_id"           => ['nullable', "string", "min:2", "max:191"],
                "profile_details"       => ['nullable', "string", "min:2", "max:191"],
                "profile_name"          => ['nullable', "string", "min:2", "max:191"],

                "contact_name"              => ['required', "string", "min:2", "max:191"],
                "billing_address_line_one"  => ['required', "string", "min:2", "max:191"],
                "billing_address_line_two"  => ['nullable', "string", "min:2", "max:191"],
                "billing_company_name"      => ['nullable', "string", "min:2", "max:191"],
                "billing_company_fca_number"=> ['nullable', "string", "min:2", "max:191"],
                "billing_town"              => ['nullable', "string", "min:2", "max:191"],
                "billing_country"           => ['nullable', "string", "min:2", "max:191"],
                "billing_post_code"         => ['nullable', "string", "min:2", "max:191"]
            ]);
            if($validator->fails()){
                return back()->withErrors($validator->errors())->withInput()->with("error", $this->getValidationError($validator));
            }
            if( $request->id == 0 ){
                $data = $this->getModel();
                $data->created_by = $request->user()->id;
                $message = 'Office Manager information added successfully';
                $this->saveActivity($request, "Create new Office Manager");
            }else{
                $message = 'Office Manager information updated successfully';
                $data = $this->getModel()->withTrashed()->find($request->id);
                $data->updated_by = $request->user()->id;
                $this->saveActivity($request, "Update Office Manager info", $data);
            }
            $advisor = $this->saveOfficeManagerInfo($data, $request);
            (new AdvisorController())->saveFirmInfo($request, $advisor);
            (new AdvisorController())->saveBillingInfo($request, $advisor);

            return back()->with("success", $message);
        }catch(Exception $e){
            return back()->with("error", $this->getError($e));
        }

    }

    /**
     * Save OfficeManager Info
     */
    protected function saveOfficeManagerInfo($data, $request){
        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->email = $request->email;
        $data->phone = $request->phone;

        $data->password = !empty($request->password) ? bcrypt($request->password) : $data->password;
        $data->address_line_one = $request->address_line_one;
        $data->address_line_two = $request->address_line_two;
        $data->post_code = $request->post_code;
        $data->town = $request->town;
        $data->country = $request->country;
        $data->subscription_plan_id = $request->subscription_plan_id;
        $data->terms_and_condition_agree_date = $request->terms_and_condition_agree_date;
        $data->is_live = false;
        $data->save();
        return $data;
    }

    /**
     * Edit Office Manager Info
     */
    public function edit(Request $request){
        $params = [
            'nav'               => 'office_manager',
            'subNav'            => 'create',
            "title"     => "Create Office Manager",
            "form_url"  => route('office_manager.create'),
            "subscription_plans" => SubscriptionPlan::where("office_manager", true)->get(),
            "edit"      => true,
            "data"      => User::find($request->id),
        ];
        $this->saveActivity($request, "Edit Office Manager page open");
        return view('backEnd.office-manager.create', $params)->render();
    }

    public function viewBilingInfo(Request $request){
        $office_manager = User::find($request->id);
        $params = [
            "data"      => $office_manager->billing_info,
        ];
        $this->saveActivity($request, "View Advisor Billing Info");
        return view('backEnd.office-manager.view-billing', $params)->render();
    }

     /**
     * Permanently Delete
     */
    public function delete(Request $request){
        try{
            $data = $this->getModel()->withTrashed()->find($request->id);
            AdvisorBillingInfo::where('advisor_id', $data->id)->delete();
            Testimonial::where('advisor_id', $data->id)->forceDelete();
            AdvisorQuestion::where('advisor_id', $data->id)->forceDelete();
            AdvisorCompliance::where('advisor_id', $data->id)->delete();
            Interview::where('advisor_id', $data->id)->delete();

            $this->saveActivity($request, "Office Manager deleted", $data);
            $data->forceDelete();
            $this->success(' Office Manager deleted successfully');
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }


    /**
     * Make the selected Office Manager As Archive
     */
    public function archive(Request $request){
        try{
            $data = $this->getModel()->withTrashed()->find($request->id);
            $data->delete();
            $this->success('Archive deleted successfully');
            $this->saveActivity($request, "Delete Office Manager");
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Make the selected Office Manager As Active from Archive
     */
    public function restore(Request $request){
        try{
            $data = $this->getModel()->withTrashed()->find($request->id);
            $data->restore();
            $this->success('Office Manager restored successfully');
            $this->saveActivity($request, "Office Manager restored", $data);
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Show Archive Office ManagerList
     */
    public function archiveList(Request $request){
        if( $request->ajax() ){
            return $this->getDataTable('archive');
        }
        $params = [
            'nav'               => 'Office Manager' ,
            'subNav'            => 'office-manager.archive_list',
            'tableColumns'      => $this->getColumns(),
            'dataTableColumns'  => $this->getDataTableColumns(),
            'dataTableUrl'      => Null,
            'pageTitle'         => 'Office Manager Archive List',
            'tableStyleClass'   => 'bg-success'
        ];
        return view('backEnd.table', $params);
    }

    /**
     * Get Office Manager DataTable
     * Type will be list & archive
     * Default Type is list
     */
    protected function getDataTable($type = 'list'){
        $data = $this->getModel()->join("subscription_plans", "subscription_plans.id", "=", "advisors.subscription_plan_id")
            ->where("subscription_plans.office_manager", true)
            ->where("advisors.office_manager_id", null);
        if( $type != "list" ){
            $data = $data->onlyTrashed();
        }
        $data = $data->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->select("advisors.*")->get();

        return DataTables::of($data)
            ->addColumn('index', function(){ return ++$this->index; })
            ->addColumn('office_manager_name', function($row){ return ($row->first_name. ' '. $row->last_name ); })
            ->addColumn('action', function($row) use($type){
                $li = "";
                if($type == 'list'){
                    $li = '<a href="'.route('advisor.view',['id' => $row->id]).'" class="btn btn-sm btn-primary" title="Advisor Profile" target="_blank"> <span class="fa fa-user fa-lg"></span> Advisor Admin</a> ';
                }
                $li .= '<a href="'.route('office_manager.edit',['id' => $row->id]).'" class="btn btn-sm btn-warning" title="Edit" > <span class="fa fa-edit fa-lg"></span> Edit</a> ';
                $li .= '<a href="'.route('office_manager.view_billing',['id' => $row->id]).'" class="btn btn-sm btn-info ajax-click-page" title="View Billing Info" > <span class="fa fa-eye fa-lg"></span> Billing Info</a> ';
                $li .= '<a href="'.route('advisor.change_password',['id' => $row->id]).'" class="btn btn-sm btn-dark ajax-click-page" title="Edit" > <span class="fa fa-edit fa-lg"></span>Change Password</a> ';
                if(empty($row->email_verified_at)){
                    $li .= '<a href="'.route('advisor.make_email_verify',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-success" title="Make Email Verify" > <i class="far fa-check-square fa-lg"></i> Manually Verify</a> ';
                    $li .= '<a href="'.route('advisor.send_email_verification',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-warning" title="Send Verification Email" > <span class="far fa-envelope fa-lg"></span> Send verification email </a> ';
                }

                if($type == 'list'){
                    if(AccessController::checkAccess("admin_delete")){
                        $li .= '<a href="'.route('office_manager.archive',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-danger " > <span class="fa fa-trash fa-lg" title="Delete" ></span> Delete</a> ';
                    }
                }else{
                    if(AccessController::checkAccess("admin_restore")){
                        $li .= '<a href="'.route('office_manager.restore',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-danger" > <i class="fas fa-redo fa-lg"></i> Restore</a> ';
                    }
                    if(AccessController::checkAccess("admin_delete")){
                        $li .= '<a href="'.route('office_manager.delete',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-danger " > <span class="fa fa-trash fa-lg" title="Delete" ></span> Permanent Delete</a> ';
                    }
                }
                return $li;
            })
            ->addcolumn('profile_name', function($row){ return $row->firm_details->profile_name; })
            ->editColumn('status', function($row){ return $this->getStatus($row->status); })
            ->addColumn("email_verify", function($row){ return !empty($row->email_verified_at) ? $this->getStatus('verified') : $this->getStatus('not_verified') ; })
            ->editColumn("created_by", function($row){ return $row->createdBy->name ?? "N/A"; })
            ->editColumn("updated_by", function($row){ return $row->updatedBy->name ?? "N/A"; })
            ->rawColumns(['action', 'publication_status', 'email_verify'])
            ->make(true);
    }



}
