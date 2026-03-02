<?php

namespace App\Http\Controllers\Voyager;

use App\Models\CustomerMaster;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class CustomerController extends VoyagerBaseController
{
    use BreadRelationshipParser;

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
            // if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
            //     $usesSoftDeletes = true;

            //     if ($request->get('showSoftDeleted')) {
            //         $showSoftDeleted = true;
            //         $query = $query->withTrashed();
            //     }
            // }

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


                return  $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }


            // Replace relationships' keys for labels and create READ links if a slug is provided.
            // $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);


            //using API for fetching user data
            $response = Http::post("http://localhost:8001/api/ocf",["offset"=>$request->offset,'page'=>$request->page]);
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
        // if (Auth::user()->can('delete', app($dataType->model_name))) {
        //     $showCheckboxColumn = true;
        // } else {
        //     foreach ($actions as $action) {
        //         if (method_exists($action, 'massAction')) {
        //             $showCheckboxColumn = true;
        //         }
        //     }
        // }


        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }


        // Define list of columns that can be sorted server side
        $sortableColumns = $this->getSortableColumns($dataType->browseRows);
        // $showCheckboxColumn = false;
        $view = 'voyager::bread.browse';


        if (view()->exists("voyager::$slug.browse")) {
            $view = "voyager::$slug.browse";
        }


        // $showCheckboxColumn = false;
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


    // public function fetchData()
    // {
    //     return response()->stream(function () {

    //         $rows = DB::table('customermaster as m')
    //             ->orderBy('m.OWNCODE', 'desc')
    //             ->cursor();

    //         $count = 0;
    //         foreach ($rows as $row) {
    //             echo json_encode($row) . "\n"; // ✅ one JSON object per line
    //             $count++;

    //             if ($count % 50 === 0) {
    //                 if (ob_get_level() > 0) ob_flush();
    //                 flush();
    //             }
    //         }

    //         if (ob_get_level() > 0) ob_flush();
    //         flush();
    //     }, 200, [
    //         'Content-Type'      => 'application/json',
    //         'X-Accel-Buffering' => 'no',
    //         'Cache-Control'     => 'no-cache',
    //     ]);
    // }

    





    public function edit(Request $request, $id)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (strlen($dataType->model_name) !== 0) {
            $model = app($dataType->model_name);

            $response = $this->api()->get("/customers/{$id}");
            if ($response->failed()) {
                return back()->with([
                    'message' => "Customer #{$id} not found (API returned {$response->status()}).",
                    'alert-type' => 'error',
                ]);
            }

            $dataTypeContent = $model->newInstance();
            $dataTypeContent->setRawAttributes((array) $response->json(), true);
            $dataTypeContent->exists = true;
        } else {
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        $this->removeRelationshipField($dataType, 'edit');

        $this->authorize('edit', $dataTypeContent);

        $isModelTranslatable = is_bread_translatable($dataTypeContent);
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';
        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    public function update(Request $request, $id)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('edit', app($dataType->model_name));

        $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();

        $payload = $request->except(['_token', '_method']);
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = json_encode($value);
            }
        }

        $apiResponse = $this->api()->put("/customers/{$id}", $payload);

        if (!$apiResponse->successful()) {
            return redirect()->back()->withInput()->with([
                'message' => 'API update failed (' . $apiResponse->status() . '): ' . $apiResponse->body(),
                'alert-type' => 'error',
            ]);
        }

        event(new BreadDataUpdated($dataType, (object) ['id' => $id]));

        return redirect()->route("voyager.{$dataType->slug}.index")->with([
            'message' => __('voyager::generic.successfully_updated') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }

    public function create(Request $request)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('add', app($dataType->model_name));

        $dataTypeContent = (strlen($dataType->model_name) !== 0)
            ? new $dataType->model_name()
            : false;

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        $this->removeRelationshipField($dataType, 'add');

        $isModelTranslatable = is_bread_translatable($dataTypeContent);
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'add', $isModelTranslatable);

        $view = 'voyager::bread.edit-add';
        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    public function store(Request $request)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('add', app($dataType->model_name));

        $this->validateBread($request->all(), $dataType->addRows)->validate();

        $payload = $request->except(['_token', '_method']);
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = json_encode($value);
            }
        }

        $response = $this->api()->post('/customers', $payload);

        if (!$response->successful()) {
            return redirect()->back()->withInput()->with([
                'message' => 'API create failed (' . $response->status() . '): ' . $response->body(),
                'alert-type' => 'error',
            ]);
        }

        event(new BreadDataAdded($dataType, (object) $response->json()));

        if ($request->has('_tagging')) {
            return response()->json(['success' => true, 'data' => $response->json()]);
        }

        return redirect()->route("voyager.{$dataType->slug}.index")->with([
            'message' => __('voyager::generic.successfully_added_new') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $slug = $this->getSlug($request);
        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $this->authorize('delete', app($dataType->model_name));

        $ids = empty($id)
            ? explode(',', $request->input('ids', ''))
            : [$id];

        $ids = array_filter(array_map('trim', $ids));

        $failed = [];
        foreach ($ids as $itemId) {
            $res = $this->api()->delete("/customers/{$itemId}");
            if ($res->failed()) {
                $failed[] = $itemId;
            }
        }

        if (!empty($failed)) {
            return redirect()->route("voyager.{$dataType->slug}.index")->with([
                'message' => 'Failed to delete customer ID(s): ' . implode(', ', $failed),
                'alert-type' => 'error',
            ]);
        }

        event(new BreadDataDeleted($dataType, (object) ['ids' => $ids]));

        $displayName = count($ids) > 1
            ? $dataType->getTranslatedAttribute('display_name_plural')
            : $dataType->getTranslatedAttribute('display_name_singular');

        return redirect()->route("voyager.{$dataType->slug}.index")->with([
            'message' => __('voyager::generic.successfully_deleted') . " {$displayName}",
            'alert-type' => 'success',
        ]);
    }
}
