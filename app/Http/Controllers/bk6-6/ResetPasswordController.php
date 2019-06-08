<?php
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Validator;
use Illuminate\Http\Request;
use Response;
use Mail;
use Auth;
use App\User;
use App\Usermeta;
use Carbon\Carbon;
use App\Http\Controllers\Controller\MailController;
class ResetPasswordController extends Controller
{
   
    public function __construct()
    {
        //$this->middleware('guest');
    }
	public function Init(Request $request)
	{
		$validator = Validator::make($request->all(), [
				'email' => 'required|email',							
		]);

		if ($validator->fails()) {
			 return response()->json([
             'status'=>false,
             'data'=>implode(",",$validator->messages()->all()),
			 'error'=>$validator->Errors()]);
			
		} 
        $user = User::where('email', $request->email)->first();
		if(!$user){
			 return response()->json([
             'status'=>false,
             'data'=>'User not Exist',
			 'error'=>'User not Exist']);
		}
		$str_random=str_random(10);
		User::where('id',$user->id)->update(['password'=>bcrypt($str_random)]);
		$user->pass =$str_random;
		$this->Email($user);
        return response()->json([
			 'status'=>true,
			 'data'=>'Temporary password sent to your email address',
			 '$str_random'=>$str_random,
			 ]);
	}
	public function Email($user){
        try {
            $data = array('name'=>$user->username,'email'=>$user->email,'pass'=>$user->pass);
            Mail::send('mail', $data, function($message) use ($user){
                $message->to($user->email)->subject
                ('CryptoHunter new Password  ');
                $message->from('cryptohuntercare@gmail.com','CryptoHunter');

            });
        }
        catch (\Exception $e) {
            //return $e->getMessage();
        }
	}
    public  function ChangePass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp'          => 'required',
            'password'              => 'required|min:4',
            'password_confirmation' => 'required|same:password',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status'=>false,
                'data'=>implode(",",$validator->messages()->all()),
                'error'=>$validator->Errors()]);

        }
        $UserMeta = UserMeta::where('user_id',Auth::id())->first();
        if(!$UserMeta->otp):

            return response()->json([
                'status'=>false,
                'data'=>'OTP Expired',
                'error'=>'OTP  Expired']);
        endif;
        if($UserMeta->otp != $request->otp):

            return response()->json([
                'status'=>false,
                'data'=>'Wrong OTP',
                'error'=>'wrong OTP']);
        endif;
        User::where('id',Auth::id())->update(['password'=>bcrypt($request->password)]);
        Usermeta::where('user_id',Auth::id())->update(['otp'=>null,'otpc'=>null]);
        return response()->json(['status' => true,'data'=>'Password Successfully changed']);
    }
    public  function ChangePassEmail()
    {
        $hasSent = UserMeta::where('user_id',Auth::id())
            ->where('otpc', '>', Carbon::now()->subMinutes(5)->toDateTimeString())
            ->get()->count();
        if($hasSent):
            return response()->json(['status'=>true,'data'=>'OTP sent to your registered Email address']);
        endif;
        $UserMeta = Usermeta::where('user_id',Auth::id())->first();
        if(!$UserMeta):
            $UserMeta =  new Usermeta;
            $UserMeta->user_id = Auth::id();
        endif;
        $str_random= mt_rand(11111, 99999);
        $UserMeta->otp =$str_random;
        $UserMeta->otpc =now();
        $UserMeta->save();
        $user =Auth::user();
        try {
            $data = array('name'=>$user->username,'email'=>$user->email,'otp'=>$str_random);
            Mail::send('reset', $data, function($message) use ($user){
                $message->to($user->email)->subject
                ('CryptoHunter Password change OTP ');
                $message->from('cryptohuntercare@gmail.com','CryptoHunter');

            });
        }
        catch (\Exception $e) {
            //return $e->getMessage();
        }
        return response()->json(['status'=>true,'data'=>'OTP sent to your registered Email address']);

    }

}
