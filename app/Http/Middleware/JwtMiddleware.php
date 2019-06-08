<?php

    namespace App\Http\Middleware;

    use Closure;
    use JWTAuth;
    use Exception;
    use App\User;
    use App\Visitor;
    use Carbon\Carbon;
    use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

    class JwtMiddleware extends BaseMiddleware
    {

        /**
         * Handle an incoming request.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Closure  $next
         * @return mixed
         */
        public function handle($request, Closure $next)
        {
            try {
                $user = JWTAuth::parseToken()->authenticate();
                if(!$user):
                    return response()->json(['status' => false,'msg'=>'Authorization failed','data'=>'Authorization failed']);
                endif;
                User::where('id', $user->id)->update(['online'=> Carbon::now()]);
                $v = new Visitor;
                $v->ip = $request->ip();
                $v->save();
            } catch (Exception $e) {
                if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                    return response()->json(['status' => false,'msg'=> 'Token is Invalid','data'=> 'Token is Invalid']);
                }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                    return response()->json(['status' => false,'msg'=>'Token is Expired','data'=>'Token is Expired']);
                }else{
                    return response()->json(['status' => false,'msg'=>'Authorization Token not found','data'=>'Authorization Token not found']);
                }
            }
            return $next($request);
        }
    }
