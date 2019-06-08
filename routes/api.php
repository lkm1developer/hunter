<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/register', 'AuthController@register');
Route::post('/login', 'AuthController@login');
Route::post('/logout', 'AuthController@logout');
Route::post('/forgot/password', 'ResetPasswordController@Init');
Route::post('/forgot/newpassword', 'ForgotPasswordController@Reset');
Route::middleware('jwt.verify')->group(function () {
	Route::get('/user', function (Request $request) {
		return $request->user();
	});
	Route::get('/mywallet/', 'WalletController@Index');
	Route::get('/mywalletcapacity/', 'WalletController@Capacity');

	Route::get('/history/', 'WalletController@History');
	Route::post('/refresh', 'AuthController@refresh');
	Route::get('/me', 'AuthController@me');
	Route::post('/photo', 'AuthController@Photo');
	Route::get('/payment', 'WalletController@Payment');
	Route::post('/withdraw', 'WalletController@Withdraw');
	Route::get('/tophunter', 'WalletController@TopHunter');
    Route::post('/leaderboard', 'WalletController@Leaderboard');
	Route::post('/catchifyoucan', 'HunterController@CatchIfYouCan');
	Route::post('/caught', 'HunterController@Caught');
	Route::get('/address', 'WalletController@UserAddress');
	Route::post('/address', 'WalletController@UserAddressAdd');
	Route::post('/subscribe', 'WalletController@Subscribe');
	Route::get('/mysubscription', 'WalletController@MySubscription');
	Route::delete('/address/coin/{id}', 'WalletController@UserAddressDelete');
    Route::get('/plans', 'StaticController@Plans');
    Route::get('/tool', 'StaticController@Tool');
    Route::post('/tool', 'WalletController@GetTool');
    Route::get('/mytool', 'WalletController@MyTool');
    Route::get('/changepassword', 'ResetPasswordController@ChangePassEmail');
    Route::post('/changepassword', 'ResetPasswordController@ChangePass');
    Route::post('/banner', 'WalletController@AddsBlock');
    Route::get('/banner', 'WalletController@Adds');
    Route::get('/availabletohunt', 'WalletController@Availabletohunt');

}
);
Route::get('/coins', 'StaticController@Coins');
Route::get('/modals', 'StaticController@Modals');
Route::get('/coins/{id}', 'StaticController@CoinSingle');
Route::get('/pages', 'StaticController@Pages');
Route::get('/pages/{id}', 'StaticController@PageSingle');
Route::get('/appsettings', 'StaticController@AppSettings');
Route::get('/allbanner', 'WalletController@AllAdds');
Route::get('/plans/{id}', 'StaticController@PlansSingle');
Route::get('/stats', 'StaticController@CryptoHunterStats');
Route::get('/stats/{id}', 'StaticController@CryptoHunterStats');



Route::fallback(function(){
    return response()->json(['message' => 'Not Found.'], 404);
})->name('api.fallback.404');
