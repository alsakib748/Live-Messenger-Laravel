<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessengerController extends Controller
{

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


}