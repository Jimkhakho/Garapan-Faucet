<?php

const
versi = "0.0.1",
host = "https://funfaucet.in/",
refflink = "https://funfaucet.in/?r=542",
youtube = "https://youtube.com/@iewil";

class Bot {
	public function __construct(){
		Display::Ban(title, versi);
		
		cookie:
		Display::Cetak("Register",refflink);
		Display::Line();
		$this->cookie = Functions::setConfig("cookie");
		$this->uagent = Functions::setConfig("user_agent");
		$this->iewil = new Iewil();
		$this->scrap = new HtmlScrap();
		$this->captcha = new Captcha();
		
		Display::Ban(title, versi);
			
		$r = $this->Dashboard();
		if(!$r['username']){
			Functions::removeConfig("cookie");
			print Display::Error("Cookie Expired\n");
			Display::Line();
			goto cookie;
		}
		
		Display::Cetak("Username",$r['username']);
		Display::Cetak("Balance",$r['balance']);
		Display::Cetak("Apikey",$this->captcha->getBalance());
		Display::Line();
		/*
		if($this->ptc()){
			Functions::removeConfig("cookie");
			print Display::Error("Cookie Expired\n");
			Display::Line();
			goto cookie;
		}
		*/
		// 5000 = cloudflare
		// 4999 = limit
		while(true){
			$faucet = $this->faucet("faucet");
			sleep(5);
			//$madFaucet = $this->faucet("madfaucet");
			$timer = $faucet;
			//$timer = min([$faucet, $madFaucet]);
			if($timer == 1000)break;
			Functions::Tmr($timer);
		}
		print Display::Error("Limit faucet reach\n");
		Display::Line();
	}
	
	public function headers($data=0){
		$h[] = "Host: ".parse_url(host)['host'];
		if($data)$h[] = "Content-Length: ".strlen($data);
		$h[] = "User-Agent: ".$this->uagent;
		$h[] = "Cookie: ".$this->cookie;
		return $h;
	}
	
	public function Dashboard(){
		das:
		$r = Requests::get(host."dashboard",$this->headers())[1];
		$scrap = $this->scrap->Result($r);
		if($scrap['locked']){
			print Display::Error("Account Locked\n");
			$tmr = explode('">',explode('<span class="counter" wait="',$r)[1])[0];
			if($tmr){
				Functions::Tmr($tmr+5);
				goto das;
			}
		}
		$bal = explode('</h2>',explode('<h2 class="f-w-600 text-white">', $r)[1])[0];
		$username = explode('</span>',explode('<span class="d-none d-xl-inline-block ml-1" key="t-henry">', $r)[1])[0];
		return ["username"=>$username, "balance"=>$bal];
	}
	public function Firewall(){
		while(1){
			$r = Requests::get(host."firewall",$this->headers())[1];
			$scrap = $this->scrap->Result($r);
			$data = $scrap['input'];
			
			if($scrap['captcha']['mt-3 mb-3 cf-turnstile']){
				$cap = $this->captcha->Turnstile($scrap['captcha']['mt-3 mb-3 cf-turnstile'], host);
				$data['cf-turnstile-response']=$cap;
				if(!$cap)continue;
			}else{
				print Display::Error("Sitekey Error\n"); 
				continue;
			}
			
			$r = Requests::post(host."firewall/verify",$this->headers(), http_build_query($data))[1];
			if(preg_match('/Invalid Captcha/',$r))continue;
			Display::Cetak("Firewall","Bypassed");
			Display::Line();
			return;
		}
	}
	private function ptc(){
		while(true){
			$r = Requests::get(host."ptc",$this->headers())[1];
			$id = explode("'", explode("ptc/view/", $r)[1])[0];//3210'
			if(preg_match('/Just a moment.../', $r)){
				print Display::Error(host."faucet/currency/".$coin.n);
				print Display::Error("Cloudflare Detect\n");
				Display::Line();
				return 1;
			}
			if(!$id)break;
			$r = Requests::get(host."ptc/view/".$id,$this->headers())[1];
			$scrap = $this->scrap->Result($r);
			$timer = explode(';', explode("var timer = ", $r)[1])[0];//10;
			$url = explode("';", explode("var url = '", $r)[1])[0];//https://tap-coin.de/refer/user/15311
			Display::Cetak("ptc", $url);
			if($timer){
				Functions::Tmr($timer+5);
			}
			
			$data = $scrap['input'];
			if($scrap['input']['_iconcaptcha-token']){
				$icon = $this->iconBypass($scrap['input']['_iconcaptcha-token']);
				if(!$icon)continue;
				$data = array_merge($data, $icon);
			}elseif($scrap['captcha']['mt-3 mb-3 cf-turnstile']){
				$data['captcha'] = "turnstile";
				$cap = $this->captcha->Turnstile($scrap['captcha']['mt-3 mb-3 cf-turnstile'], host);
				$data['cf-turnstile-response']=$cap;
				if(!$cap)continue;
			}else{
				print Display::Error("Sitekey Error\n"); 
				continue;
			}
			if(!$data){
				print Display::Error("Data not found");
				sleep(3);
				print "\r                              \r";
				continue;
			}
			
			$data = http_build_query($data);
			$r = Requests::post(host."ptc/verify/".$id,$this->headers(), $data)[1];
			$wr = explode('</div>', explode('<i class="fas fa-exclamation-circle"></i> ',$r)[1])[0];//Invalid Anti-Bot Links
			preg_match("/Swal\.fire\('([^']*)', '([^']*)', '([^']*)'\)/", $r, $matches);
			
			if($matches[1] == 'Good job!'){
				print Display::Sukses($matches[2]);
				$r = $this->Dashboard();
				Display::Cetak("Balance",$r['balance']);
				Display::Cetak("Apikey",$this->captcha->getBalance());
				Display::Line();
			}elseif($wr){
				print Display::Error($wr.n);
				Display::Line();
			}else{
				print Display::Error("no respon".n);
				Display::Line();
			}
		}
		print Display::Error("Ptc has finished\n");
		Display::Line();
	}
	private function faucet($xxx){
		$tmr = 0;
		while(true){
			$r = Requests::get(host.$xxx,$this->headers())[1];
			if(preg_match('/Daily limit reached/', $r)){
				return 1000;
			}
			$scrap = $this->scrap->Result($r);
			if($scrap['locked']){
				print Display::Error("Account Locked\n");
				$tmr = explode('">',explode('<span class="counter" wait="',$r)[1])[0];
				if($tmr){
					Functions::Tmr($tmr+5);
					continue;
				}
			}
			if($scrap['firewall']){
				print Display::Error("Firewall Detect\n");
				$this->Firewall();
				continue;
			}
			if($scrap['cloudflare']){
				print Display::Error(host.$xxx.n);
				print Display::Error("Cloudflare Detect\n");
				Display::Line();
				return 5000;
			}
			$tmr = explode('-', explode('var wait = ', $r)[1])[0];
			if($tmr){
				return 10;
			}
			
			$data = $scrap['input'];
			if(explode('rel=\"',$r)[1]){
				if($sitekey_error){
					print Display::Error("sepertinya captcha update\n");
					exit;
				}
				$antibot = $this->captcha->AntiBot($r);
				if(!$antibot)continue;
				$data['antibotlinks'] = str_replace("+"," ",$antibot);
			}
				
			if($scrap['input']['_iconcaptcha-token']){
				$icon = FreeCaptcha::iconBypass($scrap['input']['_iconcaptcha-token'], $this->headers());
				if(!$icon)continue;
				$data = array_merge($data, $icon);
			}elseif($scrap['captcha']['mt-3 mb-3 cf-turnstile']){
				$data['captcha'] = "turnstile";
				$cap = $this->captcha->Turnstile($scrap['captcha']['mt-3 mb-3 cf-turnstile'], host);
				$data['cf-turnstile-response']=$cap;
				if(!$cap)continue;
			}else{
				$sitekey_error = 1;
				print Display::Error("Sitekey Error\n"); 
				continue;
			}
			if(!$data){
				print Display::Error("Data not found");
				sleep(3);
				print "\r                              \r";
				continue;
			}
			if(is_array($data)){$data = http_build_query($data);}else{continue;}
			$r = Requests::post(host.$xxx."/verify",$this->headers(), $data)[1];
			$wr = explode('</div>', explode('<i class="fas fa-exclamation-circle"></i> ',$r)[1])[0];//Invalid Anti-Bot Links
			preg_match("/Swal\.fire\('([^']*)', '([^']*)', '([^']*)'\)/", $r, $matches);
				
			if($matches[1] == 'Good job!'){
				Display::Cetak('Claim', $xxx);
				print Display::Sukses($matches[2]);
				$r = $this->Dashboard();
				Display::Cetak("Balance",$r['balance']);
				Display::Cetak("Apikey",$this->captcha->getBalance());
				Display::Line();
				return ($xxx=="faucet")?10:10;
			}elseif($wr){
				print Display::Error($wr.n);
				Display::Line();
			}else{
				print Display::Error("no respon".n);
				//print_r($r);exit;
				Display::Line();
			}
		}
	}
}
new Bot();