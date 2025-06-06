@php
    $editTaskNotePermission = user()->permission('edit_task_notes');
    $deleteTaskNotePermission = user()->permission('delete_task_notes');
@endphp

@forelse($notes as $note)
    <div class="card w-100 rounded-0 border-0 note">
        <div class="card-horizontal">
            <div class="card-img my-1 ml-0">
                <img src="{{ $note->user->image_url }}" alt="{{ $note->user->name }}">
            </div>
            <div class="card-body border-0 pl-0 py-1">
                <div class="d-flex flex-grow-1">
                    <h4 class="card-title f-15 f-w-500 text-dark mr-3">{{ $note->user->name }}</h4>
                    <p class="card-date f-11 text-lightest mb-0">
                        {{ $note->created_at->diffForHumans() }}
                    </p>
                    <div class="dropdown ml-auto note-action">
                        <button class="btn btn-lg f-14 p-0 text-lightest  rounded  dropdown-toggle"
                            type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-ellipsis-h"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                            aria-labelledby="dropdownMenuLink" tabindex="0">
                            @if ($editTaskNotePermission == 'all' || ($editTaskNotePermission == 'added' && ($note->added_by == user()->id || $note->added_by == $userId || in_array($note->added_by, $clientIds))))
                                <a class="cursor-pointer d-block text-dark-grey f-13 py-3 px-3 edit-note"
                                    href="javascript:;" data-row-id="{{ $note->id }}">@lang('app.edit')</a>
                            @endif

                            @if ($deleteTaskNotePermission == 'all' || ($deleteTaskNotePermission == 'added' && ($note->added_by == user()->id || $note->added_by == $userId || in_array($note->added_by, $clientIds))))
                                <a class="cursor-pointer d-block text-dark-grey f-13 pb-3 px-3 delete-note"
                                    data-row-id="{{ $note->id }}" href="javascript:;">@lang('app.delete')</a>
                            @endif
                        </div>
                    </div>
                </div>
                @php
                    $isClient = \App\Models\User::isClient($note->user->id);
                    $client = \App\Models\User::where('id', $note->added_by)->where('is_client_contact', 1)->first();
                @endphp
                @if(($isClient == true) && $client)
                    <div class="text-grey f-10 float-left mt-0">{{ __('(Added By : ') . $client->name . ')' }}</div><br/>
                @endif
                <div class="card-text f-14 text-dark-grey text-justify ql-editor">{!! $note->note !!}
                </div>
            </div>
        </div>
    </div>
@empty
    <x-cards.no-record :message="__('messages.noNoteFound')" icon="clipboard" />
@endforelse
