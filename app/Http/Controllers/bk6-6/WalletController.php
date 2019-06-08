<?php
namespace App\Http\Controllers;
use Cache;
use App\Mytool;
use App\Plan;
use App\Usermeta;
use App\Withdraw;
use Validator;
use Carbon\Carbon;
use Response;
use Auth;
use App\User;
use App\Wallet;
use App\Coins;
use App\Hunted;
use App\Banner;
use App\Address;
use App\Subscriber;
use App\Tool;
use Illuminate\Http\Request;
use App\Http\Controllers\HunterController;
use DB;
use App\Http\Controllers\TransformController as TC;
class WalletController extends Controller
{
	public function __construct()
    {
		$this->t =new TC;
       //$this->middleware('auth:api', ['except' => ['login']]);
    }
	protected function setData($value)
	{
		$d=  (array)$value;
		array_walk_recursive($d, function (&$item, $key) {
			foreach($item as $k=>$v){
				if($v==null){
					$item->$k =  '';
					$d[$key]=$item;
				}
			}

		});

		return ($d);
	}
    public function Index(Request $request)
    { //mywallet
        if($request->has('init')):
            $this->WalletInit(Auth::user());
        endif;
		$plan='';$hunted=0;
        $User = User::where('id',Auth::id())->with('Usermeta')->withCount('Hunted')->with('Subscription')->first();
        if($User):
            if(!$User->subscription):
                $plan =Plan::where('id',11)->first();
            else:
                $plan =Plan::where('id',$User->subscription->plan_id)->first();
            endif;
        endif;
		$wallets = DB::table('users')

            ->join('wallets', 'wallets.user_id', '=', 'users.id')
			 ->join('coins', 'coins.id', '=', 'wallets.coin_id')
            ->select('coins.*', 'wallets.*')
            ->where('users.id', Auth::id())
            ->where('wallets.bal', '>',0)
            ->where('coins.active', true)
            ->get();
        $rate= 0.2563;
        //$ach = Hunted:: where('user_id',Auth::id())->where('coin_id',$Coordinate->coin_id)->sum('fraction');
        if($wallets):
            if(sizeof($wallets)>0):
                foreach ($wallets as &$wallet):
                    $HunterController =new HunterController;
                    $rate=$HunterController->updatePrice($wallet->coin_id);
                    $wallet->actual_coin_bal= round(($wallet->ach),7);
                    $wallet->bal_in_usd= round((($wallet->ach)*$rate),7);
                    $wallet->ach= round(( $wallet->ach ) , 7);
                endforeach;
            endif;
        endif;
		return response()->json([
		'status'=>true,
		'wallets'=>$wallets,
		'hunted'=>$User->hunted_count,
		'plan'=>$plan,
        'limit'=>$User->usermeta->limit,
        // 'debug'=>$User
		]);
    }
    public function Capacity(Request $request)
    {
        if($request->has('init')):
            $this->WalletInit(Auth::user());
        endif;
        $User = User::where('id',Auth::id())->withCount('Hunted')->with('Subscription')->first();
        //var_dump($User);die;
        if(!$User):
            return response()->json(['status'=>false,'data'=>'User not Found']);
        else:
            if(!$User->hunted_count):
                $plan =Plan::where('id',11)->first();
                return response()->json(['status'=>true,'plan'=>$plan,'hunted'=>0]);
            else:
                    if(!$User->subscription):
                        $plan =Plan::where('id',11)->first();
                        return response()->json(['status'=>true,'plan'=>$plan,'hunted'=>$User->hunted_count]);
                    else:
                        $plan =Plan::where('id',$User->subscription->plan_id)->first();
                        return response()->json(['status'=>true,'plan'=>$plan,'hunted'=>$User->hunted_count]);
                    endif;
            endif;
        endif;
    }
    public function Availabletohunt(Request $request)
    {
        if($request->has('init')):
            $this->WalletInit(Auth::user());
        endif;
		$users = Coins::where('active',1)->withCount('Available')->get();

		return response()->json(['status'=>true,'wallets'=>$users]);
    }
	// top 100 Hunter
	public function TopHunter()
    {
		$users = DB::table('wallets')
		 ->select(DB::raw('wallets.user_id, SUM(wallets.bal ) as coins,users.email,users.name,users.username'))
		  ->join('users', 'users.id', '=', 'wallets.user_id')
		  ->groupBy('wallets.user_id')
		 ->orderBy('coins','desc')
		->limit(100)->get()->toArray();
		$users= $this->t->setArrays($users);

		return response()->json(['status'=>true,'users'=>$users]);
    }
    public function Leaderboard(Request $request)
    {

        if($request->has('leaderboard_name')):
            $validator = Validator::make($request->all(), [
                'leaderboard_name' => 'required|string|unique:users',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'=>false,
                    'data'=>implode(",",$validator->messages()->all()),
                    'error'=>$validator->Errors()]);

            }
           $d= User::where('id',Auth::id())->update(['leaderboard_name'=>$request->leaderboard_name]);
        endif;

		$users = DB::table('wallets')
		 ->select(DB::raw('wallets.user_id, SUM(wallets.bal ) as coins,users.email,users.name,users.leaderboard_name,users.username'))
		  ->join('users', 'users.id', '=', 'wallets.user_id')
		  ->groupBy('wallets.user_id')
		 ->orderBy('coins','desc')
		//->limit(100)->get()->toArray();
		->limit(100)->get();
		if($users):
			$users= $users->where('coins', '>',0);
		endif;
        $yes=false;
		if(sizeof($users)>0):
            foreach ($users as &$u):
				if(!trim($u->leaderboard_name)):
					$u->leaderboard_name=$u->username;
				endif;
                if($u->user_id ==Auth::id()):
                    $yes=true;
					if(!trim($u->leaderboard_name)):
						$u->leaderboard_name='????';
					endif;
                endif;
            endforeach;
        endif;

		//$users= $this->t->setArrays($users);
        if(!$yes):
            $user=User::find(Auth::id());
            $a["user_id"]=$user->id;
            $a["coins"]=0;
            $a["email"]=$user->email;
            $a["name"]=$user->name?$user->name:'????';
            $a["leaderboard_name"]=trim($user->leaderboard_name)?$user->leaderboard_name:'????';
            $a["username"]=$user->username;
            $users[]=$a;
        endif;
		return response()->json(['status'=>true,'users'=>$users]);
    }
    public function History()
    {
        $data=DB::table('hunted')
            ->select(DB::raw('DATE(hunted.created_at) as date'), DB::raw('count(*) as coins'))
            ->join('users', 'users.id', '=', 'hunted.user_id')
            ->where('users.id',Auth::id())
            ->groupBy('date')
            ->get();
		return response()->json(['status'=>true,'data'=>$data]);
    }
	public function UserAddress()
    {
		$c= Coins::where('active',1)->with('Address')->get();
		$users = DB::table('coins')
            ->join('address',  'address.coin_id','=','coins.id' ,'right outer')
            ->select('address.address', 'coins.id','coins.name','coins.logo','coins.description')
			->where('address.user_id', Auth::id())
			->where('coins.active', 1)
            ->get();
		return response()->json(['status'=>true,'address'=>$c]);
    }

	public function UserAddressAdd(Request $request)
    {
		$validator = Validator::make($request->all(), [
				'coin_id' => 'required|numeric|exists:coins,id',
				'address' => 'required|string',
		]);

		if ($validator->fails()) {
			 return response()->json([
             'status'=>false,
             'data'=>implode(",",$validator->messages()->all()),
			 'error'=>$validator->Errors()]);

		}
		$has = Address::where('user_id',Auth::id())
				->where('coin_id',$request->get('coin_id'))
				->first();
		if($has){
			$address = $has;
		}
		else {
			$address = new Address;
		}
		$address ->address= $request->get('address');
		$address ->coin_id= $request->get('coin_id');
		$address ->user_id= Auth::id();
		$address ->save();
		return response()->json(['status'=>true,'saved'=>'Address Saved']);
    }
	public function UserAddressDelete($id)
    {
		$validator = Validator::make(['coin_id'=>$id], [
				'coin_id' => 'required|numeric|exists:coins,id',
		]);

		if ($validator->fails()) {
			 return response()->json([
             'status'=>false,
             'data'=>implode(",",$validator->messages()->all()),
			 'error'=>$validator->Errors()]);

		}
		$has = Address::where('user_id',Auth::id())
				->where('coin_id',$id)
				->first();
		if(!$has){
			return response()->json([
			 'status'=>false,
			 'data'=>'Address not exist']);
		}
		else {
			$address =  Address::destroy($has->id);
		}
		return response()->json(['status'=>true,'deleted'=>true]);
    }

    public function Subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|numeric|exists:plans,id',
            'payment_data' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'=>false,
                'data'=>implode(",",$validator->messages()->all()),
                'error'=>$validator->Errors()]);

        }
        // $has = Subscriber::where('user_id',Auth::id())
        //     ->where('plan_id',$request->get('plan_id'))->whereDate('till','>=',now())
        //     ->first();
        // if($has){
        //     return response()->json(['status'=>false,'data'=>'Already subscribed this plan and valid till '.$has->till]);
        // }
        // else {
            Subscriber::where('user_id',Auth::id())->where('active',True)->update(['active'=>false]);
            $Subscriber = new Subscriber;
            $Subscriber->plan_id = $request->get('plan_id');
            $Subscriber->till = (Carbon::now())->addmonth();
            $Subscriber->user_id = Auth::id();
            $Subscriber->payment_data = $request->payment_data;
            $Subscriber->active = True;
            $Subscriber->save();
            $plan =Plan::find($request->get('plan_id'));
            $usermeta =Usermeta::where('user_id',Auth::id())->first();
            $usermeta->limit= $usermeta->limit+$plan->coins;
            $usermeta->save();
            return response()->json(['status' => true, 'data' => 'Successfully  subscribed  plan. ']);
        // }
    }
    public function MySubscription()
    {
        $usermeta =Usermeta::where('user_id',Auth::id())->first();
        $limit =$usermeta->limit;
        $has = Subscriber::where('user_id',Auth::id())->where('active',true) ->first();
        if($has){
            $plan =Plan::where('id',$has->plan_id)->first();

            return response()->json(['status'=>true,'subscription'=>$has, 'plan'=>$plan, 'limit'=>$limit]);
        }
        else {

            return response()->json(['status' => false, 'data' => 'No Subscription found','limit'=>$limit ]);
        }
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
    public function Payment()
    {
        $rate= 0.2563;
        //var_dump(Auth::id());
        $wallets = DB::table('users')
            ->join('wallets', 'wallets.user_id', '=', 'users.id')
            ->join('coins', 'coins.id', '=', 'wallets.coin_id')
            ->select('coins.*', 'wallets.*')
            ->where('users.id', Auth::id())
            ->where('coins.active',true)
            ->where('wallets.bal', '>',0)
            ->get();
            if($wallets):
                if(sizeof($wallets)>0):
                    foreach ($wallets as &$wallet):
                        $HunterController =new HunterController;
                        $rate=$HunterController->updatePrice($wallet->coin_id);
                        $wallet->actual_coin_bal= round(($wallet->ach),7);
                        $wallet->bal_in_usd= round((($wallet->ach)*$rate),7);
                        $wallet->ach= round(( $wallet->ach ) , 7 );
                    endforeach;
                endif;
            endif;
        // if($wallets):
        //     if(sizeof($wallets)>0):
        //         foreach ($wallets as &$wallet):
        //             $HunterController =new HunterController;
        //             $rate=$HunterController->updatePrice($wallet->coin_id);
        //             $wallet->actual_coin_bal= $wallet->bal/$wallet->fraction;
        //             $wallet->bal_in_usd= ($wallet->bal/$wallet->fraction)*$rate;
        //         endforeach;
        //     endif;
        // endif;

        return response()->json(['status'=>true,'wallets'=>$wallets]);
    }
    public function Withdraw(Request $request)
    {
        $has = Withdraw::where('user_id',Auth::id())
            ->where('created_at', '>', Carbon::now()->subDays(30)->toDateTimeString())
            ->get()->count();
        if($has):
            return response()->json(['status'=>false,'data'=>'you already redeem coins within last 30 Days','debug'=>'try after 1 minutes for testing ']);
        endif;
        $hasSubscription = User::where('id',Auth::id())->with('Subscription')->first();
        //return response()->json(['status'=>false,'data'=>$hasSubscription]);
        if(!$hasSubscription):
            return response()->json(['status'=>false,'data'=>'Please Upgrade your wallet', 'line'=> __LINE__]);
        endif;
        if(!$hasSubscription->subscription):
            return response()->json(['status'=>false,'data'=>'Please Upgrade your wallet', 'line'=> __LINE__]);
        endif;
        if($hasSubscription->subscription->till < Carbon::today()):
            return response()->json(['status'=>false,'data'=>'Please Upgrade your wallet', 'line'=> __LINE__]);
        endif;
        if($hasSubscription->subscription->plan_id ==11):
            return response()->json(['status'=>false,'data'=>'Please Upgrade your wallet', 'line'=> __LINE__]);
        endif;
        $validator = Validator::make($request->all(), [
            'id' => 'required|array|exists:wallets,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'=>false,
                'data'=>implode(",",$validator->messages()->all()),
                'error'=>$validator->Errors()]);

        }
        $wallets=Wallet::whereIn('id', $request->id)
            ->where('user_id',Auth::id())
            ->where('bal','>',0)
            ->get();
        if(count($wallets)<1):
            return response()->json(['status'=>false,'data'=>'All selected coins must have balance']);
        endif;
        foreach ($wallets as $k=>$wallet):
            $address= Address::where('user_id',Auth::id())->where('coin_id',$wallet->coin_id)->first();
            if(!$address):
                $coin= Coins::find($wallet->coin_id);
                if($coin):
                    return response()->json(['status'=>false,'data'=>$coin->name.' address not found','coin_id'=>$coin->id]);
                endif;
            endif;
            $wallets[$k]->address =$address->address;
        endforeach;
        foreach ($wallets as $k=>$wallet):
            $withdraw = new Withdraw;
            $withdraw->user_id= Auth::id();
            $withdraw->coin_id =$wallet->coin_id;
            $withdraw->bal =round(($wallet->ach),7);
            $withdraw->address =$wallet->address;
            $withdraw->status =false;
            $withdraw->hash ='dummy';
            $withdraw->save();
            $usermeta =Usermeta::where('user_id',Auth::id())->first();
            $usermeta->limit= $usermeta->limit-$wallet->bal;
            $usermeta->save();
			Wallet::where('user_id',Auth::id())->where('id',$wallet->id)->update(['bal'=>0]);
			Hunted::where('user_id',Auth::id())->where('coin_id',$wallet->coin_id)->delete();
        endforeach;

        return response()->json(['status'=>true,'data'=>'Withdraw process has been started will take 3-4 days to complete']);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////
    public function GetTool(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tool_id' => 'required|numeric|exists:tools,id',
            'payment_data' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'=>false,
                'error'=>$validator->Errors()]);

        }
        $has = Mytool::where('user_id',Auth::id())
            ->where('tool_id',$request->get('tool_id'))->where('active',1)
            ->first();
        if($has):
            return response()->json(['status'=>false,'data'=>'Already using  this tool']);
        endif;
 //       $hasbal = Coins::where('py_name','SATC')->with('balance')->first();
//        return response()->json(['status'=>false,'data'=>$hasbal]);
        $tool = Tool::where('id',$request->tool_id)->first();
        if (!$tool):
            return response()->json(['status'=>false,'data'=>'Tool not exist']);
//        endif;
//        if(!$hasbal):
//            return response()->json(['status'=>false,'data'=>'not Sufficient coins to purchase this Tool ']);
//        endif;
//        if (!$hasbal->balance):
//            return response()->json(['status'=>false,'data'=>'not Sufficient coins to purchase this Tool ']);
//        endif;
//        if ($hasbal->balance->bal< $tool->coins):
//            return response()->json(['status'=>false,'data'=>'not Sufficient coins to purchase this Tool ']);
        else :
            $Mytool = new Mytool;
            $Mytool->tool_id = $request->get('tool_id');
            $Mytool->active =true;
            $Mytool->user_id = Auth::id();
            $Mytool->payment_data =$request->payment_data;
            $Mytool->save();
//            $w= Wallet::where('user_id',Auth::id())->where('coin_id',$hasbal->id)->first();
//            $w->bal = (float)$w->bal- (float)$tool->coins;
//            $w->save();
            return response()->json(['status' => true, 'data'=>'You have purchased this tool ']);
        endif;

    }
    /*/////////////////////////////////////////////////////////////////////////////////////////////*/
    public function MyTool()
    {
        $has = Mytool::where('user_id',Auth::id())->where('active',1)->with('tool')->get();
        if(!$has):
            return response()->json(['status'=>false,'data'=>'No tools purchased yet']);
        else:
            return response()->json(['status'=>true,'mytools'=>$has]);
        endif;
    }
    public function AddsBlock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_data' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'=>false,
                'data'=>implode(",",$validator->messages()->all()),
                'error'=>$validator->Errors()]);

        }
        $usermeta = Usermeta::where('user_id',Auth::id())->first();
        if(!$usermeta):
            $usermeta= new Usermeta;
            $usermeta->user_id=Auth::id();
        endif;
        $usermeta->adds_blocked=true;
        $usermeta->adds_blocked_meta=$request->payment_data;
        $usermeta->save();
        return response()->json(['status'=>true,'banner'=>[]]);
    }
    public function Adds()
    {
        $usermeta = Usermeta::where('user_id',Auth::id())->first();
        if($usermeta):
            if($usermeta->adds_blocked):
                return response()->json(['status'=>true,'banner'=>[]]);
            endif;
        endif;
        if(Cache::has('banner')):
            $id =Cache::get('banner');
            $Banner=Banner::where('id',$id)->first();
        else:
            $Banner=Banner::inRandomOrder()->first();
            if($Banner):
                $expiresAt = now()->addMinutes(.5);
                Cache::add('banner', $Banner->id, $expiresAt);
            endif;
        endif;
        return response()->json(['status'=>true,'banner'=>$Banner]);
    }
    public function AllAdds()
    {

        $Banner=Banner::get();
        return response()->json(['status'=>true,'banner'=>$Banner]);
    }

}
