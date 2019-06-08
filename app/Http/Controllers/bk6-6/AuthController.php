<?php

namespace App\Http\Controllers;

use Validator;
use Carbon\Carbon;
use Response;
use App\User;
use App\Wallet;
use App\Coins;
use App\Usermeta;
use Auth;
use Hash;
use Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use App\Http\Controllers\TransformController as TC;
use App\Http\Controllers\DistributionsController;
class AuthController extends Controller
{
	public function __construct()
    {
		$this->t =new TC;
    }
    public function register(Request $request)
    {
        // $DistributionsController =new DistributionsController;
        // $added= $DistributionsController->Store($request->lat,$request->lng);
        // return response()->json($added);
        // die;
		$validator = Validator::make($request->all(), [
				// 'ipfile' => 'required|file|mimes:txt|max:2048'
				'password' => 'required|string|min:6',
				'username' => 'required|string|unique:users',
                'email' => 'required|email|unique:users',
                'lat' => ['required','regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
                'lng' => ['required','regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],

			]);

		if ($validator->fails()) {
			 return response()->json([
             'status'=>false,
             'data'=>implode(",",$validator->messages()->all()),
			 'error'=>$validator->Errors()]);

		}
//		var_dump($request->username);die;
        $user = User::create([
             'email'    => $request->email,
             'password' => $request->password,
             'name' => '',
             'username' => $request->username,
         ]);
        if($user):
            $token = auth('api')->login($user);
            //create usermata
            $meta =new Usermeta;
            $meta ->user_id=$user->id;
            $meta ->limit=5;
            $meta->save();
            $this->WalletInit($user);
            $DistributionsController =new DistributionsController;
           $added= $DistributionsController->Store($request->lat,$request->lng);
           Log::info('addingnewcoinsfornewuseradded'.Auth::id().' @added='.$added);
            // insert dummy zero coin s in all wallet

        endif;
        $token = auth('api')->login($user);

        return $this->respondWithToken($token,$added);
    }
	public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'status'=>false,
                'data'=>'Invalid Credentials',
                'error'=>'Invalid Credentials']
                , 401);
        }

        return $this->respondWithToken($token);
    }
	public function me()
    {
		$user = auth()->user()->toArray();
		//var_dump($user);die;
		$user = $this->t->setArray($user);
        return response()->json( ['status'=>true,'user'=>$user]);
    }
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['status'=>true,'message' => 'Successfully logged out']);
    }

    protected function respondWithToken($token,$added=null)
    {
        return response()->json([
            'status'=>true,
            'added'=>$added,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60
        ]);
    }
    public function WalletInit($user)
    {
        $coins= Coins::get();
        if($coins):
            foreach ($coins as $coin):
                $has = Wallet::where('coin_id',$coin->id)->where('user_id',$user->id)->first();
                if(!$has):
                    $w = new Wallet;
                    $w->user_id =$user->id;
                    $w->coin_id= $coin->id;
                    $w->bal=0;
                    $w->save();
                endif;
            endforeach;
        endif;
    }
    public function Photo(Request $request){
        $user=auth()->user();
			$user_id=$user->id;
			if(Input::file('image')){
                $allowed=array('jpeg','jpg','png');
                $fileExt = Input::file('image')->getClientOriginalExtension();
                if(!in_array($fileExt,$allowed)){
                    return response(['status'=>false,'error' =>'Profile Image invalid']);
                }
                $fileName = str_replace('.' . $fileExt, '', Input::file('image')->getClientOriginalName());
                $fileSize = Input::file('image')->getSize();
                if($fileSize>1024*1024){
                    return response(['status'=>false,'error' =>'Profile Image too large Allowed Size 1MB']);
                }

                $image = Input::file('image');

                $input['imagename'] = time().$fileName.'.'.$image->getClientOriginalExtension();

                 $destinationPath = public_path('/images/');

                $image->move($destinationPath, $input['imagename']);
                $pimage='/crypto/public/images/'.$input['imagename'];
                User::where('id',$user_id)->update(['logo'=>$pimage]);
                return response(['status'=>true,'data' =>'Profile Image uploaded']);
            }
            return response(['status'=>false,'error' =>'Profile Image required']);
    }

}
