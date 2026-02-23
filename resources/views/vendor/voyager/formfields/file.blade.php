
@if(isset($dataTypeContent->{$row->field}))

    @php
        $disk        = config('voyager.storage.disk', 'public');
        $fieldVal    = $dataTypeContent->{$row->field};
        $decoded     = json_decode($fieldVal);
        $isJsonArray = is_array($decoded);
        $recordId    = $dataTypeContent->getKey();
    @endphp

    {{-- ── Preserve full JSON so "no re-upload" keeps existing files ── --}}
    <input type="hidden"
           name="{{ $row->field }}_existing"
           id="hidden-existing-{{ $row->field }}"
           value="{{ $fieldVal }}">

    @if($isJsonArray)
        {{-- ── Multiple files (JSON array) ── --}}
        <div id="file-list-{{ $row->field }}">
            @foreach($decoded as $file)
            <div class="file-entry"
                 data-filename="{{ $file->original_name }}"
                 style="display:flex; align-items:center; gap:8px;
                        padding:6px 0; border-bottom:1px solid #f5f5f5;">

                <i class="voyager-file" style="color:#aaa; font-size:14px; flex-shrink:0;"></i>

                <a class="fileType" target="_blank"
                   href="{{ Storage::disk($disk)->url($file->download_link) }}"
                   data-file-name="{{ $file->original_name }}"
                   data-id="{{ $recordId }}"
                   style="flex:1; font-size:13px; word-break:break-all;">
                    {{ $file->original_name }}
                </a>

                <a href="#"
                   class="voyager-x remove-multi-file"
                   data-filename="{{ $file->original_name }}"
                   data-download-link="{{ $file->download_link }}"
                   data-field="{{ $row->field }}"
                   data-id="{{ $recordId }}"
                   title="Remove {{ $file->original_name }}"
                   style="flex-shrink:0; padding:2px 8px; font-size:11px; font-weight:600;
                          border:1px solid #e74c3c; border-radius:3px;
                          color:#e74c3c; background:#fff; text-decoration:none;">
                    &#x2715;
                </a>
            </div>
            @endforeach
        </div>

    @else
        {{-- ── Single file (plain string path) ── --}}
        <div class="file-entry"
             id="file-single-{{ $row->field }}"
             style="display:flex; align-items:center; gap:8px; padding:6px 0;">

            <i class="voyager-file" style="color:#aaa; font-size:14px; flex-shrink:0;"></i>

            <a class="fileType" target="_blank"
               href="{{ Storage::disk($disk)->url($fieldVal) }}"
               data-file-name="{{ basename($fieldVal) }}"
               data-id="{{ $recordId }}"
               style="flex:1; font-size:13px; word-break:break-all;">
                {{ basename($fieldVal) }}
            </a>

            <a href="#"
               class="voyager-x remove-multi-file"
               data-filename="{{ basename($fieldVal) }}"
               data-download-link="{{ $fieldVal }}"
               data-field="{{ $row->field }}"
               data-id="{{ $recordId }}"
               title="Remove file"
               style="flex-shrink:0; padding:2px 8px; font-size:11px; font-weight:600;
                      border:1px solid #e74c3c; border-radius:3px;
                      color:#e74c3c; background:#fff; text-decoration:none;">
                &#x2715;
            </a>
        </div>
    @endif

@endif

{{-- ── Upload input (replace / add file) ── --}}
<div style="margin-top:8px;">
    <input type="file"
           name="{{ $row->field }}[]"
           data-field-name="{{ $row->field }}"
           @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) required @endif>
    <small class="text-muted" style="display:block; margin-top:4px;">
        Max 3 MB. Uploading a new file adds it alongside existing files.
    </small>
</div>