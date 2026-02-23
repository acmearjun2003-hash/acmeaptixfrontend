<?php
// app/Http/Controllers/Voyager/VoyagerUserController.php  (FRONTEND)

namespace App\Http\Controllers\Voyager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use TCG\Voyager\Http\Controllers\VoyagerBaseController;

class VoyagerUserController extends VoyagerBaseController
{
    use BreadRelationshipParser;

    private string $apiBase = 'http://localhost:8001/api';

    /*
    |--------------------------------------------------------------------------
    | HTTP CLIENT — forwards session api_token to backend
    |--------------------------------------------------------------------------
    */
    private function api(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken(session('api_token', ''))
                   ->acceptJson()
                   ->baseUrl($this->apiBase);
    }

    private function getDataType(): object
    {
        return Voyager::model('DataType')
            ->where('slug', 'users')
            ->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | BROWSE — GET /admin/users
    | Fetches list from backend API → passes to Voyager browse view
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        return $dataType = $this->getDataType();
        $this->authorize('browse', app($dataType->model_name));

     
        return  $response = $this->api()->get('/users', $request->query());
        

        if ($response->failed()) {
            return back()->with([
                'message'    => 'Failed to fetch users from API.',
                'alert-type' => 'error',
            ]);
        }

        $payload             = $response->json();
        $dataTypeContent     = collect($payload['data']);
        $search              = (object) ($payload['search']         ?? ['value' => '', 'key' => '', 'filter' => '']);
        $orderBy             = $payload['orderBy']                  ?? $dataType->order_column;
        $sortOrder           = $payload['sortOrder']                ?? $dataType->order_direction;
        $orderColumn         = $payload['orderColumn']              ?? [];
        $sortableColumns     = $payload['sortableColumns']          ?? [];
        $searchNames         = $payload['searchNames']              ?? [];
        $usesSoftDeletes     = $payload['usesSoftDeletes']          ?? false;
        $showSoftDeleted     = $payload['showSoftDeleted']          ?? false;
        $showCheckboxColumn  = $payload['showCheckboxColumn']       ?? false;
        $isServerSide        = $payload['isServerSide']             ?? false;
        $defaultSearchKey    = $payload['defaultSearchKey']         ?? null;
        $isModelTranslatable = false;

        // Build Voyager actions for the browse toolbar
        $actions = [];
        if ($dataTypeContent->isNotEmpty()) {
            foreach (Voyager::actions() as $action) {
                $instance = new $action($dataType, (object) $dataTypeContent->first());
                if ($instance->shouldActionDisplayOnDataType()) {
                    $actions[] = $instance;
                }
            }
        }

        $view = view()->exists('voyager::users.browse')
            ? 'voyager::users.browse'
            : 'voyager::bread.browse';

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

    /*
    |--------------------------------------------------------------------------
    | ADD FORM — GET /admin/users/create
    | Fetches empty scaffold + addRows from backend → renders Voyager add form
    |--------------------------------------------------------------------------
    */
    public function create(Request $request)
    {
        $dataType = $this->getDataType();
        $this->authorize('add', app($dataType->model_name));

        $response = $this->api()->get('/users/create');

        if ($response->failed()) {
            return back()->with([
                'message'    => 'Failed to load create form from API.',
                'alert-type' => 'error',
            ]);
        }

        $payload             = $response->json();
        $dataTypeContent     = (object) ($payload['data']   ?? []);
        $isModelTranslatable = $payload['isModelTranslatable'] ?? false;

        // Merge col_width from API addRows into the local dataType
        // so Voyager's edit-add blade renders correctly
        if (!empty($payload['addRows'])) {
            foreach ($dataType->addRows as $key => $row) {
                $apiRow = collect($payload['addRows'])
                    ->firstWhere('field', $row->field);
                $dataType->addRows[$key]['col_width'] =
                    $apiRow['col_width'] ?? $row->details->width ?? 100;
            }
        }

        $view = view()->exists('voyager::users.edit-add')
            ? 'voyager::users.edit-add'
            : 'voyager::bread.edit-add';

        return Voyager::view($view, compact(
            'dataType',
            'dataTypeContent',
            'isModelTranslatable'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT FORM — GET /admin/users/{id}/edit
    | Fetches prefilled user + editRows from backend → renders Voyager edit form
    |--------------------------------------------------------------------------
    */
    public function edit(Request $request, $id)
    {
        $dataType = $this->getDataType();
        $this->authorize('edit', app($dataType->model_name));

        $response = $this->api()->get("/users/{$id}/edit");

        if ($response->failed()) {
            return back()->with([
                'message'    => 'Failed to load edit form from API.',
                'alert-type' => 'error',
            ]);
        }

        $payload             = $response->json();
        $dataTypeContent     = (object) ($payload['data']      ?? []);
        $isModelTranslatable = $payload['isModelTranslatable'] ?? false;

        // Merge col_width from API editRows into local dataType editRows
        if (!empty($payload['editRows'])) {
            foreach ($dataType->editRows as $key => $row) {
                $apiRow = collect($payload['editRows'])
                    ->firstWhere('field', $row->field);
                $dataType->editRows[$key]['col_width'] =
                    $apiRow['col_width'] ?? $row->details->width ?? 100;
            }
        }

        $view = view()->exists('voyager::users.edit-add')
            ? 'voyager::users.edit-add'
            : 'voyager::bread.edit-add';

        return Voyager::view($view, compact(
            'dataType',
            'dataTypeContent',
            'isModelTranslatable'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE — POST /admin/users
    | Forwards form data to backend API → redirects with flash message
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $dataType = $this->getDataType();
        $this->authorize('add', app($dataType->model_name));

        $response = $this->api()->post('/users', $request->except(['_token']));

        if ($response->failed()) {
            return back()
                ->withInput()
                ->withErrors($response->json('errors', []))
                ->with(['message' => 'Failed to create user.', 'alert-type' => 'error']);
        }

        event(new BreadDataAdded($dataType, (object) $response->json('data', [])));

        return redirect()
            ->route('voyager.users.index')
            ->with([
                'message'    => __('voyager::generic.successfully_added_new') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE — PUT /admin/users/{id}
    | Forwards form data to backend API → redirects with flash message
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $dataType = $this->getDataType();
        $this->authorize('edit', app($dataType->model_name));

        $response = $this->api()->put("/users/{$id}", $request->except(['_token', '_method']));

        if ($response->failed()) {
            return back()
                ->withInput()
                ->withErrors($response->json('errors', []))
                ->with(['message' => 'Failed to update user.', 'alert-type' => 'error']);
        }

        event(new BreadDataUpdated($dataType, (object) $response->json('data', [])));

        return redirect()
            ->route('voyager.users.index')
            ->with([
                'message'    => __('voyager::generic.successfully_updated') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY — DELETE /admin/users/{id}
    | Forwards delete to backend API → redirects with flash message
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request, $id)
    {
        $dataType = $this->getDataType();
        $this->authorize('delete', app($dataType->model_name));

        $response = empty($id) && $request->filled('ids')
            ? $this->api()->delete('/users', ['ids' => $request->input('ids')])
            : $this->api()->delete("/users/{$id}");

        if ($response->failed()) {
            return back()->with([
                'message'    => 'Failed to delete user.',
                'alert-type' => 'error',
            ]);
        }

        event(new BreadDataDeleted($dataType, (object) $response->json()));

        return redirect()
            ->route('voyager.users.index')
            ->with([
                'message'    => __('voyager::generic.successfully_deleted') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
    }
}