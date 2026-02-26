{{--
    resources/views/vendor/voyager/users/edit-add.blade.php
--}}

@extends('voyager::master')

@section('page_title',
__('voyager::generic.'.(isset($dataTypeContent->id) ? 'edit' : 'add'))
.' '.$dataType->getTranslatedAttribute('display_name_singular')
)

@section('css')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .panel-section-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        color: #888;
        border-bottom: 1px solid #e8e8e8;
        padding-bottom: 8px;
        margin: 18px 0 14px;
    }

    .panel-section-title:first-child {
        margin-top: 0;
    }

    .form-group label {
        font-weight: 600;
        font-size: 13px;
    }

    .score-col .form-group label {
        font-size: 11px;
    }

    .score-col input {
        padding: 5px 8px;
        font-size: 13px;
        text-align: center;
    }

    .candidate-summary td {
        font-size: 13px;
        padding: 5px 8px !important;
    }

    .candidate-summary .text-muted {
        color: #999;
    }

    /* ── Validation feedback ── */
    .form-control.field-invalid {
        border-color: #e74c3c !important;
        box-shadow: 0 0 0 2px rgba(231, 76, 60, .12) !important;
    }

    .form-control.field-valid {
        border-color: #27ae60 !important;
    }

    .field-error {
        color: #e74c3c;
        font-size: 11px;
        margin-top: 3px;
        display: none;
    }

    /* ── Score field columns hidden until qualification chosen ── */
    .score-field-col {
        display: none;
    }

    .score-field-col.score-visible {
        display: block;
    }

    /* ── Form Status Widget ── */
    #form-status-panel .status-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 12px;
    }

    #form-status-panel .status-row:last-child {
        border-bottom: none;
    }

    #form-status-panel .status-icon {
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        flex-shrink: 0;
        font-weight: 700;
    }

    .status-icon.s-ok {
        background: #27ae60;
        color: #fff;
    }

    .status-icon.s-err {
        background: #e74c3c;
        color: #fff;
    }

    .status-icon.s-idle {
        background: #ddd;
        color: #888;
    }

    .status-label {
        flex: 1;
        color: #555;
    }

    .status-msg {
        color: #999;
        font-size: 11px;
    }

    /* ── Role badge ── */
    .role-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .role-badge.candidate {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .role-badge.hr {
        background: #e3f2fd;
        color: #1565c0;
    }

    .role-badge.admin {
        background: #fce4ec;
        color: #ad1457;
    }

    .role-badge.other {
        background: #f5f5f5;
        color: #666;
    }

    /* ── Validation rules quick-ref ── */
    .rules-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .rules-list li {
        padding: 5px 0;
        border-bottom: 1px solid #f5f5f5;
        font-size: 12px;
        color: #555;
        display: flex;
        gap: 7px;
    }

    .rules-list li:last-child {
        border-bottom: none;
    }

    .rules-list li .rule-icon {
        color: #aaa;
        font-size: 13px;
        flex-shrink: 0;
    }

    /* ── Account tips ── */
    .tip-card {
        background: #f9f9f9;
        border-radius: 6px;
        padding: 10px 12px;
        margin-bottom: 8px;
        font-size: 12px;
        color: #555;
        border-left: 3px solid #ddd;
    }

    .tip-card.tip-blue {
        border-color: #2196F3;
        background: #e3f2fd22;
    }

    .tip-card.tip-green {
        border-color: #4CAF50;
        background: #e8f5e922;
    }

    .tip-card.tip-orange {
        border-color: #FF9800;
        background: #fff8e122;
    }

    .tip-card strong {
        display: block;
        margin-bottom: 2px;
        color: #333;
    }
</style>
@stop

@section('page_header')
<h1 class="page-title">
    <i class="{{ $dataType->icon }}"></i>
    {{ __('voyager::generic.'.(isset($dataTypeContent->id) ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }}
</h1>
@stop

@section('content')
@php
$isAddMode = !isset($dataTypeContent->id);
@endphp
<div class="page-content container-fluid">
    <form id="user-edit-add-form" class="form-edit-add" role="form"
        action="@if(!is_null($dataTypeContent->getKey())){{ route('voyager.'.$dataType->slug.'.update', $dataTypeContent->getKey()) }}@else{{ route('voyager.'.$dataType->slug.'.store') }}@endif"
        method="POST" enctype="multipart/form-data" autocomplete="off">

        @if(isset($dataTypeContent->id))
        {{ method_field('PUT') }}
        @endif
        {{ csrf_field() }}

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @php
        $operation = isset($dataTypeContent->id) ? 'edit' : 'add';
        $dataTypeRows = $dataType->{$operation.'Rows'};

        $accountFields    = ['name','email','password','locale'];
        $contactFields    = ['mobileno'];
        $roleFields       = ['user_belongsto_role_relationship','user_belongstomany_role_relationship'];
        $avatarFields     = ['avatar'];
        $candidateFields  = ['post','referenceby','highestquali'];
        $scoreFields      = ['ssc','hsc','diploma','degree','masterdegree'];
        $assessmentFields = ['aptiscore','examstarted','aptidate','aptitime','techroundpercent','interviewpercent'];

        // FIX: was $has('mobileno' || $has('email')) — JS short-circuit in PHP string
        $has = fn($field) => $dataTypeRows->where('field', $field)->isNotEmpty();

        $knownFields = array_merge(
            $accountFields, $contactFields, $roleFields,
            $avatarFields, $candidateFields, $scoreFields, $assessmentFields
        );
        $extraRows = $dataTypeRows->filter(fn($r) => !in_array($r->field, $knownFields));

        $currentRoleName = strtolower($dataTypeContent->role->name ?? '');
        $isCandidate = str_contains($currentRoleName, 'candidate');
        $isHR        = str_contains($currentRoleName, 'hr') || str_contains($currentRoleName, 'human');
        $isAdmin     = str_contains($currentRoleName, 'admin');

        $roleBadgeClass = $isCandidate ? 'candidate' : ($isHR ? 'hr' : ($isAdmin ? 'admin' : 'other'));
        @endphp

        <script>
            var CANDIDATE_ROLE_KEYWORD = 'candidate';
            var INITIAL_IS_CANDIDATE   = {{ $isCandidate ? 'true' : 'false' }};
            var IS_EDIT_MODE           = {{ isset($dataTypeContent->id) ? 'true' : 'false' }};
        </script>

        <div class="row">

            {{-- ═══════════════════ LEFT COLUMN ═══════════════════ --}}
            <div class="col-md-">

                {{-- ── PANEL 1 : Account Details ── --}}
                @php
                $showAccount = $dataTypeRows->whereIn('field',
                    array_merge($accountFields, $contactFields, $roleFields))->isNotEmpty();
                @endphp
                @if($showAccount)
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Account Details</h3>
                    </div>
                    <div class="panel-body">

                        {{-- FIX: was $has('mobileno' || $has('email')) — incorrect PHP --}}
                        @if($has('name') || $has('mobileno') || $has('email'))
                        <div class="row">
                            @if($has('name'))
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="name">{{ __('voyager::generic.name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        placeholder="Full Name" maxlength="20"
                                        value="{{ old('name', $dataTypeContent->name ?? '') }}">
                                    <span class="field-error" id="name-error"></span>
                                </div>
                            </div>
                            @endif
                            @if($has('mobileno'))
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="mobileno">Mobile Number</label>
                                    <input type="text" class="form-control" id="mobileno" name="mobileno"
                                        placeholder="10-digit mobile number" maxlength="10"
                                        value="{{ old('mobileno', $dataTypeContent->mobileno ?? '') }}">
                                    <span class="field-error" id="mobileno-error"></span>
                                </div>
                            </div>
                            @endif
                            @if($has('email'))
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="email">{{ __('voyager::generic.email') }} <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="Email Address"
                                        value="{{ old('email', $dataTypeContent->email ?? '') }}">
                                    <span class="field-error" id="email-error"></span>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        @can('editRoles', $dataTypeContent)
                        @if($has('password') || $has('user_belongsto_role_relationship') || $has('user_belongstomany_role_relationship'))
                        <div class="row">
                            @if($has('user_belongsto_role_relationship'))
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label>{{ __('voyager::profile.role_default') }}</label>
                                    @php
                                        $row     = $dataTypeRows->where('field','user_belongsto_role_relationship')->first();
                                        $options = $row->details;
                                    @endphp
                                    @include('voyager::formfields.relationship')
                                </div>
                            </div>
                            @endif

                            {{-- Additional roles commented out as in original --}}
                            {{-- @if($has('user_belongstomany_role_relationship')) ... @endif --}}

                            @if($has('password'))
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="password">{{ __('voyager::generic.password') }}</label>
                                    @if(isset($dataTypeContent->password))
                                    <small class="text-muted d-block">{{ __('voyager::profile.password_hint') }}</small>
                                    @endif
                                    <input type="password" class="form-control" id="password" name="password"
                                        value="" autocomplete="new-password">
                                    <span class="field-error" id="password-error"></span>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
                        @endcan

                        @if($has('locale'))
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="locale">{{ __('voyager::generic.locale') }}</label>
                                    @php $selected_locale = $dataTypeContent->locale ?? config('app.locale','en'); @endphp
                                    <select class="form-control select2" id="locale" name="locale">
                                        @foreach(Voyager::getLocales() as $locale)
                                        <option value="{{ $locale }}" {{ $locale == $selected_locale ? 'selected' : '' }}>
                                            {{ $locale }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
                @endif

                {{-- ── PANEL 2 : Candidate Details ── --}}
                @php
                $showCandidate = $dataTypeRows->whereIn('field',
                    array_merge($candidateFields, $scoreFields))->isNotEmpty();
                @endphp
                @if($showCandidate)
                <div class="panel panel-bordered" id="panel-candidate">
                    <div class="panel-heading">
                        <h3 class="panel-title">Candidate Details</h3>
                    </div>
                    <div class="panel-body">

                        @if($has('post') || $has('referenceby') || $has('highestquali'))
                        <div class="row">
                            @if($has('post'))
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="post">Applied Post</label>
                                    <input type="number" class="form-control" id="post" name="post"
                                        placeholder="e.g. 1" min="0"
                                        value="{{ old('post', $dataTypeContent->post ?? '') }}">
                                </div>
                            </div>
                            @endif
                            @if($has('referenceby'))
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="referenceby">Reference By</label>
                                    <input type="text" class="form-control" id="referenceby" name="referenceby"
                                        placeholder="e.g. Naukri / Referral"
                                        value="{{ old('referenceby', $dataTypeContent->referenceby ?? '') }}">
                                </div>
                            </div>
                            @endif
                            @if($has('highestquali'))
                            <div class="col-sm-4">
                                <div class="form-group">
                                    <label for="highestquali">Highest Qualification</label>
                                    <select class="form-control" id="highestquali" name="highestquali">
                                        <option value="">— Select —</option>
                                        @foreach(['SSC','HSC','Diploma','Degree','MasterDegree'] as $q)
                                        <option value="{{ $q }}"
                                            {{ old('highestquali', $dataTypeContent->highestquali ?? '') == $q ? 'selected' : '' }}>
                                            {{ $q === 'MasterDegree' ? 'Master Degree' : $q }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        @php
                        $scoreMap = [
                            'ssc'        => ['label' => 'SSC',     'order' => 1],
                            'hsc'        => ['label' => 'HSC',     'order' => 2],
                            'diploma'    => ['label' => 'Diploma', 'order' => 3],
                            'degree'     => ['label' => 'Degree',  'order' => 4],
                            'masterdegree' => ['label' => 'Masters','order'=> 5],
                        ];
                        $permittedScores = array_filter(array_keys($scoreMap), fn($f) => $has($f));
                        @endphp
                        @if(count($permittedScores))
                        <div class="panel-section-title">Academic Scores (%)</div>
                        <div class="row score-col" id="score-fields-row">
                            @foreach($permittedScores as $field)
                            <div class="col-xs-6 col-sm-2 score-field-col"
                                id="score-col-{{ $field }}"
                                data-score-order="{{ $scoreMap[$field]['order'] }}">
                                <div class="form-group">
                                    <label for="{{ $field }}">{{ $scoreMap[$field]['label'] }} %</label>
                                    <input type="number" class="form-control score-pct-input"
                                        id="{{ $field }}" name="{{ $field }}"
                                        placeholder="0" min="0" max="100" step="0.01"
                                        value="{{ old($field, $dataTypeContent->{$field} ?? '0') }}">
                                    <span class="field-error" id="{{ $field }}-error"></span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif

                    </div>
                </div>
                @endif

                {{-- ── PANEL 3 : Assessment Results ── --}}
                @php
                $showAssessment = $dataTypeRows->whereIn('field', $assessmentFields)->isNotEmpty();
                @endphp
                @if($showAssessment)
                <div class="panel panel-bordered" id="panel-assessment">
                    <div class="panel-heading">
                        <h3 class="panel-title">Assessment Results</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">

                            @if($has('aptiscore'))
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="aptiscore">Aptitude Score</label>
                                    <input type="number" class="form-control" id="aptiscore" name="aptiscore"
                                        placeholder="0" min="0"
                                        value="{{ old('aptiscore', $dataTypeContent->aptiscore ?? '0') }}">
                                </div>
                            </div>
                            @endif

                            @if($has('techroundpercent'))
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="techroundpercent">Tech Round %</label>
                                    <input type="number" class="form-control assess-pct-input"
                                        id="techroundpercent" name="techroundpercent"
                                        placeholder="0.00" min="0" max="100" step="0.01"
                                        value="{{ old('techroundpercent', $dataTypeContent->techroundpercent ?? '0.00') }}">
                                    <span class="field-error" id="techroundpercent-error"></span>
                                </div>
                            </div>
                            @endif

                            @if($has('interviewpercent'))
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="interviewpercent">Interview %</label>
                                    <input type="number" class="form-control assess-pct-input"
                                        id="interviewpercent" name="interviewpercent"
                                        placeholder="0.00" min="0" max="100" step="0.01"
                                        value="{{ old('interviewpercent', $dataTypeContent->interviewpercent ?? '0.00') }}">
                                    <span class="field-error" id="interviewpercent-error"></span>
                                </div>
                            </div>
                            @endif

                            @if($has('examstarted'))
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="examstarted">Exam Started</label>
                                    <select class="form-control" id="examstarted" name="examstarted">
                                        <option value="0" {{ old('examstarted', $dataTypeContent->examstarted ?? 0) == 0 ? 'selected' : '' }}>No</option>
                                        <option value="1" {{ old('examstarted', $dataTypeContent->examstarted ?? 0) == 1 ? 'selected' : '' }}>Yes</option>
                                    </select>
                                </div>
                            </div>
                            @endif

                        </div>

                        @if($has('aptidate') || $has('aptitime'))
                        <div class="row">
                            @if($has('aptidate'))
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="aptidate">Aptitude Date</label>
                                    <input type="text" class="form-control" id="aptidate" name="aptidate"
                                        placeholder="YYYYMMDD"
                                        value="{{ old('aptidate', $dataTypeContent->aptidate ?? '') }}">
                                </div>
                            </div>
                            @endif
                            @if($has('aptitime'))
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="aptitime">Aptitude Time</label>
                                    <input type="text" class="form-control" id="aptitime" name="aptitime"
                                        placeholder="HHMM"
                                        value="{{ old('aptitime', $dataTypeContent->aptitime ?? '') }}">
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                    </div>
                </div>
                @endif

                {{-- ── PANEL 4 : Extra fields ── --}}
                @if($extraRows->isNotEmpty())
                <div class="panel panel-bordered">
                    <div class="panel-heading">
                        <h3 class="panel-title">Additional Fields</h3>
                    </div>
                    <div class="panel-body">
                        @foreach($extraRows as $row)
                        <div class="form-group">
                            <label for="{{ $row->field }}">{{ $row->getTranslatedAttribute('display_name') }}</label>
                            @include('voyager::formfields.'.$row->type, [
                                'row'             => $row,
                                'dataType'        => $dataType,
                                'dataTypeContent' => $dataTypeContent,
                                'content'         => $dataTypeContent->{$row->field} ?? '',
                                'view'            => $operation,
                                'options'         => $row->details,
                            ])
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>{{-- end col-md-8 --}}

            {{-- ═══════════════════ RIGHT COLUMN ═══════════════════ --}}
            <div class="col-md-4">

                {{-- ── Avatar ── --}}
                @if($has('avatar'))
                <div class="panel panel-bordered panel-warning">
                    <div class="panel-heading">
                        <h3 class="panel-title">Profile Photo</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            @if(!empty($dataTypeContent->avatar))
                            <img src="{{ filter_var($dataTypeContent->avatar, FILTER_VALIDATE_URL)
                                ? $dataTypeContent->avatar
                                : Voyager::image($dataTypeContent->avatar) }}"
                                style="width:100%;max-width:200px;height:auto;display:block;
                                       padding:2px;border:1px solid #ddd;margin-bottom:10px;border-radius:4px;" />
                            @else
                            <div style="width:100%;max-width:200px;height:130px;background:#f9f9f9;
                                        border:1px dashed #ccc;border-radius:4px;display:flex;
                                        align-items:center;justify-content:center;margin-bottom:10px;
                                        color:#bbb;font-size:12px;">No Photo</div>
                            @endif
                            <input type="file" data-name="avatar" name="avatar" accept="image/*">
                            <small class="text-muted">JPG / PNG — Max 2MB</small>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ── Candidate Summary (candidate + edit only) ── --}}
                @if(isset($dataTypeContent->id))
                @php
                $summaryFields = array_merge($candidateFields, $scoreFields, $assessmentFields);
                $showSummary   = $isCandidate && $dataTypeRows->whereIn('field', $summaryFields)->isNotEmpty();
                @endphp
                @if($showSummary)
                <div class="panel panel-bordered panel-info" id="panel-candidate-summary">
                    <div class="panel-heading">
                        <h3 class="panel-title">Candidate Summary</h3>
                    </div>
                    <div class="panel-body" style="padding:10px">
                        <table class="table table-condensed candidate-summary" style="margin:0">
                            @if($has('post'))
                            <tr>
                                <td class="text-muted">Post</td>
                                <td><strong>{{ $dataTypeContent->post ?: '—' }}</strong></td>
                            </tr>
                            @endif
                            @if($has('highestquali'))
                            <tr>
                                <td class="text-muted">Qualification</td>
                                <td><strong>{{ $dataTypeContent->highestquali ?: '—' }}</strong></td>
                            </tr>
                            @endif
                            @if($has('aptiscore'))
                            <tr>
                                <td class="text-muted">Apti Score</td>
                                <td><strong>{{ $dataTypeContent->aptiscore ?? 0 }}</strong></td>
                            </tr>
                            @endif
                            @if($has('techroundpercent'))
                            <tr>
                                <td class="text-muted">Tech Round</td>
                                <td><strong>{{ $dataTypeContent->techroundpercent ?? '0.00' }}%</strong></td>
                            </tr>
                            @endif
                            @if($has('interviewpercent'))
                            <tr>
                                <td class="text-muted">Interview</td>
                                <td><strong>{{ $dataTypeContent->interviewpercent ?? '0.00' }}%</strong></td>
                            </tr>
                            @endif
                            @if($has('examstarted'))
                            <tr>
                                <td class="text-muted">Exam</td>
                                <td>
                                    @if($dataTypeContent->examstarted)
                                    <span class="label label-success">Started</span>
                                    @else
                                    <span class="label label-default">Not Started</span>
                                    @endif
                                </td>
                            </tr>
                            @endif
                            @if($has('referenceby'))
                            <tr>
                                <td class="text-muted">Reference</td>
                                <td><strong>{{ $dataTypeContent->referenceby ?: '—' }}</strong></td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>
                @endif
                @endif

            </div>{{-- end col-md-4 --}}

        </div>{{-- end .row --}}

        <button type="submit" class="btn btn-primary pull-right save" id="form-submit-btn">
            <i class="voyager-floppy"></i> {{ __('voyager::generic.save') }}
        </button>
    </form>

    <iframe id="form_target" name="form_target" style="display:none"></iframe>
    <form id="my_form" action="{{ route('voyager.upload') }}" target="form_target" method="post"
        enctype="multipart/form-data" style="width:0;height:0;overflow:hidden">
        {{ csrf_field() }}
        <input name="image" id="upload_file" type="file"
            onchange="$('#my_form').submit();this.value='';">
        <input type="hidden" name="type_slug" id="type_slug" value="{{ $dataType->slug }}">
    </form>

</div>
@stop

@section('javascript')
<script>
$(document).ready(function () {

    $('.toggleswitch').bootstrapToggle();

    var QUALI_ORDER = {
        'SSC': 1, 'HSC': 2, 'Diploma': 3, 'Degree': 4, 'MasterDegree': 5
    };

    // ═══════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════

    function setError($input, $span, msg) {
        $input.addClass('field-invalid').removeClass('field-valid');
        $span.text(msg).show();
    }

    function setValid($input, $span) {
        $input.removeClass('field-invalid').addClass('field-valid');
        $span.hide();
    }

    function resetState($input, $span) {
        $input.removeClass('field-invalid field-valid');
        $span.hide();
    }

    // ═══════════════════════════════════════════════
    // FORM STATUS WIDGET
    // ═══════════════════════════════════════════════

    function setStatusRow(rowId, iconClass, iconText, msgText) {
        var $row = $('#' + rowId);
        if (!$row.length) return;
        $row.find('.status-icon').removeClass('s-ok s-err s-idle')
            .addClass(iconClass).text(iconText);
        $row.find('.status-msg').text(msgText);
    }

    function updateOverallBadge() {
        var hasError     = $('#user-edit-add-form .field-invalid').length > 0;
        var hasUntouched = ($('#name').val().trim() === '' || $('#email').val().trim() === '');
        var $badge = $('#fs-overall-badge');
        if (!$badge.length) return;
        if (hasError) {
            $badge.css({ background: '#fdecea', color: '#c62828' }).text('Fix errors before saving');
        } else if (hasUntouched) {
            $badge.css({ background: '#f0f0f0', color: '#888' }).text('Fill required fields to continue');
        } else {
            $badge.css({ background: '#e8f5e9', color: '#2e7d32' }).text('✓ Ready to save');
        }
    }

    function syncStatusName() {
        var v = $('#name').val().trim();
        if (!v) {
            setStatusRow('fs-name', 's-idle', '—', 'not filled');
        } else if (v.length > 20) {
            setStatusRow('fs-name', 's-err', '✕', 'too long (' + v.length + '/20)');
        } else {
            setStatusRow('fs-name', 's-ok', '✓', v.length + '/20 chars');
        }
        updateOverallBadge();
    }

    function syncStatusMobile() {
        var v = $('#mobileno').val().trim();
        if (!v) {
            setStatusRow('fs-mobile', 's-idle', '—', 'optional');
        } else if (!/^\d{10}$/.test(v)) {
            setStatusRow('fs-mobile', 's-err', '✕', v.length + '/10 digits');
        } else {
            setStatusRow('fs-mobile', 's-ok', '✓', '10 digits ok');
        }
        updateOverallBadge();
    }

    function syncStatusEmail() {
        var v = $('#email').val().trim();
        if (!v) {
            setStatusRow('fs-email', 's-idle', '—', 'not filled');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) {
            setStatusRow('fs-email', 's-err', '✕', 'invalid format');
        } else {
            setStatusRow('fs-email', 's-ok', '✓', 'valid email');
        }
        updateOverallBadge();
    }

    function syncStatusPassword() {
        var v = $('#password').val();
        if (!v) {
            var msg = IS_EDIT_MODE ? 'unchanged' : 'required';
            var cls = IS_EDIT_MODE ? 's-ok' : 's-idle';
            var ico = IS_EDIT_MODE ? '✓' : '—';
            setStatusRow('fs-password', cls, ico, msg);
        } else if (v.length < 8) {
            setStatusRow('fs-password', 's-err', '✕', v.length + '/8 chars');
        } else {
            setStatusRow('fs-password', 's-ok', '✓', v.length + ' chars');
        }
        updateOverallBadge();
    }

    // ═══════════════════════════════════════════════
    // VALIDATORS
    // ═══════════════════════════════════════════════

    function validateName() {
        var $f = $('#name'), $e = $('#name-error');
        if (!$f.length) return true;
        var v = $f.val().trim();
        if (!v) { setError($f, $e, 'Name is required.'); return false; }
        if (v.length > 20) { setError($f, $e, 'Name must not exceed 20 characters (current: ' + v.length + ').'); return false; }
        setValid($f, $e);
        return true;
    }

    function validateMobile() {
        var $f = $('#mobileno'), $e = $('#mobileno-error');
        if (!$f.length) return true;
        var v = $f.val().trim();
        if (v === '') { resetState($f, $e); return true; }
        if (!/^\d{10}$/.test(v)) { setError($f, $e, 'Mobile number must be exactly 10 digits.'); return false; }
        setValid($f, $e);
        return true;
    }

    function validateEmail() {
        var $f = $('#email'), $e = $('#email-error');
        if (!$f.length) return true;
        var v = $f.val().trim();
        if (!v) { setError($f, $e, 'Email is required.'); return false; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) { setError($f, $e, 'Enter a valid email address.'); return false; }
        setValid($f, $e);
        return true;
    }

    function validatePassword() {
        var $f = $('#password'), $e = $('#password-error');
        if (!$f.length) return true;
        var v = $f.val();
        if (IS_EDIT_MODE && v === '') { resetState($f, $e); return true; }
        if (!IS_EDIT_MODE && v === '') { setError($f, $e, 'Password is required.'); return false; }
        if (v.length < 8) { setError($f, $e, 'Password must be at least 8 characters (current: ' + v.length + ').'); return false; }
        setValid($f, $e);
        return true;
    }

    function validatePctField($f) {
        if (!$f.length) return true;
        var id = $f.attr('id'), $e = $('#' + id + '-error');
        var $col = $f.closest('.score-field-col');
        if ($col.length && !$col.hasClass('score-visible')) {
            resetState($f, $e);
            return true;
        }
        var raw = $f.val(), v = parseFloat(raw);
        if (raw === '' || isNaN(v)) { setError($f, $e, 'Please enter a number between 0 and 100.'); return false; }
        if (v < 0 || v > 100) { setError($f, $e, 'Value must be between 0 and 100.'); return false; }
        setValid($f, $e);
        return true;
    }

    // ═══════════════════════════════════════════════
    // SCORE VISIBILITY  (Highest Qualification dropdown)
    // ═══════════════════════════════════════════════

    function updateScoreVisibility(selectedQuali) {
        var maxOrder = QUALI_ORDER[selectedQuali] || 0;
        $('.score-field-col').each(function () {
            var colOrder = parseInt($(this).data('score-order'), 10);
            if (maxOrder > 0 && colOrder <= maxOrder) {
                $(this).addClass('score-visible');
            } else {
                $(this).removeClass('score-visible');
                var $inp = $(this).find('input');
                resetState($inp, $('#' + $inp.attr('id') + '-error'));
            }
        });
    }

    // ═══════════════════════════════════════════════
    // CANDIDATE PANEL VISIBILITY  (Role dropdown)
    // FIX: the original always passed 'show' regardless of the boolean param
    // FIX: Voyager's relationship select uses name="role_id" — broaden the selector
    // ═══════════════════════════════════════════════

    function updateCandidatePanels(isCandidate) {
        $('#panel-candidate, #panel-assessment, #panel-candidate-summary').show();
    }

    // Voyager relationship fields can render as:
    //   select[name="role_id"]          (belongsTo single)
    //   select[data-field="role_id"]
    //   select inside a div with id containing "role"
    // We target all of these with a broad delegated listener.
    $(document).on('change', 'select[name="role_id"], select[id*="role"], select[data-field*="role"]', function () {
        var chosen = $(this).find('option:selected').text().toLowerCase();
        updateCandidatePanels(chosen.indexOf(CANDIDATE_ROLE_KEYWORD) !== -1);
    });

    // Also watch for Select2 / chosen changes (Voyager sometimes uses these)
    $(document).on('select2:select select2:unselect', 'select[name="role_id"], select[id*="role"], select[data-field*="role"]', function () {
        var chosen = $(this).find('option:selected').text().toLowerCase();
        updateCandidatePanels(chosen.indexOf(CANDIDATE_ROLE_KEYWORD) !== -1);
    });

    // ═══════════════════════════════════════════════
    // REAL-TIME LISTENERS
    // ═══════════════════════════════════════════════

    $('#name').on('input blur', function () { validateName(); syncStatusName(); });

    $('#mobileno').on('input', function () {
        $(this).val($(this).val().replace(/\D/g, '').slice(0, 10));
        validateMobile();
        syncStatusMobile();
    }).on('blur', function () { validateMobile(); syncStatusMobile(); });

    $('#email').on('input blur', function () { validateEmail(); syncStatusEmail(); });

    $('#password').on('input blur', function () { validatePassword(); syncStatusPassword(); });

    $('#user-edit-add-form')
        .on('input', '.score-pct-input', function () { validatePctField($(this)); })
        .on('blur',  '.score-pct-input', function () {
            var v = parseFloat($(this).val());
            if (!isNaN(v)) $(this).val(Math.min(100, Math.max(0, v)));
            validatePctField($(this));
        })
        .on('input', '.assess-pct-input', function () { validatePctField($(this)); })
        .on('blur',  '.assess-pct-input', function () {
            var v = parseFloat($(this).val());
            if (!isNaN(v)) $(this).val(Math.min(100, Math.max(0, v)));
            validatePctField($(this));
        });

    // Highest Qualification → show/hide academic score columns
    $('#highestquali').on('change', function () {
        updateScoreVisibility($(this).val());
        $('.score-field-col.score-visible .score-pct-input').each(function () {
            if ($(this).hasClass('field-invalid') || $(this).hasClass('field-valid')) {
                validatePctField($(this));
            }
        });
    });

    // ═══════════════════════════════════════════════
    // FORM SUBMIT GATE
    // ═══════════════════════════════════════════════

    $('#user-edit-add-form').on('submit', function (e) {
        var ok = true;
        ok = validateName()     && ok;
        ok = validateMobile()   && ok;
        ok = validateEmail()    && ok;
        ok = validatePassword() && ok;

        if ($('#panel-candidate').is(':visible')) {
            $('.score-field-col.score-visible .score-pct-input').each(function () {
                ok = validatePctField($(this)) && ok;
            });
        }
        if ($('#panel-assessment').is(':visible')) {
            $('.assess-pct-input').each(function () {
                ok = validatePctField($(this)) && ok;
            });
        }

        if (!ok) {
            e.preventDefault();
            var $first = $('#user-edit-add-form .field-invalid:first');
            if ($first.length) {
                $('html,body').animate({ scrollTop: $first.offset().top - 120 }, 300);
                $first.focus();
            }
        }
    });

    // ═══════════════════════════════════════════════
    // INIT
    // ═══════════════════════════════════════════════

    updateCandidatePanels(INITIAL_IS_CANDIDATE);
    updateScoreVisibility($('#highestquali').val());

    syncStatusName();
    syncStatusEmail();
    syncStatusPassword();
    if ($('#mobileno').length) syncStatusMobile();

});

// ═══════════════════════════════════════════════
// MULTI-FILE REMOVE HANDLER
// ═══════════════════════════════════════════════

$(document).on('click', '.remove-multi-file', function (e) {
    e.preventDefault();
    if (!confirm('Remove this file? This cannot be undone.')) return;

    var $btn         = $(this);
    var originalName = $btn.data('filename');
    var downloadLink = $btn.data('download-link');
    var fieldName    = $btn.data('field');
    var recordId     = $btn.data('id');

    if (!originalName || !fieldName || !recordId) {
        alert('Missing file information. Please refresh the page and try again.');
        return;
    }

    $btn.css('pointer-events', 'none').css('opacity', '0.5').text('…');

    $.ajax({
        url: '/admin/users/remove-file',
        type: 'POST',
        data: {
            _token:        $('meta[name="csrf-token"]').attr('content'),
            id:            recordId,
            field:         fieldName,
            filename:      originalName,
            download_link: downloadLink,
        },
        success: function (res) {
            if (res.success) {
                $btn.closest('.file-entry').fadeOut(250, function () { $(this).remove(); });
                var $hidden = $('#hidden-existing-' + fieldName);
                if ($hidden.length && res.new_value !== undefined) {
                    $hidden.val(res.new_value);
                }
            } else {
                $btn.css('pointer-events', '').css('opacity', '1').text('✕');
                alert('Error: ' + (res.message || 'Unknown error. Check server logs.'));
            }
        },
        error: function (xhr) {
            $btn.css('pointer-events', '').css('opacity', '1').text('✕');
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'Server error ' + xhr.status;
            alert(msg);
        }
    });
});
</script>
@stop