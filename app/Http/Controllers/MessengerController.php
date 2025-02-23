<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\Auth;

class MessengerController extends Controller
{

    use FileUploadTrait;

    public function index(): View
    {
        return view('messenger.index');
    }

    // todo: Search user profiles
    public function search(Request $request)
    {

        $getRecords = null;

        $query = $request->get('query');

        $records = User::where('id', '!=', Auth::user()->id)->where('name', 'LIKE', "%" . $query . "%")
            ->orWhere('user_name', 'LIKE', "%" . $query . "%")
            ->paginate(10);

        // dd($records);

        // return $records;

        if ($records->total() < 1) {
            $getRecords .= "<p class='text-center'>Nothing to show.</p>";
        }

        foreach ($records as $record) {
            $getRecords .= view('messenger.components.search-item', compact('record'))->render();
        }

        return response()->json([
            'records' => $getRecords,
            'last_page' => $records->lastPage()
        ]);

    }

    //todo: fetch user by id
    public function fetchIdInfo(Request $request)
    {
        $fetch = User::where('id', $request->id)->first();

        return response()->json([
            'fetch' => $fetch
        ]);
    }

    public function sendMessage(Request $request)
    {
        // dd($request->all());
        $request->validate([
            // 'message' => ['required'],
            'id' => ['required', 'integer'],
            'temporaryMsgId' => ['required'],
            'attachment' => ['nullable', 'max:1024', 'image']
        ]);

        // todo: Store the message in db
        $attachmentPath = $this->uploadFile($request, 'attachment');
        $message = new Message();
        $message->from_id = Auth::user()->id;
        $message->to_id = $request->id;
        $message->body = $request->message;
        if ($attachmentPath)
            $message->attachment =
                json_encode($attachmentPath);
        $message->save();

        return response()->json([
            'message' => $message->attachment ? $this->messageCard($message, true) : $this->messageCard($message),
            'tempID' => $request->temporaryMsgId,
        ]);

    }

    function messageCard($message, $attachment = false)
    {
        return view('messenger.components.message-card', compact('message', 'attachment'))->render();
    }

}