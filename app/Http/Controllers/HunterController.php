<?php
namespace App\Http\Controllers;

use App\Coordinate;
use Validator;
use Carbon\Carbon;
use Response;
use Auth;
use App\User;
use App\Wallet;
use App\Address;
use App\Distribution;
use App\Usermeta;
use App\Hunted;
use App\Game;
use App\Coins;
use App\Subscriber;
use Illuminate\Http\Request;
use DB;
use Log;
use Cache;
use App\Plan;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use AnthonyMartin\GeoLocation\GeoLocation as GeoLocation;
use App\Http\Controllers\DistributionsController;
class HunterController extends Controller
{
    public function __construct()
    {
        $this->log = new Logger('Hunter');
        $this->log->pushHandler(new StreamHandler(storage_path('logs/Hunter'.date('d-m-y',time()).'.log')), Logger::INFO);
    }
	protected function setData($data)
	{
		$data =(array)$data;
		array_walk_recursive($data,function(&$item){$item=strval($item);});
		return $data;
	}
    public function toUser($token = false)
    {
        $payload = $this->getPayload($token);

        if (! $user = $this->user->getBy($this->identifier, $payload['sub'])) {
            return false;
        }

        return $user;
    }
    public function Index()
    {
		$users = DB::table('users')

            ->join('wallets', 'wallets.user_id', '=', 'users.id')
			 ->join('coins', 'coins.id', '=', 'wallets.coin_id')
            ->select('coins.*', 'wallets.*')
            ->where('users.id', Auth::id())
            ->get();
		$users= $this->setData($users);
		return response()->json(['status'=>true,'user'=>$users]);
    }
	// top 100 Hunter
	public function CatchIfYouCan(Request $request)
    {

		$validator = Validator::make($request->all(), [
            'lat' => ['required','regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'lng' => ['required','regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
        ]);

		if ($validator->fails()) {
			 return response()->json([
             'status'=>false,
             'data'=>implode(",",$validator->messages()->all()),
			 'error'=>$validator->Errors()]);

        }
        $lat =$request->lat;
        $lng= $request->lng;
        $user= Auth::user();
        $this->IsNewLocation($lat,$lng);
        $distance =40;
        $has=false;
        if($request->has('distance')):
            $has=true;
            $distance=$request->distance;

        endif;
        $distance= ($distance/1000);
        //var_dump($distance);

        $lifetime = User::where('id',Auth::id())->withCount('lifetime')->first();
        //Log::useFiles(storage_path('hunter.log'));
        $this->log->info('Usercame '.$user->email.' lat='.  $lat.' lng='.$lng.' distance='.$distance.' has='.$has);
		$edison = GeoLocation::fromDegrees($lat,$lng);
		$coordinates = $edison->boundingCoordinates($distance, 'kilometers');
        $latMin =$coordinates[0]->getLatitudeInDegrees();
        $latMax =$coordinates[1]->getLatitudeInDegrees();
        $lngMin =$coordinates[0]->getLongitudeInDegrees();
        $lngMax =$coordinates[1]->getLongitudeInDegrees();
        $activeCoins= Coins::where('active',1)->pluck('id');

            $coinArroundMe =Coordinate::
            whereBetween('lat', [$latMin, $latMax])
            ->whereBetween('lng', [$lngMin, $lngMax])
            ->whereIn('coin_id', $activeCoins)
            ->where('deleted_at', null)
            ->where('used',false)
            ->get()->toArray();


        if ($coinArroundMe):
            foreach ($coinArroundMe as &$coin):
                $from = GeoLocation::fromDegrees($lat, $lng);
                $to = GeoLocation::fromDegrees($coin['lat'], $coin['lng']);
                $d=$from->distanceTo($to, 'kilometers');
                $d=$d*1000;
                $coin['distance']=$d;
                $coin['modal']=Coins::where('id',$coin['coin_id'])->get()->pluck('modal');
            endforeach;
        endif;
        $total= count($coinArroundMe);
        $allCoins=(array)$coinArroundMe;
		usort($allCoins, function($a, $b) {
			return $a['distance'] <=> $b['distance'];
        });
        $sent=[]    ;
        $Rest=array_slice($allCoins,0,30);
        foreach($Rest as $r):
            $sent[]=$r['id'];
        endforeach;
        //$coinArroundMe=$coinArroundMe->orderBy('distance', 'ASC');
        //var_dump(count($Rest));
        $this->log->info('Usercamegot '.$user->email.' lat='.  $lat.' lng='.$lng.' distance='.$distance.' has='.$has.' total'.$total.' sentids '. implode(',',$sent));
        return response()->json([
            'total'=>$total,
            'coinArroundMe'=>$Rest,
            'status'=>true,
            ]);
    }
	public function Caught(Request $request)
    {
    //     $HunterController =new HunterController;
	// 	//$c =Coins::find($Coordinate->coin_id);
    //    echo  $rate=$HunterController->updatePrice(5);
    //     die;
        $User = User::where('id',Auth::id())->withCount('Hunted')->with('Subscription')->first();
        $user = Auth::user();
        $usermeta =Usermeta::where('user_id',Auth::id())->first();

        $limit= $usermeta->limit;
        //$usermeta->save();

        if($User):
            // if($User->hunted_count):
                if($User->hunted_count>=$limit):
                        return response()->json(['status'=>false,'upgrade'=>true,'data'=>'Please upgrade your Wallet already hunted coins '.$User->hunted_count. '  Wallet Limit is '.$limit]);
                    // else:
                    //     $plan =Plan::where('id',$User->subscription->plan_id)->first();
                    //     if(!$plan):
                    //         return response()->json(['status'=>false,'upgrade'=>true,'data'=>'Please upgrade your Wallet already hunted coins '.$User->hunted_count]);
                    //     else:
                    //         if($limit<= $User->hunted_count):
                    //             return response()->json(['status'=>false,'upgrade'=>true, 'data'=>'Please upgrade your Wallet already hunted coins '.$User->hunted_count. ' Wallet Limit is '.$limit]);
                    //         endif;
                    //     endif;
                    // endif;
                endif;
            // endif;
        endif;
        $validator = Validator::make($request->all(), [
            'id' => 'required|numeric|exists:coordinates,id',
            'lat' => ['required','regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'lng' => ['required','regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
        ]);

		if ($validator->fails()) {
			 return response()->json([
			 'status'=>false,
             'upgrade'=>false,
             'data'=>implode(",",$validator->messages()->all()),
			 'error'=>$validator->Errors()]);

		}
		$lat =$request->lat;
		$lng= $request->lng;
		$edison = GeoLocation::fromDegrees($lat,$lng);
		$coordinates = $edison->boundingCoordinates(0.15, 'kilometers');
        $latMin =$coordinates[0]->getLatitudeInDegrees();
        $latMax =$coordinates[1]->getLatitudeInDegrees();
        $lngMin =$coordinates[0]->getLongitudeInDegrees();
        $lngMax =$coordinates[1]->getLongitudeInDegrees();
		// $targetCoin =Coordinate::where('id',$request->id)->where('used',false)->get()->count();
		$targetCoin =Coordinate::where('id',$request->id)->first();
        if($targetCoin){
            if($targetCoin->used){
                if($targetCoin->user_id){
                    $grabber= User::where('id',$targetCoin->user_id)->first();
                    //return response()->json(['grabber'=>$grabber,'hasGrabbed'=>$targetCoin]);
                    if($grabber){
                        if($grabber->id ==Auth::id())
                        {
                            $this->log->info('caching error-same triedby '.$user->email.' lat='.  $lat.' lng='.$lng.'  coinid='. $request->id .' line='. __LINE__);
                            return response()->json([
                                'status'=>false,
                                'caught'=>false,
                                'upgrade'=>false,
                                'data'=> ' You already grabbed this',
                                'debug'=>$targetCoin
                            ]);
                        }
                        else
                        {
                            $this->log->info('caching error-othername='.$grabber->username.' triedby '.$user->email.' lat='.  $lat.' lng='.$lng.'  coinid='. $request->id.' line='. __LINE__);

                            return response()->json([
                                'status'=>false,
                                'caught'=>false,
                                'upgrade'=>false,
                                'data'=> $grabber->username .' grabbed',
                                'debug'=>$targetCoin
                            ]);
                        }
                    }
                    else {
                        $this->log->info('caching error-SomeoneElseGrabbed triedby '.$user->email.' lat='.  $lat.' lng='.$lng.'   coinid='. $request->id.' line='. __LINE__);

                        return response()->json([
                            'status'=>false,
                            'caught'=>false,
                            'upgrade'=>false,
                            'data'=> ' Someone else grabbed',
                            'debug'=>$targetCoin
                        ]);
                    }
                }
            }
        }
        $activeCoins= Coins::where('active',1)->pluck('id');
        $coinArroundMe =Coordinate::
            whereBetween('lat', [$latMin, $latMax])
            ->whereBetween('lng', [$lngMin, $lngMax])
            ->whereIn('coin_id', $activeCoins)
            ->where('deleted_at', null)
            ->where('used',false)
            ->pluck('id')
            ->toArray();
        if(sizeof($coinArroundMe)==0){
            $this->log->info('caching Coin not within your radius'.$user->email.' lat='.  $lat.' lng='.$lng.'  coinid='. $request->id.' line='. __LINE__);

            return response()->json([
                'status'=>false,
                'caught'=>false,
                'upgrade'=>false,
                'data'=>'Coin not within your radius '
            ]);
        }
        if(!in_array($request->id,$coinArroundMe)){
            $this->log->info('caching Coin not within your radius'.$user->email.' lat='.  $lat.' lng='.$lng.'  coinid='. $request->id.' line='. __LINE__);

            return response()->json([
                'status'=>false,
                'caught'=>false,
                'upgrade'=>false,
                'data'=>'Coin not within your radius '
            ]);
        }
        $Coordinate =Coordinate::where('id',$request->id)->first();
        $user= auth()->user();
        if(!$user){
            $this->log->info('caching please re login'.$user->email.' lat='.  $lat.' lng='.$lng.'  coinid='. $request->id.' line='. __LINE__);

            return response()->json([
                'status'=>false,
                'caught'=>false,
                'upgrade'=>false,
                'data'=>'please re login'
            ]);
        }
        $CurrentCoin =Coins::find($Coordinate->coin_id);
        //var_dump($CurrentCoin->fraction);die;
        $hunted =new Hunted;
        $user_id= $user->id;
        $hunted->user_id =$user_id;
        $hunted->coin_id =$Coordinate->coin_id;
        $hunted->fraction =round(($CurrentCoin->fraction),7);
        $hunted->dist_id =$Coordinate->dist_id;
        $hunted->save();
        $Wallet=Wallet::where('user_id',$user_id)->
                where('coin_id',$Coordinate->coin_id)->first();
        $ach = Hunted:: where('user_id',Auth::id())->where('coin_id',$Coordinate->coin_id)->sum('fraction');
        $ach = round ($ach, 7);
        if(!$Wallet):
            $Wallet= new Wallet;
            $Wallet->user_id=$user_id;
            $Wallet->coin_id=$Coordinate->coin_id;
            $Wallet->ach=$ach;
            $bal=$Wallet->bal=1;
        else:
            $bal=$Wallet->bal=($Wallet->bal)+1;
            $Wallet->ach=$ach;
        endif;
        $Wallet->save();
        Coordinate::where('id',$request->id)->update(['used'=>true,'user_id'=>Auth::id()]);
		$rate= 0.2563;
		$HunterController =new HunterController;
        $c =Coins::find($Coordinate->coin_id);
        $remainingRealCoinValue = round((($c->actual_limit) - ($c->fraction)),7);
        Coins::where('id',$Coordinate->coin_id )->update(['actual_limit'=>$remainingRealCoinValue]);
		$rate=$HunterController->updatePrice($Coordinate->coin_id);
		$actual_coin_bal= $ach;
        $bal_in_usd= round((($actual_coin_bal)*$rate),7);
        $this->log->info('grabbed'.$user->email.' lat='.  $lat.' lng='.$lng.'   coinid='. $request->id.' line='. __LINE__);
        return response()->json([
            'status'=>true,
            'bal'=>$bal,
            'single_coin_value'=>round(($c->fraction),7),
            'single_coin_value_in_usd'=> round((($c->fraction)*$rate),7),
            'actual_coin_bal'=>$actual_coin_bal,
            'bal_in_usd'=>$bal_in_usd,
            'name'=>(Coins::where('id',$Coordinate->coin_id)->first())->py_name
            ]);
    }
    public function updatePrice($id){
        if(Cache::has('usd_price'.$id)):
            return Cache::get('usd_price'.$id);

        else:
        $coin=Coins::where('id',$id)->with('Meta')->first();
        $coin->mn_data ='';
        $client = new \GuzzleHttp\Client();
        if($coin){
            if($coin->py_name == 'BTC' || $coin->py_name == 'LTC' || $coin->py_name == 'ETH'){
                $url='https://api.coinbase.com/v2/prices/'.$coin->py_name.'-USD/spot';
                        $apidata = $client->request('GET', $url);
                        $mn_data = json_decode($apidata->getBody(), true);
                        if(is_array($mn_data)){

                            $p = $mn_data['data']['amount'];
                            $cid = round(((1/$p)* $coin->cid),7);
                            $bal = floor($coin->actual_limit/$cid);
                            Coins::where('id',$id)->update(['price'=>$p, 'fraction'=>$cid,'bal'=>$bal]);
                            //echo 'updating';
                            if(!Cache::has('usd_price'.$coin->id)){
                                Cache::put('usd_price'.$coin->id, $p, now()->addMinutes(12));
                            }
                        }
            } else {
                   try {
                       $url='https://masternodes.online/mno_api/?apiseed=MNOAPI-0063-ac44ae38-5c9bea7e-285b-2193405f';
                        $apidata = $client->request('GET', $url);
                        $mn_data = json_decode($apidata->getBody(), true);
                        if ($mn_data){
                            if (is_array($mn_data)){
                                if (sizeof($mn_data) > 0){
                                    unset($mn_data[0]);
                                    $found = array();
                                    foreach($mn_data as $arr){
                                        if($coin->py_name == 'SWYFT'){
                                            if($arr['coin_ticker']=='SATC-BN' || $arr['coin_ticker']=='SATC-SN'){
                                                $found = $arr;
                                            }
                                        }else {
                                            if($arr['coin_ticker']==$coin->py_name){
                                                $found = $arr;
                                            }
                                        }

                                    }

                                    if(array_key_exists('price_usd',$found)){
                                        //var_dump($found);
                                        $cid = round(((1/$found['price_usd'])* $coin->cid),7);
                                        $bal = floor($coin->actual_limit/$cid);
                                        Coins::where('id',$id)->update(['price'=>$found['price_usd'], 'fraction'=>$cid, 'bal'=>$bal]);
                                        //echo 'updating';
                                        if(!Cache::has('usd_price'.$coin->id)){
                                            Cache::put('usd_price'.$coin->id, $found['price_usd'], now()->addMinutes(12));
                                        }
                                    }

                                    $coin->mn_data = $found;
                                }
                            }
                        }
                   }
                   catch (\Exception $e) {
                       //return $e->getMessage();
                   }
            }
        }

        return Cache::get('usd_price'.$coin->id) ? Cache::get('usd_price'.$coin->id) : 0.2563;
    endif;
    }
    public function IsNewLocation($lat,$lng){
        $distance= 1;
		$edison = GeoLocation::fromDegrees($lat,$lng);
		$coordinates = $edison->boundingCoordinates($distance, 'kilometers');
        $latMin =$coordinates[0]->getLatitudeInDegrees();
        $latMax =$coordinates[1]->getLatitudeInDegrees();
        $lngMin =$coordinates[0]->getLongitudeInDegrees();
        $lngMax =$coordinates[1]->getLongitudeInDegrees();
        $IsNewLocation =Coordinate::whereBetween('lat', [$latMin, $latMax])->whereBetween('lng', [$lngMin, $lngMax])->get()->count();
        //var_dump($IsNewLocation);die;
        if($IsNewLocation <1){
            $DistributionsController =new DistributionsController;
            $added= $DistributionsController->foundNewLocation($lat,$lng);
            $this->log->info('old user on new location added coin = '.$added);
        }

    }


}
