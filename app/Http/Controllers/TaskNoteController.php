<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Http\Requests\Tasks\StoreTaskNote;
use App\Models\Task;
use App\Models\TaskNote;
use App\Helper\UserService;
use App\Models\ClientContact;

class TaskNoteController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.tasks';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('tasks', $this->user->modules));
            return $next($request);
        });
    }

    /**
     *
     * @param StoreTaskNote $request
     * @return void
     */
    public function store(StoreTaskNote $request)
    {
        $this->addPermission = user()->permission('add_task_notes');
        $task = Task::findOrFail($request->taskId);
        $taskUsers = $task->users->pluck('id')->toArray();
        $this->userId = UserService::getUserId();
        $this->clientIds = ClientContact::where('user_id', $this->userId)->pluck('client_id')->toArray();

        abort_403(!(
            $this->addPermission == 'all'
            || ($this->addPermission == 'added' && ($task->added_by == user()->id || $task->added_by == $this->userId || in_array($task->added_by, $this->clientIds)))
            || ($this->addPermission == 'owned' && in_array(user()->id, $taskUsers))
            || ($this->addPermission == 'added' && (in_array(user()->id, $taskUsers) || $task->added_by == user()->id || $task->added_by == $this->userId || in_array($task->added_by, $this->clientIds)))
        ));

        $note = new TaskNote();
        $note->note = trim_editor($request->note);
        $note->task_id = $request->taskId;
        $note->user_id = $this->userId;
        $note->save();

        $this->notes = TaskNote::where('task_id', $request->taskId)->orderByDesc('id')->get();
        $view = view('tasks.notes.show', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $note = TaskNote::findOrFail($id);
        $this->deleteTaskNotePermission = user()->permission('delete_task_notes');
        $this->userId = UserService::getUserId();
        $this->clientIds = ClientContact::where('user_id', $this->userId)->pluck('client_id')->toArray();
        abort_403(!($this->deleteTaskNotePermission == 'all' || ($this->deleteTaskNotePermission == 'added' && ($note->added_by == user()->id || $note->added_by == $this->userId || in_array($note->added_by, $this->clientIds)))));

        $note_task_id = $note->task_id;
        $note->delete();
        $this->notes = TaskNote::with('task')->where('task_id', $note_task_id)->orderByDesc('id')->get();
        $view = view('tasks.notes.show', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    /**
     * XXXXXXXXXXX
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $this->note = TaskNote::with('user', 'task')->findOrFail($id);
        $this->editTaskNotePermission = user()->permission('edit_task_notes');
        $userId = UserService::getUserId();
        $this->clientIds = ClientContact::where('user_id', $userId)->pluck('client_id')->toArray();
        abort_403(!($this->editTaskNotePermission == 'all' || ($this->editTaskNotePermission == 'added' && ($this->note->added_by == user()->id || $this->note->added_by == $userId || in_array($this->note->added_by, $this->clientIds)))));

        $taskuserData = [];
        $usersData = $this->note->task->users;

        foreach ($usersData as $user) {
            $url = route('employees.show', [$user->id]);

            $taskuserData[] = ['id' => $user->id, 'value' => $user->name, 'image' => $user->image_url, 'link' => $url];

        }

        $this->taskuserData = $taskuserData;


        return view('tasks.notes.edit', $this->data);

    }

    public function update(StoreTaskNote $request, $id)
    {
        $note = TaskNote::findOrFail($id);
        $this->editTaskNotePermission = user()->permission('edit_task_notes');
        $this->userId = UserService::getUserId();
        $this->clientIds = ClientContact::where('user_id', $this->userId)->pluck('client_id')->toArray();

        abort_403(!($this->editTaskNotePermission == 'all' || ($this->editTaskNotePermission == 'added' && ($note->added_by == user()->id || $note->added_by == $this->userId || in_array($note->added_by, $this->clientIds)))));

        $note->note = trim_editor($request->note);
        $note->save();

        $this->notes = TaskNote::with('task')->where('task_id', $note->task_id)->orderByDesc('id')->get();
        $view = view('tasks.notes.show', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);

    }

}
