<?php

namespace App\Http\Controllers;
class TransformController extends Controller
{	
	public function setArray($value)
	{
		if(is_array($value)){
			foreach($value as $k=>$v){
				if($v==null){
					$value[$k] =  '';
				}
			}
		}
		return ($value);
	}
	public function setArrays($value)
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
}