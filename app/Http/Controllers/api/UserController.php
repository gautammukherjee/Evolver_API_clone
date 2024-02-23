<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // /**
    //  * Display a listing of the resource.
    //  *
    //  * @return \Illuminate\Http\Response
    //  */
    // public function index()
    // {
    //     //
    // }

    //login function
    public function login(Request $request)
    {
        $email = $request->email;
        $password = md5($request->password);
        $sql = "SELECT * FROM users WHERE email='" . $email . "' AND passwd='" . $password . "' AND deleted=0";
        // $sql = "SELECT * FROM users WHERE email='" . $email . "' AND passwd='" . $password . "'";
        echo $sql;
        $result = DB::connection('pgsql2')->select($sql);
        // print_r($result);

        if (empty($result)) {
            return response()->json(['success' => false, 'error' => 'Unauthorized']);
        } else {
            return response()->json(['success' => true, 'search' => $result]);
        }

        // return response()->json([
        //     'search' => $result
        // ]);
    }


}