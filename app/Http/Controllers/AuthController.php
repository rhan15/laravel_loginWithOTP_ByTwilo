<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Exists;
use PHPUnit\Framework\MockObject\Api;

class AuthController extends Controller
{
    use ApiResponser;

    public function getOtp(Request $request)
    {
        $rules = [
            'country_code' => 'required|min:1',
            'phone_number' => 'required|min:9|max:13'
        ];

        $this->validate($request, $rules);

        $user = User::where('country_code', $request->country_code)
            ->where('phone_number', $request->phone_number)
            ->first();

        if ($user) {
            $user->update([
                'otp' => User::generateOTP(),
            ]);
            return $this->showOne($user);
        } else {

            // ! Need to handle when user not exist yet
            $data['country_code'] = $request->country_code;
            $data['phone_number'] = $request->phone_number;
            $data['admin'] = User::REGULAR_USER;
            $data['otp'] = User::generateOTP();

            // $userr = User::create($data);

            // $user->create($data);
            // return $this->showOne($user);

            $newUser = User::create($data);
            return $this->showOne($newUser);
        }
    }

    public function verifyOtp(Request $request)
    {
        $rules = [
            'country_code' => 'required|min:1',
            'phone_number' => 'required|min:9|max:13',
            'otp' => 'required|digits:6',
        ];

        $this->validate($request, $rules);

        $user = User::where('phone_number', $request->phone_number)
            ->where('otp', $request->otp)
            ->first();

        // dd($user);

        // if ($user->profile == null) {
        //     return response('bisaaa');
        // }

        // $relation = $user->relation()->exists();
        // dd($relation);

        // $relation = count($user->relation);
        // dd($relation);

        // if(!is_null($user->relation)) {
        //   return response('mantap');
        // }


        if ($user) {
            Auth::login($user);
            $authUser = Auth::user();
            $tokenResult = $authUser->createToken('Personal Access Token', [])  ;

            $user->update([
                'otp' => null,
            ]);

            // ! Need to handle whether it's new or old user
            $response['token'] =  $tokenResult->accessToken;
            $response['is_new'] = true;

            if ($user->profile) {
                $response['is_new'] = false;
            }

            return response()->json($response);

            

            // ! Bad, redundant
            // if ($user->profile) {
            //     return response()->json([
            //         'token' => $tokenResult->accessToken,
            //         'is_new' => true,
            //     ]);
            // }

            // return response()->json([
            //     'token' => $tokenResult->accessToken,
            //     'is_new' => false,
            // ]);
        }

        // ! Need to handle when user not exist
        return $this->errorResponse('Bandel kamu yaa sana pulang', 409);
    }

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required',
            'birth_date' => 'required|date',
            'gender' => 'required'

        ];

        $this->validate($request, $rules);
        $data = $request->all();

        $user = auth()->user(); // gimana cara dapetin id usernya ko sedangkan usernya belum tebuat
        // auth()->id();
        // dd($user); // Object/Model User
        
        $profile = Profile::create([
            'name' => $request->name,
            'birth_date' => $request->birth_date,
            'gender' => $request->gender,
            'user_id' => $user->id,

        ]);

        return $this->showOne($profile);
        // Auth::user()->token()->revoke();

        // 

        //  $token= $request->user()->tokens->find($token);
        // $token->revoke();




    }

    public function forgetpassword(Request $request)
    {
        $rules = [
            'otp' => 'required|digits:6',
            'pin' => 'required|digits:6|confirmed'

        ];

        $this->validate($request, $rules);

        $user = User::where('otp', $request->otp)
            ->first();
            // dd($user);

        if ($user) {
            $user->update([
                'pin' => bcrypt($request->pin),
            ]);
            return $this->showOne($user);
        } 
        else {
            return $this->errorResponse('otp not found', 409);
           

        }
    }

    public function updateadmin(Request $request)
    {
        $rules = [
            'country_code' => 'required|min:1',
            'phone_number' => 'required|min:9|max:13',
            'admin' => 'required',
            ];

            $this->validate($request, $rules);
            // dd($user);
            $user = User::where('phone_number', $request->phone_number)
            ->first();

            if ($user) {
                $user->update([
                    'admin' => $request->admin,
                ]);
                return $this->showOne($user);
            }
            return $this->errorResponse('Phone number not found', 409);
        
    }

    public function logout(Request $request)
    {
        $user = auth()->user();

        $token = $request->user()->token();
        // dd($token);
        $token->revoke();

        return response('Selamat Kamu sudah di keluarkan');

    }




}
