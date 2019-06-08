<?php
namespace App\Http\Controllers;
use Auth;
use App\Coins;
use App\Coordinate;
use App\Distribution;
use App\Http\Controllers\Controller;
use Log;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use AnthonyMartin\GeoLocation\GeoLocation as GeoLocation;
class DistributionsController extends Controller
{
    public function __construct()
    {
        $this->logs = new Logger('DIST');
        $this->logs->pushHandler(new StreamHandler(storage_path('logs/Distribution'.date('d-m-y',time()).'.log')), Logger::INFO);
    }
    private  $added=0;
    public function Store($lat,$lng, $isRegister=false)
    {
        if($isRegister){
            $coinsOb=Coins::inRandomOrder()->where('active',1)->where('dist_at_reg', true)->withCount('Distributed')->get()->toArray();
        } else {
            $coinsOb=Coins::inRandomOrder()->where('active',1)->withCount('Distributed')->get()->toArray();
        }

         $a=[];
         foreach($coinsOb as $c){
             unset($c['name']);
             unset($c['fraction']);
             unset($c['heading']);
             unset($c['description']);
             unset($c['logo']);
             unset($c['modal']);
             if($c['distributed_count'] < $c['bal']){
                $c['track']=$c['distributed_count'];
                $c['added']=0;
                $a[$c['id']] = $c;
             }

         }
        //$coin_id=Coins::inRandomOrder()->where('active',1)->withCount('Distributed')->get();
        //return $a;

        $radius =50;
        $unit ='meter';
        $coins =13;
        //$coin_id=Coins::inRandomOrder()->where('active',1)->get()->pluck('id')->toArray();

        $coin= new Distribution;
        $this->Distribute($radius,$unit,$coins,$lat,$lng,1,$a,false,8);
        $radius =3000;
        $unit ='meter';
        $coins =200;
        //$coin_id=Coins::inRandomOrder()->where('active',1)->get()->with('Distributed')->pluck('id','bal','Distributed')->toArray();
        //return response()->json();
        if($isRegister){
            $coinsOb=Coins::inRandomOrder()->where('active',1)->where('dist_at_reg', true)->withCount('Distributed')->get()->toArray();
        } else {
            $coinsOb=Coins::inRandomOrder()->where('active',1)->withCount('Distributed')->get()->toArray();
        }

         $a=[];
         foreach($coinsOb as $c){
             unset($c['name']);
             unset($c['fraction']);
             unset($c['heading']);
             unset($c['description']);
             unset($c['logo']);
             unset($c['modal']);
             if($c['distributed_count'] < $c['bal']){
                $c['track']=$c['distributed_count'];
                $c['added']=0;
                $a[$c['id']] = $c;
             }

         }
        $coin= new Distribution;
        $this->Distribute($radius,$unit,$coins,$lat,$lng,1,$a,false,30);
        return $this->added;
    }
    public function foundNewLocation($lat,$lng){
        $coinsOb=Coins::inRandomOrder()->where('active',1)->where('dist_at_reg', true)->withCount('Distributed')->get()->toArray();
        $a=[];
        foreach($coinsOb as $c){
            unset($c['name']);
            unset($c['fraction']);
            unset($c['heading']);
            unset($c['description']);
            unset($c['logo']);
            unset($c['modal']);
            if($c['distributed_count'] < $c['bal']){
                $c['track']=$c['distributed_count'];
                $c['added']=0;
                $a[$c['id']] = $c;
             }

        }
       $radius =300;
       $unit ='meter';
       $coins =5;
       $this->Distribute($radius,$unit,$coins,$lat,$lng,1,$a,false,25);
       return $this->added;
    }
    public function Distribute($radius,$unit,$coins,$lat,$lng,$dist_id,$coin_id,$update=false,$gap){
        // 0.00001= 1.1 meter
         // 33 meter = 0.0003
         $pureIds =array_keys($coin_id);
         $this->logs->info('critical', ['coins='.json_encode($coin_id)]);
        $km= $unit=='meter'? $radius/1000:$radius;
        $edison = GeoLocation::fromDegrees($lat, $lng);
        $coordinates = $edison->boundingCoordinates($km, 'km');
        $latMin =$coordinates[0]->getLatitudeInDegrees();
        $latMax =$coordinates[1]->getLatitudeInDegrees();
        $lngMin =$coordinates[0]->getLongitudeInDegrees();
        $lngMax =$coordinates[1]->getLongitudeInDegrees();
        $latRange = range(round($latMin,5),round($latMax,5),0.0001);
        $lngRange = range(round($lngMin,5),round($lngMax,5),0.0001);

        shuffle($latRange);
        shuffle($lngRange);
        if(sizeof($latRange)>0):
            foreach ($latRange as $key=>$l):
                if($key<$coins):
                    $this->k=$key;
                    $thisLat =$l;
                    $thisLng =$lngRange[array_rand($lngRange)];
                    if($this->SameCoinInMyRadius($thisLat,$thisLng,$gap,$coin_id)):
                        $d= new Coordinate;
                        if(count($pureIds) == 0){
                            break;
                        }
                        $luckyCoinId = $pureIds[array_rand($pureIds)];
                        if($coin_id[$luckyCoinId]['track'] >= $coin_id[$luckyCoinId]['bal']){
                            // $this->logs->info('crossingLimitAAA  for '.json_encode($pureIds));
                            unset($pureIds[$luckyCoinId]);
                            // $this->logs->info('crossingLimitAAA  for '.json_encode($pureIds));
                            $this->logs->warning('crossingLimit  for '.$luckyCoinId);
                        } else {
                            $coin_id[$luckyCoinId]['track'] = ($coin_id[$luckyCoinId]['track'])+1;

                       // var_dump($luckyCoin['id']);
                        if(array_key_exists($luckyCoinId,$coin_id)){
                        $d->coin_id =$luckyCoinId;
                        //$d->coin_id =$coin_id;
                        $d->dist_id= $dist_id;
                        $d->lat=$thisLat;
                        $d->lng =$thisLng;
                        $d->new =true;
                        $d->used=false;
                        $d->save();
                        $this->added++;
                        $this->logs->info('luckyCoinId  for '.$luckyCoinId);
                        $this->logs->info('added for '.Auth::id().' @thisLat='.$thisLat.' thisLng='.$thisLng .' for ='.json_encode($coin_id[$luckyCoinId]));
                        }
                    }

                    endif;
                endif;
            endforeach;
            $this->logs->info('coindAddedWithLimit='.json_encode($coin_id));
        endif;

    }
    public function SameCoinInMyRadius($lat,$lng,$radius,$coin_id){
		$edison = GeoLocation::fromDegrees($lat,$lng);
		$coordinates = $edison->boundingCoordinates(($radius/1000), 'kilometers');
        $latMin =$coordinates[0]->getLatitudeInDegrees();
        $latMax =$coordinates[1]->getLatitudeInDegrees();
        $lngMin =$coordinates[0]->getLongitudeInDegrees();
        $lngMax =$coordinates[1]->getLongitudeInDegrees();
		$activeCoins= Coins::where('active',1)->pluck('id');
        $coinArroundMe =Coordinate::
            whereBetween('lat', [$latMin, $latMax])
            ->whereBetween('lng', [$lngMin, $lngMax])
            //->where('coin_id', $coin_id)
            ->where('used',false)->get()->count();
        if($coinArroundMe>0):
            return false;
        else:
            return true;
        endif;
    }

}
