<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>home</title>
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="{{ url('/public/style.css') }}" type="text/css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

 <body>
	 <header class="main-header">
		  <div class="container">
			  <div class="back-button"><img src="{{ url('/public/images/arrow.png')}}"></div>
			  <h2>{{ $coin->name }} ({{ $coin->py_name }})</h2>
	 </header>
	 <div class="middle-sec">
		<div class="container">
		   <div class="middle-sec-inner">
		      <div class="coin-list">
			      <img src="{{ url('/public/'.$coin->logo)}}">
				  <h3>{{ $coin->name }}</h3>
				  
				  <div class="coin-link"><a href="{{ $coin->meta->website }}">{{ $coin->meta->website }}</a></div>
			  </div>
			  <div class="social-icon">
			     <ul>
				    <li><a href="#"><img src="/public/images/twitter.png"></a></li>
					<li><a href="#"><img src="/public/images/discord.png"></a></li>
					<li><a href="#"><img src="/public/images/bitcoin.png"></a></li>
					<li><a href="#"><img src="/public/images/telegram.png"></a></li>
					<li><a href="#"><img src="/public/images/github.png"></a></li>
				 </ul>
			  </div>
			  <div class="full-umg"><img src="/public/images/business-VoIP.jpg"></div>
			  <!------LPC STATS----->
			  <div class="lpc-stats">
			      <h2>LPC STATS</h2>
				  <ul>
				     <li>
					    <div class="panel panel-default gray-bg">
							<div class="panel-heading ">Daily Income</div>
							<div class="panel-body">
							  <p>$0.777</p>
							  <p>0.000035 BTC</p>
							  <p class="green">2.34565  LPC</p>
							</div>
						</div>
					 </li>
					  <li>
					    <div class="panel panel-default green-bg">
							<div class="panel-heading ">Weekly Income</div>
							<div class="panel-body">
							  <p>$0.777</p>
							  <p>0.000035 BTC</p>
							  <p class="green">2.34565  LPC</p>
							</div>
						</div>
					 </li>
					 <li>
					    <div class="panel panel-default blue-bg">
							<div class="panel-heading">Monthly Income</div>
							<div class="panel-body">
							  <p>$0.777</p>
							  <p>0.000035 BTC</p>
							  <p class="green">2.34565  LPC</p>
							</div>
						</div>
					 </li>
					 <li>
					    <div class="panel panel-default red-bg">
							<div class="panel-heading ">Yearly Income</div>
							<div class="panel-body">
							  <p>$0.777</p>
							  <p>0.000035 BTC</p>
							  <p class="green">2.34565  LPC</p>
							</div>
						</div>
					 </li>
					 
				  </ul>
			  </div>
			  <!-----------Price-table------>
			  <div class="price-volume">
			     <ul>
				     <li>Price: <span>$0.241440</span></li>
					 <li>Volume: <span>$0.241440</span></li>
					 <li>Marketcap: <span>$0.241440</span></li>
					 <li>Change: <span class="green">$0.241440</span></li>
					 <li>Rio (annual): <span>$0.241440</span></li>
					 <li>Active Masternodes: <span>$0.241440</span></li>
					 <li>Required Coins for Masternodes: <span>$0.241440</span></li>
					 <li>Masternodes worth: <span>$0.241440</span></li>
				 </ul>
			  </div>
		   </div>
		 </div>
	 </div>
<!----------------------middle-sec-close------------------------->
	 <footer class="main-footer">
		  <div class="container">
			 <div class="footer-inner">
			    <img src="/public/images/mno-logo.png">
				<a href="https://masternodes.online/">Powered by https://masternodes.online/</a>
			 </div>
		 </div>
	 </footer>
<!----------------------footer-close------------------------->
  </body>
</html>
