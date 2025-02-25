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

    //  todo: fetch messages from database
    function fetchMessages(Request $request)
    {

        $messages = Message::where('from_id', Auth::user()->id)
            ->where('to_id', $request->id)
            ->orWhere('from_id', $request->id)
            ->where('to_id', Auth::user()->id)
            ->latest()
            ->paginate(20);

        $response = [
            'last_page' => $messages->lastPage(),
            'last_message' => $messages->last(),
            'messages' => ''
        ];

        // todo: here we have to do little validation
        if (count($messages) < 1) {
            $response['messages'] = "<div class='d-flex align-items-center justify-content-center h-100'><p>Say 'hi' and start messaging.</p></div>";
            return response()->json($response);
        }

        $allMessages = '';

        foreach ($messages->reverse() as $message) {
            $allMessages .= $this->messageCard($message, $message->attachment ? true : false);
        }

        $response['messages'] = $allMessages;

        return response()->json($response);

    }
}