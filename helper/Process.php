<?php

/**
 * Description of Process
 *
 * @author Recep UNCU
 */
class Process {

    private function zipExtract($path, $filename) {
        $zip = new ZipArchive;
        if ($zip->open($path . $filename) === TRUE) {
            $zip->extractTo($path);
            $zip->close();
            unlink($path . $filename);
        } else {
            throw new Exception('Zip file cannot extract!');
        }
    }

    public function getXMLfromZIP($MethodName, $Request) {
		$result = new SimpleXMLElement('<root></root>');
        try {
            $cuser = new ClientUser();
            $cuser->CompanyCode = CU_COMPANYCODE;
            $cuser->UserName = CU_USERNAME;
            $cuser->PassWord = CU_PASSWORD;
            $cuser->WorkYear = CU_WORKYEAR;
            $cuser->MethodName = strval($MethodName);

            $client = new SOAPClient(WSDL_SITEURL);
            $params = ['cuser' => $cuser, 'Request' => strval($Request)];
            $return = $client->Invoke($params);

            file_put_contents(ABSPATH . $return->InvokeResult->filename, $return->InvokeResult->blob_data)
                    or new Exception('Zip file cannot save!', 0);
            $this->zipExtract(ABSPATH, $return->InvokeResult->filename);

            $file = file_get_contents(ABSPATH . str_replace('zip', 'xml', $return->InvokeResult->filename))
                    or new Exception('xml file cannot open!', 0);
			$result = new SimpleXMLElement($file);
			
            unlink(ABSPATH . str_replace('zip', 'xml', $return->InvokeResult->filename));
        } catch (Exception $e) {
            throw new Exception('Exception occured: ' . $e);
        }
        return $result;
    }

}
