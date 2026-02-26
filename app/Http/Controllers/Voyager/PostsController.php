<?php

namespace App\Http\Controllers\Voyager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;
use TCG\Voyager\Models\Permission;

class PostsController extends VoyagerBaseController
{
    use BreadRelationshipParser;

   
    // =========================================================================
    //  B — BROWSE
    // =========================================================================

      public function index(Request $request)
    {
        // GET THE SLUG, ex. 'posts', 'pages', etc.
        $slug = $this->getSlug($request);

        // GET THE DataType based on the slug
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];
        if ($dataType->server_side) {
            $searchNames = $dataType->browseRows->mapWithKeys(function ($row) {
                return [$row['field'] => $row->getTranslatedAttribute('display_name')];
            });
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            $query = $model::select($dataType->name . '.*');

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope' . ucfirst($dataType->scope))) {
                $query->{$dataType->scope}();
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%' . $search->value . '%';

                $searchField = $dataType->name . '.' . $search->key;
                if ($row = $this->findSearchableRelationshipRow($dataType->rows->where('type', 'relationship'), $search->key)) {
                    $query->whereIn(
                        $searchField,
                        $row->details->model::where($row->details->label, $search_filter, $search_value)->pluck('id')->toArray()
                    );
                } else {
                    if ($dataType->browseRows->pluck('field')->contains($search->key)) {
                        $query->where($searchField, $search_filter, $search_value);
                    }
                }
            }

            $row = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
            if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($row))) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                if (!empty($row)) {
                    $query->select([
                        $dataType->name . '.*',
                        'joined.' . $row->details->label . ' as ' . $orderBy,
                    ])->leftJoin(
                        $row->details->table . ' as joined',
                        $dataType->name . '.' . $row->details->column,
                        'joined.' . $row->details->key
                    );
                }

                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);

            //using API for fetching user data
            $response = Http::get("http://localhost:8001/api/posts");
            $responseData = $response->json();


            $dataTypeContent = collect($responseData)->map(function ($item) use ($model) {
                $instance = $model->newInstance();
                $instance->setRawAttributes((array) $item, true); // ← fill with $item data
                $instance->exists = true;                          // ← mark as existing DB record
                return $instance;
            });
            // dd($dataTypeContent);

        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($model);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'browse', $isModelTranslatable);

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        // $actions = [];
        // if (!empty($dataTypeContent->first())) {
        //     foreach (Voyager::actions() as $action) {
        //         $action = new $action($dataType, $dataTypeContent->first());

        //         if ($action->shouldActionDisplayOnDataType()) {
        //             $actions[] = $action;
        //         }
        //     }
        // }

        $actions = [];
        if (!empty($dataTypeContent)) {
            foreach (Voyager::actions() as $action) {
                // Only include the Edit action
                if (!str_ends_with($action, 'EditAction')) {
                    continue;
                }

                $action = new $action($dataType, $dataTypeContent);

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);
        $showCheckboxColumn = false;
        $view = 'voyager::bread.browse';

        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }

        $showCheckboxColumn = false;
        return Voyager::view($view, compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortableColumns',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn'
        ));
    }

    // =========================================================================
    //  R — READ (show)
    // =========================================================================

    public function show(Request $request, $id)
    {
        $slug     = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('read', app($dataType->model_name));

        // ── Fetch single role from API ────────────────────────────────────
        $response = $this->api()->get("/posts/{$id}");

        if ($response->failed()) {
            return back()->with([
                'message'    => "Role #{$id} not found (API returned {$response->status()}).",
                'alert-type' => 'error',
            ]);
        }

        $dataTypeContent     = $this->toObject($response->json());
        $isModelTranslatable = false;
        $isSoftDeleted       = false;

        $view = $this->resolveView($slug, 'read');

        return Voyager::view($view, compact(
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'isSoftDeleted'
        ));
    }

    // =========================================================================
    //  E — EDIT (GET)
    // =========================================================================

    // public function edit(Request $request, $id)
    // {
    //     $slug     = $this->getSlug($request);
    //     $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

    //     $this->authorize('edit', app($dataType->model_name));

    //     // ── Fetch single role from API ────────────────────────────────────
    //     $response = $this->api()->get("/posts/{$id}");

    //     if ($response->failed()) {
    //         return back()->with([
    //             'message'    => "Role #{$id} not found (API returned {$response->status()}).",
    //             'alert-type' => 'error',
    //         ]);
    //     }

    //     // Hydrate the API response with the permissions collection that
    //     // Voyager's posts/edit-add.blade.php requires.
    //     $dataTypeContent = $this->hydrateRoleObject($response->json());

    //     // Voyager uses col_width from editRows details
    //     foreach ($dataType->editRows as $key => $row) {
    //         $dataType->editRows[$key]['col_width'] = $row->details->width ?? 100;
    //     }

    //     $isModelTranslatable = false;

    //     $view = $this->resolveView($slug, 'edit-add');

    //     return Voyager::view($view, compact(
    //         'dataType',
    //         'dataTypeContent',
    //         'isModelTranslatable'
    //     ));
    // }

    // =========================================================================
    //  E — UPDATE (PUT/PATCH)
    // =========================================================================

    public function update(Request $request, $id)
    {
        $slug     = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('edit', app($dataType->model_name));

        // ── Validate via Voyager rules ────────────────────────────────────
        $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)
             ->validate();

        // ── Collect fields defined in editRows ────────────────────────────
        $payload = $this->buildPayload($request, $dataType->editRows);

        // ── Send PUT to API ───────────────────────────────────────────────
        $response = $this->api()->put("/posts/{$id}", $payload);

        if ($response->failed()) {
            return back()
                ->withInput()
                ->with([
                    'message'    => 'API error while updating role: ' . $this->extractApiError($response),
                    'alert-type' => 'error',
                ]);
        }

        $data = $this->toObject($response->json());

        // ── Sync permissions in the local pivot table ─────────────────────
        // Voyager's role form posts `permissions` as an array of permission IDs.
        // These are stored locally (permission_role pivot), not on the API.
        $roleModel = Voyager::model('Role')->find($id);
        if ($roleModel && $request->has('permissions')) {
            $permissionIds = array_filter(array_map('intval', (array) $request->input('permissions')));
            $roleModel->permissions()->sync($permissionIds);
        }

        event(new BreadDataUpdated($dataType, $data));

        return redirect()
            ->route("voyager.{$dataType->slug}.index")
            ->with([
                'message'    => __('voyager::generic.successfully_updated') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
    }

    // =========================================================================
    //  A — CREATE (GET)
    // =========================================================================

    // public function create(Request $request)
    // {
    //     $slug     = $this->getSlug($request);
    //     $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

    //     $this->authorize('add', app($dataType->model_name));

    //     // Blank object with empty permissions collection so Voyager's
    //     // posts/edit-add.blade.php can iterate $dataTypeContent->permissions
    //     // without throwing "Undefined property".
    //     $dataTypeContent = $this->hydrateRoleObject([]);

    //     foreach ($dataType->addRows as $key => $row) {
    //         $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
    //     }

    //     $isModelTranslatable = false;

    //     $view = $this->resolveView($slug, 'edit-add');

    //     return Voyager::view($view, compact(
    //         'dataType',
    //         'dataTypeContent',
    //         'isModelTranslatable'
    //     ));
    // }

    // =========================================================================
    //  A — STORE (POST)
    // =========================================================================

    public function store(Request $request)
    {
        $slug     = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('add', app($dataType->model_name));

        // ── Validate via Voyager rules ────────────────────────────────────
        $this->validateBread($request->all(), $dataType->addRows)->validate();

        // ── Collect fields defined in addRows ─────────────────────────────
        $payload = $this->buildPayload($request, $dataType->addRows);

        // ── Send POST to API ──────────────────────────────────────────────
        $response = $this->api()->post('/posts', $payload);

        if ($response->failed()) {
            return back()
                ->withInput()
                ->with([
                    'message'    => 'API error while creating role: ' . $this->extractApiError($response),
                    'alert-type' => 'error',
                ]);
        }

        $data = $this->toObject($response->json());

        // ── Sync permissions in the local pivot table ─────────────────────
        // The API creates the role; we sync its permissions locally so Voyager's
        // permission checks work correctly.
        $newId = $data->id ?? null;
        if ($newId && $request->has('permissions')) {
            $roleModel     = Voyager::model('Role')->find($newId);
            $permissionIds = array_filter(array_map('intval', (array) $request->input('permissions')));
            if ($roleModel) {
                $roleModel->permissions()->sync($permissionIds);
            }
        }

        event(new BreadDataAdded($dataType, $data));

        // Handle tagging (Voyager's inline relation creator)
        if ($request->has('_tagging')) {
            return response()->json(['success' => true, 'data' => $data]);
        }

        return redirect()
            ->route("voyager.{$dataType->slug}.index")
            ->with([
                'message'    => __('voyager::generic.successfully_added_new') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
    }

    // =========================================================================
    //  D — DESTROY (DELETE)
    // =========================================================================

    public function destroy(Request $request, $id)
    {
        $slug     = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('delete', app($dataType->model_name));

        // Support bulk delete (comma-separated ids in POST body)
        $ids = empty($id)
            ? explode(',', $request->input('ids', ''))
            : [$id];

        $ids    = array_filter(array_map('intval', $ids));
        $failed = [];
        $lastData = (object) [];

        foreach ($ids as $itemId) {
            $response = $this->api()->delete("/posts/{$itemId}");

            if ($response->failed()) {
                $failed[] = $itemId;
                continue;
            }

            $lastData = $this->toObject(array_merge(
                ['id' => $itemId],
                is_array($response->json()) ? $response->json() : []
            ));
        }

        if (!empty($failed)) {
            return redirect()
                ->route("voyager.{$dataType->slug}.index")
                ->with([
                    'message'    => 'Failed to delete role ID(s): ' . implode(', ', $failed),
                    'alert-type' => 'error',
                ]);
        }

        $displayName = count($ids) > 1
            ? $dataType->getTranslatedAttribute('display_name_plural')
            : $dataType->getTranslatedAttribute('display_name_singular');

        event(new BreadDataDeleted($dataType, $lastData));

        return redirect()
            ->route("voyager.{$dataType->slug}.index")
            ->with([
                'message'    => __('voyager::generic.successfully_deleted') . " {$displayName}",
                'alert-type' => 'success',
            ]);
    }

    // =========================================================================
    //  RELATION  — Select2 AJAX endpoint (used by relationship formfields)
    // =========================================================================

    public function relation(Request $request)
    {
        $slug     = $this->getSlug($request);
        $page     = (int) $request->input('page', 1);
        $onPage   = 50;
        $search   = $request->input('search', false);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        $method   = $request->input('method', 'add');

        $this->authorize($method, app($dataType->model_name));

        $rows = $dataType->{$method . 'Rows'};

        foreach ($rows as $row) {
            if ($row->field !== $request->input('type')) {
                continue;
            }

            $options  = $row->details;
            $apiPath  = $this->guessApiPathFromModel($options->model ?? '');
            $response = $this->api()->get($apiPath);

            if ($response->failed()) {
                return response()->json(['results' => [], 'pagination' => ['more' => false]]);
            }

            $all = collect($response->json());

            // Filter by search term
            if ($search) {
                $labelField = $options->label ?? 'name';
                $all = $all->filter(
                    fn($item) => stripos($item[$labelField] ?? '', $search) !== false
                );
            }

            // Sort
            if (!empty($options->sort->field)) {
                $dir = strtolower($options->sort->direction ?? 'asc');
                $all = $dir === 'desc'
                    ? $all->sortByDesc($options->sort->field)
                    : $all->sortBy($options->sort->field);
            }

            $total   = $all->count();
            $results = [];

            if (!$row->required && !$search && $page === 1) {
                $results[] = ['id' => '', 'text' => __('voyager::generic.none')];
            }

            $keyField   = $options->key   ?? 'id';
            $labelField = $options->label ?? 'name';

            $all->forPage($page, $onPage)->each(function ($item) use (&$results, $keyField, $labelField) {
                $results[] = [
                    'id'   => $item[$keyField]   ?? '',
                    'text' => $item[$labelField] ?? '',
                ];
            });

            return response()->json([
                'results'    => $results,
                'pagination' => ['more' => $total > ($onPage * ($page - 1) + $onPage)],
            ]);
        }

        return response()->json([], 404);
    }

    // =========================================================================
    //  REMOVE MEDIA  — file / image deletion from edit form
    // =========================================================================

    public function remove_media(Request $request)
    {
        // posts typically don't have file fields.
        // Extend this if your Role model gains file/image columns.
        return response()->json([
            'data' => [
                'status'  => 501,
                'message' => 'Media removal is not supported for API-backed resources.',
            ],
        ], 501);
    }

    // =========================================================================
    //  ORDER  — drag-and-drop ordering
    // =========================================================================

    public function order(Request $request)
    {
        $slug     = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('edit', app($dataType->model_name));

        if (empty($dataType->order_column) || empty($dataType->order_display_column)) {
            return redirect()
                ->route("voyager.{$dataType->slug}.index")
                ->with([
                    'message'    => __('voyager::bread.ordering_not_set'),
                    'alert-type' => 'error',
                ]);
        }

        $response = $this->api()->get('/posts');
        $results  = collect($response->json())
            ->map(fn($item) => $this->toObject($item))
            ->sortBy($dataType->order_column)
            ->values();

        $display_column = $dataType->order_display_column;
        $dataRow        = Voyager::model('DataRow')
            ->whereDataTypeId($dataType->id)
            ->whereField($display_column)
            ->first();

        $view = $this->resolveView($slug, 'order');

        return Voyager::view($view, compact(
            'dataType',
            'display_column',
            'dataRow',
            'results'
        ));
    }

    public function update_order(Request $request)
    {
        $slug     = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('edit', app($dataType->model_name));

        $order  = json_decode($request->input('order'));
        $column = $dataType->order_column;

        foreach ($order as $key => $item) {
            // PATCH each item's order column individually
            $this->api()->patch("/posts/{$item->id}", [
                $column => $key + 1,
            ]);
        }
    }

    // =========================================================================
    //  MASS ACTION
    // =========================================================================

    public function action(Request $request)
    {
        $slug     = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $action = new $request->action($dataType, null);

        return $action->massAction(
            explode(',', $request->ids),
            $request->headers->get('referer')
        );
    }

    // =========================================================================
    //  PRIVATE UTILITIES
    // =========================================================================

    /**
     * Build a payload array from the request using only the fields
     * defined in the given BREAD rows collection.
     */
    private function buildPayload(Request $request, $rows): array
    {
        $payload = [];

        foreach ($rows as $row) {
            $field = $row->field;

            // Skip virtual relationship fields — extract FK column instead
            if ($row->type === 'relationship') {
                $fk = $row->details->column ?? null;
                if ($fk && $request->has($fk)) {
                    $payload[$fk] = $request->input($fk);
                }
                continue;
            }

            if (!$request->has($field)) {
                continue;
            }

            $value = $request->input($field);

            // Normalise checkbox / toggle → boolean
            if (in_array($row->type, ['checkbox', 'toggleswitch'])) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            // Normalise select_multiple / multiple_checkbox → array
            if (in_array($row->type, ['select_multiple', 'multiple_checkbox'])) {
                $value = is_array($value) ? $value : json_decode($value, true) ?? [];
            }

            $payload[$field] = $value;
        }

        return $payload;
    }

    /**
     * Extract a human-readable error string from a failed HTTP response.
     */
    private function extractApiError(\Illuminate\Http\Client\Response $response): string
    {
        $body = $response->json();

        if (is_array($body)) {
            return $body['message']
                ?? $body['error']
                ?? json_encode($body);
        }

        return (string) $response->body() ?: "HTTP {$response->status()}";
    }

    /**
     * Naive guess of the API path from a fully-qualified model class name.
     * e.g.  App\Models\Role  →  /posts
     *       App\Models\Post  →  /posts
     */
    private function guessApiPathFromModel(string $modelClass): string
    {
        if (empty($modelClass)) {
            return '/';
        }

        $basename = class_basename($modelClass);   // "Role"

        // Use Laravel's Str::plural if available, else append 's'
        $plural = class_exists(\Illuminate\Support\Str::class)
            ? \Illuminate\Support\Str::plural(strtolower($basename))
            : strtolower($basename) . 's';

        return '/' . $plural;
    }

    /**
     * Return sortable column names from a rows collection.
     */
    protected function getSortableColumns($rows): array
    {
        return $rows->filter(function ($item) {
            if ($item->type !== 'relationship') {
                return true;
            }
            if ($item->details->type !== 'belongsTo') {
                return false;
            }
            return !$this->relationIsUsingAccessorAsLabel($item->details);
        })->pluck('field')->toArray();
    }

    protected function relationIsUsingAccessorAsLabel($details): bool
    {
        return in_array(
            $details->label,
            app($details->model)->additional_attributes ?? []
        );
    }

     /** Base URL of the external API server */
    private const API_BASE = 'http://localhost:8001/api';

    // =========================================================================
    //  HELPERS
    // =========================================================================

    /**
     * Return a configured Http pending request.
     * Add auth headers / tokens here once needed.
     */
    private function api(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl(self::API_BASE)
                   ->acceptJson()
                   ->timeout(15);
    }

    /**
     * Resolve the Voyager DataType for the current slug.
     */
    private function getDataType(Request $request)
    {
        $slug = $this->getSlug($request);
        return Voyager::model('DataType')->where('slug', '=', $slug)->first();
    }

    /**
     * Build the Voyager actions array from a sample item.
     */
    private function buildActions($dataType, $firstItem): array
    {
        $actions = [];
        foreach (Voyager::actions() as $actionClass) {
            $action = new $actionClass($dataType, $firstItem);
            if ($action->shouldActionDisplayOnDataType()) {
                $actions[] = $action;
            }
        }
        return $actions;
    }

    /**
     * Cast an associative array (from JSON) to a plain object so Voyager
     * blade templates can access properties with ->field syntax.
     */
    private function toObject(array $item): object
    {
        return (object) $item;
    }

    /**
     * Take a raw API role array/object and attach the `permissions` relationship
     * that Voyager's posts/edit-add.blade.php unconditionally accesses via
     * $dataTypeContent->permissions->pluck('id').
     *
     * For an existing role we load its permission IDs from the local pivot table
     * (permission_role).  For a new role we attach an empty collection.
     */
    private function hydrateRoleObject($raw): object
    {
        // Normalise to array first
        $data = is_array($raw) ? $raw : (array) $raw;

        $roleId = $data['id'] ?? null;

        if ($roleId) {
            // Load the Voyager Role model with its permissions from the local DB
            $roleModel   = Voyager::model('Role')->with('permissions')->find($roleId);
            $permissions = $roleModel ? $roleModel->permissions : collect();
        } else {
            // Add mode — no permissions selected yet
            $permissions = collect();
        }

        $obj              = (object) $data;
        $obj->permissions = $permissions;

        return $obj;
    }

    /**
     * Resolve the correct browse / edit-add / read view path.
     */
    private function resolveView(string $slug, string $type): string
    {
        $custom = "voyager::$slug.$type";
        return view()->exists($custom) ? $custom : "voyager::bread.$type";
    }

}