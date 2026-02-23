@extends('voyager::master')

@section('page_title', __('voyager::generic.viewing').' '.$dataType->getTranslatedAttribute('display_name_plural'))

@section('page_header')
<div class="container-fluid">
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i> {{ $dataType->getTranslatedAttribute('display_name_plural') }}
    </h1>
    @can('add', app($dataType->model_name))
    <a href="{{ route('voyager.'.$dataType->slug.'.create') }}" class="btn btn-success btn-add-new">
        <i class="voyager-plus"></i> <span>{{ __('voyager::generic.add_new') }}</span>
    </a>
    @endcan
    @can('edit', app($dataType->model_name))
    @if(!empty($dataType->order_column) && !empty($dataType->order_display_column))
    <a href="{{ route('voyager.'.$dataType->slug.'.order') }}" class="btn btn-primary btn-add-new">
        <i class="voyager-list"></i> <span>{{ __('voyager::bread.order') }}</span>
    </a>
    @endif
    @endcan
    @foreach($actions as $action)
    @if(method_exists($action, 'massAction'))
    @include('voyager::bread.partials.actions', ['action' => $action, 'data' => null])
    @endif
    @endforeach
    @include('voyager::multilingual.language-selector')


</div>
@stop

@section('css')
@if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
<link rel="stylesheet" href="{{ voyager_asset('lib/css/responsive.dataTables.min.css') }}">
@endif
<style>
    /* ── Table-only scroll ─────────────────────────────────── */
    #table-scroll-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #ccc #f5f5f5;
    }

    #table-scroll-wrapper::-webkit-scrollbar {
        height: 6px;
    }

    #table-scroll-wrapper::-webkit-scrollbar-track {
        background: #f5f5f5;
        border-radius: 3px;
    }

    #table-scroll-wrapper::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    #dataTable {
        min-width: 700px;
    }

    /* ── Multi-filter bar ──────────────────────────────────── */
    #multi-filter-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 10px;
        padding: 12px 14px;
        margin-bottom: 14px;
        background: #f9f9f9;
        border: 1px solid #e8e8e8;
        border-radius: 6px;
    }

    .mf-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex: 1 1 130px;
        min-width: 120px;
        max-width: 210px;
    }

    .mf-group.mf-range-group {
        flex: 1 1 200px;
        max-width: 260px;
    }

    .mf-group label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .9px;
        color: #999;
        margin: 0;
        white-space: nowrap;
    }

    .mf-control {
        height: 34px;
        padding: 4px 8px;
        font-size: 13px;
        border: 1px solid #d0d0d0;
        border-radius: 4px;
        background: #fff;
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        width: 100%;
        box-sizing: border-box;
    }

    .mf-control:focus {
        border-color: #5897fb;
        box-shadow: 0 0 0 2px rgba(88, 151, 251, .18);
    }

    .mf-control.filter-active {
        border-color: #5897fb;
        background: #f0f5ff;
    }

    .mf-range-pair {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .mf-range-pair .mf-control {
        width: 0;
        flex: 1 1 0;
    }

    .mf-range-pair .sep {
        color: #bbb;
        font-size: 14px;
        flex-shrink: 0;
    }

    .mf-actions {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        justify-content: flex-end;
        flex-shrink: 0;
    }

    #btn-clear-filters {
        height: 34px;
        padding: 0 14px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #ccc;
        border-radius: 4px;
        background: #fff;
        cursor: pointer;
        color: #555;
        white-space: nowrap;
        transition: background .15s, border-color .15s;
    }

    #btn-clear-filters:hover {
        background: #f0f0f0;
        border-color: #aaa;
    }

    #filter-count {
        font-size: 11px;
        color: #888;
        text-align: center;
        min-height: 15px;
        white-space: nowrap;
    }
</style>
@stop

@section('content')

@php
/* ── Load all roles from DB once for the static dropdown ── */
$allRoles = \TCG\Voyager\Models\Role::orderBy('name')->get();
@endphp

<div class="page-content browse container-fluid">
    @include('voyager::alerts')


    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-body">



                    {{-- ══════════════════════════════════════════
                         TABLE — ONLY this div scrolls
                    ══════════════════════════════════════════ --}}
                    <div id="table-scroll-wrapper">
                        <table id="dataTable" class="table table-hover">
                            <thead>
                                <tr>
                                    @foreach($dataType->browseRows as $row)
                                    <th>
                                        @if($isServerSide && in_array($row->field, $sortableColumns))
                                        <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                            @endif
                                            {{ $row->getTranslatedAttribute('display_name') }}
                                            @if($isServerSide)
                                            @if($row->isCurrentSortField($orderBy))
                                            @if($sortOrder == 'asc')
                                            <i class="voyager-angle-up pull-right"></i>
                                            @else
                                            <i class="voyager-angle-down pull-right"></i>
                                            @endif
                                            @endif
                                        </a>
                                        @endif
                                    </th>
                                    @endforeach
                                    <th class="actions text-right dt-not-orderable">{{ __('voyager::generic.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dataTypeContent as $data)
                                <tr>
                                    @foreach($dataType->browseRows as $row)
                                    @php
                                    if ($data->{$row->field.'_browse'}) {
                                    $data->{$row->field} = $data->{$row->field.'_browse'};
                                    }
                                    @endphp
                                    <td
                                        {{--
                                            data-role stores the EXACT role name from the DB
                                            (e.g. "admin", "hradmin", "candidate").
                                            The <select> options above use the same names,
                                            so JS can do a plain === comparison — no mapping.

                                            We stamp it on every relationship cell, not just
                                            when field === 'user_belongsto_role_relationship',
                                            because Voyager may rename the display field.
                                        --}}
                                        @if($row->field === 'user_belongsto_role_relationship')
                                        data-role="{{ $data->role->name ?? '' }}"
                                        @endif
                                        @if($row->field === 'post') data-post="{{ $data->{$row->field} ?? '' }}" @endif
                                        @if($row->field === 'highestquali') data-highestquali="{{ $data->{$row->field} ?? '' }}" @endif
                                        @if($row->field === 'aptiscore') data-aptiscore="{{ is_numeric($data->{$row->field}) ? $data->{$row->field} : '' }}" @endif
                                        >
                                        @if(isset($row->details->view))
                                        @include($row->details->view, ['row'=>$row,'dataType'=>$dataType,'dataTypeContent'=>$dataTypeContent,'content'=>$data->{$row->field},'action'=>'browse','view'=>'browse','options'=>$row->details])
                                        @elseif($row->type == 'image')
                                        <img src="@if(!filter_var($data->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image($data->{$row->field}) }}@else{{ $data->{$row->field} }}@endif" style="width:100px">
                                        @elseif($row->type == 'relationship')
                                        @include('voyager::formfields.relationship', ['view'=>'browse','options'=>$row->details])
                                        @elseif($row->type == 'select_multiple')
                                        @if(property_exists($row->details, 'relationship'))
                                        @foreach($data->{$row->field} as $item){{ $item->{$row->field} }}@endforeach
                                        @elseif(property_exists($row->details, 'options'))
                                        @if(!empty(json_decode($data->{$row->field})))
                                        @foreach(json_decode($data->{$row->field}) as $item)
                                        @if(@$row->details->options->{$item}){{ $row->details->options->{$item}.(!$loop->last?', ':'') }}@endif
                                        @endforeach
                                        @else{{ __('voyager::generic.none') }}@endif
                                        @endif
                                        @elseif($row->type == 'multiple_checkbox' && property_exists($row->details, 'options'))
                                        @if(@count(json_decode($data->{$row->field})) > 0)
                                        @foreach(json_decode($data->{$row->field}) as $item)
                                        @if(@$row->details->options->{$item}){{ $row->details->options->{$item}.(!$loop->last?', ':'') }}@endif
                                        @endforeach
                                        @else{{ __('voyager::generic.none') }}@endif
                                        @elseif(($row->type == 'select_dropdown' || $row->type == 'radio_btn') && property_exists($row->details, 'options'))
                                        {!! $row->details->options->{$data->{$row->field}} ?? '' !!}
                                        @elseif($row->type == 'date' || $row->type == 'timestamp')
                                        @if(property_exists($row->details, 'format') && !is_null($data->{$row->field}))
                                        {{ \Carbon\Carbon::parse($data->{$row->field})->formatLocalized($row->details->format) }}
                                        @else{{ $data->{$row->field} }}@endif
                                        @elseif($row->type == 'checkbox')
                                        @if(property_exists($row->details, 'on') && property_exists($row->details, 'off'))
                                        @if($data->{$row->field})<span class="label label-info">{{ $row->details->on }}</span>
                                        @else<span class="label label-primary">{{ $row->details->off }}</span>@endif
                                        @else{{ $data->{$row->field} }}@endif
                                        @elseif($row->type == 'color')
                                        <span class="badge badge-lg" style="background-color:{{ $data->{$row->field} }}">{{ $data->{$row->field} }}</span>
                                        @elseif($row->type == 'text')
                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                        <div>{{ mb_strlen($data->{$row->field}) > 200 ? mb_substr($data->{$row->field},0,200).' ...' : $data->{$row->field} }}</div>
                                        @elseif($row->type == 'text_area')
                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                        <div>{{ mb_strlen($data->{$row->field}) > 200 ? mb_substr($data->{$row->field},0,200).' ...' : $data->{$row->field} }}</div>
                                        @elseif($row->type == 'file' && !empty($data->{$row->field}))
                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                        @if(json_decode($data->{$row->field}) !== null)
                                        @foreach(json_decode($data->{$row->field}) as $file)
                                        <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}" target="_blank">{{ $file->original_name ?: '' }}</a><br>
                                        @endforeach
                                        @else
                                        <a href="{{ Storage::disk(config('voyager.storage.disk'))->url($data->{$row->field}) }}" target="_blank">Download</a>
                                        @endif
                                        @elseif($row->type == 'rich_text_box')
                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                        <div>{{ mb_strlen(strip_tags($data->{$row->field},'<b><i><u>')) > 200 ? mb_substr(strip_tags($data->{$row->field},'<b><i><u>'),0,200).' ...' : strip_tags($data->{$row->field},'<b><i><u>') }}</div>
                                        @elseif($row->type == 'coordinates')
                                        @include('voyager::partials.coordinates-static-image')
                                        @elseif($row->type == 'multiple_images')
                                        @php $images = json_decode($data->{$row->field}); @endphp
                                        @if($images)
                                        @php $images = array_slice($images,0,3); @endphp
                                        @foreach($images as $image)
                                        <img src="@if(!filter_var($image,FILTER_VALIDATE_URL)){{ Voyager::image($image) }}@else{{ $image }}@endif" style="width:50px">
                                        @endforeach
                                        @endif
                                        @elseif($row->type == 'media_picker')
                                        @php $files = is_array($data->{$row->field}) ? $data->{$row->field} : json_decode($data->{$row->field}); @endphp
                                        @if($files)
                                        @if(property_exists($row->details,'show_as_images') && $row->details->show_as_images)
                                        @foreach(array_slice($files,0,3) as $file)<img src="@if(!filter_var($file,FILTER_VALIDATE_URL)){{ Voyager::image($file) }}@else{{ $file }}@endif" style="width:50px">@endforeach
                                        @else<ul>@foreach(array_slice($files,0,3) as $file)<li>{{ $file }}</li>@endforeach</ul>@endif
                                        @if(count($files)>3){{ __('voyager::media.files_more',['count'=>count($files)-3]) }}@endif
                                        @elseif(is_array($files) && count($files)==0)
                                        {{ trans_choice('voyager::media.files',0) }}
                                        @elseif($data->{$row->field}!='')
                                        @if(property_exists($row->details,'show_as_images') && $row->details->show_as_images)
                                        <img src="@if(!filter_var($data->{$row->field},FILTER_VALIDATE_URL)){{ Voyager::image($data->{$row->field}) }}@else{{ $data->{$row->field} }}@endif" style="width:50px">
                                        @else{{ $data->{$row->field} }}@endif
                                        @else{{ trans_choice('voyager::media.files',0) }}@endif
                                        @else
                                        @include('voyager::multilingual.input-hidden-bread-browse')
                                        <span>{{ $data->{$row->field} }}</span>
                                        @endif
                                    </td>
                                    @endforeach

                                    <td class="no-sort no-click bread-actions">
                                        @can('edit', $data)
                                        <a href="{{ route('voyager.'.$dataType->slug.'.edit', $data->getKey()) }}"
                                            title="Edit" class="btn btn-sm btn-warning pull-right edit">
                                            <i class="voyager-edit"></i>
                                            <span class="hidden-xs hidden-sm">Edit</span>
                                        </a>
                                        @endcan
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>{{-- #table-scroll-wrapper --}}

                    {{-- ══════════════════════════════════════════
                         SERVER-SIDE PAGINATION — never scrolls
                    ══════════════════════════════════════════ --}}
                    @if($isServerSide)
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;flex-wrap:wrap;gap:8px;">
                        <div role="status" class="show-res" aria-live="polite" style="font-size:13px;color:#666;">
                            {{ trans_choice('voyager::generic.showing_entries', $dataTypeContent->total(), [
                                'from' => $dataTypeContent->firstItem(),
                                'to'   => $dataTypeContent->lastItem(),
                                'all'  => $dataTypeContent->total()
                            ]) }}
                        </div>
                        <div>
                            {{ $dataTypeContent->appends([
                                's'               => $search->value,
                                'filter'          => $search->filter,
                                'key'             => $search->key,
                                'order_by'        => $orderBy,
                                'sort_order'      => $sortOrder,
                                'showSoftDeleted' => $showSoftDeleted,
                            ])->links() }}
                        </div>
                    </div>
                    @endif

                </div>{{-- panel-body --}}
            </div>{{-- panel --}}
        </div>
    </div>
</div>
@stop

@section('javascript')
@if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
<script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
@endif
<script>
     var dtTable = null;

    @if(!$dataType->server_side)
    dtTable = $('#dataTable').DataTable({!! json_encode(array_merge([
        'language'   => __('voyager::datatable'),
        'order'      => [],
        'columnDefs' => [['targets' => 'dt-not-orderable', 'searchable' => false, 'orderable' => false]],
    ], config('voyager.dashboard.data_tables', [])), true) !!});
    @else
    $('#search-input select').select2({ minimumResultsForSearch: Infinity });
    @endif

    @if($isModelTranslatable)
    $('.side-body').multilingual();
    $('#dataTable').on('draw.dt', function () {
        $('.side-body').data('multilingual').init();
    });
    @endif
</script>
@stop