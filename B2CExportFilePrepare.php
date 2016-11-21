<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('default_socket_timeout', 120);

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/helper/ClientUser.php';
require_once dirname(__FILE__) . '/helper/MethodName.php';
require_once dirname(__FILE__) . '/helper/InvokeRequest.php';
require_once dirname(__FILE__) . '/helper/Process.php';

function base64ToImage($base64_string, $output_file) {
	$file = fopen($output_file, "wb");

	$data = explode(',', $base64_string);

	fwrite($file, base64_decode($data[1]));
	fclose($file);

	return $output_file;
}

try {
    $callStartTime = microtime(true);

    $p = new Process();
	$request = new InvokeRequest();
	
	$req1 = $request->get(['root'=>'']);	
    $xml1element = $p->getXMLfromZIP(MethodName::B2CExportFilePrepare, $req1);
        
	$req2 = $request->get(['root'=>['item'=>$xml1element->item]]);
    $xml2element = $p->getXMLfromZIP(MethodName::B2CDownloadZipFile, $req2);
	
    $ns = $xml2element->getNamespaces(true);
    $DokumanPaket = $xml2element->children($ns['dp'])->children();
    $UrunSicilItems = $DokumanPaket->Eleman->ElemanListe->children($ns['ub']);

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset('utf8');

	$sql_uruntanim = [];
	$query_uruntanim = '';
	
	$sql_image = [];
	$query_image = '';
	
	$sql_muadil = [];
	$query_muadil = '';
	
	$sql_skys = [];
	$query_skys = '';
	
	$sql_gsozellik = [];
	$query_gsozellik = '';
    
    foreach ($UrunSicilItems as $UrunSicilItem) {
        $sql_uruntanim[] = sprintf("('%s','%s','%s','%s','%s','%s','%s','%s','%s',"
                . "'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',"
                . "'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s',"
                . "'%s','%s','%s')", trim(strval($UrunSicilItem->UrunTanim->UrKodu)), trim(strval($UrunSicilItem->UrunTanim->UrAdi)), trim(strval($UrunSicilItem->UrunTanim->SicilKodu)), trim(strval($UrunSicilItem->UrunTanim->Crud)), trim(strval($UrunSicilItem->UrunTanim->SicilAdi)), trim(strval($UrunSicilItem->UrunTanim->SicilAdi1)), trim(strval($UrunSicilItem->UrunTanim->SicilAdiy)), trim(strval($UrunSicilItem->UrunTanim->OzelKod1)), trim(strval($UrunSicilItem->UrunTanim->OzelKod2)), trim(strval($UrunSicilItem->UrunTanim->OzelKod3)), trim(strval($UrunSicilItem->UrunTanim->OzelKod4)), trim(strval($UrunSicilItem->UrunTanim->OzelKod5)), trim(strval($UrunSicilItem->UrunTanim->OzelKod6)), trim(strval($UrunSicilItem->UrunTanim->OzelKod7)), trim(strval($UrunSicilItem->UrunTanim->OzelKod8)), trim(strval($UrunSicilItem->UrunTanim->Sinif)), trim(strval($UrunSicilItem->UrunTanim->PerSatFiyat)), trim(strval($UrunSicilItem->UrunTanim->ParaCinsi)), trim(strval($UrunSicilItem->UrunTanim->KamSatFiyat)), trim(strval($UrunSicilItem->UrunTanim->KamParaCinsi)), trim(strval($UrunSicilItem->UrunTanim->KamKodu)), trim(strval($UrunSicilItem->UrunTanim->KamBasTar)), trim(strval($UrunSicilItem->UrunTanim->KamBitTar)), trim(strval($UrunSicilItem->UrunTanim->KdvOrani)), trim(strval($UrunSicilItem->UrunTanim->IndOran)), trim(strval($UrunSicilItem->UrunTanim->OlcuBirimi)), trim(strval($UrunSicilItem->UrunTanim->BarkodKodu)), trim(strval($UrunSicilItem->UrunTanim->PaketMiktari)), trim(strval($UrunSicilItem->UrunTanim->AgirligiKg)), trim(strval($UrunSicilItem->UrunTanim->HacmiM3)), trim(strval($UrunSicilItem->UrunTanim->StokMevcudu)), trim(strval($UrunSicilItem->UrunTanim->TeminSuresi)), trim(strval($UrunSicilItem->UrunTanim->KayitTarihi)), trim(strval($UrunSicilItem->UrunTanim->Notlar)), trim(strval($UrunSicilItem->UrunTanim->KategoriKodu)), trim(strval($UrunSicilItem->UrunTanim->MuadilKodu)));
				
		//Images
		foreach((array)$UrunSicilItem->Images as $Image){
			if(!empty(trim(strval($Image->IFilename)))){
				$IPath = base64ToImage($Image->IBase64Value, $Image->IFilename);
				$sql_image[] = sprintf("('%s','%s','%s','%s')", 
					trim(strval($UrunSicilItem->UrunTanim->SicilKodu)), 
					trim(strval($Image->IFilename)), 
					trim(strval($Image->IBase64Value)), 
					trim(strval($IPath)));
			}
		}				
		
		//Muadil
		foreach((array)$UrunSicilItem->Muadils as $Muadil){
			if(!empty(trim(strval($Muadil->MUreticiKodu)))){
				$sql_muadil[] = sprintf("('%s','%s','%s')", 
					trim(strval($UrunSicilItem->UrunTanim->SicilKodu)), 
					trim(strval($Muadil->MUreticiAdi)), 
					trim(strval($Muadil->MUreticiKodu)));
			}
		}

		//Gsozellik
		foreach((array)$UrunSicilItem->Gsozelliks as $Gsozellik){			 
			if(!empty(trim(strval($Gsozellik->Ozkod)))){
				$sql_gsozellik[] = sprintf("('%s','%s','%s','%s','%s')", 
					trim(strval($UrunSicilItem->UrunTanim->SicilKodu)), 
					trim(strval($Gsozellik->Ozkod)), 
					trim(strval($Gsozellik->Ozadi)), 
					trim(strval($Gsozellik->Ozdeger)), 
					trim(strval($Gsozellik->IBase64Value)));
			}
		}		
		
		//Skys
		foreach((array)$UrunSicilItem->Skys as $sky){
			if(!empty(trim(strval($sky->SkyMarkaAdi)))){
				$sql_skys[] = sprintf("('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", 
					trim(strval($UrunSicilItem->UrunTanim->SicilKodu)), 
					trim(strval($sky->SkyMarkaAdi)), 
					trim(strval($sky->SkyMarkaModelAdi)), 
					trim(strval($sky->SkyOemNo)), 
					trim(strval($sky->SkyOzelKod4)), 
					trim(strval($sky->SkyOzelKod5)), 
					trim(strval($sky->SkyOzelKod6)), 
					trim(strval($sky->SkyOzelKod7)), 
					trim(strval($sky->SkyOzelKod8)), 
					trim(strval($sky->SkyOzelKod9)), 
					trim(strval($sky->SkyOzelKod10)), 
					trim(strval($sky->SkyOtoak)), 
					trim(strval($sky->SkyAktifmi)));
			}
		}
		
    } //$UrunSicilItems end;	
		
    $sql_uruntanim_ust = 'REPLACE INTO uruntanim (UrKodu,UrAdi,SicilKodu,Crud,SicilAdi,'
            . 'SicilAdi1,SicilAdiy,OzelKod1,OzelKod2,OzelKod3,OzelKod4,OzelKod5,'
            . 'OzelKod6,OzelKod7,OzelKod8,Sinif,PerSatFiyat,ParaCinsi,KamSatFiyat,'
            . 'KamParaCinsi,KamKodu,KamBasTar,KamBitTar,KdvOrani,IndOran,OlcuBirimi,'
            . 'BarkodKodu,PaketMiktari,AgirligiKg,HacmiM3,StokMevcudu,TeminSuresi,'
            . 'KayitTarihi,Notlar,KategoriKodu,MuadilKodu) VALUES ';		
	$query_uruntanim = $sql_uruntanim_ust . implode(',', $sql_uruntanim) . ';';
	
	$sql_image_ust = 'REPLACE INTO image (SicilKodu,IFilename,IBase64Value,IPath) VALUES ';		
	$query_image .= !empty($sql_image) ? ($sql_image_ust . implode(',', $sql_image) . ';') : '';	
	
	$sql_gsozellik_ust = 'REPLACE INTO gsozellik (SicilKodu,Ozkod,Ozadi,Ozdeger,IBase64Value) VALUES ';
	$query_gsozellik .= !empty($sql_gsozellik) ? ($sql_gsozellik_ust . implode(',', $sql_gsozellik) . ';') : '';	
	
	$sql_muadil_ust = 'REPLACE INTO muadil (SicilKodu,MUreticiAdi,MUreticiKodu) VALUES ';		
	$query_muadil .= !empty($sql_muadil) ? ($sql_muadil_ust . implode(',', $sql_muadil) . ';') : '';	
	
	$sql_skys_ust = 'REPLACE INTO sky ( SicilKodu
										,SkyMarkaAdi
										,SkyMarkaModelAdi
										,SkyOemNo
										,SkyOzelKod4
										,SkyOzelKod5
										,SkyOzelKod6
										,SkyOzelKod7
										,SkyOzelKod8
										,SkyOzelKod9
										,SkyOzelKod10
										,SkyOtoak
										,SkyAktifmi) VALUES ';
	$query_skys .= !empty($sql_skys) ? ($sql_skys_ust . implode(',', $sql_skys) . ';') : '';	
		
	if(!empty($query_uruntanim))
		$conn->query($query_uruntanim);
	if(!empty($query_image))
		$conn->query($query_image);
	if(!empty($query_gsozellik))
		$conn->query($query_gsozellik);
	if(!empty($query_muadil))
		$conn->query($query_muadil);
	if(!empty($query_skys))
		$conn->query($query_skys);
	$conn->close();

    $callEndTime = microtime(true);
    $callTime = $callEndTime - $callStartTime;
    echo 'Request end time ', sprintf('%.4f', $callTime), ' seconds', PHP_EOL;
} catch (Exception $e) {
    echo "Exception occured: " . $e;
}
