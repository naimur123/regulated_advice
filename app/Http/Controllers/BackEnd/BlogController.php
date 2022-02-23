<?php

namespace App\Http\Controllers\BackEnd;

use App\Blogs;
use App\Admin;
use App\Http\Controllers\Controller;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /**
     * Get Table Column List
     */
    private function getColumns(){
        $columns = ['#', 'image', 'title', 'description', 'publication_status', 'created_by', 'updated_by', 'action'];
        return $columns;
    }

    /**
     * Get DataTable Column List
     */
    private function getDataTableColumns(){
        $columns = ['index', 'image', 'title', 'description', 'publication_status', 'created_by', 'updated_by', 'action'];
        return $columns;
    }

    /**
     * Get Current Table Model
     */
    private function getModel(){
        return new Blogs();
    }

    /**
     * Show blog List  without Archive
     */
    public function index(Request $request){        
        if( $request->ajax() ){
            return $this->getDataTable();
        }

        $this->saveActivity($request, "Blog Table Page Show");
        $params = [
            'nav'               => 'blog',
            'subNav'            => 'blog.list',
            'tableColumns'      => $this->getColumns(),
            'dataTableColumns'  => $this->getDataTableColumns(),
            'dataTableUrl'      => Null,
            'create'            => AccessController::checkAccess("blog_create") ? route('blog.create') : false,
            'pageTitle'         => 'Blog List',
            'tableStyleClass'   => 'bg-success',
            "modalSizeClass"    => "modal-lg"
        ];
        return view('backEnd.table', $params);
    }

    /**
     * Create New Admin
     */
    public function create(Request $request){
        $params = [
            "title"     => "Create Blog",
            "form_url"  => route('blog.create'),
            "admins"  => Admin::where("is_developer", 0)->get(),
        ]; 
        $this->saveActivity($request, "Create Blog Page Open");
        return view('backEnd.blog.create', $params)->render();
    }

    /**
     * Store blog Information
     */
    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(),[
                'title'         => ['required','string','min:2', 'max:191'],
                'description'   => ['required','string','min:2', 'max:4000'],
                'publication_status' => ['required','numeric']
            ]);

            if( $request->id == 0 ){
                if( $validator->fails()){
                    $this->message = $this->getValidationError($validator);
                    $this->modal = false;
                    return $this->output();
                }
                $data = $this->getModel();
                $data->slug = Str::slug($request->title .'-'. Str::random(3));
                $data->created_by = $request->user()->id;
                $message = 'Blog information added successfully';
                $this->saveActivity($request, "Add New Blog");
            }else{
                $data = $this->getModel()->find($request->id);
                $data->updated_by = $request->user()->id;
                $message = 'Blog information updated successfully';
                $this->saveActivity($request, "Update Blog Data", $data);
            }
            
            $data->admin_id = $request->admin_id;
            $data->title = $request->title;
            $data->description = $request->description;
            $data->read_time = $request->read_time;
            $data->meta_description = $request->meta_description;
            $data->meta_tag = $request->meta_tag;
            $data->publication_status = $request->publication_status;
            $data->image = $this->uploadImage($request, 'image', $this->others_dir, 400, null, $data->image);
            $data->save();
            $this->success($message);
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }

    /**
     * Edit blog Info
     */
    public function edit(Request $request){
        $params = [
            "title"     => "Edit Blog",
            "form_url"  => route('blog.create'),
            "data"      => $this->getModel()->find($request->id),
            "admins"  => Admin::where("is_developer", 0)->get(),
        ];
        $this->saveActivity($request, "Edit Blog Page Open");
        return view('backEnd.blog.create', $params)->render();
    }

    /**
     * View blog Message
     */
    public function view(Request $request){
        $params = [
            "data"      => $this->getModel()->find($request->id)
        ];
        return view('backEnd.blog.view', $params)->render();
    }

    /**
     * Archive Blog
     */
    public function archive(Request $request){
        try{
            $data = $this->getModel()->find($request->id);
            $this->RemoveFile($data->image);
            $data->delete();
            $this->success("Deleted successfully");
            $this->saveActivity($request, "Delete Blog Data");
        }catch(Exception $e){
            $this->message = $this->getError($e);
        }
        return $this->output();
    }


    /**
     * Get blog   DataTable
     * Type will be list & archive
     * Default Type is list
     */
    protected function getDataTable($type = 'list'){
        if( $type == "list" ){
            $data = $this->getModel()->orderBy('id', 'ASC')->get();
        }else{
            $data = $this->getModel()->onlyTrashed()->orderBy('id', 'ASC')->get();
        }
        return DataTables::of($data)
            ->addColumn('index', function(){ return ++$this->index; })
            ->editColumn('description', function($row){ return wordwrap($row->description, 70, '<br>'); })
            ->addColumn('action', function($row) use($type){                
                $li = '<a href="'.route('blog.view',['id' => $row->id]).'" class="ajax-click-page btn btn-sm btn-primary" title="View Message" > <span class="fa fa-eye"></span> </a> ';
                if(AccessController::checkAccess("blog_update")){
                    $li .= '<a href="'.route('blog.edit',['id' => $row->id]).'" class="ajax-click-page btn btn-sm btn-info" title="Edit" > <span class="fa fa-edit"></span> </a> ';
                }                
                if($type == 'list'){
                    if(AccessController::checkAccess("blog_delete")){
                        $li .= '<a href="'.route('blog.archive',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-danger " > <span class="fa fa-trash" title="Delete" ></span> </a> ';
                    }
                }else{
                    if(AccessController::checkAccess("blog_restore")){
                        $li .= '<a href="'.route('blog.restore',['id' => $row->id]).'" class="ajax-click btn btn-sm btn-danger" > <i class="fas fa-redo"></i> </a> ';
                    }
                }
                return $li;
            })
            ->editColumn('image', function($row){
                return '<img src="'.asset($row->image).'" style="height:60px;">';
            })
            ->editColumn("advisor_id", function($row){ return $this->getStatus($row->advisor_id); })
            ->editColumn("read_time", function($row){ return $this->getStatus($row->read_time); })
            ->editColumn("publication_status", function($row){ return $this->getStatus($row->publication_status); })
            ->editColumn("created_by", function($row){ return $row->createdBy->name ?? "N/A"; })
            ->editColumn("updated_by", function($row){ return $row->updatedBy->name ?? "N/A"; })
            ->rawColumns(['action', 'publication_status' ,'description', 'image'])
            ->make(true);
    }
}
