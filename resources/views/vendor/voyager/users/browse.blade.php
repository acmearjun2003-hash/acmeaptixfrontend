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
    #table-scroll-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #ccc #f5f5f5;
    }

    #table-scroll-wrapper::-webkit-scrollbar { height: 6px; }
    #table-scroll-wrapper::-webkit-scrollbar-track { background: #f5f5f5; border-radius: 3px; }
    #table-scroll-wrapper::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }

    #dataTable { min-width: 700px; }

    /* ── Loading overlay ── */
    #api-loading {
        display: none;
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,.75);
        z-index: 10;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
    }
    #api-loading.active { display: flex; }
    .spinner {
        width: 36px; height: 36px;
        border: 3px solid #e0e0e0;
        border-top-color: #5897fb;
        border-radius: 50%;
        animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Filter bar ── */
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
        display: flex; flex-direction: column; gap: 4px;
        flex: 1 1 130px; min-width: 120px; max-width: 210px;
    }
    .mf-group label {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .9px; color: #999; margin: 0; white-space: nowrap;
    }
    .mf-control {
        height: 34px; padding: 4px 8px; font-size: 13px;
        border: 1px solid #d0d0d0; border-radius: 4px; background: #fff;
        outline: none; transition: border-color .15s, box-shadow .15s;
        width: 100%; box-sizing: border-box;
    }
    .mf-control:focus { border-color: #5897fb; box-shadow: 0 0 0 2px rgba(88,151,251,.18); }
    .mf-control.filter-active { border-color: #5897fb; background: #f0f5ff; }
    .mf-actions { display: flex; flex-direction: column; align-items: center; gap: 4px; justify-content: flex-end; flex-shrink: 0; }
    #btn-clear-filters {
        height: 34px; padding: 0 14px; font-size: 12px; font-weight: 600;
        border: 1px solid #ccc; border-radius: 4px; background: #fff;
        cursor: pointer; color: #555; white-space: nowrap;
        transition: background .15s, border-color .15s;
    }
    #btn-clear-filters:hover { background: #f0f0f0; border-color: #aaa; }
    #filter-count { font-size: 11px; color: #888; text-align: center; min-height: 15px; white-space: nowrap; }

    /* ── Aptiscore sort ── */
    #th-aptiscore { cursor: default; user-select: none; }
    .apti-sort-icon { display: inline-block; width: 14px; text-align: center; }
    .apti-sort-asc  .apti-sort-icon::after { content: '▲'; font-size: 10px; color: #5897fb; margin-left: 3px; }
    .apti-sort-desc .apti-sort-icon::after { content: '▼'; font-size: 10px; color: #5897fb; margin-left: 3px; }

    /* ── API error banner ── */
    #api-error {
        display: none;
        padding: 10px 14px;
        background: #fff3f3;
        border: 1px solid #f5c6c6;
        border-radius: 4px;
        color: #c0392b;
        font-size: 13px;
        margin-bottom: 12px;
    }
    #api-error.active { display: block; }

    /* ── Pagination ── */
    #api-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 10px;
        flex-wrap: wrap;
        gap: 8px;
        font-size: 13px;
        color: #666;
    }
    .api-page-btn {
        display: inline-block;
        padding: 4px 10px;
        border: 1px solid #d0d0d0;
        border-radius: 4px;
        background: #fff;
        cursor: pointer;
        font-size: 12px;
        color: #333;
        margin: 0 2px;
        transition: background .12s, border-color .12s;
    }
    .api-page-btn:hover { background: #f0f5ff; border-color: #5897fb; }
    .api-page-btn.active { background: #5897fb; color: #fff; border-color: #5897fb; }
    .api-page-btn:disabled { opacity: .45; cursor: not-allowed; }
</style>
@stop

@section('content')
<div class="page-content browse container-fluid">
    @include('voyager::alerts')

    {{-- API error banner --}}
    <div id="api-error"></div>

    {{-- Filter bar --}}
    <div id="multi-filter-bar">
        <div class="mf-group">
            <label for="mf_role">Role</label>
            <select id="mf_role" class="mf-control">
                <option value="">All roles</option>
            </select>
        </div>
        <div class="mf-group">
            <label for="mf_post">Post</label>
            <select id="mf_post" class="mf-control">
                <option value="">All posts</option>
            </select>
        </div>
        <div class="mf-group">
            <label for="mf_quali">Qualification</label>
            <select id="mf_quali" class="mf-control">
                <option value="">All qualifications</option>
                <option value="SSC">SSC</option>
                <option value="HSC">HSC</option>
                <option value="Diploma">Diploma</option>
                <option value="Degree">Degree</option>
                <option value="MasterDegree">Master Degree</option>
            </select>
        </div>
        <div class="mf-group">
            <label for="mf_apti_sort">Aptiscore</label>
            <select id="mf_apti_sort" class="mf-control">
                <option value="">No sort</option>
                <option value="desc">&#9660;&nbsp; Highest first</option>
                <option value="asc">&#9650;&nbsp; Lowest first</option>
            </select>
        </div>
        <div class="mf-actions">
            <button type="button" id="btn-clear-filters">&#x2715;&nbsp;Clear all</button>
        </div>
        <span id="filter-count"></span>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-bordered">
                <div class="panel-body" style="position:relative;">

                    {{-- Loading overlay --}}
                    <div id="api-loading">
                        <div class="spinner"></div>
                    </div>

                    <div id="table-scroll-wrapper">
                        <table id="dataTable" class="table table-hover">
                            <thead>
                                <tr>
                                    @foreach($dataType->browseRows as $row)
                                    <th @if($row->field === 'aptiscore') id="th-aptiscore" @endif>
                                        {{ $row->getTranslatedAttribute('display_name') }}
                                        @if($row->field === 'aptiscore')<span class="apti-sort-icon"></span>@endif
                                    </th>
                                    @endforeach
                                    <th class="actions text-right">{{ __('voyager::generic.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="api-tbody">
                                <tr><td colspan="20" class="text-center" style="padding:30px;color:#aaa;">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- API-driven pagination --}}
                    <div id="api-pagination" style="display:none;">
                        <div id="api-showing"></div>
                        <div id="api-pages"></div>
                    </div>

                </div>
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

    /*
    |--------------------------------------------------------------------------
    | CONFIG
    | API_BASE  — backend API root (port 8001)
    | API_TOKEN — Sanctum token stored in session by the login interceptor,
    |             printed into the page by Laravel so JS can read it once.
    |--------------------------------------------------------------------------
    */
    const API_BASE  = 'http://localhost:8001/api';
    const API_TOKEN = '{{ session("api_token", "") }}';

    // Voyager browse-row definitions passed from the controller
    @php
        $browseRowsForJs = $dataType->browseRows->map(function($r) {
            return [
                'field'        => $r->field,
                'type'         => $r->type,
                'display_name' => $r->getTranslatedAttribute('display_name'),
                'details'      => $r->details,
            ];
        })->values()->toArray();
    @endphp
    const BROWSE_ROWS = {!! json_encode($browseRowsForJs) !!};

    // Edit route template — we replace __ID__ in JS
    const EDIT_ROUTE_TPL = '{{ route("voyager.".$dataType->slug.".edit", "__ID__") }}';

    /*
    |--------------------------------------------------------------------------
    | STATE
    |--------------------------------------------------------------------------
    */
    let allUsers     = [];   // full list fetched from API
    let filteredRows = [];   // after client-side filters applied
    let currentPage  = 1;
    const PER_PAGE   = 15;

    /*
    |--------------------------------------------------------------------------
    | FETCH from backend API
    |--------------------------------------------------------------------------
    */
    async function fetchUsers(params = {}) {
        showLoading(true);
        hideError();

        const qs = new URLSearchParams(
            Object.entries(params).filter(([, v]) => v !== '' && v != null)
        ).toString();

        const url = `${API_BASE}/users${qs ? '?' + qs : ''}`;

        try {
            const res = await fetch(url, {
                method : 'GET',
                headers: {
                    'Accept'       : 'application/json',
                    'Content-Type' : 'application/json',
                    'Authorization': `Bearer ${API_TOKEN}`,
                },
            });

            if (!res.ok) {
                const err = await res.json().catch(() => ({}));
                throw new Error(err.message || `HTTP ${res.status}`);
            }

            const payload = await res.json();

            /*
             * payload.data may be:
             *   • a plain array  (server_side = false)
             *   • a paginator object with .data[] (server_side = true)
             */
            allUsers = Array.isArray(payload.data)
                ? payload.data
                : (payload.data.data ?? []);

            applyFiltersAndRender();

        } catch (e) {
            showError('Failed to load users: ' + e.message);
            renderRows([]);
        } finally {
            showLoading(false);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FILTER — pure client-side, no extra API call needed
    |--------------------------------------------------------------------------
    */
    function applyFiltersAndRender() {
        const roleFilter  = $('#mf_role').val().trim();
        const postFilter  = $('#mf_post').val().trim();
        const qualiFilter = $('#mf_quali').val().trim();
        const aptiSort    = $('#mf_apti_sort').val();

        // Filter
        filteredRows = allUsers.filter(u => {
            const roleId = String(u.role_id ?? '');
            const postId = String(
                (u.post && typeof u.post === 'object') ? u.post.post_id : (u.post ?? '')
            );
            const quali  = String(u.highestquali ?? '');

            return (!roleFilter  || roleId === roleFilter)
                && (!postFilter  || postId === postFilter)
                && (!qualiFilter || quali  === qualiFilter);
        });

        // Sort by aptiscore if selected
        if (aptiSort) {
            filteredRows = [...filteredRows].sort((a, b) => {
                const na = parseFloat(a.aptiscore) || 0;
                const nb = parseFloat(b.aptiscore) || 0;
                return aptiSort === 'desc' ? nb - na : na - nb;
            });
            $('#th-aptiscore')
                .removeClass('apti-sort-asc apti-sort-desc')
                .addClass('apti-sort-' + aptiSort);
        } else {
            $('#th-aptiscore').removeClass('apti-sort-asc apti-sort-desc');
        }

        // Toggle filter-active classes
        $('#mf_role').toggleClass('filter-active',     !!roleFilter);
        $('#mf_post').toggleClass('filter-active',     !!postFilter);
        $('#mf_quali').toggleClass('filter-active',    !!qualiFilter);
        $('#mf_apti_sort').toggleClass('filter-active', !!aptiSort);

        const active = !!(roleFilter || postFilter || qualiFilter || aptiSort);
        $('#filter-count').text(active ? filteredRows.length + ' / ' + allUsers.length + ' rows' : '');

        currentPage = 1;
        renderPage();
        populateFilterDropdowns();
    }

    /*
    |--------------------------------------------------------------------------
    | RENDER — current page slice of filteredRows
    |--------------------------------------------------------------------------
    */
    function renderPage() {
        const total = filteredRows.length;
        const pages = Math.max(1, Math.ceil(total / PER_PAGE));
        if (currentPage > pages) currentPage = pages;

        const start = (currentPage - 1) * PER_PAGE;
        const slice = filteredRows.slice(start, start + PER_PAGE);

        renderRows(slice);
        renderPagination(total, pages);
    }

    function renderRows(rows) {
        const $tbody = $('#api-tbody');
        $tbody.empty();

        if (!rows.length) {
            $tbody.append(
                '<tr><td colspan="20" class="text-center" style="padding:30px;color:#aaa;">No records found.</td></tr>'
            );
            return;
        }

        rows.forEach(user => {
            const $tr = $('<tr>');

            BROWSE_ROWS.forEach(row => {
                const $td  = $('<td>');
                const field = row.field;

                /*
                 * ── Resolve post name ──────────────────────────────────
                 * API returns post as a full object: { post_id, post_name, … }
                 * We read post_id and post_name directly from it.
                 */
                let postId   = '';
                let postName = '';
                if (user.post && typeof user.post === 'object') {
                    postId   = user.post.post_id   ?? '';
                    postName = user.post.post_name ?? '';
                } else if (user.post) {
                    postId = user.post;
                }

                /*
                 * ── Resolve role name ──────────────────────────────────
                 * API returns role as a full object: { id, name, display_name }
                 */
                const roleId   = user.role_id ?? (user.role ? user.role.id : '');
                const roleName = user.role ? (user.role.name ?? '') : '';

                // Stamp data-* attributes for filter JS to read
                if (field === 'user_belongsto_role_relationship' || field === 'role_id') {
                    $td.attr('data-role-id',   roleId);
                    $td.attr('data-role-name', roleName);
                }
                if (field === 'post') {
                    $td.attr('data-post-id',   postId);
                    $td.attr('data-post-name', postName);
                }
                if (field === 'highestquali') {
                    $td.attr('data-highestquali', user.highestquali ?? '');
                }
                if (field === 'aptiscore') {
                    $td.attr('data-aptiscore', isNaN(parseFloat(user.aptiscore)) ? '' : user.aptiscore);
                }

                // ── Cell content ───────────────────────────────────────
                let cellValue = '';

                if (field === 'post') {
                    // Always show post_name, never the raw FK integer
                    cellValue = postName || postId || '—';

                } else if (field === 'user_belongsto_role_relationship' || field === 'role_id') {
                    cellValue = user.role ? (user.role.display_name || user.role.name) : (user.role_id ?? '—');

                } else if (row.type === 'image') {
                    const src = user[field] || '';
                    cellValue = src ? `<img src="${src}" style="width:100px">` : '';

                } else if (row.type === 'checkbox') {
                    cellValue = user[field]
                        ? '<span class="label label-info">Yes</span>'
                        : '<span class="label label-primary">No</span>';

                } else if (row.type === 'color') {
                    const c = user[field] || '';
                    cellValue = `<span class="badge badge-lg" style="background-color:${c}">${c}</span>`;

                } else if (row.type === 'date' || row.type === 'timestamp') {
                    cellValue = user[field] ? new Date(user[field]).toLocaleDateString() : '';

                } else if (row.type === 'file') {
                    // File field — API returns JSON string: [{"download_link":"...","original_name":"..."}]
                    const raw = user[field];
                    let files = [];
                    try {
                        const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
                        files = Array.isArray(parsed) ? parsed : [];
                    } catch(e) { files = []; }

                    if (!files.length) {
                        cellValue = '—';
                    } else {
                        // STORAGE_URL is generated by Laravel's Storage facade (same disk Voyager uses)
                        // so it resolves correctly regardless of local/S3/etc config.
                        const STORAGE_BASE = '{{ rtrim(Storage::disk(config("voyager.storage.disk"))->url(""), "/") }}';
                        cellValue = files.map(function(f) {
                            // Normalise backslashes from Windows paths stored in DB
                            const link    = (f.download_link || '').replace(/\\\\/g, '/').replace(/\\/g, '/');
                            const name    = f.original_name || link.split('/').pop() || 'Download';
                            const fullUrl = STORAGE_BASE + '/' + link.replace(/^\/+/, '');
                            return '<a href="' + fullUrl + '" target="_blank" rel="noopener" '
                                 + 'style="display:inline-flex;align-items:center;gap:5px;'
                                 + 'color:#5897fb;text-decoration:none;font-size:13px;">'
                                 + '<i class="voyager-file-text" style="font-size:14px;"></i>'
                                 + '<span>' + name + '</span>'
                                 + '</a>';
                        }).join('<br>');
                    }

                } else {
                    // Default — truncate long text
                    const val = String(user[field] ?? '');
                    cellValue = val.length > 200 ? val.substring(0, 200) + ' …' : val;
                }

                $td.html(cellValue);
                $tr.append($td);
            });

            // ── Actions column ─────────────────────────────────────────
            const editUrl = EDIT_ROUTE_TPL.replace('__ID__', user.id);
            $tr.append(`
                <td class="no-sort no-click bread-actions">
                    <a href="${editUrl}" title="Edit" class="btn btn-sm btn-warning pull-right edit">
                        <i class="voyager-edit"></i>
                        <span class="hidden-xs hidden-sm">Edit</span>
                    </a>
                </td>
            `);

            $('#api-tbody').append($tr);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | PAGINATION
    |--------------------------------------------------------------------------
    */
    function renderPagination(total, pages) {
        const $pg = $('#api-pagination');

        if (total === 0) { $pg.hide(); return; }
        $pg.show();

        const from = (currentPage - 1) * PER_PAGE + 1;
        const to   = Math.min(currentPage * PER_PAGE, total);
        $('#api-showing').text(`Showing ${from}–${to} of ${total} entries`);

        const $pages = $('#api-pages').empty();

        // Prev
        const $prev = $('<button class="api-page-btn">‹ Prev</button>')
            .prop('disabled', currentPage === 1)
            .on('click', () => { currentPage--; renderPage(); });
        $pages.append($prev);

        // Page numbers (show max 5 around current)
        const range = buildPageRange(currentPage, pages);
        range.forEach(p => {
            if (p === '…') {
                $pages.append('<span style="padding:0 4px;color:#aaa;">…</span>');
            } else {
                const $btn = $(`<button class="api-page-btn${p === currentPage ? ' active' : ''}">${p}</button>`)
                    .on('click', () => { currentPage = p; renderPage(); });
                $pages.append($btn);
            }
        });

        // Next
        const $next = $('<button class="api-page-btn">Next ›</button>')
            .prop('disabled', currentPage === pages)
            .on('click', () => { currentPage++; renderPage(); });
        $pages.append($next);
    }

    function buildPageRange(cur, total) {
        if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
        const pages = [];
        pages.push(1);
        if (cur > 3) pages.push('…');
        for (let p = Math.max(2, cur - 1); p <= Math.min(total - 1, cur + 1); p++) pages.push(p);
        if (cur < total - 2) pages.push('…');
        pages.push(total);
        return pages;
    }

    /*
    |--------------------------------------------------------------------------
    | POPULATE FILTER DROPDOWNS from rendered rows
    |--------------------------------------------------------------------------
    */
    function populateFilterDropdowns() {
        buildDropdown('#mf_role', 'data-role-id',  'data-role-name');
        buildDropdown('#mf_post', 'data-post-id',  'data-post-name');
    }

    function buildDropdown(selectId, attrVal, attrLabel) {
        const $sel    = $(selectId);
        const current = $sel.val();
        const seen    = new Set();
        const opts    = [];

        // Read from ALL filteredRows, not just the current page slice
        filteredRows.forEach(user => {
            let val = '', label = '';

            if (attrVal === 'data-role-id') {
                val   = String(user.role_id ?? (user.role ? user.role.id : ''));
                label = user.role ? (user.role.name ?? '') : '';
            } else if (attrVal === 'data-post-id') {
                val   = String(user.post && typeof user.post === 'object'
                    ? (user.post.post_id ?? '')
                    : (user.post ?? ''));
                label = user.post && typeof user.post === 'object'
                    ? (user.post.post_name ?? '')
                    : '';
            }

            if (val && !seen.has(val)) {
                seen.add(val);
                opts.push({ val, label: label || val });
            }
        });

        opts.sort((a, b) => a.label.localeCompare(b.label));

        $sel.find('option:not(:first)').remove();
        opts.forEach(o => $sel.append(`<option value="${o.val}">${o.label}</option>`));

        if (current) $sel.val(current);
    }

    /*
    |--------------------------------------------------------------------------
    | UI HELPERS
    |--------------------------------------------------------------------------
    */
    function showLoading(on) {
        $('#api-loading').toggleClass('active', on);
    }

    function showError(msg) {
        $('#api-error').text(msg).addClass('active');
    }

    function hideError() {
        $('#api-error').text('').removeClass('active');
    }

    /*
    |--------------------------------------------------------------------------
    | EVENT BINDINGS
    |--------------------------------------------------------------------------
    */
    $('#mf_role, #mf_post, #mf_quali, #mf_apti_sort').on('change', applyFiltersAndRender);

    $('#btn-clear-filters').on('click', function () {
        $('#mf_role, #mf_post, #mf_quali, #mf_apti_sort').val('').removeClass('filter-active');
        $('#th-aptiscore').removeClass('apti-sort-asc apti-sort-desc');
        $('#filter-count').text('');
        applyFiltersAndRender();
    });

    /*
    |--------------------------------------------------------------------------
    | BOOT — fetch on page load
    |--------------------------------------------------------------------------
    */
    fetchUsers();

});
</script>
@stop