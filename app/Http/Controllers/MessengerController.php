<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\User;
use App\Models\Message;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MessengerController extends Controller
{

    use FileUploadTrait;

    public function index(): View
    {
        $favoriteList = Favorite::with('user:id,name,avatar')->where('user_id', Auth::user()->id)->get();
        return view('messenger.index', compact('favoriteList'));
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

        $favorite = Favorite::where(['user_id' => Auth::user()->id, 'favorite_id' => $fetch->id])->exists();

        $sharedPhotos = Message::where('from_id', Auth::user()->id)
            ->where('to_id', $request->id)
            ->whereNotNull('attachment')
            ->orWhere('from_id', $request->id)
            ->where('to_id', Auth::user()->id)
            ->whereNotNull('attachment')
            ->latest()
            ->get();

        $content = '';
        foreach ($sharedPhotos as $photo) {
            $content .= view('messenger.components.gallery-item', compact('photo'))->render();
        }

        return response()->json([
            'fetch' => $fetch,
            'favorite' => $favorite,
            'shared_photos' => $content
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

    // todo: Fetch Contacts from database
    function fetchContacts(Request $request)
    {

        $users = Message::join('users', function ($join) {
            $join->on('messages.from_id', '=', 'users.id')
                ->orOn('messages.to_id', '=', 'users.id');
        })
            ->where(function ($q) {
                $q->where('messages.from_id', Auth::user()->id)
                    ->orWhere('messages.to_id', Auth::user()->id);
            })
            ->where('users.id', '!=', Auth::user()->id)
            ->select('users.id', 'users.name', 'users.user_name', 'users.avatar', DB::raw('MAX(messages.created_at) as max_created_at'))
            ->groupBy('users.id', 'users.name', 'users.user_name', 'users.avatar')
            ->orderBy('max_created_at', 'desc')
            ->paginate(10);

        if (count($users) > 0) {
            $contacts = '';
            foreach ($users as $user) {
                $contacts .= $this->getContactItem($user);
            }
        } else {
            $contacts = "<p>Your contact list is empty!</p>";
        }

        return response()->json([
            'contacts' => $contacts,
            'last_page' => $users->lastPage()
        ]);

    }

    function getContactItem($user)
    {
        $lastMessage = Message::where('from_id', Auth::user()->id)
            ->where('to_id', $user->id)
            ->orWhere('from_id', $user->id)
            ->where('to_id', Auth::user()->id)
            ->latest()
            ->first();

        $unseenCounter = Message::where('from_id', $user->id)->where('to_id', Auth::user()->id)->where('seen', 0)->count();


        return view('messenger.components.contact-list-item', compact('lastMessage', 'unseenCounter', 'user'))->render();
    }

    // todo: Update Contact Item
    public function updateContactItem(Request $request)
    {
        // get user data

        $user = User::where('id', $request->user_id)->first();

        if (!$user) {
            return response()->json([
                'message' => "User not found!"
            ], 401);
        }

        $contactItem = $this->getContactItem($user);

        return response()->json([
            'contact_item' => $contactItem
        ], 200);

    }

    public function makeSeen(Request $request)
    {

        Message::where('from_id', $request->id)
            ->where('to_id', Auth::user()->id)
            ->where('seen', 0)->update(['seen' => 1]);

        return true;

    }

    // todo: Add/Remove to favorite list

    public function favorite(Request $request)
    {

        $request->validate([
            'id' => ['required', 'integer']
        ]);

        $query = Favorite::where(['user_id' => Auth::user()->id, 'favorite_id' => $request->id]);

        $favoriteStatus = $query->exists();

        if (!$favoriteStatus) {
            $star = new Favorite();
            $star->user_id = Auth::user()->id;
            $star->favorite_id = $request->id;
            $star->save();
            return response(['status' => 'added']);
        } else {
            $query->delete();
            return response(['status' => 'removed']);
        }

    }

    // todo: Delete Message

    function deleteMessage(Request $request)
    {
        // dd($request->all());
        $message = Message::findOrFail($request->message_id);

        if ($message->from_id == Auth::user()->id) {
            $message->delete();
            return response()->json([
                'id' => $request->message_id
            ], 200);
        }

        return;
    }

}