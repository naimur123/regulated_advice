@extends('backEnd.masterPage')
@section('mainPart')
<div class="page-body">
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="{{ isset($create) ? 'col-8' : 'col-12' }}" >
                    <h5>{{ ucfirst( str_replace(['_','-'], ' ', $pageTitle) ) }}</h5>
                </div>
                @if( isset($create) && $create )
                    <div class="col-4 text-right">
                        <a class="ajax-click-page btn btn-primary btn-sm" href="{{ url($create) }}">Create new</a>
                    </div>
                @endif
            </div>
            
        </div>
        <div class="card-body">
            <div class="dt-plugin-buttons"></div>
            <div class="dt-responsive table-responsive">
                <table id="table" class="table table-striped table-bordered nowrap">
                    <thead class="{{ isset($tableStyleClass) ? $tableStyleClass : 'bg-primary'}}">
                        <tr>
                            <th>SN.</th>
                            <th>Activity</th>
                            <th>IP</th>
                            <th>Admin</th>
                            <th>Advisor Admin</th>
                            <th>Date Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activity_logs as $list)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $list->activity }}</td>
                            <td>{{ $list->ip }}</td>
                            <td>{{ $list->admin->name ?? "N/A" }}</td>
                            <td>{{ $list->advisor->first_name ?? "N/A" }} {{ $list->advisor->last_name ?? ""}}</td>
                            <td>{{ $list->created_at }}</td>                            
                        </tr>                                
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-2 text-right">
                {!! $activity_logs->links() !!}
            </div>
        </div>
    </div>
</div>  
  
                                                  
@endsection

