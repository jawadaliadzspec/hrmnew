<?php

namespace App\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Task;
use App\Models\TaskFile;
use Illuminate\Http\Request;
use App\Helper\UserService;
use App\Models\ClientContact;

class TaskFileController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageIcon = 'icon-layers';
        $this->pageTitle = 'app.menu.taskFiles';
    }

    /**
     * @param Request $request
     * @return mixed|void
     * @throws \Froiden\RestAPI\Exceptions\RelatedResourceNotFoundException
     */
    public function store(Request $request)
    {
        $this->addPermission = user()->permission('add_task_files');
        $task = Task::findOrFail($request->task_id);
        $taskUsers = $task->users->pluck('id')->toArray();
        $this->userId = UserService::getUserId();
        $this->clientIds = ClientContact::where('user_id', $this->userId)->pluck('client_id')->toArray();

        abort_403(!(
            $this->addPermission == 'all'
            || ($this->addPermission == 'added' && ($task->added_by == user()->id || $task->added_by == $this->userId || in_array($task->added_by, $this->clientIds)))
            || ($this->addPermission == 'owned' && in_array(user()->id, $taskUsers))
            || ($this->addPermission == 'added' && (in_array(user()->id, $taskUsers) || $task->added_by == user()->id || $task->added_by == $this->userId || in_array($task->added_by, $this->clientIds)))
        ));

        if ($request->hasFile('file')) {

            foreach ($request->file as $fileData) {
                $file = new TaskFile();
                $file->task_id = $request->task_id;

                $filename = Files::uploadLocalOrS3($fileData, TaskFile::FILE_PATH.'/' . $request->task_id);

                $file->user_id = $this->user->id;
                $file->filename = $fileData->getClientOriginalName();
                $file->hashname = $filename;
                $file->size = $fileData->getSize();
                $file->save();

                $this->logTaskActivity($task->id, $this->user->id, 'fileActivity', $task->board_column_id);
            }

            $this->files = TaskFile::where('task_id', $request->task_id)->orderByDesc('id');
            $viewTaskFilePermission = user()->permission('view_task_files');

            if ($viewTaskFilePermission == 'added') {
                $this->files = $this->files->where('added_by', user()->id);
            }

            $this->files = $this->files->get();
            $view = view('tasks.files.show', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'view' => $view]);
        }

    }

    /**
     * @param Request $request
     * @param int $id
     * @return array
     * @throws \Throwable
     */
    public function destroy(Request $request, $id)
    {
        $file = TaskFile::findOrFail($id);
        $this->deletePermission = user()->permission('delete_task_files');
        $userId = UserService::getUserId();
        $this->clientIds = ClientContact::where('user_id', $userId)->pluck('client_id')->toArray();
        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && ($file->added_by == user()->id || $file->added_by == $userId || in_array($file->added_by, $this->clientIds)))));

        TaskFile::destroy($id);

        $this->files = TaskFile::where('task_id', $file->task_id)->orderByDesc('id')->get();

        $view = view('tasks.files.show', $this->data)->render();

        return Reply::successWithData(__('messages.deleteSuccess'), ['view' => $view]);

    }

    /**
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download($id)
    {
        $file = TaskFile::whereRaw('md5(id) = ?', $id)->firstOrFail();
        $this->viewPermission = user()->permission('view_task_files');
        $userId = UserService::getUserId();
        abort_403(!($this->viewPermission == 'all' || ($this->viewPermission == 'added' && ($file->added_by == user()->id || $file->added_by == $userId))));

        return download_local_s3($file, 'task-files/' . $file->task_id . '/' . $file->hashname);

    }

}
