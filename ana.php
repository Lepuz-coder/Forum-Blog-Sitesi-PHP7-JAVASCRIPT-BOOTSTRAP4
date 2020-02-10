<?php
$db = new PDO("mysql:host=localhost;dbname=dizi_forum;charset=utf8","root","");

@session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require('src/PHPMailer.php');
require('src/Exception.php');
require('src/SMTP.php');

date_default_timezone_set('Europe/Istanbul');

function krypto($veri){
	
	$veri = md5(sha1(sha1(md5($veri))));
	return $veri;
	
}

function clear($veri){
	$veri = htmlspecialchars(strip_tags($veri));
	return $veri;
}

function seo($s) {
 $tr = array('ş','Ş','ı','I','İ','ğ','Ğ','ü','Ü','ö','Ö','Ç','ç','(',')','/',':',',');
 $eng = array('s','s','i','i','i','g','g','u','u','o','o','c','c','','','-','-','');
 $s = str_replace($tr,$eng,$s);
 $s = strtolower($s);
 $s = preg_replace('/&amp;amp;amp;amp;amp;amp;amp;amp;amp;.+?;/', '', $s);
 $s = preg_replace('/\s+/', '-', $s);
 $s = preg_replace('|-+|', '-', $s);
 $s = preg_replace('/#/', '', $s);
 $s = str_replace('.', '', $s);
 $s = trim($s, '-');
 return $s;
}

function tarih($veri){
	$seconds = strtotime(date("Y-m-d H:i:s")) - strtotime($veri);

		
		$days    = floor($seconds / 86400);
		$month = floor($days/30);
		$year = floor($month/12);
		$hours   = floor(($seconds - ($days * 86400)) / 3600);
		$minutes = floor(($seconds - ($days * 86400) - ($hours * 3600))/60);
		$seconds = floor(($seconds - ($days * 86400) - ($hours * 3600) - ($minutes*60)));
			

		if($year >0){
			$tarih = $year . " yıl";
		}else if($month > 0){
			$tarih = $month . " ay";
		}else if($days > 0){
			$tarih = $days . " gün";
		}else if($hours > 0){
			$tarih = $hours . " saat";
		}else if($minutes > 0){
			$tarih = $minutes . " dk";
		}else if($seconds > 0){
			$tarih = $seconds . " sn";
		}else{
			$tarih = "şimdi";
		}
	
	return $tarih;
}

class kayit_islemleri{
	
	public $kullanicilar = array();
	
	function db_kullanicilar_cek($db){
		
		$cek = $db->prepare("select * from kullanicilar");
		$cek->execute();
		
				while($sonuc = $cek->fetch(PDO::FETCH_ASSOC)):
		
					$this->kullanicilar[$sonuc['id']] = array(
						
							"kullanici_ad" => $sonuc['kullanici_ad'],
							"sifre" => $sonuc["sifre"],
							"email" => $sonuc["email"],
							"email_onay" => $sonuc["email_onay"]
						
					);
		
				endwhile;
		
		
	}
	
	function kontrol($ad,$email,$sifre,$sifreonay,$db){
		//bu fonksiyondan önce db_kullanicilari_cek'i çağırmalısın
		
		$ad = clear($ad);
		$email = clear($email);
		$sifre = clear($sifre);
		$sifreonay = clear($sifreonay);
		$kullanicilar = $this->kullanicilar;
		
		$sonuc = array();//karşıya vereceğimiz json bilgisi
		$islem = true;//devam edip etmeyeceğini kontrol etmek için
		
		if(empty($ad) || empty($email) || empty($sifre) || empty($sifreonay)):
		
			$sonuc["sonuc"] = "bos";
		
		elseif(strlen($sifre) < 8):
		
			$sonuc["sonuc"] = "sifrekisa";
		
			else:
				
			
		
			if($sifre != $sifreonay):
		
				$sonuc["sonuc"] = "sifre";
				$islem = false;
				else:
			
			$checkemail = explode("@",$email);
		
			if(count($checkemail) != 2):
		
				$sonuc["sonuc"] = "notemail";
				$islem = false;
		
			else:
			
			foreach($kullanicilar as $val):
		
				if($val["kullanici_ad"] == $ad):
					
					$sonuc["sonuc"] = "ad";
					$islem = false;
		
				elseif($val["email"] == $email):
		
					$sonuc["sonuc"] = "email";
					$islem = false;
		
				endif;
			
			endforeach;
			
			endif;
			
		endif;
		
			if($islem):
		
				
				$sonuc["sonuc"] = "true";
				$sonuc["icerik"] = '
					
					
				<script>
				$(document).ready(function(){
				$(\'#onayla\').click(function(){
				var kod = $(\'#kod\').val();
		
		$.post("ana.php?islem=onayla",{"kod":kod},function(donen){
			var cevap = $.parseJSON(donen);
				if(cevap.sonuc == "true"){
					$(\'#kapsayici2\').slideUp(500);
					$(\'#sonuc2\').html("<div class=\'alert alert-success mt-2\'>Email Onaylandı !</div>");
					setTimeout(function(){
						window.location.reload();
					},1000)
				}else{
					$(\'#sonuc2\').html("<div class=\'alert alert-danger mt-2\'>Yanlış Kod</div>");
					setTimeout(function(){
						$(\'#sonuc2\').html("");
						$(\'#kod\').val("");
					},1000)
				}
			
		});
		
	})
	
})
				
				</script>
				<div id="kapsayici2">
				<div class="alert alert-info text-center">Emailinize onay kodu gönderilmiştir</div>
				
				
				<input type="text" class="form-control text-center" placeholder="Kodu Giriniz" id="kod">
				<p class="text-muted text-center mt-3">(isterseniz daha sonra onaylayabilirsiniz)</p>
				<div class="row mt-4 mx-auto">
				
					<div class="col-4 btn btn-success mx-auto" id="onayla">ONAYLA</div>
					<a class="col-4 btn btn-primary ml-2 mx-auto" href="index.php">SONRA</a>
					
				</div>
				</div>
				<div id="sonuc2">
				
				
				</div>
				';
		
				$sifre = krypto($sifre);
		
				$ekle = $db->prepare("insert into kullanicilar(kullanici_ad,sifre,email,email_onay,rütbe,resim) VALUES(?,?,?,?,?,?)");
				$ekle->bindParam(1,$ad,PDO::PARAM_STR);
				$ekle->bindParam(2,$sifre,PDO::PARAM_STR);
				$ekle->bindParam(3,$email,PDO::PARAM_STR);
				$ekle->bindValue(4,0,PDO::PARAM_INT);
				$ekle->bindValue(5,"Okur",PDO::PARAM_STR);
				$ekle->bindValue(6,"resimler/human.jpg",PDO::PARAM_STR);
				$ekle->execute();
		
				$id = $db->lastInsertId();
			
				setcookie(krypto("kulid"),$id,time()+60*60*24);
			
				self::mail_gonder($id,$email,$db);
		
				$dosya = fopen("mesaj_bildirim/".$ad.".txt","a");
				fwrite($dosya,"0-0");
				fclose($dosya);
				
			endif;
				
		endif;
		
		echo json_encode($sonuc);
		
	}
	
	function mail_gonder($kulid,$email,$db){
	
		$kod = mt_rand(1000,9999);
		
	$mail = new PHPMailer(true);

	$mail->SMTPDebug = 0;
	$mail->isSMTP();
	$mail->CharSet='UTF-8';

	$mail->Host='smtp.gmail.com';
	$mail->SMTPAuth = true;
	$mail->Username="DenemeLepuz@gmail.com";
	$mail->Password="emosano1907";
	$mail->SMTPSecure = 'ssl';
	$mail->Port=465;

	$mail->setFrom($mail->Username,"Lepuz Resmi Dizi Sitesi");

	$mail->isHTML(true);
		
	$mail->addAddress($email);
	$mail->Subject = "Onay";
	$mail->Body="Onay Kodu : <b>".$kod."</b><br><a href = 'http://localhost/php/Bireyselu/forum/onay.php?kod=".krypto($kod)."' style='text-align:center;'>ONAYLAMAK İÇİN TIKLAYINIZ</a><br><b>Linkle onaylamak için önce giriş yapmalısınız!!<b>";

	if($mail->send()):
		
		self::kod_db_ekle($kulid,$kod,$db);
		
	endif;
		
	}
	
	function kod_db_ekle($kulid,$kod,$db){
		$kod = krypto($kod);
		$ekle = $db->prepare("insert into email_kod(kullanici_id,kod) VALUES($kulid,'$kod')");
		$ekle->execute();
		
	}
	
	function kod_kontrol($kod,$db){
				
		$cevap = array();
		
		$kulid = $_COOKIE[krypto("kulid")];
		
			$cek = $db->prepare("select * from email_kod where kullanici_id=$kulid");
			$cek->execute();
			$son = $cek->fetch(PDO::FETCH_ASSOC);
			if($son['kod'] == $kod){
				$cevap["sonuc"] = "true";
				
			$upd = $db->prepare("update kullanicilar set email_onay=1 where id = $kulid");
			$upd->execute();
				
			$dlt = $db->prepare("delete from email_kod where kullanici_id = $kulid");
			$dlt->execute();
				
				
			}else{
				$cevap["sonuc"] = "false";
			}
		
		return json_encode($cevap);
		
	}
}

class giris_islemleri{
	
	function kontrol($db){
		
		$cevap = array();
		
		$ad = $_POST['inputUsername'];
		$sifre = $_POST['inputPassword'];
		$ad = clear($ad);
		$sifre = clear($sifre);
		$sifre = krypto($sifre);

		   if(preg_match("/@/",$ad)):
			
				$sec = $db->prepare("select * from kullanicilar where email=? and sifre = ?");
				$sec->execute(array($ad,$sifre));
				
					if($sec->rowCount() != 0):
		
						$cevap["sonuc"] = "true";
		
						self::giris($ad,$db,false);
		
					else:
		
						$cevap["sonuc"] = "false";
		
						$sec = $db->prepare("select * from kullanicilar where email=?");
						$sec->execute(array($ad));
						
						if($sec->rowCount() != 0):
		
						$cevap["hata"] = "sifre";
		
						else:
		
						$cevap["hata"] = "email";
					
						endif;
		
					endif;
		
			else:
	
					$sec = $db->prepare("select * from kullanicilar where kullanici_ad=? and sifre = ?");
				$sec->execute(array($ad,$sifre));
				
					if($sec->rowCount() != 0):
		
						$cevap["sonuc"] = "true";
						self::giris($ad,$db,true);
		
					else:
		
						$cevap["sonuc"] = "false";
		
						$sec = $db->prepare("select * from kullanicilar where kullanici_ad=?");
						$sec->execute(array($ad));
						
						if($sec->rowCount() != 0):
		
						$cevap["hata"] = "sifre";
		
						else:
		
						$cevap["hata"] = "kulad";
					
						endif;
		
					endif;
				
			endif;
		
			echo json_encode($cevap);
	}
	
	function giris($veri,$db,$islem){
		
		if($islem):
		
		$cek = $db->prepare("select * from kullanicilar where kullanici_ad = '$veri'");
		$cek->execute();
		$sonuc = $cek->fetch(PDO::FETCH_ASSOC);
		$id = $sonuc["id"];
			setcookie(krypto("kulid"),$sonuc["id"],time()+60*60*24);
		
		else:
		
		$cek = $db->prepare("select * from kullanicilar where email = '$veri'");
		$cek->execute();
		$sonuc = $cek->fetch(PDO::FETCH_ASSOC);
		$id = $sonuc["id"];
			setcookie(krypto("kulid"),$sonuc["id"],time()+60*60*24);
		
		endif;
		setcookie(krypto("mesaj"),"",time()-10);
		$upd = $db->prepare("update kullanicilar set durum=1 where id=?");
		$upd->execute(array($id));
	}
}

class yazi_islemleri{
	
	function tur_secim($tur){
		setcookie(krypto('tur_isim'),"",time()-10);
		if($tur == "dizi"):
		
			setcookie(krypto('tur_isim'),"dizi");
		
		elseif($tur == "film"):
			setcookie(krypto('tur_isim'),"film");
		
		endif;
		
	}
	
	function dizi_ara($aranan,$db){
		
		if(!empty($aranan)):
		
		$aranan = trim($aranan);
		
		$sec = $db->prepare("select * from diziler where dizi_ad ='$aranan'");
		$sec->execute();
		
		if($sec->rowCount() != 0):
		
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		echo '<input type="button" class="btn btn-secondary btn-block tikla_sakla" value="'.$sonuc["dizi_ad"].'" name="aranan"> ';
		
		else:
		
		$sec = $db->prepare("select * from diziler where dizi_ad LIKE '%$aranan%'");
		$sec->execute();
				
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
		
				echo '<input type="button" class="btn btn-secondary btn-block tikla_sakla" value="'.$sonuc["dizi_ad"].'" name="aranan"> ';
		
			endwhile;
		
		endif;
		
		
			echo '<script>
				$(document).ready(function(){
					$(\'input[name="aranan"]\').click(function(){
					
		$(\'#dizi_ara\').val($(this).val().trim());
		$(\'#sonuc\').html("");
			})
				})
			
			</script>';
		
		
		endif;
			
			
	}
	
	function film_ara($aranan,$db){
		
		if(!empty($aranan)):
		
		$aranan = trim($aranan);
		
		$sec = $db->prepare("select * from filmler where film_ad ='$aranan'");
		$sec->execute();
		
		if($sec->rowCount() != 0):
		
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		echo '<input type="button" class="btn btn-secondary btn-block tikla_sakla" value="'.$sonuc["film_ad"].'" name="aranan"> ';
		
		else:
		
		$sec = $db->prepare("select * from filmler where film_ad LIKE '$aranan%'");
		$sec->execute();
				
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
		
				echo '<input type="button" class="btn btn-secondary btn-block tikla_sakla" value="'.$sonuc["film_ad"].'" name="aranan"> ';
		
			endwhile;
		
		endif;
		
		
			echo '<script>
				$(document).ready(function(){
					$(\'input[name="aranan"]\').click(function(){
					
		$(\'#film_ara\').val($(this).val().trim());
		$(\'#sonuc\').html("");
			})
				})
			
			</script>';
		
		
		endif;
			
			
	}
	
	function dizi_kontrol($aranan,$db){
		
		$cevap = array();
		
		$kontrol = $db->prepare("select * from diziler where dizi_ad = '$aranan'");
		$kontrol->execute();
		
			if($kontrol->rowCount() == 0):
		
				$cevap["sonuc"] = "false";
		
			else:
		
				$cevap["sonuc"] = "true";
				$cevap["isim"] = $kontrol->fetch(PDO::FETCH_ASSOC)["dizi_ad"];
				setcookie(krypto("dizi_film_isim"),$aranan);
		
			endif;
		
			echo json_encode($cevap);
		
	}
	
	function film_kontrol($aranan,$db){
		
		$cevap = array();
		
		$kontrol = $db->prepare("select * from filmler where film_ad = ?");
		$kontrol->bindParam(1,$aranan,PDO::PARAM_STR);
		$kontrol->execute();
		
			if($kontrol->rowCount() == 0):
		
				$cevap["sonuc"] = "false";
		
			else:
		
				$cevap["sonuc"] = "true";
				$cevap["isim"] = $kontrol->fetch(PDO::FETCH_ASSOC)["film_ad"];
				setcookie(krypto("dizi_film_isim"),$aranan);
		
			endif;
		
			echo json_encode($cevap);
		
	}
	
	function dizi_film_isim_secim_kontrol($db){
		
		if(isset($_COOKIE[krypto('dizi_film_isim')])):
		
			$sonuc["cevap"] = "true";
		
			$bak = $db->prepare("select * from diziler where dizi_ad = ?");
			$bak->bindParam(1,$_COOKIE[krypto('dizi_film_isim')]);
			$bak->execute();
		
				if($bak->rowCount()>0):
		
				$sonuc["tur"] = "dizi";
		
				else:
		
				$sonuc["tur"] = "film";
		
				endif;
		
			$sonuc["isim"] = $_COOKIE[krypto('dizi_film_isim')];
		else:
		
			$sonuc["cevap"] = "false";
		
		endif;
		
		echo json_encode($sonuc);
	}
	
	function headerlar_duzenleme($icerik){
		
		$icerik = str_replace("<h1>",'<span style=" display: block;
  font-size: 2.5rem;
  margin-bottom: .5rem;
    font-family: inherit;
    font-weight: 500;
    line-height: 1.2;
    color: inherit;">',$icerik);
		$icerik = str_replace("</h1>","</span>",$icerik);
		
		$icerik = str_replace("<h2>",'<span style="display: block;
  font-size:2rem;
  font-family: inherit;
    font-weight: 500;
    line-height: 1.2;
    color: inherit;">',$icerik);
		$icerik = str_replace("</h2>","</span>",$icerik);
		
		$icerik = trim($icerik);
		
		return $icerik;
		
	}
	
	function yazi_verileri_al(){
		
		$baslik = $_POST['yazi_baslik'];
		$giris = $_POST['yazi_baslangic'];
		$icerik = $_POST['yazi_icerik'];//icerik için işlemler yapılacak
		$icerik = self::headerlar_duzenleme($icerik);
		$puan = $_POST['puan_sonuc'];
		
		$stripicerik = strip_tags($icerik);
		
		$baslik = clear($baslik);
		$giris = clear($giris);
		
		$dizi = $_COOKIE[krypto("dizi_film_isim")];
				
		$resim = $_FILES['yazi_resim']['name'];
		
		$ay = date("F");
		$gun = date("d");
		$yil = date("Y");
		$saat = date("H") . ":" . date("i");
		
		$tarih_tr = array('January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart', "April" => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran', 'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül', 'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık','Monday' => 'Pazartesi','Tuesday' => 'Salı','Wednesday' => 'Çarşamba','Thursday' => 'Perşembe','Friday' => 'Cuma','Saturday' => 'Cumartesi','Sunday' => 'Pazar');

		$ay = strtr($ay,$tarih_tr);
		
		$tarih = $gun . " " . $ay . ", " . $yil . " saat " . $saat ;
		
		if(empty($resim)):
		
		$veriler = array("baslik"=>$baslik,"giris"=>$giris,"icerik"=>$icerik,"diziad"=>$dizi,"resim"=>"","puan"=>$puan,"tarih"=>$tarih,"sonuc"=>"true","stripicerik"=>$stripicerik);
				
		else:
		
		$tip = $_FILES['yazi_resim']["type"];
		
		$izin_verilen_tipler = array("image/png","image/jpeg");
		
			if(!in_array($tip,$izin_verilen_tipler)):

			$veriler = array("sonuc"=>"false","hata"=>"*Resim formatı sadece png ya da jpg olabilir");

			else:
		
				if($_FILES['yazi_resim']['size'] > 1024*1024*5):
		
				$veriler = array("sonuc"=>"false","hata"=>"*Resim boyutu en fazla 5 mb olabilir");
		
				else:
		
				$tmp_isim = md5(mt_rand(1000,9999));
		
				$tip = $_FILES['yazi_resim']['name'];
		
				$tip = explode(".",$tip);
		
				$tip = end($tip);
		
				move_uploaded_file($_FILES['yazi_resim']['tmp_name'],"tmp_resimler/".$tmp_isim.".".$tip);
		
				$veriler = array("baslik"=>$baslik,"giris"=>$giris,"icerik"=>$icerik,"diziad"=>$dizi,"tarih"=>$tarih,"puan"=>$puan,"resim"=>"tmp_resimler/".$tmp_isim.".".$tip,"sonuc"=>"true","stripicerik"=>$stripicerik);
		
				endif;
		
			endif;
		
		endif;
		
		echo json_encode($veriler);
		
	}
	
	function kullanici_rutbe_al($id,$db){
		
		$cek = $db->prepare("select * from kullanicilar where id=$id");
		$cek->execute();
		$sonuc = $cek->fetch(PDO::FETCH_ASSOC);
		
		$rutbe = $sonuc["rütbe"];
		
		$cek = $db->prepare("select * from yazilar where kullanici_id=$id");
		$cek->execute();
		
		$sayi = $cek->rowCount();
		$sayi++;
		
		if($sayi < 5 && $sayi > 0 ):
		
			$rutbe2 = "Acemi Yazar";
		
			elseif($sayi < 10 && $sayi >= 5 ):
		
			$rutbe2 = "Tecrübeli Yazar";
		
			elseif($sayi < 50 && $sayi >=10 ):
		
			$rutbe2 = "Kıdemli Yazar";
		
			elseif($sayi < 100 && $sayi >=50):
			
			$rutbe2 = "Usta Yazar";
		
			elseif($sayi >= 100):
		
			$rutbe2 = "Efsanevi Yazar";
		
		endif;
		
			if($rutbe == $rutbe2):
		
			return "true";
		
			else:
		
			$upd = $db->prepare("update kullanicilar set rütbe=? where id=?");
			$upd->execute(array($rutbe2,$id));
		
			return $rutbe2;
		
			endif;
		
		
	}
	
	function yazi_gonder($db){
				
		$kulid = $_COOKIE[krypto("kulid")];
		$baslik = $_POST['yazi_baslik'];
		$giris = $_POST['yazi_baslangic'];
		$icerik = $_POST['yazi_icerik'];//icerik için işlemler yapılacak
		$icerik = self::headerlar_duzenleme($icerik);
		$puan = $_POST['puan_sonuc'];
		
		$rutbe = self::kullanici_rutbe_al($kulid,$db);
		
		if($rutbe === "true"):
		
			$level = "false";
			$rutbe = "";
		
			else:
		
			$level = "true";
		
		endif;
		
		if($puan > 5){
			$puan = 5;
		}
				
		$baslik = clear($baslik);
		$giris = clear($giris);
		
		$dizi = $_COOKIE[krypto("dizi_film_isim")];
				
		$resim = $_FILES['yazi_resim']['name'];
		
		$ay = date("F");
		$gun = date("d");
		$yil = date("Y");
		$saat = date("H") . ":" . date("i");
		
		$tarih_tr = array('January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart', "April" => 'Nisan', 'May' => 'Mayıs', 'June' => 'Haziran', 'July' => 'Temmuz', 'August' => 'Ağustos', 'September' => 'Eylül', 'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık','Monday' => 'Pazartesi','Tuesday' => 'Salı','Wednesday' => 'Çarşamba','Thursday' => 'Perşembe','Friday' => 'Cuma','Saturday' => 'Cumartesi','Sunday' => 'Pazar');

		$ay = strtr($ay,$tarih_tr);
		
		$tarih = $gun . " " . $ay . ", " . $yil . " saat " . $saat ;
		
		$tarih2 = date("Y-m-d H:i:s") ;
		
		if(empty($resim)):
		
		$resim = "";	
		
		else:
		
		$tip = $_FILES['yazi_resim']["type"];
		
		$izin_verilen_tipler = array("image/png","image/jpeg");
		
			if(!in_array($tip,$izin_verilen_tipler)):

			$veriler = array("sonuc"=>"false","hata"=>"*Resim formatı sadece png ya da jpg olabilir");

			else:
		
				if($_FILES['yazi_resim']['size'] > 1024*1024*5):
		
				$veriler = array("sonuc"=>"false","hata"=>"*Resim boyutu en fazla 5 mb olabilir");
		
				else:
		
				$tmp_isim = md5(mt_rand(1000,9999));
		
				$tip = $_FILES['yazi_resim']['name'];
		
				$tip = explode(".",$tip);
		
				$tip = end($tip);
		
				move_uploaded_file($_FILES['yazi_resim']['tmp_name'],"tmp_resimler/".$tmp_isim.".".$tip);
		
				$resim = "tmp_resimler/".$tmp_isim.".".$tip;
		
				endif;
		
			endif;
		
		endif;
		
		$tur = $_COOKIE[krypto("tur_isim")];
		$zero = 0;
		
		$islem2 = new veri_cek_islemleri;
		$islem2->kullanici_ad_bilgileri_cek($db);
		$kulad = $islem2->kullanici_bilgileri[$_COOKIE[krypto("kulid")]];
		
		if($tur == "film"):
		
		$sec = $db->prepare("select * from populer_filmler where film_ad=?");
		$sec->execute(array($dizi));
				
		elseif($tur == "dizi"):
		
		$sec = $db->prepare("select * from populer_diziler where dizi_ad=?");
		$sec->execute(array($dizi));
		
		endif;
		
		if($sec->rowCount() == 0){
				$popy = 0;
			}else{
				$popy = 1;
			}
		
		$ekle = $db->prepare("insert into yazilar(kullanici_id,dizi_film_ad,baslik,tur,yazi_icerik,begeni_sayisi,tarih,resim,begenenler,tarih_val,inceleyenler,puan,kullanici_ad,tur_populer) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
		
		$ekle->execute(array($kulid,$dizi,$baslik,$tur,$icerik,$zero,$tarih,$resim,$zero,$tarih2,$zero,$puan,$kulad,$popy));
		
		$id = $db->lastInsertId();
		
		setcookie(krypto("dizi_film_isim"),"",time()-10);
		setcookie(krypto("tur_isim"),"",time()-10);
		
		echo json_encode(array("id"=>$id,"level"=>$level,"rutbe"=>$rutbe));
	}
}

class veri_cek_islemleri extends mesaj_bildirim{
	
	public $kullanici_bilgileri = array();
		
	public $secilen_yazilar;
	
	public $aranma_sonuc,$populer_secim,$begeni_secim;
		
	public $cevaplar= array();
		
	function yazi_bilgileri_al($db){
		
		$yazi_id = $_GET['yazi_id'];
		
		$sec = $db->prepare("select * from yazilar where id=$yazi_id");
		$sec->execute();
		
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
		$kul = $db->prepare("select * from kullanicilar where id = ?");
		$kul->execute(array($sonuc["kullanici_id"]));
		
		$kulad = $kul->fetch(PDO::FETCH_ASSOC);
		
		 
		
		
		$veriler = array("ad"=>$sonuc["dizi_film_ad"],"baslik"=>$sonuc["baslik"],"tur"=>$sonuc["tur"],"icerik"=>$sonuc["yazi_icerik"],"begeni_sayisi"=>$sonuc["begeni_sayisi"],"tarih"=>$sonuc["tarih"],"resim"=>$sonuc["resim"],"kulad"=>$kulad["kullanici_ad"],"puan"=>$sonuc["puan"]);
		
		return $veriler;
	}
	
	function yorumlari_al($db){
		
		self::cevap_bilgileri_cek($db);
		
		$info = self::kullanicilari_cek($db);
		
		$yazi_id = $_POST['yazi_id'];
		
		$sec = $db->prepare("select * from yorumlar where yazi_id=$yazi_id order by id desc ");
		$sec->execute();
		
		if($sec->rowCount() == 0):
		
		?>

		<div class="jumbotron">
	  <h4 class="display-4">Yorum yok</h4>
	  <p class="lead">Hadi ! ilk yorumu yazın, ve yazı hakkındaki düşüncelerinizi herkesle paylaşın</p>
	  
	</div>

		<?php
		
		else:
				
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
				
			if(in_array($sonuc["id"],$this->cevaplar)):
		
			$tarih = tarih($sonuc["tarih"]);
		
			echo '
			
			 <div class="media mb-4">
          <a href="kullanici/'.$info[$sonuc["kullanici_id"]]["ad"].'"><img class="d-flex mr-3 rounded-circle" src="'.$info[$sonuc["kullanici_id"]]["resim"].'" alt="" width="50" height="50" title="Hesaba git"></a>
          <div class="media-body" style="word-wrap: break-word;">
			  
            <h5 class="mt-0">'.$info[$sonuc["kullanici_id"]]["ad"].'<span class="float-right lead mt-1" style="font-size:15px;">'.$tarih.'</span></h5>
           
		  	'.$sonuc["yorum_icerik"].'
		   <br>
			  <a class="text-info mr-2 mt-1 cevapla_buton btn-sm" style="float: left;" data-id="'.$sonuc["id"].'">Cevapla</a>
			  <br>
			  
            
               
			   <div class="card my-4 sakla mt-5 col-12" id="cevap_iskelet_'.$sonuc["id"].'">
          <div class="card-body">
              <div class="form-group">
                <textarea class="form-control cevap_textarea" rows="3" id="yorum_icerik_'.$sonuc["id"].'" data-id="'.$sonuc["id"].'"></textarea>
              </div>
			  <span class="text-danger" id="cevap_uyari_'.$sonuc["id"].'"></span>
			  <button type="button" class="btn btn-outline-danger btn-sm float-right iptal_cevap" data-id="'.$sonuc["id"].'">İptal</button>
			  
              <button type="button" class="btn btn-outline-info btn-sm float-right mr-3 cevap_gonder" data-id="'.$sonuc["id"].'">Gönder</button>
            
			
			
          </div>
        </div>
			   
              
		
		
		';
		

			$sec2 = $db->prepare("select * from cevaplar where yorum_id=?");
			$sec2->execute(array($sonuc["id"]));
		
			while($sonuc2 = $sec2->fetch(PDO::FETCH_ASSOC)):
			
			$tarih = tarih($sonuc2["tarih"]);
		
		
				echo'
				
				<div class="media mt-4">
               <a href="kullanici/'.$info[$sonuc["kullanici_id"]]["ad"].'"><img class="d-flex mr-3 rounded-circle" src="'.$info[$sonuc2["kullanici_id"]]["resim"].'" alt="" width="50" height="50" title="Hesaba git"></a>
              <div class="media-body">
                <h5 class="mt-0">'.$info[$sonuc2["kullanici_id"]]["ad"].'<span class="float-right lead mt-1" style="font-size:15px; ml-2">'.$tarih.'</span></h5>
               '.$sonuc2["icerik"].'
              </div>
            </div>
				
				';
			
			endwhile;
		
		echo'
          </div>
	</div>
			
			
			';
		
			else:
		
		$tarih = tarih($sonuc["tarih"]);
		
				echo '
				
				<div class="media mb-4">
          <a href="kullanici/'.$info[$sonuc["kullanici_id"]]["ad"].'"> <img class="d-flex mr-3 rounded-circle" src="'.$info[$sonuc["kullanici_id"]]["resim"].'" alt="" width="50" height="50" title="Hesaba git"></a>
			
          <div class="media-body" style="word-wrap: break-word; ">
			  
            <h5 class="mt-0">'.$info[$sonuc["kullanici_id"]]["ad"].'<span class="float-right lead mt-1" style="font-size:15px;">'.$tarih.'</span></h5>
           	
		   	
		  	'.$sonuc["yorum_icerik"].'
		   		<br>
			  <a class="text-info mr-2 mt-1 cevapla_buton btn-sm" style="float: left; cursor=pointer" data-id="'.$sonuc["id"].'">Cevapla</a>
          	
		  </div>
			
        </div>
		<div class="card my-4 sakla" id="cevap_iskelet_'.$sonuc["id"].'">
          <div class="card-body">
              <div class="form-group">
                <textarea class="form-control cevap_textarea" rows="3" id="yorum_icerik_'.$sonuc["id"].'" data-id="'.$sonuc["id"].'"></textarea>
              </div>
			  <button type="button" class="btn btn-outline-danger btn-sm float-right iptal_cevap" data-id="'.$sonuc["id"].'">İptal</button>
			  
              <button type="button" class="btn btn-outline-info btn-sm float-right mr-3 cevap_gonder" data-id="'.$sonuc["id"].'">Gönder</button>
            
			
			
          </div>
        </div>
				
				';
		
		endif;
		
			endwhile;
		
		echo '<script type="text/javascript" src="cevap.js">
				
					
				
				</script>';
		endif;
	}
	
	function kullanici_ad_bilgileri_cek($db){
		
		$cek = $db->prepare("select * from kullanicilar");
		$cek->execute();
		
			while($sonuc = $cek->fetch(PDO::FETCH_ASSOC)):
		
				$this->kullanici_bilgileri[$sonuc["id"]] = $sonuc["kullanici_ad"];
		
			endwhile;
		
	}
	
	function kullanici_tum_bilgileri_cek($db,$ad,$tip=false){
		
		if($tip):
		$sec = $db->prepare("select * from kullanicilar where id='$ad'");
		else:
		$sec = $db->prepare("select * from kullanicilar where kullanici_ad='$ad'");
		endif;
		
		$sec->execute();
		
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
		$bilgi = array();
		$bilgi["ad"] = $sonuc["kullanici_ad"];
		$bilgi["instagram"] = $sonuc["instagram"];
		$bilgi["twitter"] = $sonuc["twitter"];
		$bilgi["resim"] = $sonuc["resim"];
		$bilgi["rütbe"] = $sonuc["rütbe"];
		$bilgi["resim"] = $sonuc["resim"];
		$bilgi["id"] = $sonuc["id"];
		
	
		
		
		$id = $sonuc["id"];
	
		$sec3 = $db->prepare("select * from takipler where takip_edilen_id = $id");
		$sec3->execute();
		
		$bilgi["takipçi"] = $sec3->rowCount();
		
		$sec4 = $db->prepare("select * from takipler where takip_eden_id = $id");
		$sec4->execute();
		
		$bilgi["takip"] = $sec4->rowCount();
		
		
		@$idU = $_COOKIE[krypto("kulid")];
		$sec5 = $db->prepare("select * from takipler where takip_edilen_id=$id and takip_eden_id=$idU");
		$sec5->execute();
		
			if($sec5->rowCount()>0):
		
			$bilgi["currentTakip"] = true;
		
			else:
		
			$bilgi["currentTakip"] = false;
		
			endif;
		
		$sec2 = $db->prepare("select * from yazilar where kullanici_id='$id'");
		$sec2->execute();
		
		if(empty($bilgi["instagram"])):
		$bilgi["instagram"] = "yok";
		endif;
		if(empty($bilgi["twitter"])):
		$bilgi["twitter"] = "yok";
		endif;
		
		$bilgi["yazi"] = $sec2->rowCount();
			
		return $bilgi;
		
	}
	
	function kullanicinin_yazilari_cek($db,$ad,$sayfa){
		
		self::kullanici_ad_bilgileri_cek($db);
		
		$baslangic_sayi = ( $sayfa - 1 ) * 5;
		
		$id = array_search($ad,$this->kullanici_bilgileri);
		
		$cek = $db->prepare("select * from yazilar where kullanici_id=$id order by id desc LIMIT $baslangic_sayi,5");
		$cek->execute();
		$check = true;
		if($cek->rowCount() == 0 && $sayfa == 1):
		
		echo '<h3 class="col-12 mx-auto text-center text-danger mt-5">Hiçbir yazı eklenmemiş</h3>';
		$check = false;
		endif;
		
			while($sonuc = $cek->fetch(PDO::FETCH_ASSOC)):
			
		$link = "yazilar" . "/" . $sonuc["tur"] . "/" .seo($sonuc["dizi_film_ad"]) . "/" . seo($sonuc["baslik"]) . "/" . $ad . "/" . $sonuc["id"];
		$text = $sonuc["yazi_icerik"];
		
		$tarih = tarih($sonuc["tarih_val"]);
		
		$text = strip_tags($text);
			?>

			<div class="alert alert-light mt-4 col-lg-12 col-md-12 col-10 mx-auto" style="box-shadow: 0px 0px 15px gray;  border-radius: 5px;"  >
					
						<div class="container " style="word-break: break-all;">
							
							<div class="row">
							
									<div class="col-md-10 col-lg-10 col-12">
								
									<h3 style="word-break: normal; overflow: hidden;" class="text-dark"><a href="<?php echo $link; ?>" class="text-dark" ><?php echo $sonuc["baslik"]; ?></a></h3>
										<div  style=" overflow: hidden;" class="responsiveheight">
										<p class="lead text-secondary" style="word-break:normal;"><?php echo $text; ?></p>	
										</div><p class="mt-2 text-dark" style="font-size: 20px;">Yazan kullanıcı :<span class="text-info"> <?php echo $ad; ?></span></p>	
									<hr class="destkopcizgihide">
									<h3 class="text-info" style="word-break: normal;"><?php echo $sonuc["dizi_film_ad"]; ?></h3>
									
								</div>
							
								<div class="col-2 col-md-2 col-12" class="text-responsive-boyut">
									<hr class="destkopcizgihide">
								<p style="font-size: 15px;" class="text-dark"><?php echo $tarih; ?> önce</p>
								<hr>
								<p style="font-size: 15px;" class="text-dark"><?php echo $sonuc["inceleme"]; ?> İnceleme</p>
								<hr>
								<p style="font-size: 15px;" class="text-dark"><?php echo $sonuc["begeni_sayisi"]; ?> beğenme</p>
								<hr>
									<a class="btn btn-primary btn-block text-white" href="<?php echo $link; ?>" >Oku</a>
								</div>
							</div>
													
						</div>
					
					</div>  
					
			<?php
		
		
		
			endwhile;
		
		$sayfa++;
		
			if($cek->rowCount() >=5):
		
			echo '
			<script type="text/javascript" src="devam.js"></script>
				<a class="col-6 btn btn-light mx-auto kullanicibuton" data-id="'.$sayfa.'" data-title="'.$ad.'" >Devamını Gör..</a>
			
			';
		
			elseif($check):
		
			echo '
			
			<a class="col-6 btn btn-light mx-auto disabled" >Tümünü Gördünüz</a>				
			';
		endif;
		
	}
	
	function cevap_bilgileri_cek($db){
		
		$cek = $db->prepare("select * from cevaplar");
		$cek->execute();
		
			while($sonuc = $cek->fetch(PDO::FETCH_ASSOC)):
		
				$this->cevaplar[] = $sonuc["yorum_id"];
		
			endwhile;
		
		
		
	}
	
	function yazilar_sablonu($sorgu1,$butonisim,$db,$islem,$tur,$sayfa){
		self::kullanici_ad_bilgileri_cek($db);
		
		
		$cek = $db->prepare($sorgu1);
		$cek->execute();
		
		if($cek->rowCount() == 0):
			
			$this->aranma_sonuc = 0;
		
		else:
		
			$this->aranma_sonuc = 1;
		
			while($sonuc = $cek->fetch(PDO::FETCH_ASSOC)):
		
		$kulad = "";
		@$kulad = $this->kullanici_bilgileri[$sonuc["kullanici_id"]];
		
		$link = "yazilar" . "/" . $sonuc["tur"] . "/" .seo($sonuc["dizi_film_ad"]) . "/" . seo($sonuc["baslik"]) . "/" . $kulad . "/" . $sonuc["id"];
		$text = $sonuc["yazi_icerik"];
		
		$tarih = tarih($sonuc["tarih_val"]);
		
		$text = strip_tags($text);
		
		if(!empty($kulad)):
		
		?>

			<div class="alert alert-light mt-4" >
					
						<div class="container " style="word-break: break-all;">
							
							<div class="row">
							
									<div class="col-md-10 col-lg-10 col-12">
								
									<h2 style="word-break: normal; overflow: hidden;"> <a href="<?php echo $link; ?>" class="text-dark" title="Yazıya git"><?php echo $sonuc["baslik"]; ?></a></h2>
										
										
										<div  style=" overflow: hidden;" class="responsiveheight">
											
											
											
										<p class="lead" style="word-break:normal;"><?php echo $text; ?></p>	
										
											
										</div>
										
										<p class="mt-2 text-dark" style="font-size: 20px;">Yazan kullanıcı :
											
											<span class="text-info"> <a href="kullanici/<?php echo $kulad; ?>" title="Kullanıcı hesabına git"><?php echo $kulad; ?> </a> </span> </p>	
										
									<hr class="destkopcizgihide">
									<h3 class="text-info" style="word-break: normal;"><?php echo $sonuc["dizi_film_ad"];?></h3>
									
								</div>
							
								<div class="col-2 col-md-2 col-12" class="text-responsive-boyut">
									<hr class="destkopcizgihide">
								<p style="font-size: 15px;" class="text-dark"><?php echo $tarih; ?> önce</p>
								<hr>
								<p style="font-size: 15px;" class="text-dark"><?php echo $sonuc["inceleme"]; ?> İnceleme</p>
								<hr>
								<p style="font-size: 15px;" class="text-dark"><?php echo $sonuc["begeni_sayisi"]; ?> beğenme</p>
								<hr>
									<a class="btn btn-primary btn-block text-white" href="<?php echo $link; ?>" >Oku</a>
								</div>
							</div>
													
						</div>
					
					</div>

		<?php
			endif;
			endwhile;
		
			$sayfa++;
		
			if($cek->rowCount() >=5):
		
			echo '
			<script type="text/javascript" src="devam.js"></script>
			<div class="row">
				<a class="col-6 btn btn-light mx-auto '.$butonisim.' " data-id="'.$sayfa.'" data-title="'.$tur.'" >Devamını Gör..</a>
			</div>
			
			<div class="devam_yeri">
			
			</div>
			
			';
		
			else:
		
			echo '
			
				<div class="row">
				<a class="col-6 btn btn-light mx-auto devambuton1 active">Tüm Sonuçları Gördünüz</a>
			</div>
				
			';
		endif;
			endif;
	}
	
	function yazilari_cek($db,$islem,$sayfa,$tur=null,$aranan){
		
		$baslangic_sayi = ( $sayfa - 1 ) * 5;
		
		switch($islem):
		
		case "tumyazilar":
						
		self::yazilar_sablonu("select * from yazilar where tur = '$tur' order by id desc LIMIT $baslangic_sayi,5 ","devambuton1",$db,$islem,$tur,$sayfa);
		
		break;
		
		case "populeryazilar":
		
		
		self::yazilar_sablonu("select * from yazilar where tur = '$tur' order by inceleme desc LIMIT $baslangic_sayi,5 ","devambuton2",$db,$islem,$tur,$sayfa);
		
		break;
		
		case "takip-edilen-yazilar":
		$id = $_COOKIE[krypto("kulid")];
	
		$sec = $db->prepare("select * from takipler where takip_eden_id = $id");
		$sec->execute();
		
		if($sec->rowCount() == 0):
		
		?>

		<div class="jumbotron mt-3">
				  <h2 class="text-dark text-center">Kimseyi takip etmiyorsunuz</h2>
				  <hr class="my-4">
						<p>Eklemek istediğiniz kullanıcıları buradan aratabilirsiniz.</p>
						<div class="mt-3">
      <input class="form-control col-10 float-left" placeholder="Ara"  id="aranan_deger1">
      <button class="btn btn-dark mx-auto col-2  ml-4" id="ara_buton1">Ara</button>
    </div>
	  
	  <script>
	 
		  $('#ara_buton1').click(function(){
			  
			  var aranan = $('#aranan_deger1').val();
			   window.location.href="keşfet/"+aranan;
			  
		  })
		  $('#aranan_deger1').on("keyup",function(e){
			  
			  if(e.keyCode == 13){
				  var aranan = $('#aranan_deger1').val();
			   	  window.location.href="keşfet/"+aranan;
			  }
			  
		  })
	  
	  </script>
						
				</div>
		
		<?php
		else:
		
		$takipler = "0";
		
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
		
				$takipler .= "," . $sonuc["takip_edilen_id"];
		
			endwhile;
		
		
		
		 self::yazilar_sablonu("select * from yazilar where kullanici_id IN($takipler) and tur='$tur' order by id desc LIMIT $baslangic_sayi,5","devambuton3",$db,$islem,$tur,$sayfa);
		
		if($this->aranma_sonuc == 0):
			
		?>

		<div class="jumbotron mt-3">
				  <h2 class="text-dark text-center">Henüz takip ettikleriniz yazı eklememiş</h2>
				  <hr class="my-4">
				  		<?php if($tur == "dizi"): ?>
						<p>Dilerseniz bulmak istediğiniz dizinin yazılarını arayabilirsiniz.</p>
						<?php else : ?>
						<p>Dilerseniz bulmak istediğiniz filmin yazılarını arayabilirsiniz.</p>
						<?php endif; ?>
						<div class="mt-3">
      <input class="form-control col-10 float-left" placeholder="Ara"  id="aranan_deger1">
      <button class="btn btn-dark mx-auto col-2  ml-4" id="ara_buton1">Ara</button>
    </div>
	  
	  <script>
	 
		  $('#ara_buton1').click(function(){
			  
			  var aranan = $('#aranan_deger1').val();
			   window.location.href="keşfet/"+aranan;
			  
		  })
		  $('#aranan_deger1').on("keyup",function(e){
			  
			  if(e.keyCode == 13){
				  var aranan = $('#aranan_deger1').val();
			   	  window.location.href="keşfet/"+aranan;
			  }
			  
		  })
	  
	  </script>
						
				</div>

		<?php
		endif;
		endif;
		
		break;
		
		case "keşfet":
		
		echo "<h2 class='mx-auto text-center'>\"".$aranan."\" için sonuçlar</h2>";
		
		self::yazilar_sablonu("select * from yazilar where dizi_film_ad LIKE '$aranan%' order by id desc LIMIT $baslangic_sayi,5 ","devambuton3",$db,$islem,$tur,$sayfa);
		
			if($this->aranma_sonuc == 0):
		
			self::yazilar_sablonu("select * from yazilar where kullanici_ad LIKE '$aranan%' order by id desc LIMIT $baslangic_sayi,5 ","devambuton3",$db,$islem,$tur,$sayfa);
		
				if($this->aranma_sonuc == 0):

				self::yazilar_sablonu("select * from yazilar where baslik LIKE '$aranan%' order by id desc LIMIT $baslangic_sayi,5 ","devambuton3",$db,$islem,$tur,$sayfa);
		
				  if($this->aranma_sonuc == 0):


					echo '<div class="jumbotron">
				  <p class="lead text-danger">Üzgünüz hiçbir yazı bulamadık :( </p>
				  <hr class="my-4">
				  <p>Arama motoru var olan yazılardan <b>dizi, film, kullanıcı adı ve başlığı</b> tarayarak sonuç döndürür.
				  <hr>Eğer ki araradığınız dizinin/filmin yazısının olmadığını düşünüyorsanız hemen yazı ekleyebilirsiniz.</p>
				  <a class="btn btn-primary btn-lg" href="yaziekle.php" role="button">Yazı Ekle</a>
				</div>';
							
				   endif;
		
				endif;
		
			endif;
		
		break;
		
		case "populer":
		
		if($tur == "dizi"):
		
		$sec = $db->prepare("select * from populer_diziler where dizi_ad = ?");
		$sec->bindParam(1,$aranan,PDO::PARAM_STR);
		$sec->execute();
		
		elseif($tur == "film"):
		
		$sec = $db->prepare("select * from populer_filmler where film_ad = ?");
		$sec->bindParam(1,$aranan,PDO::PARAM_STR);
		$sec->execute();
		
		endif;
		
		if($sec->rowCount() == 0):
		
		echo '<div class="jumbotron">
				  <p class="lead text-danger">Bu '.$tur.' henüz popüler değil :( </p>
				  <hr class="my-4">
				  <p>Eğer ki bunun popüler olması gerektiğini düşünüyorsanız bizimle iletişime geçiniz.</p>
				  <a class="btn btn-primary btn-lg" href="yaziekle.php" role="button">İletişim</a>
				</div>';
							
		
		else:
		
		self::yazilar_sablonu("select * from yazilar where dizi_film_ad = '$aranan' order by id desc LIMIT $baslangic_sayi,5 ","devambuton3",$db,$islem,$tur,$sayfa);
		

					if($this->aranma_sonuc == 0):


					echo '<div class="jumbotron">
				  <p class="lead text-danger">Üzgünüz hiçbir yazı bulamadık :( </p>
				  <hr class="my-4">
				  <p>Popüler olmasına rağmen bir tanecik sonuç yok rezillik..<br>Hadi ! İlk yazıyı ekleyen sen ol !</p>
				  <a class="btn btn-primary btn-lg" href="yaziekle.php" role="button">Yazı Ekle</a>
				</div>';
							
				   endif;
							
		
		endif;
		
		break;
		
		
		
		endswitch;
		
	}
	
	function populer_yazilari_cek($db,$sorgu,$tur,$tip){
		
		$al = $db->prepare($sorgu);
		$al->execute();
		
		$veri = array();
		
			while($sonuc = $al->fetch(PDO::FETCH_ASSOC)):
		
				$veri[$sonuc[$tur]] = "0";	
		
			endwhile;
		
		$sec = $db->prepare("select * from yazilar");
		$sec->execute();
		
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
		
				if(array_key_exists($sonuc["dizi_film_ad"],$veri)):
		
				$veri[$sonuc["dizi_film_ad"]] = $veri[$sonuc["dizi_film_ad"]] + 1;
		
				endif;
		
			endwhile;
		
			foreach($veri as $key=>$val):
		
				echo '
					
					<li class="list-group-item d-flex justify-content-between align-items-center">
					<a href="populer/'.$tip.'/'.$key.'" class="text-dark" title=\'"'.$key.'" yazılarına git\'>'.$key.'</a>
					<span class="badge badge-secondary badge-pill" title="Toplam yazı sayısı">'.$val.'</span>
				  </li>
				
				';
		
			endforeach;
		
		
	}
	
	function begenilen_yazilari_cek($db){
		
		self::kullanici_ad_bilgileri_cek($db);
		
		$sec = $db->prepare("select * from yazilar order by begeni_sayisi desc LIMIT 8");
		$sec->execute();
		
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
			
			@$kulad = $this->kullanici_bilgileri[$sonuc["kullanici_id"]];
		
		$link = "yazilar" . "/" . $sonuc["tur"] . "/" .seo($sonuc["dizi_film_ad"]) . "/" . seo($sonuc["baslik"]) . "/" . $kulad . "/" . $sonuc["id"];
		
			?>
		
			<li class="list-group-item d-flex justify-content-between align-items-center">
				
				<a href="<?php echo $link; ?>" class="text-dark" title="Yazıya git"><b><?php echo $sonuc["baslik"]; ?> </b> (<span class="text-info"><?php echo $sonuc["dizi_film_ad"]; ?></span>)</a>
					<span class="badge badge-secondary badge-pill" title="İnceleme sayısı"><?php echo $sonuc["inceleme"]; ?></span>
				
				  </li>

			<?php
			endwhile;
		
	}
	
	function populer_yazilari_li_cek($db){
		
		self::kullanici_ad_bilgileri_cek($db);
		
		$sec = $db->prepare("select * from yazilar order by inceleme desc LIMIT 8");
		$sec->execute();
		
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
			
			@$kulad = $this->kullanici_bilgileri[$sonuc["kullanici_id"]];
		
		$link = "yazilar" . "/" . $sonuc["tur"] . "/" .seo($sonuc["dizi_film_ad"]) . "/" . seo($sonuc["baslik"]) . "/" . $kulad . "/" . $sonuc["id"];
		
			?>
		
			<li class="list-group-item d-flex justify-content-between align-items-center">
				
				<a href="<?php echo $link; ?>" class="text-dark" title="Yazıya git"><b><?php echo $sonuc["baslik"]; ?> </b> (<span class="text-info"><?php echo $sonuc["dizi_film_ad"]; ?></span>)</a>
					<span class="badge badge-secondary badge-pill" title="İnceleme sayısı"><?php echo $sonuc["inceleme"]; ?></span>
				
				  </li>

			<?php
			endwhile;
		
		
	}
	
	function yazilar_sablonu2($sorgu1,$db,$sinir){
		
		self::kullanici_ad_bilgileri_cek($db);
		
		
		$cek = $db->prepare($sorgu1);
		$cek->execute();
		
		if($cek->rowCount() < $sinir):
			
			$this->aranma_sonuc = 0;
			
		else:
				
			$this->aranma_sonuc = 1;
			
			$i = 1;
			
			while($sonuc = $cek->fetch(PDO::FETCH_ASSOC)):
		
		$this->secilen_yazilar .= ",".$sonuc["id"];
		
		$kulad = "";
		@$kulad = $this->kullanici_bilgileri[$sonuc["kullanici_id"]];
		
		$link = "yazilar" . "/" . $sonuc["tur"] . "/" .seo($sonuc["dizi_film_ad"]) . "/" . seo($sonuc["baslik"]) . "/" . $kulad . "/" . $sonuc["id"];
		$text = $sonuc["yazi_icerik"];
		
		$tarih = tarih($sonuc["tarih_val"]);
		
		$text = strip_tags($text);
		
		if(!empty($kulad)):
			if($i == 1):
		?>
		<div class="carousel-item active">
			<?php else: ?>
		<div class="carousel-item">
			<?php endif; ?>
			<div class="alert alert-light mt-4 col-lg-10 col-md-10 col-12 mx-auto" >
					
						<div class="container " style="word-break: break-all;">
							
							<div class="row">
							
									<div class="col-md-10 col-lg-10 col-12">
								
									<h3 style="word-break: normal; overflow: hidden;"> <a href="<?php echo $link; ?>" class="text-dark" ><?php echo $sonuc["baslik"]; ?></a></h3>
										<div  style=" overflow: hidden;" class="responsiveheight">
										<p class="lead" style="word-break:normal;"><?php echo $text; ?></p>	
										</div><p class="mt-2 text-dark" style="font-size: 20px;">Yazan kullanıcı :<span class="text-info"> <?php echo $kulad; ?></span></p>	
									<hr class="destkopcizgihide">
									<h3 class="text-info" style="word-break: normal;"><?php echo $sonuc["dizi_film_ad"];?></h3>
									
								</div>
							
								<div class="col-2 col-md-2 col-12" class="text-responsive-boyut">
									<hr class="destkopcizgihide">
								<p style="font-size: 15px;" class="text-dark"><?php echo $tarih; ?> önce</p>
								<hr>
								<p style="font-size: 15px;" class="text-dark"><?php echo $sonuc["inceleme"]; ?> İnceleme</p>
								<hr>
								<p style="font-size: 15px;" class="text-dark"><?php echo $sonuc["begeni_sayisi"]; ?> beğenme</p>
								<hr>
									<a class="btn btn-primary btn-block text-white" href="<?php echo $link; ?>" >Oku</a>
								</div>
							</div>
													
						</div>
					
					</div>
				</div>
		<?php
			endif;
			$i++;
			endwhile;
		
		endif;
	}
	
	function onerilen_yazilari_cek($db,$tur,$dizi_film_ad,$numara,$yazi_id){
		
		switch($numara):
		
		case "1":
		
		$this->secilen_yazilar = $yazi_id;
		$not = $this->secilen_yazilar;
		
		self::yazilar_sablonu2("select * from yazilar where dizi_film_ad LIKE '%$dizi_film_ad%' and id NOT IN($not) LIMIT 3",$db,3);
		
		$this->populer_secim = 1;
			
			if($this->aranma_sonuc == 0):
			
				self::yazilar_sablonu2("select * from yazilar where tur = '$tur' and tur_populer=1 and id NOT IN($not) LIMIT 3",$db,3);
		
				$this->populer_secim = 0;
		
			endif;
		
		break;
		
		case "2":
		
		$not = $this->secilen_yazilar;
		
		
			if($this->populer_secim):
				self::yazilar_sablonu2("select * from yazilar where tur = '$tur' and tur_populer=1 and id NOT IN($not) LIMIT 3",$db,3);	
		
				if($this->aranma_sonuc == 0):

				self::yazilar_sablonu2("select * from yazilar where tur = '$tur' and id NOT IN($not) order by begeni_sayisi desc LIMIT 3",$db,3);
		
				endif;
		
			else:
		
				self::yazilar_sablonu2("select * from yazilar where tur = '$tur' and id NOT IN($not) order by begeni_sayisi desc LIMIT 3",$db,3);
		
			endif;
		
		break;
		
		case "3":
		
		if($tur == "film"){$tur="dizi";}elseif($tur == "dizi"){$tur="film";}
		
		self::yazilar_sablonu2("select * from yazilar where tur = '$tur' and tur_populer=1  LIMIT 3",$db,3);
		
			if($this->aranma_sonuc == 0):

				self::yazilar_sablonu2("select * from yazilar where tur = '$tur' order by begeni_sayisi desc LIMIT 3",$db,3);
		
			endif;
		
		break;
		
		
		endswitch;
	}
	
	function kullanicilari_cek($db){
		
		$bilgiler = array();
		
		$cek = $db->prepare("select * from kullanicilar");
		$cek->execute();
		
			while($sonuc = $cek->fetch(PDO::FETCH_ASSOC)):
		
				$bilgiler[$sonuc["id"]] = array(
				
					"ad"=>$sonuc["kullanici_ad"],
					"resim"=>$sonuc["resim"],
					"durum"=>$sonuc["durum"],
					"rütbe"=>$sonuc["rütbe"]
		
				);
		
			endwhile;
		
		return $bilgiler;
		
	}
	
}

class yazilar_islemleri{
	
	function bildirim_ekle($tur,$id,$icerik,$yazi_id,$db){
		
				if($tur == "cevap"):
				//id = yorum_id
		
				$cek = $db->prepare("select * from yorumlar where id=$id");
				$cek->execute();
				$sonuc = $cek->fetch(PDO::FETCH_ASSOC);
		
				$id = $sonuc["kullanici_id"];
				$yazi_id = $sonuc["yazi_id"];
				
				endif;
		
		
				$gonderen_kul_id = $_COOKIE[krypto("kulid")];
				$tarih = date("Y-m-d H:i:s");	
		
				if($id != $gonderen_kul_id):
				
				$ekle = $db->prepare("insert into bildirimler(gonderen_kullanici,alan_kullanici,tur,yazi_id,icerik,tarih) VALUES(?,?,?,?,?,?)");
				$ekle->execute(array($gonderen_kul_id,$id,$tur,$yazi_id,$icerik,$tarih));
		
				endif;
				
	}
	
	function bildirim_cikar($id,$db){
		
				$gonderen_kul_id = $_COOKIE[krypto("kulid")];
				
				$dlt = $db->prepare("delete from bildirimler where gonderen_kullanici=? and alan_kullanici=?");
				$dlt->execute(array($gonderen_kul_id,$id));
		
	}
	
	function yazi_kul_id_cek($db,$yazi_id){
		
		$cek = $db->prepare("select * from yazilar where id=$yazi_id");
		$cek->execute();
		$sonuc = $cek->fetch(PDO::FETCH_ASSOC);
		
			$kulid = $sonuc["kullanici_id"];
		
		return $kulid;
		
	}
	
	
	function yazi_begen($db){
		
		$yazi_id = $_POST['id'];
		
		$kulid = self::yazi_kul_id_cek($db,$yazi_id);
		
		
		$sec = $db->prepare("select * from yazilar where id=$yazi_id");
		$sec->execute();
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
		$textb = $sonuc["begenenler"];
		
		$dizib = explode("-",$textb);

		
		
				if(isset($_COOKIE[krypto("kulid")])):
		
		
					if(in_array($_COOKIE[krypto("kulid")],$dizib)):
		
					$key = array_search($_COOKIE[krypto("kulid")],$dizib);
					unset($dizib[$key]);
		
					$textb = implode("-",$dizib);
		
					$upd = $db->prepare("update yazilar set begeni_sayisi = begeni_sayisi - 1, begenenler='$textb' where id=$yazi_id ");
					$upd->execute();
		
					echo json_encode(array("cevap"=>"false"));
		
					self::bildirim_cikar($kulid,$db);
		
				else:
					$dizib[] = $_COOKIE[krypto("kulid")];

					$textb = implode("-",$dizib);
			
					$upd = $db->prepare("update yazilar set begeni_sayisi = begeni_sayisi + 1, begenenler='$textb' where id=$yazi_id ");
					$upd->execute();

					echo json_encode(array("cevap"=>"true"));
						
					self::bildirim_ekle("begenme",$kulid,"",$yazi_id,$db);
		
				endif;
		
			
				else:
		
		
				echo json_encode(array("cevap"=>"nonuser"));
		
		
				endif;
				
		
		
				
	}
	
	function yorum_gonder($db){
		
		
		if(!isset($_COOKIE[krypto("kulid")])):
		
		echo json_encode(array("cevap"=>"false","hata"=>"giris"));
		
		else:
		
		$cek = $db->prepare("select * from kullanicilar where id=?");
		$cek->execute(array($_COOKIE[krypto("kulid")]));
		$sonuc = $cek->fetch(PDO::FETCH_ASSOC);
		
			if($sonuc["email_onay"] == 0):
		
			echo json_encode(array("cevap"=>"false","hata"=>"onay"));
		
			else:
			
			

			$icerik = $_POST['yorum_icerik'];
			$yazi_id = $_POST['yazi_id'];
			$kulid = $_COOKIE[krypto("kulid")];

			$tarih = date("Y-m-d H:i:s") ;

			$id = self::yazi_kul_id_cek($db,$yazi_id);

			$ekle = $db->prepare("insert into yorumlar(yazi_id,kullanici_id,yorum_icerik,tarih) VALUES(?,?,?,?)");
			$ekle->execute(array($yazi_id,$kulid,$icerik,$tarih));

			echo json_encode(array("cevap"=>"true"));
		
			self::bildirim_ekle("yorum",$id,$icerik,$yazi_id,$db);

			endif;
		
		endif;
		
		
	}
	
	function cevap_gonder($db){
		
		
		if(!isset($_COOKIE[krypto("kulid")])):
		
			echo json_encode(array("cevap"=>"false","hata"=>"giris"));
		
		else:
		
			$cek = $db->prepare("select * from kullanicilar where id=?");
		$cek->execute(array($_COOKIE[krypto("kulid")]));
		$sonuc = $cek->fetch(PDO::FETCH_ASSOC);
		
			if($sonuc["email_onay"] == 0):
		
			echo json_encode(array("cevap"=>"false","hata"=>"onay"));
		
			else:
		
			$yorum_id = $_POST['yorum_id'];
		$icerik = $_POST['icerik'];
		
		$kulid = $_COOKIE[krypto("kulid")];
		
		$tarih = date("Y-m-d H:i:s") ;

		$ekle = $db->prepare("insert into cevaplar(yorum_id,kullanici_id,icerik,tarih) VALUES(?,?,?,?)");
		$ekle->execute(array($yorum_id,$kulid,$icerik,$tarih));
		
		echo json_encode(array("cevap"=>"true"));
				
		self::bildirim_ekle("cevap",$yorum_id,$icerik,"",$db);
		
			endif;
		
		endif;
		
		
		
	}
	
	function inceleme_ekle($db,$yazi_id){
			
		
		if(!isset($_COOKIE[krypto("inceleme")])):
		
			$upd = $db->prepare("update yazilar set inceleme = inceleme + 1 where id=$yazi_id ");
					$upd->execute();
			setcookie(krypto("inceleme"),"true",time()+60*60*120);
		
		endif;
		
		/*Sadece kullanıcılarla inceleme artırma sistemi
		
		$sec = $db->prepare("select * from yazilar where id=$yazi_id");
		$sec->execute();
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
		$textb = $sonuc["inceleyenler"];
		
		$dizib = explode("-",$textb);

		
				if(isset($_COOKIE[krypto("kulid")])):
		
					if(!in_array($_COOKIE[krypto("kulid")],$dizib)):
		
			
					$dizib[] = $_COOKIE[krypto("kulid")];

					$textb = implode("-",$dizib);

					$upd = $db->prepare("update yazilar set inceleme = inceleme + 1, inceleyenler='$textb' where id=$yazi_id ");
					$upd->execute();
								
		
				endif;		
		
				endif;
				
	}*/
	}
}

class kullanici_ayarlari{
	
	function resim_degis($db){
		
		$cevap = array();
		
		$ad = $_FILES['resim']['name'];
		$boyut = $_FILES['resim']['size'];
		$tip = $_FILES['resim']['type'];
		$tmp = $_FILES['resim']['tmp_name'];
		
			if(empty($ad)):
		
				$cevap["sonuc"] = "false";
				$cevap["hata"] = "bos";
		
			elseif($boyut > 1024*1024*5):
		
				$cevap["sonuc"] = "false";
				$cevap["hata"] = "boyut";
		
			elseif($tip != "image/png" && $tip != "image/jpeg"):
		
				$cevap["sonuc"] = "false";
				$cevap["hata"] = "tip";
		
			else:
		
				$flag = true;
		
				$sec = $db->prepare("select * from kullanicilar");
				$sec->execute();
				$uzantilar = array();
		
					while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
		
						
		
						if($sonuc["id"] == $_COOKIE[krypto("kulid")]):
		
						unlink($sonuc["resim"]);
		
						else:
		
						$uzantilar[] = $sonuc["resim"];
		
						endif;
		
					endwhile;
				
				while($flag):
		
				$random = md5(mt_rand(0,999999999));
		
				$array = explode(".",$ad);
				$uzanti = end($array);
		
				$new_name = "kullanici_resimleri/" . $random . "." . $uzanti;
		
					if(!in_array($new_name,$uzantilar)):
		
						$flag = false;
		
					else:
		
						$flag = true;
		
					endif;
		
				endwhile;
		
				
				move_uploaded_file($tmp,$new_name);
		
				$upd = $db->prepare("update kullanicilar set resim=? where id =?");
				$upd->execute(array($new_name,$_COOKIE[krypto("kulid")]));
		
				$cevap["sonuc"] = true;
				$cevap["resim"] = $new_name;
		
			endif;
		
			echo json_encode($cevap);
	}
	
	function kullanici_bilgileri($db,$id){
		
		$sec = $db->prepare("select * from kullanicilar where id = $id");
		$sec->execute();
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
			$bilgiler = array(
				"ad" => $sonuc["kullanici_ad"],
				"sifre" => $sonuc["sifre"],
				"email" => $sonuc["email"],
				"emailonay" => $sonuc["email_onay"],
				"resim" => $sonuc["resim"]
			);
		
		return $bilgiler;
		
	}
	
	function isim_degis($db){
		
		$cevap = array();
		
		$sifre = $_POST['sifre'];
		$sifre = krypto($sifre);
		
		$id = $_COOKIE[krypto("kulid")];
		
		$sec = $db->prepare("select * from kullanicilar where id = $id");
		$sec->execute();
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
			if($sifre != $sonuc["sifre"]):
		
			 $cevap["sonuc"] = "false";
			 $cevap["hata"] = "sifre";
		
			else:
		
			$kulad = $_POST['yeni_kulad'];
		
			$sec = $db->prepare("select  * from kullanicilar where kullanici_ad=?");
		    $sec->execute(array($kulad));
		
				if($sec->rowCount() >0):
		
				$cevap["sonuc"] = "false";
				$cevap["hata"] = "ad";
		
				else:
		
				$cevap["sonuc"] = "true";
				$cevap["ad"] = $kulad;
		
				$upd = $db->prepare("update kullanicilar set kullanici_ad=? where id=?");
				$upd->execute(array($kulad,$id));
		
		
				endif;
		
			endif;
		
		echo json_encode($cevap);
	}
	
	function sifre_degis($db){
		
		$cevap = array();
		
		$eskisifre = $_POST['eski_sifre'];
		$yenisifre = $_POST['yeni_sifre'];
		$yenisifre_tekrar = $_POST['yeni_sifre_tekrar'];
		
		$id = $_COOKIE[krypto("kulid")];
		
		$sec = $db->prepare("select * from kullanicilar where id = ?");
		$sec->execute(array($id));
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
			if(empty($eskisifre) || empty($yenisifre) || empty($yenisifre_tekrar)):
		
				$cevap["sonuc"] = "false";
				$cevap["hata"] = "bos";
				
		
			elseif(krypto($eskisifre) != $sonuc["sifre"]):
		
				$cevap["sonuc"] = "false";
				$cevap["hata"] = "sifre";
		
			elseif(strlen($yenisifre) < 8 ):
		
				$cevap["sonuc"] = "false";
				$cevap["hata"] = "uzunluk";
		
			elseif($yenisifre != $yenisifre_tekrar):
		
				$cevap["sonuc"] = "false";
				$cevap["hata"] = "sifretekrar";
		
			else:
		
				$cevap["sonuc"] = "true";
		
				$sifre = krypto($yenisifre);
		
				$upd = $db->prepare("update kullanicilar set sifre = ? where id = ?");
				$upd->execute(array($sifre,$id));
		
			
		
			endif;
		
		echo json_encode($cevap);
		
	}
	
	function kod_kontrol($db){
		
		$id = $_COOKIE[krypto("kulid")];
		$kod = $_POST['kod'];
		$cevap = array();
		
		$sec = $db->prepare("select * from email_kod where kullanici_id=$id");
		$sec->execute();
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
			if(krypto($kod) != $sonuc["kod"]):
		
			$cevap["sonuc"] = "false";
		
			else:
		
			$cevap["sonuc"] = "true";
		
			$upd = $db->prepare("update kullanicilar set email_onay=1 where id = $id");
			$upd->execute();
				
			$dlt = $db->prepare("delete from email_kod where kullanici_id = $id");
			$dlt->execute();
		
			endif;
		
		echo json_encode($cevap);
		
	}
	
	function tekrar_mail($db,$email){
	$kod = mt_rand(1000,9999);
	$cevap = array();
		
		if(isset($_COOKIE[krypto("mail_send")])):
		
			$cevap["sonuc"] = "false";
		
		else:
		
		$mail = new PHPMailer(true);

	$mail->SMTPDebug = 0;
	$mail->isSMTP();
	$mail->CharSet='UTF-8';

	$mail->Host='smtp.gmail.com';
	$mail->SMTPAuth = true;
	$mail->Username="emirhan.durusoy@gmail.com";
	$mail->Password="38Fetenfi";
	$mail->SMTPSecure = 'ssl';
	$mail->Port=465;

	$mail->setFrom($mail->Username,"Lepuz Resmi Dizi Sitesi");

	$mail->isHTML(true);
		
	$mail->addAddress($email);
	$mail->Subject = "Onay";
	$mail->Body="Onay Kodu : <b>".$kod."</b><br><a href = 'http://localhost/php/Bireyselu/forum/onay.php?kod=".krypto($kod)."' style='text-align:center;'>ONAYLAMAK İÇİN TIKLAYINIZ</a><br><b>Linkle onaylamak için önce giriş yapmalısınız!!(Üyelik formundan direk buraya geldiyseniz de sistem giriş yapmışsınız sayar)<b>";

	if($mail->send()):
		
		$cevap["sonuc"] = "true";
		
		setcookie(krypto("mail_send"),"1",time()+60*3);
		
		self::kod_db_update($db,$kod);
		
	endif;
		
		endif;
		
		echo json_encode($cevap);
		
	}
	
	function kod_db_update($db,$kod){
		
		$kod = krypto($kod);
		$id = $_COOKIE[krypto("kulid")];
		
		$upd = $db->prepare("update email_kod set kod=? where kullanici_id=?");
		$upd->execute(array($kod,$id));
		
	}
	
}

class takip_islemleri extends yazilar_islemleri{
	
	function takip_et($db){
		
		$sonuc = array();
		
		$takip_edilen_id = self::kullanici_id_al($_POST['ad'],$db);
		$takip_eden_id = $_COOKIE[krypto("kulid")];
		
		$sec = $db->prepare("select * from takipler where takip_edilen_id=$takip_edilen_id and takip_eden_id=$takip_eden_id");
		$sec->execute();
				
				if($sec->rowCount()>0):
		
					$sonuc["cevap"] = "false";
		
				else:
		
					$sonuc["cevap"] = "true";
		
		$ekle = $db->prepare("insert into takipler(takip_edilen_id,takip_eden_id) VALUES($takip_edilen_id,$takip_eden_id)");
		$ekle->execute();
		
				self::bildirim_ekle("takip",$takip_edilen_id,"",0,$db);
		
				endif;
			
		echo json_encode($sonuc);
	}
	
	function kullanici_id_al($ad,$db){
		
		$sec = $db->prepare("select * from kullanicilar where kullanici_ad=?");
		$sec->execute(array($ad));
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
		$id = $sonuc["id"];
		
		return $id;
		
		
	}
	
	function takip_birak($db){
		
		$sonuc = array();
		
		$takip_eden_id = $_COOKIE[krypto("kulid")];
		$takip_edilen_id = self::kullanici_id_al($_POST['ad'],$db);
		
		$dlt = $db->prepare("delete from takipler where takip_eden_id=$takip_eden_id and takip_edilen_id=$takip_edilen_id");
		$dlt->execute();
		
		if($dlt->rowCount()>0):
		
		$sonuc["cevap"] = "true";
		
		self::bildirim_cikar($takip_edilen_id,$db);
		
		else:
		
		$sonuc["cevap"] = "false";
		
		endif;
		
		echo json_encode($sonuc);
		
	}
}

class mesaj_islemleri extends veri_cek_islemleri{
	
	public $bilgiler = array();
	
	public $icerik;
	
	function kullanici_bilgileri_sakla($db){
		
		$this->bilgiler = self::kullanicilari_cek($db);
	}
	
	function mesaj_kisileri_cek($db){
		
		$bilgiler = $this->bilgiler;
		
		$mesajlar = array();
		
		$kulid = $_COOKIE[krypto("kulid")];
		
		$sec = $db->prepare("select * from mesajlar where kullanici_1 = $kulid or kullanici_2 = $kulid");
		$sec->execute();
		
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
		
				if($sonuc["kullanici_1"] == $kulid):
					$id = $sonuc["kullanici_2"];
				else:
					$id = $sonuc["kullanici_1"];
				endif;
		if($id != $kulid):
		$mesaj = '
			
			
			<li class="contact kisi_mesaj" data-id="'.$id.'" name="'.$bilgiler[$id]["ad"].'">
					<div class="wrap">';
						if($bilgiler[$id]["durum"] == 1):
						$mesaj .= '<span class="contact-status online"></span>'; 
						else : $mesaj .='<span class="contact-status"></span>'; 
						endif;
		
						$mesaj .= '
						<img src="'.$bilgiler[$id]["resim"].'" alt="" class="online responsive-resim-boyut2"/>
						<div class="meta">
							<p class="name">'.ucfirst($bilgiler[$id]["ad"]).'</p>';
							
						
						  
						$mesaj .= '<p class="preview icerik_sifirla" id="mesaj_kisi_'.$id.'"></p>';
						 
						
					$mesaj .='
						</div>
					</div>
				</li>
						';
		
			if($bilgiler[$id]["durum"] == 1):
			array_unshift($mesajlar,$mesaj);
			else:
			array_push($mesajlar,$mesaj);
			
			endif;
		
		endif;
		
			endwhile;
		
			foreach($mesajlar as $val):
		
				echo $val;
		
			endforeach;
		
		echo '<script type="text/javascript" src="kutu_acma.js"></script>';
		
	}
	
	function mesaj_kisileri_cek_mobil($db){
		
		$bilgiler = $this->bilgiler;
		
		$mesajlar = array();
		
		$kulid = $_COOKIE[krypto("kulid")];
		
		$sec = $db->prepare("select * from mesajlar where kullanici_1 = $kulid or kullanici_2 = $kulid");
		$sec->execute();
		
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
		
				if($sonuc["kullanici_1"] == $kulid):
					$id = $sonuc["kullanici_2"];
				else:
					$id = $sonuc["kullanici_1"];
				endif;
		if($id != $kulid):
		
		$mesaj = '
		
		<div class="card b-1 hover-shadow mb-20">
        <div class="media card-body">
            <div class="media-left pr-12">
                <img class="avatar avatar-xl rounded-circle" src="'.$bilgiler[$id]["resim"].'" alt="...">
            </div>
            <div class="media-body">
                <div class="mb-2">
                    <span class="fs-20 pr-16">'.ucfirst($bilgiler[$id]["ad"]).'</span>
                </div>
                <small class="fs-16 fw-300 ls-1">'.$bilgiler[$id]["rütbe"].'</small>
            </div>
            
        </div>
        <footer class="card-footer flexbox align-items-center">
            <div>
                <strong class="text-info icerik_sifirla" id="mobil_kisi_'.$id.'"></strong>
            </div>
            <div class="card-hover-show">
                <div class="btn btn-xs fs-10 btn-bold btn-info kisi_mesaj" data-id="'.$id.'">Mesaj Gönder</div>
        
            </div>
        </footer>
    </div>
		
		';
		
		
			if($bilgiler[$id]["durum"] == 1):
			array_unshift($mesajlar,$mesaj);
			else:
			array_push($mesajlar,$mesaj);
			
			endif;
		
		endif;
		
			endwhile;
		
			foreach($mesajlar as $val):
		
				echo $val;
		
			endforeach;
		echo '<script type="text/javascript" src="kutu_acma.js"></script>';
	}
	
	function mesajlarda_ara($db){
		
		self::kullanici_bilgileri_sakla($db);
		
		$bilgiler = $this->bilgiler;
		
		$mesajlar = array();
		
		$kulid = $_COOKIE[krypto("kulid")];
		
		$sec = $db->prepare("select * from mesajlar where kullanici_1 = $kulid or kullanici_2 = $kulid");
		$sec->execute();
		
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
		
				if($sonuc["kullanici_1"] == $kulid):
					$id = $sonuc["kullanici_2"];
				else:
					$id = $sonuc["kullanici_1"];
				endif;
		if($id != $kulid):
		
			$desen = "/^".$_POST['aranan']."/i";
			if(preg_match($desen,$bilgiler[$id]["ad"])):
		
		$mesaj = '
			
			
			<li class="contact kisi_mesaj" data-id="'.$id.'" name="'.$bilgiler[$id]["ad"].'">
					<div class="wrap">';
						if($bilgiler[$id]["durum"] == 1):
						$mesaj .= '<span class="contact-status online"></span>'; 
						else : $mesaj .='<span class="contact-status"></span>'; 
						endif;
		
						$mesaj .= '
						<img src="'.$bilgiler[$id]["resim"].'" alt="" class="online responsive-resim-boyut2"/>
						<div class="meta">
							<p class="name">'.ucfirst($bilgiler[$id]["ad"]).'</p>';
							
						
						  
						$mesaj .= '<p class="preview icerik_sifirla" id="mesaj_kisi_'.$id.'"></p>';
						 
						
					$mesaj .='
						</div>
					</div>
				</li>
						';
		
				if($bilgiler[$id]["durum"] == 1):
				array_unshift($mesajlar,$mesaj);
				else:
				array_push($mesajlar,$mesaj);

				endif;
			endif;
		endif;
		
			endwhile;
		
			foreach($mesajlar as $val):
		
				echo $val;
		
			endforeach;
		
		echo '<script type="text/javascript" src="kutu_acma.js"></script>';
		
	}
	
	function mesajlarda_ara_mobil($db){
		
		self::kullanici_bilgileri_sakla($db);
		
		$bilgiler = $this->bilgiler;
		
		$mesajlar = array();
		
		$kulid = $_COOKIE[krypto("kulid")];
		
		$sec = $db->prepare("select * from mesajlar where kullanici_1 = $kulid or kullanici_2 = $kulid");
		$sec->execute();
		
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
		
				if($sonuc["kullanici_1"] == $kulid):
					$id = $sonuc["kullanici_2"];
				else:
					$id = $sonuc["kullanici_1"];
				endif;
		if($id != $kulid):
		
		$desen = "/^".$_POST['aranan']."/i";
			if(preg_match($desen,$bilgiler[$id]["ad"])):
		
		$mesaj = '
		
		<div class="card b-1 hover-shadow mb-20">
        <div class="media card-body">
            <div class="media-left pr-12">
                <img class="avatar avatar-xl rounded-circle" src="'.$bilgiler[$id]["resim"].'" alt="...">
            </div>
            <div class="media-body">
                <div class="mb-2">
                    <span class="fs-20 pr-16">'.ucfirst($bilgiler[$id]["ad"]).'</span>
                </div>
                <small class="fs-16 fw-300 ls-1">'.$bilgiler[$id]["rütbe"].'</small>
            </div>
            
        </div>
        <footer class="card-footer flexbox align-items-center">
            <div>
                <strong class="text-info icerik_sifirla" id="mobil_kisi_'.$id.'"></strong>
            </div>
            <div class="card-hover-show">
                <div class="btn btn-xs fs-10 btn-bold btn-info kisi_mesaj" data-id="'.$id.'">Mesaj Gönder</div>
        
            </div>
        </footer>
    </div>
		
		';
		
		
				if($bilgiler[$id]["durum"] == 1):
				array_unshift($mesajlar,$mesaj);
				else:
				array_push($mesajlar,$mesaj);

				endif;
			endif;
		endif;
		
			endwhile;
		
			foreach($mesajlar as $val):
		
				echo $val;
		
			endforeach;
		echo '<script type="text/javascript" src="kutu_acma.js"></script>';
		
	}
	
	function mesaj_kutusu_oku($db){
		
		if(!isset($_COOKIE[krypto("mesaj")])):
		
		?>
			
			<li class="replies">
			<img src="resimler/human.jpg" alt="" class="responsive-resim-boyut2" />
			<p>Konuşmak için mesaj kutusu seçiniz</p>
			</li>
			<li class="replies">
			<img src="resimler/human.jpg" alt="" class="responsive-resim-boyut2" />
			<p>Ya da yeni mesaj kutusu oluşturunuz</p>
			</li>
			
		<?php
		
		else:
		
		$bilgiler = json_decode($_COOKIE[krypto("mesaj")],true);
		
		$dosya = fopen("mesajlar/".$bilgiler["textbox"],"r");
		@$icerik = fread($dosya,filesize("mesajlar/".$bilgiler["textbox"]));
		fclose($dosya);
		
		//Lepuz#356%Naber(=%=)mirayyugur#356%iyi sen
		
		$mesajlar = explode("(=%=)",$icerik);
				
			foreach($mesajlar as $val):
		
				$mesaj_kisi = explode("#356%",$val);
				
				$kisi = $mesaj_kisi[0];
				@$mesaj = $mesaj_kisi[1];
		
				if($kisi == $bilgiler["kulad1"] && !empty($mesaj)):
		
				?>
					<li class="replies">
					<img src="<?php echo $bilgiler["resim1"] ?>" alt="" class="responsive-resim-boyut2" />
					<p><?php echo $mesaj; ?></p>
					</li>
				
				<?php
		
				elseif(!empty($mesaj)):
		
				?>
			
					<li class="sent">
					<img src="<?php echo $bilgiler["resim2"] ?>" alt="" class="responsive-resim-boyut2" />
					<p><?php echo $mesaj; ?></p>
					</li>
			
				<?php
		
				endif;
		
			endforeach;
		endif;
		
	}
	
	function mesajlaşan_bilgileri_setle($db,$id){
		
		$bilgiler = $this->bilgiler;
		
		$kulid = $_COOKIE[krypto("kulid")];
		
		
		$sec = $db->prepare("select * from mesajlar where kullanici_1=$id and kullanici_2=$kulid or kullanici_1=$kulid and kullanici_2=$id ");
		$sec->execute();
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
		$textbox = $sonuc["mesaj_kutusu"];
		
		//kulad1 giriş yapan kullanıcın id'sine aittir
		$kulad1 = $bilgiler[$kulid]["ad"];
		$kulad2 = $bilgiler[$id]["ad"];
		
		$resim1 = $bilgiler[$kulid]["resim"];
		$resim2 = $bilgiler[$id]["resim"];
		
		$info = array(
			"textbox"=>$textbox,
			"kulad1"=>$kulad1,
			"kulad2"=>$kulad2,
			"resim1"=>$resim1,
			"resim2"=>$resim2,
			"kulid2"=>$id
		);
		
		$info = json_encode($info);
		
		setcookie(krypto("mesaj"),$info);
		
		$kulad2 = ucfirst($kulad2);
		
		echo json_encode(array("ad"=>$kulad2,"resim"=>$resim2));
		
	}
	
	function mesaj_gonder(){
		
		$bilgiler = json_decode($_COOKIE[krypto("mesaj")],true);
		
		$textbox = $bilgiler["textbox"];
		
		if(!empty(trim($_POST['mesaj']))):
		
		$mesaj = "(=%=)".$bilgiler["kulad1"] . "#356%" . $_POST['mesaj'];
		
		$dosya = fopen("mesajlar/".$textbox,"a");
		fwrite($dosya,$mesaj);
		fclose($dosya);
		
		$bilgiler["textbox"] = explode(".",$bilgiler["textbox"]);
		$bilgiler["textbox"] = $bilgiler["textbox"][0];
		
		$textbox = $bilgiler["textbox"] . "-" .$bilgiler["kulad2"] . ".txt";
		
		self::mesaj_durum_degis("1",$textbox);
		
		self::bildirim_ekle($bilgiler["kulad2"]);
		
		endif;
		
		
	}
	
	function mesaj_durum_kontrol(){
		
		$sonuc = array();
		
		$bilgi = json_decode($_COOKIE[krypto("mesaj")],true);
		
		$bilgi["textbox"] = explode(".",$bilgi["textbox"]);
		$bilgi["textbox"] = $bilgi["textbox"][0];
		
		$textbox = $bilgi["textbox"] . "-" .$bilgi["kulad1"] . ".txt";		
		
		$dosya = fopen("mesaj_durum/".$textbox,"r");
		$icerik = fread($dosya,filesize("mesaj_durum/".$textbox));
		fclose($dosya);

		if($icerik == 1):
		
			$sonuc["cevap"] = "true";
		
		self::mesaj_durum_degis("0",$textbox);
		
		else:
		
			$sonuc["cevap"] = "false";
		
		endif;
		
		echo json_encode($sonuc);
		
		
		
	}
	
	function mesaj_durum_degis($val,$textbox){
		
		$dosya = fopen("mesaj_durum/".$textbox,"w");
		fwrite($dosya,$val);
		fclose($dosya);
		
	}
	
	function db_isim_ara($db){
		
		$deger = $_POST['deger'];
		$id = $_COOKIE[krypto("kulid")];
		$ara = $db->prepare("select * from kullanicilar where kullanici_ad LIKE '$deger%' and id NOT IN($id)");
		$ara->execute();
		
			while($sonuc = $ara->fetch(PDO::FETCH_ASSOC)):
			?>
			<div class="btn btn-secondary btn-block kullanici_sec"><?php echo $sonuc["kullanici_ad"]; ?></div>
			
			
			
			<?php
			endwhile;
			?>
			
			<script>
			
				$('.kullanici_sec').click(function(){
					
					$('#istek_isim').val($(this).html());
					$('#istek_sonuc').html("");
					
				})
			
			</script>
			
			<?php
	}
	
	public $dosya_isim;
	public $id2;
	
	function isim_kontrol($db){
		
		$isim = $_POST['isim'];
		
		$sec = $db->prepare("select * from kullanicilar where kullanici_ad=?");
		$sec->execute(array($isim));
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
		
		if($sonuc["id"] != $_COOKIE[krypto("kulid")]):
		
		
		
		$sec = $db->prepare("select * from kullanicilar where kullanici_ad = ?");
		$sec->execute(array($isim));
		$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
			if($sec->rowCount() == 0){
				echo json_encode(array("cevap"=>"false","hata"=>"yok"));
			}else{
				
				$id1 = $_COOKIE[krypto("kulid")];
				$id2 = $sonuc["id"];
				
				$sec = $db->prepare("select * from mesajlar where kullanici_1=$id1 and kullanici_2=$id2 or kullanici_1=$id2 and kullanici_2=$id1");
				$sec->execute();
				
					if($sec->rowCount() >0){
						
						echo json_encode(array("cevap"=>"false","hata"=>"var"));
						
					}else{
						return true;
					}
				
			}
		
		else:
		
			echo json_encode(array("cevap"=>"false","hata"=>"kendi"));
		
		endif;
		
	}
	
	function dosya_isim_olustur($db){
		
		$dosya_isim = md5(mt_rand(0,99999999));
		
		$flag = true;
		
			while($flag):
		
				$sec = $db->prepare("select * from mesajlar where mesaj_kutusu = ?");
				$sec->execute(array($dosya_isim));
		
					if($sec->rowCount() > 0){
						
						$dosya_isim = md5(mt_rand(99999999,999999999999999999));
						
					}else{
						$flag = false;
					}
						
		
			endwhile;
		
		$this->dosya_isim = $dosya_isim . ".txt";
	}
	
	function mesaj_kutu_db_ekle($db){
		
				$dosya_isim = $this->dosya_isim;
		
				$kulad = $_POST['isim'];
		
				$sec = $db->prepare("select * from kullanicilar where kullanici_ad = ?");
				$sec->execute(array($kulad));
		
				$sonuc = $sec->fetch(PDO::FETCH_ASSOC);
				
				$id2 = $sonuc["id"];
				$this->id2 = $id2;
				$id1 = $_COOKIE[krypto("kulid")];
		
				$ekle = $db->prepare("insert into mesajlar(kullanici_1,kullanici_2,mesaj_kutusu) VALUES(?,?,?)");
				$ekle->execute(array($id1,$id2,$dosya_isim));
	}
	
	function txt_dosya_olustur($db){
		
		$dosya_isim = $this->dosya_isim;
		
		touch("mesajlar/".$dosya_isim);
		
		self::kullanici_ad_bilgileri_cek($db);
		
		$kulad1 = $this->kullanici_bilgileri[$_COOKIE[krypto("kulid")]];
		$kulad2 = $_POST['isim'];
		
		$dosya_isim = explode(".",$dosya_isim);
		$dosya_isim = $dosya_isim[0];
		
		touch("mesaj_durum/".$dosya_isim."-".$kulad1.".txt");
		
		self::mesaj_durum_degis("0",$dosya_isim."-".$kulad1.".txt");
		
		touch("mesaj_durum/".$dosya_isim."-".$kulad2.".txt");
		
		self::mesaj_durum_degis("0",$dosya_isim."-".$kulad2.".txt");
		
		
		
		
	}
	
	function yeni_mesaj_kutusu($db){
		
		$sonuc = array();
		
		if(self::isim_kontrol($db)):		
				
		self::dosya_isim_olustur($db);
		
		self::mesaj_kutu_db_ekle($db);
				
		self::txt_dosya_olustur($db);
		
		$sonuc["cevap"] = "true";
		
		echo json_encode($sonuc);
		
		endif;

		
	}
}

class mesaj_bildirim{
	
	function bildirim_ekle($ad){
		
		$id = $_COOKIE[krypto("kulid")];
		
		$dosya = fopen("mesaj_bildirim/".$ad.".txt","r");
		$icerik = fread($dosya,filesize("mesaj_bildirim/".$ad.".txt"));
		fclose($dosya);
		
		$array = explode("-",$icerik);
		
		if(!in_array($id,$array)):
		
			$array[] = $id;
		
	    endif;
		
		$icerik = implode("-",$array);
		
		$dosya = fopen("mesaj_bildirim/".$ad.".txt","w");
		fwrite($dosya,$icerik);
		fclose($dosya);
		
		
	}
	
	function bildirim_cikar(){
		
		$info = json_decode($_COOKIE[krypto("mesaj")],true);
		
		$id = $info["kulid2"];
		$ad = $info["kulad1"];
		
		$dosya = fopen("mesaj_bildirim/".$ad.".txt","r");
		$icerik = fread($dosya,filesize("mesaj_bildirim/".$ad.".txt"));
		fclose($dosya);
		
		$array = explode("-",$icerik);
		
		if(in_array($id,$array)):
		
		$key = array_search($id,$array);
		
		unset($array[$key]);
		
		$icerik = implode("-",$array);
		
		$dosya = fopen("mesaj_bildirim/".$ad.".txt","w");
		fwrite($dosya,$icerik);
		fclose($dosya);
		
		endif;
	}
	
	function bildirim_kontrol(){
		
		$cevap = array();
		
		$veri = json_decode($_COOKIE[krypto("mesaj")],true);
		$ad = $veri["kulad1"];
		
		$dosya = fopen("mesaj_bildirim/".$ad.".txt","r");
		$icerik = fread($dosya,filesize("mesaj_bildirim/".$ad.".txt"));
		
		$array = explode("-",$icerik);
		
		foreach($array as $val):
			
			if($val != 0):
			$val = trim($val);
			$cevap[] = $val;
			
		
			endif;
		
		endforeach;
				
		echo json_encode($cevap);
		
	}
	
	function mesaj_bildirim_sayi($ad){
		
		$dosya = fopen("mesaj_bildirim/".$ad.".txt","r");
		$icerik = fread($dosya,filesize("mesaj_bildirim/".$ad.".txt"));
		$array = explode("-",$icerik);
		
		return count($array) - 2;
	}
	
}

class bildirimler extends veri_cek_islemleri{
	
	
	function bildirim_sayi($db){
		
		$id = $_COOKIE[krypto("kulid")];
		
		$sec = $db->prepare("select * from bildirimler where alan_kullanici=?");
		$sec->execute(array($id));
		
		$bildirim_sayi = $sec->rowCount();
		
		return $bildirim_sayi;
	}
	
	function bildirimleri_al($db){
		
		$id = $_COOKIE[krypto("kulid")];
		
		$sec = $db->prepare("select * from bildirimler where alan_kullanici=?");
		$sec->execute(array($id));
				
			while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
			
					
		
				switch($sonuc["tur"]):
		
					case "begenme":

				self::begenme_bildirim($sonuc["gonderen_kullanici"],$sonuc["tarih"],$sonuc["yazi_id"],$sonuc["id"],$db);

					break;
		
					case "yorum":

				self::yorum_bildirim($sonuc["gonderen_kullanici"],$sonuc["icerik"],$sonuc["tarih"],$sonuc["yazi_id"],$sonuc["id"],$db);

					break;
				
					case "cevap":

				self::cevap_bildirim($sonuc["gonderen_kullanici"],$sonuc["icerik"],$sonuc["tarih"],$sonuc["yazi_id"],$sonuc["id"],$db);
				
		
					break;
		
					case "takip":

				self::takip_bildirim($sonuc["gonderen_kullanici"],$sonuc["tarih"],null,$sonuc["id"],$db);
				
					break;
		
				endswitch;
		
			endwhile;
		
		?>
			<script>
			
				$('.okundu').on("mouseover",function(){
					
					$(this).animate({
                width: "49px"
						}, 200);
					
				})
				
				$('.okundu').on("mouseout",function(){
					
					$(this).animate({
                width: "45px"
						}, 5);
					
				})
				
				$('.okundu').on("click",function(){
					
					var id = $(this).attr("data-id");
					
					$.post("ana.php?islem=bildirim_sil",{"id":id},function(donen){})
					
					$(this).parent().parent().fadeOut(500);
					
					var sayi = $('#bildirim_sayi_1').html();
					sayi--;
					$('#bildirim_sayi_1').html(sayi);
					$('#bildirim_sayi_2').html(sayi);
					
				})
				
				
				
			</script>
		<?php
	}
	
	function begenme_bildirim($kulid,$tarih,$yazi_id,$bid,$db){
		
		$info = self::kullanicilari_cek($db);
		
		$kulad = $info[$kulid]["ad"];
		
		$tarih = tarih($tarih);
		
		$cek = $db->prepare("select * from yazilar where id=$yazi_id");
		$cek->execute();
		$sonuc = $cek->fetch(PDO::FETCH_ASSOC);
		
		$link = "yazilar" . "/" . $sonuc["tur"] . "/" .seo($sonuc["dizi_film_ad"]) . "/" . seo($sonuc["baslik"]) . "/" . $kulad . "/" . $sonuc["id"];
		
		?>
			
			<li class="notification-box">
                        <div class="row">
                          <div class="col-lg-3 col-sm-3 col-3 text-center">
                            <img src="<?php echo $info[$kulid]["resim"]; ?>" class="rounded-circle responsive-resim">
                          </div>    
                          <div class="col-lg-8 col-sm-8 col-8">
                            <strong class="text-info"><?php echo ucfirst($kulad); ?></strong>
							  
                            <div>
                              <a href="<?php echo $link; ?>" title="Yazıya git">Yazınızı Beğendi </a><br>
                            </div>
                            <small class="text-warning"><?php echo $tarih; ?> Önce</small>
							 <img src="resimler/tik.png" title="okundu olarak işaretle" style="float: right; cursor: pointer;" width="45" class="okundu" data-id="<?php echo $bid; ?>">
                          </div>    
                        </div>
                      </li>
			
		<?php
		
	}
	
	function takip_bildirim($kulid,$tarih,$yazi_id,$bid,$db){
		
		$info = self::kullanicilari_cek($db);
		
		$kulad = $info[$kulid]["ad"];
		
		$tarih = tarih($tarih);
		
		$link ="http://localhost/php/Bireyselu/forum/kullanici/".$kulad;
		
		?>
			
			<li class="notification-box">
                        <div class="row">
                          <div class="col-lg-3 col-sm-3 col-3 text-center">
                            <img src="<?php echo $info[$kulid]["resim"]; ?>" class="rounded-circle responsive-resim">
                          </div>    
                          <div class="col-lg-8 col-sm-8 col-8">
							  <strong class="text-info"><a href="<?php echo $link; ?>" title="Kullanıcıya git"><?php echo ucfirst($kulad); ?></a></strong>
							  
                            <div>
                              <a href="<?php echo $link; ?>" title="Kullanıcıya git">Sizi takip etmeye başladı </a><br>
                            </div>
                            <small class="text-warning"><?php echo $tarih; ?> Önce</small>
							 <img src="resimler/tik.png" title="okundu olarak işaretle" style="float: right; cursor: pointer;" width="45" class="okundu" data-id="<?php echo $bid; ?>">
                          </div>    
                        </div>
                      </li>
			
		<?php
		
	}
	
	function yorum_bildirim($kulid,$icerik,$tarih,$yazi_id,$bid,$db){
		
		$info = self::kullanicilari_cek($db);
		
		$kulad = $info[$kulid]["ad"];
		
		$tarih = tarih($tarih);
		
		$cek = $db->prepare("select * from yazilar where id=$yazi_id");
		$cek->execute();
		$sonuc = $cek->fetch(PDO::FETCH_ASSOC);
		
		$link = "yazilar" . "/" . $sonuc["tur"] . "/" .seo($sonuc["dizi_film_ad"]) . "/" . seo($sonuc["baslik"]) . "/" . $kulad . "/" . $sonuc["id"];
		
		?>
			
			 <li class="notification-box">
                        <div class="row">
                          <div class="col-lg-3 col-sm-3 col-3 text-center">
                            <img src="<?php echo $info[$kulid]["resim"]; ?>" class="rounded-circle  responsive-resim" width="50" height="50">
                          </div>    
                          <div class="col-lg-8 col-sm-8 col-8">
                            <strong class="text-info"><?php echo ucfirst($kulad); ?></strong>
							  
                            <div>
                              <a href="<?php echo $link; ?>" title="yazıya git">Yazınıza Yorum Yaptı : </a><br>
								<span class="text-muted">"<?php echo $icerik; ?>"</span>
                            </div>
                            <small class="text-warning"><?php echo $tarih; ?> önce</small>
							   <img src="resimler/tik.png" title="okundu olarak işaretle" style="float: right; cursor: pointer;" width="45" class="okundu" data-id="<?php echo $bid; ?>">
                          </div>    
                        </div>
                      </li>
			
		<?php
		
	}
	
	function cevap_bildirim($kulid,$icerik,$tarih,$yazi_id,$bid,$db){
		
		$info = self::kullanicilari_cek($db);
		
		$kulad = $info[$kulid]["ad"];
		
		$tarih = tarih($tarih);
		
		$cek = $db->prepare("select * from yazilar where id=$yazi_id");
		$cek->execute();
		$sonuc = $cek->fetch(PDO::FETCH_ASSOC);
		
		$link = "yazilar" . "/" . $sonuc["tur"] . "/" .seo($sonuc["dizi_film_ad"]) . "/" . seo($sonuc["baslik"]) . "/" . $kulad . "/" . $sonuc["id"];
		
		?>
			
			 <li class="notification-box">
                        <div class="row">
                          <div class="col-lg-3 col-sm-3 col-3 text-center">
                            <img src="<?php echo $info[$kulid]["resim"]; ?>" class="rounded-circle  responsive-resim" width="50" height="50">
                          </div>    
                          <div class="col-lg-8 col-sm-8 col-8">
                            <strong class="text-info"><?php echo ucfirst($kulad); ?></strong>
							  
                            <div>
                              <a href="<?php echo $link; ?>" title="yazıya git">Yorumunuza cevap verdi : </a><br>
								<span class="text-muted">"<?php echo $icerik; ?>"</span>
                            </div>
                            <small class="text-warning"><?php echo $tarih; ?> önce</small>
							   <img src="resimler/tik.png" title="okundu olarak işaretle" style="float: right; cursor: pointer;" width="45" class="okundu" data-id="<?php echo $bid; ?>">
                          </div>    
                        </div>
                      </li>
			
		<?php
		
	}
	
	function bildirim_sil($db){
		
		$id = $_POST['id'];
		
		$dlt = $db->prepare("delete from bildirimler where id=$id");
		$dlt->execute();
		
	}
	
	function tum_bildirim_sil($db){
		
		$id = $_COOKIE[krypto("kulid")];
		
		$dlt = $db->prepare("delete from bildirimler where alan_kullanici=$id");
		$dlt->execute();
		
	}
	
}

class arama_islemleri{
	
	function kullanicilarda_ara($aranan,$db){
		
		$sec = $db->prepare("select * from kullanicilar where kullanici_ad LIKE '$aranan%'");
		$sec->execute();
		
		if(isset($_COOKIE[krypto("kulid")])):
		
		$id = $_COOKIE[krypto("kulid")];
				$al = $db->prepare("select * from takipler where takip_eden_id=?");
				$al->execute(array($id));			
		
				$takipler = array();
					
					while($sonuc = $al->fetch(PDO::FETCH_ASSOC)):
						
						$takipler[] = $sonuc["takip_edilen_id"];
		
					endwhile;
		endif;
			if($sec->rowCount() != 0):
		
				while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
				$link = "kullanici/".$sonuc["kullanici_ad"];
				?>
			
				<li class="list-group-item d-flex justify-content-between align-items-center">
				
					<a href="<?php echo $link; ?>"><img src="resimler/human.jpg" width="30" height="30" class="rounded-circle"></a> <a href="<?php echo $link; ?>" class="text-dark"><?php echo $sonuc["kullanici_ad"]; ?></a>
					<span class="text-info"><?php echo $sonuc["takipçi"]; ?> takipçi</span>
					
					<?php if(!isset($_COOKIE[krypto("kulid")])): ?>
					
					<?php elseif(in_array($sonuc["id"],$takipler)): ?>
					<button class="btn btn-danger btn-sm takip_birak" data-id="<?php echo $sonuc["kullanici_ad"]; ?>">Takibi bırak</button>
					<?php elseif(isset($_COOKIE[krypto("kulid")])) : ?>
					<button class="btn btn-primary btn-sm takip_et" data-id="<?php echo $sonuc["kullanici_ad"]; ?>">Takip et</button>
					<?php endif; ?>
					
					
				</li>
			
				<?php
		
				endwhile;
		
			else:
		
				$sec = $db->prepare("select * from kullanicilar where kullanici_ad LIKE '%$aranan%'");
				$sec->execute();
		
				
				
		
				if($sec->rowCount() != 0):
		
				while($sonuc = $sec->fetch(PDO::FETCH_ASSOC)):
		
				?>
			
				<li class="list-group-item d-flex justify-content-between align-items-center">
				
					<img src="resimler/human.jpg" width="30" height="30" class="rounded-circle"> <span ><?php echo $sonuc["kullanici_ad"]; ?></span>
					<span class="text-info"><?php echo $sonuc["takipçi"]; ?> takipçi</span>
					
					<?php if(!isset($_COOKIE[krypto("kulid")])): ?>
					
					<?php elseif(in_array($sonuc["id"],$takipler)): ?>
					<button class="btn btn-danger btn-sm takip_birak">Takibi bırak</button>
					<?php else: ?>
					<button class="btn btn-primary btn-sm takip_et">Takip et</button>
					<?php endif; ?>
					
				</li>
			
				<?php
		
				endwhile;
		
				else:
		
				?>
			
				<li class="list-group-item d-flex justify-content-between align-items-center">
					
					<p class="text-danger mx-auto">Üzgünüz hiçbir kullanıcı bulunamadı  </p>
					
				</li>
			
				<?php
		
				endif;
		
			endif;
		
		?>
			<script>
			
				$('.takip_et').click(function(){
					
					var ad = $(this).attr("data-id");
					var btn = $(this);
					
						$.post("ana.php?islem=takip_et",{"ad":ad},function(donen){
							
							donen = $.parseJSON(donen);
							
								if(donen.cevap == "true"){
									
								
									
									window.location.reload();
								}
							
						})
					
				})
				
				$('.takip_birak').click(function(){
					
					var ad = $(this).attr("data-id");
					var btn = $(this);
					
					$.post("ana.php?islem=takip_birak",{"ad":ad},function(donen){
						
					donen = $.parseJSON(donen);
							
								if(donen.cevap == "true"){
									
									
									
									window.location.reload();
								
								}
						
						})
								
								})
			
			</script>
			
		<?php
	}
	
}

$kayit_islemleri = new kayit_islemleri;

@$islem = $_GET['islem'];

switch($islem):

case "kayit":

if($_POST):


$kayit_islemleri->db_kullanicilar_cek($db);

$kayit_islemleri->kontrol($_POST['inputUserame'],$_POST['inputEmail'],$_POST['inputPassword'],$_POST['inputConfirmPassword'],$db);

else:

echo "hata var";

endif;

break;

case "onayla":
	
	if(isset($_COOKIE[krypto("kulid")])):
	echo $kayit_islemleri->kod_kontrol(krypto($_POST['kod']),$db);
	else:
	echo "HATA";
	endif;
break;

case "giris":

	if($_POST):

	$islem = new giris_islemleri;

	$islem->kontrol($db);

	endif;

break;

case "dizi_ara":

	if($_POST):

	$islem = new yazi_islemleri;

	$islem->dizi_ara($_POST['aranan'],$db);

	endif;

break;

case "film_ara":

	if($_POST):

	$islem = new yazi_islemleri;

	$islem->film_ara($_POST['aranan'],$db);

	endif;

break;


case "dizi_secildi":

	if($_POST):

	$islem = new yazi_islemleri;

	$islem->dizi_kontrol($_POST['dizi'],$db);

	endif;

break;

case "film_secildi":

	if($_POST):

	$islem = new yazi_islemleri;

	$islem->film_kontrol($_POST['film'],$db);

	endif;

break;

case "session_isim_kontrol":

	if($_POST):

	$islem = new yazi_islemleri;

	$islem->dizi_film_isim_secim_kontrol($db);

	endif;

break;

case "yazi_onizle":

	if($_POST):

	$islem = new yazi_islemleri;

	$islem->yazi_verileri_al();

	endif;

break;

case "onizle_puan_al":

	if($_POST):

	if($_POST['puan'] == 0):

	$puan = 0;

	else:

	$puan = $_POST['puan'];

	endif;

	for($i=1;$i<=$puan;$i++):

	echo '<div class="btn btn-sm btn-warning mt-3 ml-1 d-inline-block yıldız-buton" >&#9733;</div>';

	endfor;

	for($i=1;$i<=5-$puan;$i++):

	echo '<div class="btn btn-sm btn-dark mt-3 ml-1 d-inline-block yıldız-buton" >&#9733;</div>';

	endfor;

	endif;

break;

case "tur_secimi":

	if($_POST):

	$islem = new yazi_islemleri;

	$islem->tur_secim($_POST['tur']);

	endif;


break;

case "yazi_gonder":

	if($_POST):

	$islem = new yazi_islemleri;

	$islem->yazi_gonder($db);

	endif;

break;

case "yazi_begen":

	if($_POST):

	$islem = new yazilar_islemleri;

	$islem->yazi_begen($db);


	endif;

break;

case "yorum_gonder":

	if($_POST):

	$islem = new yazilar_islemleri;

	$islem->yorum_gonder($db);

	endif;

break;

case "yorumlari_getir":

	$islem = new veri_cek_islemleri;

	$islem->yorumlari_al($db);


break;

case "cevap_gonder":

	if($_POST):

	$islem = new yazilar_islemleri;

	$islem->cevap_gonder($db);

	endif;


break;

case "yazilari_yazdir":


	$islem = new veri_cek_islemleri;
					
	$islem->yazilari_cek($db,$_POST['yazi'],$_POST['sayfa'],$_POST['tur'],$_POST['aranan']);
					


break;

case "kullanici_yazilari_cek":

	$islem = new veri_cek_islemleri;

	$islem->kullanicinin_yazilari_cek($db,$_POST['id'],$_POST['sayfa']);

break;

case "cikis_yap":

$id = $_COOKIE[krypto("kulid")];
$upd = $db->prepare("update kullanicilar set durum=0 where id=?");
$upd->execute(array($id));

setcookie(krypto("kulid"),"",time()-10);

header("Refresh:2");

break;

case "resim_degistir":


		$islem = new kullanici_ayarlari;

		$islem->resim_degis($db);


break;

case "isim_degis":

		

		$islem = new kullanici_ayarlari;

		$islem->isim_degis($db);

break;

case "sifre_degis":

	if($_POST):

	$islem = new kullanici_ayarlari;

	$islem->sifre_degis($db);


	endif;


break;

case "email_tekrar_onay":

	if($_POST):

	$islem = new kullanici_ayarlari;

	$islem->kod_kontrol($db);

	endif;

break;

case "email_tekrar_gonder":

	if($_POST):

	$islem = new kullanici_ayarlari;

	$islem->tekrar_mail($db,$_POST['email']);

	endif;

break;

case "takip_et":

	if($_POST):

	$islem = new takip_islemleri;
	$islem->takip_et($db);

	endif;

break;

case "takip_birak":

	if($_POST):

	$islem = new takip_islemleri;
	$islem->takip_birak($db);

	endif;

break;

case "mesaj_kutusu_oku":

$mesajlar = new mesaj_islemleri;
$mesajlar->mesaj_kutusu_oku($db);
	

break;

case "mesajlajan_bilgileri_setle":

$mesajlar = new mesaj_islemleri;

$mesajlar->kullanici_bilgileri_sakla($db);
$mesajlar->mesajlaşan_bilgileri_setle($db,$_POST['id']);

break;

case "mesaj_gonder":

$mesajlar = new mesaj_islemleri;
$mesajlar->mesaj_gonder();

break;

case "mesaj_durum_kontrol":

$mesajlar = new mesaj_islemleri;
$mesajlar->mesaj_durum_kontrol();

break;

case "db_isim_ara":

	if($_POST):

$mesajlar = new mesaj_islemleri;
$mesajlar->db_isim_ara($db);

	endif;

break;

case "mesaj_kutu_olustur":

	if($_POST):
		
	$mesajlar = new mesaj_islemleri;
	$mesajlar->yeni_mesaj_kutusu($db);

	endif;

break;

case "mesaj_kisileri_cek":

	

	$mesajlar = new mesaj_islemleri;

	$mesajlar->kullanici_bilgileri_sakla($db);
	$mesajlar->mesaj_kisileri_cek($db);
	

break;

case "mesaj_kisi_guncelle":

	$islem = new mesaj_bildirim;
	$islem->bildirim_kontrol();

break;

case "bildirim_cikar":

	$islem = new mesaj_bildirim;
	$islem->bildirim_cikar();

break;

case "mesajlarda_ara":

	if($_POST):

	$islem = new mesaj_islemleri;
	$islem->mesajlarda_ara($db);

	endif;

break;

case "tum_mesaj_kisileri_cek":

	$islem = new mesaj_islemleri;
	$islem->kullanici_bilgileri_sakla($db);
	$islem->mesaj_kisileri_cek($db);

break;

case "mesajlarda_ara_mobil":

	$islem = new mesaj_islemleri;
	$islem->mesajlarda_ara_mobil($db);

break;

case "mesaj_kisileri_cek_mobil":

	$islem = new mesaj_islemleri;
	$islem->kullanici_bilgileri_sakla($db);
	$islem->mesaj_kisileri_cek_mobil($db);

break;

case "bildirim_sil":

	if($_POST):

	$islem = new bildirimler;
	$islem->bildirim_sil($db);

	endif;

break;

case "tum_bildirimleri_sil":

	$islem = new bildirimler;
	$islem->tum_bildirim_sil($db);

break;

case "karakter_sayisi_bul":

	$icerik = $_POST['icerik'];
	$icerik = strip_tags($icerik);
	$icerik = str_replace(array("&ccedil;","&nbsp;","&uuml;","&ouml;"),array("a","a","a","a"),$icerik);
	echo strlen($icerik);
	

break;

case "mesaj_kutu_uzaktan_olustur":

$_SESSION['mesaj_kulad'] = $_POST['ad'];


break;

endswitch;






?>