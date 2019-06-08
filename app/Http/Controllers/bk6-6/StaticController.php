<?php
namespace App\Http\Controllers;
use http\Env\Request;
use Redirect;
use App\Page;
use App\Coins;
use App\Coinsmeta;
use App\Usermeta;
use App\Banner;
use App\Plan;
use Response;
use Auth;
use App\Subscriber;
use App\Tool;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Http\Controllers\Controller;
class StaticController extends Controller
{

    public function Coins()
    {

       $coins=Coins::where('active',true)->get();
	   return response()->json(['status'=>true,'coins'=>$coins]);
    }
	public function Modals()
    {

       $coins=Coins::where('active',1)->select(['id','name','py_name','modal'])->get();
//       $coins=Coins::where('modal','!=',false)->get();
//       if ($coins):
//           foreach ($coins as $k=>$coin):
//               $coins[$k]->modal= $coin->modal.'?t='.$coin->id;
//           endforeach;
//       endif;
	   return response()->json(['status'=>true,'modals'=>$coins]);
    }

    public function CoinSingle($id)
    {
        $coins=Coins::where('id',$id)->with('Meta')->withCount('Available')->first();
		return response()->json(['status'=>true,'coins'=>$coins]);
    }

	public function Pages()
    {

       $Pages=Page::get();
	   return response()->json(['status'=>true,'pages'=>$Pages]);
    }

    public function PageSingle($id)
    {
        $Pages=Page::where('id',$id)->first();
		return response()->json(['status'=>true,'pages'=>$Pages]);
    }
	public function Plans()
    {

       $Plans=Plan::where('active',1)->get();
       $has = Subscriber::where('user_id',Auth::id())->whereDate('till','>=',now())->where('active',true) ->first();
       if($Plans):
           foreach ($Plans as &$p):
               if($has):
                   if($p->id == $has->plan_id):
                       $p->msg='Active';
                       $p->subscribed=true;
                   else:
                        if($p->coins==5):
                         $p->msg='NA';
                        else:
                        $p->msg='Buy';
                        endif;
                       $p->subscribed=false;
                   endif;
               else:
                    if($p->coins==5):
                        $p->msg='NA';
                    else:
                        $p->msg='Buy';
                    endif;
                    $p->subscribed=false;
               endif;
           endforeach;
       endif;
       $meta =Usermeta::where('user_id',Auth::id())->first();
	   return response()->json(['status'=>true,'plans'=>$Plans,'limit'=>$meta->limit]);
    }

    public function PlansSingle($id)
    {
        $Plans=Plan::where('id',$id)->first();
		return response()->json(['status'=>true,'plans'=>$Plans]);
    }


    public function Tool()
    {
        $tools=Tool::where('active',1)->withCount('My_Purchased')->get();
        $hasbal = Coins::where('py_name','SATC')->with('balance')->first();
        $myBal =0;
        if($hasbal):
            if ($hasbal->balance):
                $myBal=$hasbal->balance->bal;
            endif;
        endif;
        if ($tools):
        foreach ($tools as $k=>$tool):
            if ($tool->coins< $myBal):
                $tools[$k]->available_to_purchase= true;
            else:
                $tools[$k]->available_to_purchase= false;
            endif;
        endforeach;
        endif;
        return response()->json(['status'=>true,'tools'=>$tools]);
    }


    public function AppSettings(){
        return response()->json([
            'status'=>true,
            'name' => 'CryptoHunter',
            'description'=> 'Crypto Hunter ',
            'logo'=>'/public/images/logo.png',
            'bannerFee'=>  2.99,
            'bannerRefreshInterval'=>300,
        ]);
    }
    public function CryptoHunterStats($id=null)
    {
		if($id):
			 $coin=Coins::where('id',$id)->with('Meta')->first();
		else:
			if(Cache::has('CryptoHunterStats')):
				$id =Cache::get('CryptoHunterStats');
				$coin=Coins::where('id',$id)->with('Meta')->first();
			else:
				$coin=Coins::inRandomOrder()->where('active',1)->with('Meta')->first();
				if($coin):
					$expiresAt = now()->addMinutes(.5);
					Cache::add('CryptoHunterStats', $coin->id, $expiresAt);
				endif;
			endif;
        endif;


        $client = new \GuzzleHttp\Client();
        if($coin):
            $coin->daily_income_usd = '';
            $coin->daily_income_btc = '';
            $coin->daily_income_coin = '';
            $coin->price = '';
            $coin->volume = '';
            $coin->active_masternode = '';
            $coin->masternode_price = '';
            $coin->required_coin_for_mns = '';
            $coin->provider_logo = 'images/mno-logo.png';

//            foreach($Allmasternode as $key=>&$coin):
                if($coin->meta):
//                    try {
                       $url='http://51.75.240.208:3003/mn_stats_api/' . $coin->meta->shortnm;
                        $apidata = $client->request('GET', $url);
                        $mn_data = json_decode($apidata->getBody(), true);
                        $coin->mn_data = '';

                        if ($mn_data):
                            if (is_array($mn_data)):
                                if (sizeof($mn_data) > 0):
                                    $data = $mn_data[0];
                                    $coin->daily_income_usd = $data['usd_value'];
                                    $coin->daily_income_btc = $data['btc_value'];
                                    $coin->daily_income_coin = $data['btc_value'];
                                    $coin->price = $data['usd_value'];
                                    $coin->volume = $data['volume_usd'];
                                    $coin->active_masternode = $data['nb_node'];
                                    $coin->masternode_price = $data['mn_worth_usd'];
                                    $coin->required_coin_for_mns = $data['colaterall'];
                                    $coin->provider_logo = 'images/mno-logo.png';

                                    if(!Cache::has('usd_price'.$coin->id)):
                                        Cache::put('usd_price'.$coin->id, $data['usd_value'], now()->addMinutes(120));
                                    endif;
                                    unset($data['detail_reward_24h']);
                                    $coin->mn_data = $data;

                                    if ($data['avg_reward_24h']):
                                        if(!$data['avg_reward_24h']==0):

                                        $roi = $coin->meta->minbal / $data['avg_reward_24h'];
                                        $perc = (365 / round($coin->meta->minbal / $data['avg_reward_24h'],5)) * 100;
                                        $coin->roi1 = round($perc,3);
                                        $coin->roi2 = round($roi,3);
                                        endif;
                                    else:
                                        $coin->roi1 = 0;
                                        $coin->roi2 = 0;
                                    endif;
                                endif;
                            endif;
                        endif;
//                    }
//                    catch (\Exception $e) {
//                        //return $e->getMessage();
//                    }
                endif;
//            endforeach;
        endif;
        //return view('stats')->with('coin',$coin);
        return response()->json(['status'=>true,'coin'=>$coin]);
    }

}
