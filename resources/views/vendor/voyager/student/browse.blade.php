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
        @can('delete', app($dataType->model_name))
            @if($usesSoftDeletes)
                <input type="checkbox" @if ($showSoftDeleted) checked @endif id="show_soft_deletes"
                    data-toggle="toggle"
                    data-on="{{ __('voyager::bread.soft_deletes_off') }}"
                    data-off="{{ __('voyager::bread.soft_deletes_on') }}">
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
    /* ── API status badge ── */
    #api-status {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; padding: 4px 10px; border-radius: 20px;
        background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7;
        margin-left: 12px; vertical-align: middle;
    }
    #api-status.error { background: #fdecea; color: #c62828; border-color: #ef9a9a; }
    #api-status .dot  { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

    /* ── Empty / loading state rows ── */
    #tbody-loading td, #tbody-empty td { text-align: center; padding: 32px 0; color: #999; font-size: 14px; }
</style>
@stop

@section('content')
<div class="page-content browse container-fluid">
    @include('voyager::alerts')
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-body">

                    @if($isServerSide)
                    <form method="get" class="form-search">
                        <div id="search-input">
                            <div class="col-2">
                                <select id="search_key" name="key">
                                    @foreach($searchNames as $key => $name)
                                        <option value="{{ $key }}"
                                            @if($search->key == $key || (empty($search->key) && $key == $defaultSearchKey))
                                                selected
                                            @endif>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-2">
                                <select id="filter" name="filter">
                                    <option value="contains" @if($search->filter == 'contains') selected @endif>contains</option>
                                    <option value="equals"   @if($search->filter == 'equals')   selected @endif>=</option>
                                </select>
                            </div>
                            <div class="input-group col-md-12">
                                <input type="text" class="form-control"
                                    placeholder="{{ __('voyager::generic.search') }}"
                                    name="s" value="{{ $search->value }}">
                                <span class="input-group-btn">
                                    <button class="btn btn-info btn-lg" type="submit">
                                        <i class="voyager-search"></i>
                                    </button>
                                </span>
                            </div>
                        </div>
                        @if(Request::has('sort_order') && Request::has('order_by'))
                            <input type="hidden" name="sort_order" value="{{ Request::get('sort_order') }}">
                            <input type="hidden" name="order_by"   value="{{ Request::get('order_by') }}">
                        @endif
                    </form>
                    @endif

                    <div class="table-responsive">
                        <table id="dataTable" class="table table-hover">
                            <thead>
                                <tr>
                                    {{--
                                        Column headers are driven by the BREAD browseRows config
                                        exactly like the stock browse view.
                                        Only the ID and Name columns exist in the API payload,
                                        but Voyager will still render whatever columns are ticked
                                        in the BREAD admin — cells with no matching key will be empty.
                                    --}}
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
                                {{--
                                    $dataTypeContent is a plain Laravel Collection of stdClass objects
                                    built from the API JSON in the controller.
                                    Each object has at minimum:  ->id  ->name
                                    We iterate the BREAD browseRows so column order matches the header.
                                --}}
                                @forelse($dataTypeContent as $data)
                                <tr>
                                    @foreach($dataType->browseRows as $row)
                                    <td>
                                        {{--
                                            Use property_exists / isset because stdClass objects from
                                            the API may not carry every field that the BREAD config lists.
                                        --}}
                                        @php
                                            $cellValue = property_exists($data, $row->field)
                                                ? $data->{$row->field}
                                                : null;
                                        @endphp

                                        @if($row->type == 'image' && $cellValue)
                                            <img src="{{ filter_var($cellValue, FILTER_VALIDATE_URL)
                                                ? $cellValue
                                                : Voyager::image($cellValue) }}" style="width:100px">

                                        @elseif($row->type == 'checkbox')
                                            @if(property_exists($row->details, 'on') && property_exists($row->details, 'off'))
                                                @if($cellValue)
                                                    <span class="label label-info">{{ $row->details->on }}</span>
                                                @else
                                                    <span class="label label-primary">{{ $row->details->off }}</span>
                                                @endif
                                            @else
                                                {{ $cellValue }}
                                            @endif

                                        @elseif($row->type == 'color' && $cellValue)
                                            <span class="badge badge-lg"
                                                style="background-color:{{ $cellValue }}">{{ $cellValue }}</span>

                                        @elseif(($row->type == 'select_dropdown' || $row->type == 'radio_btn')
                                                && property_exists($row->details, 'options'))
                                            {!! $row->details->options->{$cellValue} ?? '' !!}

                                        @elseif($row->type == 'date' || $row->type == 'timestamp')
                                            @if(property_exists($row->details, 'format') && $cellValue)
                                                {{ \Carbon\Carbon::parse($cellValue)->formatLocalized($row->details->format) }}
                                            @else
                                                {{ $cellValue }}
                                            @endif

                                        @elseif($row->type == 'text' || $row->type == 'text_area')
                                            <div>{{ mb_strlen((string)$cellValue) > 200
                                                ? mb_substr((string)$cellValue, 0, 200) . ' …'
                                                : $cellValue }}</div>

                                        @else
                                            {{-- Default: just echo the value --}}
                                            <span>{{ $cellValue }}</span>
                                        @endif
                                    </td>
                                    @endforeach

                                    {{-- Action buttons (Edit / Delete etc.) ──────────── --}}
                                    <td class="no-sort text-right no-click bread-actions">
                                        @foreach($actions as $action)
                                            @if(!method_exists($action, 'massAction'))
                                                @include('voyager::bread.partials.actions', ['action' => $action])
                                            @endif
                                        @endforeach
                                         <a href="{{ route('voyager.'.$dataType->slug.'.edit', $data->id) }}"
                                        class="btn btn-sm btn-warning edit">
                                            <i class="voyager-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ $dataType->browseRows->count() + 1 }}"
                                        style="text-align:center;padding:32px;color:#aaa;">
                                        No records returned from the API.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($isServerSide)
                    <div class="pull-left">
                        <div role="status" class="show-res" aria-live="polite">
                            {{ trans_choice('voyager::generic.showing_entries', $dataTypeContent->count(), [
                                'from' => 1,
                                'to'   => $dataTypeContent->count(),
                                'all'  => $dataTypeContent->count(),
                            ]) }}
                        </div>
                    </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Single delete modal (unchanged from stock) --}}
<div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"
                    aria-label="{{ __('voyager::generic.close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="voyager-trash"></i>
                    {{ __('voyager::generic.delete_question') }}
                    {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?
                </h4>
            </div>
            <div class="modal-footer">
                <form action="#" id="delete_form" method="POST">
                    {{ method_field('DELETE') }}
                    {{ csrf_field() }}
                    <input type="submit" class="btn btn-danger pull-right delete-confirm"
                        value="{{ __('voyager::generic.delete_confirm') }}">
                </form>
                <button type="button" class="btn btn-default pull-right"
                    data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('javascript')
@if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
    <script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
@endif
<script>
$(document).ready(function () {

    @if(!$dataType->server_side)
    $('#dataTable').DataTable({!! json_encode(array_merge([
        'order'       => $orderColumn,
        'language'    => __('voyager::datatable'),
        'columnDefs'  => [['targets' => 'dt-not-orderable', 'searchable' => false, 'orderable' => false]],
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

    /* Delete modal */
    $('td').on('click', '.delete', function () {
        $('#delete_form')[0].action =
            '{{ route('voyager.'.$dataType->slug.'.destroy', '__id') }}'
            .replace('__id', $(this).data('id'));
        $('#delete_modal').modal('show');
    });

    @if($usesSoftDeletes)
    @php
        $params = [
            's'          => $search->value,
            'filter'     => $search->filter,
            'key'        => $search->key,
            'order_by'   => $orderBy,
            'sort_order' => $sortOrder,
        ];
    @endphp
    $('#show_soft_deletes').change(function () {
        var checked = $(this).prop('checked') ? 1 : 0;
        var href = checked
            ? '{{ route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 1]), true) }}'
            : '{{ route('voyager.'.$dataType->slug.'.index', array_merge($params, ['showSoftDeleted' => 0]), true) }}';
        $('<a id="redir" href="' + href + '"></a>').appendTo('body')[0].click();
    });
    @endif

});
</script>
@stop