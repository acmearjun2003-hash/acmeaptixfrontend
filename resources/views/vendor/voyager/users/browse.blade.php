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
        {{-- 
        @can('delete', app($dataType->model_name))
            @include('voyager::partials.bulk-delete')
        @endcan
         --}}
        @can('edit', app($dataType->model_name))
            @if(!empty($dataType->order_column) && !empty($dataType->order_display_column))
                <a href="{{ route('voyager.'.$dataType->slug.'.order') }}" class="btn btn-primary btn-add-new">
                    <i class="voyager-list"></i> <span>{{ __('voyager::bread.order') }}</span>
                </a>
            @endif
        @endcan
        @can('delete', app($dataType->model_name))
            @if($usesSoftDeletes)
                <input type="checkbox" @if ($showSoftDeleted) checked @endif id="show_soft_deletes" data-toggle="toggle" data-on="{{ __('voyager::bread.soft_deletes_off') }}" data-off="{{ __('voyager::bread.soft_deletes_on') }}">
            @endif
        @endcan
        @foreach($actions as $action)
            @if (method_exists($action, 'massAction'))
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
@stop

@section('content')
    <div class="page-content browse container-fluid">
        @include('voyager::alerts')

        {{-- Multi filters --}}
        <form id="user-filters" method="GET" action="{{ url()->current() }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="mf_role">Role</label>
                        <select id="mf_role" name="role_id" class="form-control">
                            <option value="">All roles</option>
                            @foreach($rolesDropdown as $name => $id)
                                <option value="{{ $id }}" @if((string)$id === (string)request('role_id')) selected @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="mf_post">Post</label>
                        <select id="mf_post" name="post" class="form-control">
                            <option value="">All posts</option>
                            @foreach($postsDropdown as $name => $id)
                                <option value="{{ $id }}" @if((string)$id === (string)request('post')) selected @endif>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="mf_quali">Qualification</label>
                        <select id="mf_quali" name="highestquali" class="form-control">
                            <option value="">All qualifications</option>
                            <option value="SSC" @if(request('highestquali') === 'SSC') selected @endif>SSC</option>
                            <option value="HSC" @if(request('highestquali') === 'HSC') selected @endif>HSC</option>
                            <option value="Diploma" @if(request('highestquali') === 'Diploma') selected @endif>Diploma</option>
                            <option value="Degree" @if(request('highestquali') === 'Degree') selected @endif>Degree</option>
                            <option value="MasterDegree" @if(request('highestquali') === 'MasterDegree') selected @endif>Master Degree</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="mf_apti_sort">Aptiscore</label>
                        <select id="mf_apti_sort" name="aptiscore_sort" class="form-control">
                            <option value="">No sort</option>
                            <option value="desc" @if(request('aptiscore_sort') === 'desc') selected @endif>&#9660;&nbsp; Highest first</option>
                            <option value="asc" @if(request('aptiscore_sort') === 'asc') selected @endif>&#9650;&nbsp; Lowest first</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" id="btn-clear-filters" class="btn btn-default btn-block">Clear</button>
                    </div>
                </div>
            </div>

            {{-- Preserve existing search / sort / soft-delete params when applying filters --}}
            <input type="hidden" name="s" value="{{ request('s') }}">
            <input type="hidden" name="key" value="{{ request('key') }}">
            <input type="hidden" name="filter" value="{{ request('filter') }}">
            <input type="hidden" name="order_by" value="{{ $orderBy }}">
            <input type="hidden" name="sort_order" value="{{ $sortOrder }}">
            @if($showSoftDeleted)
                <input type="hidden" name="showSoftDeleted" value="1">
            @endif
        </form>

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body">
                        @if ($isServerSide)
                            <form method="get" class="form-search">
                                <div id="search-input">
                                    <div class="col-2">
                                        <select id="search_key" name="key">
                                            @foreach($searchNames as $key => $name)
                                                <option value="{{ $key }}" @if($search->key == $key || (empty($search->key) && $key == $defaultSearchKey)) selected @endif>{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-2">
                                        <select id="filter" name="filter">
                                            <option value="contains" @if($search->filter == "contains") selected @endif>contains</option>
                                            <option value="equals" @if($search->filter == "equals") selected @endif>=</option>
                                        </select>
                                    </div>
                                    <div class="input-group col-md-12">
                                        <input type="text" class="form-control" placeholder="{{ __('voyager::generic.search') }}" name="s" value="{{ $search->value }}">
                                        <span class="input-group-btn">
                                            <button class="btn btn-info btn-lg" type="submit">
                                                <i class="voyager-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                                @if (Request::has('sort_order') && Request::has('order_by'))
                                    <input type="hidden" name="sort_order" value="{{ Request::get('sort_order') }}">
                                    <input type="hidden" name="order_by" value="{{ Request::get('order_by') }}">
                                @endif
                            </form>
                        @endif
                        <div class="table-responsive">
                            <table id="dataTable" class="table table-hover">
                                <thead>
                                    <tr>
                                        @if($showCheckboxColumn)
                                            <th class="dt-not-orderable">
                                                <input type="checkbox" class="select_all">
                                            </th>
                                        @endif
                                        @foreach($dataType->browseRows as $row)
                                        <th>
                                            @if ($isServerSide && in_array($row->field, $sortableColumns))
                                                <a href="{{ $row->sortByUrl($orderBy, $sortOrder) }}">
                                            @endif
                                            {{ $row->getTranslatedAttribute('display_name') }}
                                            @if ($isServerSide)
                                                @if ($row->isCurrentSortField($orderBy))
                                                    @if ($sortOrder == 'asc')
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
                                    @include('voyager::users.partials.tbody')
                                </tbody>
                            </table>
                        </div>
                        @if ($isServerSide)
                            <div class="pull-left">
                                <div role="status" class="show-res" aria-live="polite">{{ trans_choice(
                                    'voyager::generic.showing_entries', $dataTypeContent->total(), [
                                        'from' => $dataTypeContent->firstItem(),
                                        'to' => $dataTypeContent->lastItem(),
                                        'all' => $dataTypeContent->total()
                                    ]) }}</div>
                            </div>
                            <div class="pull-right">
                                {{ $dataTypeContent->appends([
                                    's' => $search->value,
                                    'filter' => $search->filter,
                                    'key' => $search->key,
                                    'order_by' => $orderBy,
                                    'sort_order' => $sortOrder,
                                    'showSoftDeleted' => $showSoftDeleted,
                                ])->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::generic.delete_question') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</h4>
                </div>
                <div class="modal-footer">
                    <form action="#" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm" value="{{ __('voyager::generic.delete_confirm') }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@stop


@section('javascript')
    <!-- DataTables -->
    @if(!$dataType->server_side && config('dashboard.data_tables.responsive'))
        <script src="{{ voyager_asset('lib/js/dataTables.responsive.min.js') }}"></script>
    @endif
    @php
        $softDeleteBaseParams = [
            's' => $search->value,
            'filter' => $search->filter,
            'key' => $search->key,
            'order_by' => $orderBy,
            'sort_order' => $sortOrder,
        ];

        $dtOptions = array_merge([
            "order" => $orderColumn,
            "language" => __('voyager::datatable'),
            "columnDefs" => [
                ['targets' => 'dt-not-orderable', 'searchable' =>  false, 'orderable' => false],
            ],
        ], config('voyager.dashboard.data_tables', []));
    @endphp
    <div
        id="users-browse-config"
        data-server-side="{{ $dataType->server_side ? 1 : 0 }}"
        data-model-translatable="{{ $isModelTranslatable ? 1 : 0 }}"
        data-uses-soft-deletes="{{ $usesSoftDeletes ? 1 : 0 }}"
        data-delete-url-template="{{ route('voyager.'.$dataType->slug.'.destroy', '__id') }}"
        data-soft-delete-on-url="{{ route('voyager.'.$dataType->slug.'.index', array_merge($softDeleteBaseParams, ['showSoftDeleted' => 1]), true) }}"
        data-soft-delete-off-url="{{ route('voyager.'.$dataType->slug.'.index', array_merge($softDeleteBaseParams, ['showSoftDeleted' => 0]), true) }}"
        data-dt-options='{{ json_encode($dtOptions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) }}'
        style="display:none"
    ></div>
    
    <script>
        (function () {
            var cfg = document.getElementById('users-browse-config');
            if (!cfg) return;

            var isServerSide = cfg.getAttribute('data-server-side') === '1';
            var isModelTranslatable = cfg.getAttribute('data-model-translatable') === '1';
            var usesSoftDeletes = cfg.getAttribute('data-uses-soft-deletes') === '1';
            var deleteUrlTemplate = cfg.getAttribute('data-delete-url-template') || '';
            var softDeleteOnUrl = cfg.getAttribute('data-soft-delete-on-url') || '';
            var softDeleteOffUrl = cfg.getAttribute('data-soft-delete-off-url') || '';

            var dtOptionsRaw = cfg.getAttribute('data-dt-options') || '';
            var dtOptions = null;
            try { dtOptions = JSON.parse(dtOptionsRaw); } catch (e) { dtOptions = null; }

            function hasDataTable() {
                return $.fn.DataTable && $.fn.dataTable && $.fn.dataTable.isDataTable('#dataTable');
            }

            function destroyDataTableIfAny() {
                if (hasDataTable()) {
                    $('#dataTable').DataTable().destroy();
                }
            }

            function initDataTableIfNeeded() {
                if (isServerSide) return;
                if (!dtOptions || !$.fn.DataTable) return;
                $('#dataTable').DataTable(dtOptions);
            }

            function initMultilingualIfNeeded() {
                if (!isModelTranslatable) return;
                if (!$('.side-body').data('multilingual')) return;
                $('.side-body').data('multilingual').init();
            }

            function applyColorBadges() {
                $('.js-color-badge').each(function () {
                    var c = $(this).data('color');
                    if (c) {
                        $(this).css('background-color', c);
                    }
                });
            }

            function updateBrowserUrlFromForm($form) {
                var browserParams = $form.serializeArray().filter(function (p) {
                    return p.name !== 'ajax';
                });
                var qs = $.param(browserParams);
                var url = window.location.pathname + (qs ? ('?' + qs) : '');
                window.history.replaceState(null, '', url);
            }

            function refreshTableFromFilters() {
                var $form = $('#user-filters');
                if (!$form.length) return;

                var requestParams = $form.serializeArray();
                requestParams.push({ name: 'ajax', value: '1' });
                updateBrowserUrlFromForm($form);

                destroyDataTableIfAny();
                $('#dataTable tbody').html('<tr><td colspan="100%">Loading...</td></tr>');

                $.ajax({
                    url: window.location.pathname,
                    method: 'GET',
                    data: $.param(requestParams),
                    dataType: 'json',
                }).done(function (resp) {
                    if (resp && resp.tbody !== undefined) {
                        $('#dataTable tbody').html(resp.tbody);
                    }
                    initDataTableIfNeeded();
                    initMultilingualIfNeeded();
                    applyColorBadges();
                }).fail(function () {
                    $('#dataTable tbody').html('<tr><td colspan="100%">Failed to load data.</td></tr>');
                });
            }

            $(document).ready(function () {
                if (!isServerSide) {
                    initDataTableIfNeeded();
                } else {
                    $('#search-input select').select2({
                        minimumResultsForSearch: Infinity
                    });
                }

                if (isModelTranslatable) {
                    $('.side-body').multilingual();
                    $('#dataTable').on('draw.dt', function(){
                        initMultilingualIfNeeded();
                    });
                }

                applyColorBadges();

                $('.select_all').on('click', function(e) {
                    $('input[name="row_id"]').prop('checked', $(this).prop('checked')).trigger('change');
                });

                $('#mf_role, #mf_post, #mf_quali, #mf_apti_sort').on('change', refreshTableFromFilters);

                $('#btn-clear-filters').on('click', function () {
                    $('#mf_role').val('');
                    $('#mf_post').val('');
                    $('#mf_quali').val('');
                    $('#mf_apti_sort').val('');
                    refreshTableFromFilters();
                });

                if (usesSoftDeletes) {
                    $('#show_soft_deletes').on('change', function() {
                        window.location.href = $(this).prop('checked') ? softDeleteOnUrl : softDeleteOffUrl;
                    });
                }
            });

            $(document).on('click', '.delete', function () {
                if (deleteUrlTemplate) {
                    $('#delete_form')[0].action = deleteUrlTemplate.replace('__id', $(this).data('id'));
                }
                $('#delete_modal').modal('show');
            });

            $(document).on('change', 'input[name="row_id"]', function () {
                var ids = [];
                $('input[name="row_id"]').each(function() {
                    if ($(this).is(':checked')) {
                        ids.push($(this).val());
                    }
                });
                $('.selected_ids').val(ids);
            });
        })();
    </script>
@stop
