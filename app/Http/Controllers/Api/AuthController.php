<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function google_login(Request $request) {
        $client = new \Google_Client(['client_id' => config('service.google.client_id')]);  // Specify the CLIENT_ID of the app that accesses the backend
        $response = $client->verifyIdToken($request->credential);
        if (!$response) {
            return response()->json([
                'message' => array('Login via google gagal'),
                'redirect_to' => 'login'
            ], 401);
        }
        $google_id = $response["jti"];
        $gmail = $response["email"];
        $name = $response["name"];
        $images = $response["picture"];
        $user = User::query()->where('email', $gmail)->first();
        if($user != null){
            $msg = array('Selamat! kamu telah berhasil login!');
            $data['token'] = $user->createToken('webApp')->plainTextToken;
            $user->fcm_token = $request->fcm_token;
            $user->save();

            $data['message'] = $msg;
            $data['data'] = $user;
            $data['redirect_to'] = 'homepage';
            $this->code = 200;
            return response()->json($data, $this->code);
        } else {
            $user = new User();
            $user->name = $name;
            $user->email = $gmail;
            $user->avatar = $images;
            $user->password = bcrypt($google_id);
            $user->email_verified_at = now();
            $user->social = "google";
            $user->social_id = $google_id;
            $user->save();

            $msg = array('Selamat! kamu telah berhasil login!');
            $data['token'] = $user->createToken('webApp')->plainTextToken;

            $data['message'] = $msg;
            $data['data'] = $user;
            $data['redirect_to'] = 'homepage';
            $this->code = 200;

            return response()->json($data, $this->code);
        }
    }
}
