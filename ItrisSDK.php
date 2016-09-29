<?php
	require_once('lib/nusoap.php');
	require_once('lib/Serializer.php');

	/***
		ToDo:
			Migrate from nusoap.php to native SOAP extension ()
	***/

	class Itris {
		// Create SOAP client with WsItris.
		public function ItsCreateClient($ws, &$oSoapClient) {
			try {
				$oSoapClient = new nusoap_client ( $ws, true );	
			} catch(Exception $e) {
				$response = array(
					'status' => 500,
					'message' => 'Error en el servidor: ' . $e->getMessage(),
					'error' => true
				);

				return $response;
			}
			
			$sError = $oSoapClient -> getError();
			if($sError) {
				$response = array(
					'status' => 400,
					'message' => $sError,
					'error' => true
				);
			} else {
				$response = array(
					'status' => 200,
					'message' => 'Conexión establecida',
					'error' => false,
					'SoapClient' => $oSoapClient
				);
			}

			return $response;
		}
		// End CreateClient

		// ItsLogin / Do login with database (db), username and pasword.
		public function ItsLogin( $oSoapClient, $db, $username, $password, &$sUserSession ) {
			$itsParameters = array(
				'DBName' => $db,
				'UserName' => $username,
				'UserPwd' => $password,
				'LicType' => 'WS',
				'UserSession' => ''
			);

			try {

				$LoginResponse = $oSoapClient -> call( 'ItsLogin' , $itsParameters );
				
			} catch(Exception $e) {
				$response = array(
					'status' => 500,
					'message' => 'Error en el servidor: ' . $e->getMessage(),
					'error' => true
				);

				return $response;
			}


			$DataResult = $LoginResponse['ItsLoginResult'];
			$UserSession = $LoginResponse['UserSession'];

			if($DataResult == 0) {
				$response = array(
					'status' => 201,
					'message' => 'Inicio de sesión exitoso',
					'error' => false,
					'UserSession' => $UserSession
				);
			} else if ($DataResult == 1) {
				$response = $this -> ItsGetLastError( $oSoapClient , $UserSession );
			}

			return $response;
 		}
 		// End login

 		// Get last error of the current UserSession
 		public function ItsGetLastError ( $oSoapClient , $sUserSession ) {
 			$GetErrorParams = array(
 				'UserSession' => $sUserSession
 			);

 			try {

 				$ErrorResult = $oSoapClient -> call ( 'ItsGetLastError' , $GetErrorParams );
 				
 			} catch(Exception $e) {
				$response = array(
					'status' => 500,
					'message' => 'Error en el servidor: ' . $e->getMessage(),
					'error' => true
				);

				return $response;
			}


 			$LastErrorResult = $ErrorResult['ItsGetLastErrorResult'];

 			if($LastErrorResult == 0) {
 				$response = array(
 					'status' => 403,
 					'message' => $ErrorResult['Error'],
 					'error' => true,
 					'UserSession' => $sUserSession
 				);
 			} else {
 				$response = array(
 					'status' => 200,
 					'message' => 'No se encontró ningún error con el UserSession ' . $sUserSession,
 					'error' => false,
 					'UserSession' => $sUserSession
 				);
 			}
 			return $response;
 		}
 		// End LastError

 		// ItsGetData of a particular ITRIS Class
 		public function ItsGetData( $oSoapClient , $sUserSession , $ItsClass , $RecordCount = 10, $SQLFilter = '', $SQLSort = '', &$XMLData = ''){

 			$aParamsItsGetData = array(
 				'UserSession' => $sUserSession,
 				'ItsClassName' => $ItsClass,
 				'RecordCount' => $RecordCount,
 				'SQLFilter' => $SQLFilter,
 				'SQLSort' => $SQLSort
 			);

 			try {

 				$GetDataResult = $oSoapClient -> call( 'ItsGetData', $aParamsItsGetData );	

 			} catch (Exception $e) {
 				$response = array(
					'status' => 500,
					'message' => 'Error en el servidor: ' . $e->getMessage(),
					'error' => true
				);

				return $response;
 			}

 			

 			$DataResult = $GetDataResult['ItsGetDataResult'];

 			if($DataResult == 1){
 				$response = $this -> ItsGetLastError( $oSoapClient , $sUserSession );
 			} else if ($DataResult == 0) {
 				$XMLData = $GetDataResult['XMLData'];

 				$JSONData = $this -> XML_to_JSON($XMLData);

 				$response = array(
 					'status' => 200,
 					'message' => 'Se encontraron ' . $RecordCount . ' registros solicitados',
 					'error' => false,
 					'data' => $JSONData
 				);
 			}
 			return $response;
 		}
 		// End ItsGetData

 		// ItsPrepareAppend function to init the insert
 		public function ItsPrepareAppend( $oSoapClient , $sUserSession , $ItsClass , $XMLData = '' , &$DataSession = '') {

 			$aPrepareAppendParams = array(
 				'UserSession' => $sUserSession,
 				'ItsClassName' => $ItsClass
 			);

 			try {

	 			$ResultPrepareAppend = $oSoapClient -> call ( 'ItsPrepareAppend' , $aPrepareAppendParams );

 			} catch(Exception $e) {
				$response = array(
					'status' => 500,
					'message' => 'Error en el servidor: ' . $e->getMessage(),
					'error' => true
				);

				return $response;
			}


 			$DataResult = $ResultPrepareAppend['ItsPrepareAppendResult'];

 			if($DataResult == 1) {
 				$response = $this -> ItsGetLastError( $oSoapClient , $sUserSession );
 			} else if ($DataResult == 0) {

 				if(isset($ResultPrepareAppend['XMLData'])) {
 					$XMLData = $ResultPrepareAppend['XMLData'];

	 				$JSONData = $this -> XML_to_JSON($XMLData);

	 				$DataSession = $ResultPrepareAppend['DataSession'];

	 				$response = array(
	 					'status' => 200,
	 					'message' => 'Se inició correctamente la inserción del registro',
	 					'error' => false,
	 					'DataSession' => $DataSession,
	 					'data' => $JSONData
	 				);	
 				} else {
 					$response = array(
	 					'status' => 400,
	 					'message' => 'La clase no es agregable',
	 					'error' => true
	 				);	
 				}

 				
 			}

 			return $response;

 		}
 		// End ItsPrepareAppend

 		// ItsSetData
 		public function ItsSetData ( $oSoapClient , $sUserSession , $sDataSession , $JSONData , &$oXMLData ) {

 			$XMLData = $this -> JSON_to_XML( $JSONData );

 			$SetDataParams = array(
 				'UserSession' => $sUserSession,
 				'DataSession' => $sDataSession,
 				'iXMLData' => $XMLData
 			);

 			try {

 				$ResultSetData = $oSoapClient->call( 'ItsSetData' , $SetDataParams );
 				
 			} catch(Exception $e) {
				$response = array(
					'status' => 500,
					'message' => 'Error en el servidor: ' . $e->getMessage(),
					'error' => true
				);

				return $response;
			}


 			$DataResult = $ResultSetData['ItsSetDataResult'];

 			if($DataResult == 0) {
 				$oXMLData = $ResultSetData['oXMLData'];

 				$ResponseData = $this -> XML_to_JSON($oXMLData);

 				$response = array(
 					'status' => 302,
 					'message' => 'La inserción de datos se ha realizado exitosamente',
 					'DataSession' => $sUserSession,
 					'UserSession' => $sDataSession,
 					'error' => false,
 					'data' => $ResponseData
 				);
 			} else {
 				$response = $this -> ItsGetLastError( $oSoapClient , $sUserSession );
 			}

 			return $response;
 		}
 		// End ItsSetData

 		// ItsPost
 		public function ItsPost( $oSoapClient , $sUserSession , $sDataSession ) {
 			$aPostParams = array(
 				'UserSession' => $sUserSession,
 				'DataSession' => $sDataSession
 			);

 			try {

 				$PostResult = $oSoapClient->call( 'ItsPost' , $aPostParams );
 				
 			} catch(Exception $e) {
				$response = array(
					'status' => 500,
					'message' => 'Error en el servidor: ' . $e->getMessage(),
					'error' => true
				);

				return $response;
			}

 			$DataResult = $PostResult['ItsPostResult'];

 			$XMLData = $PostResult['XMLData'];

 			$ResponseData = $this -> XML_to_JSON($XMLData);

 			if($DataResult == 0) {
 				$response = array(
 					'status' => 200,
 					'message' => 'Comprobante creado exitosamente',
 					'data' => $ResponseData,
 					'error' => false
 				);
 			} else {
 				$response = $this -> ItsGetLastError( $oSoapClient , $sUserSession );
 			}

 			return $response;

 		}
 		// End ItsPost



 		// ItsLogout / Finish session
 		public function ItsLogout ( $oSoapClient , $sUserSession ) {
 			$aLogoutParams = array(
 				'UserSession' => $sUserSession
 			);

 			try {

 				$ResultLogout = $oSoapClient -> call ( 'ItsLogout' , $aLogoutParams );

 			} catch(Exception $e) {

				$response = array(
					'status' => 500,
					'message' => 'Error en el servidor: ' . $e->getMessage(),
					'error' => true
				);

				return $response;
			} 

 			

 			$DataResult = $ResultLogout['ItsLogoutResult'];

 			if($DataResult == 0) {
 				$response = array(
 					'status' => 200,
 					'message' => 'Finalizó la sesión ' . $sUserSession . ' correctamente',
 					'error' => false
 				);
 			} else {
 				$response = $this -> ItsGetLastError( $oSoapClient , $sUserSession );
 			}

 			return $response;
 		}
 		// Endlogout

 		// ItsDelete params -> ( soapClient , UserSession , ItsClass , ItsIDOfClass )
 		public function ItsDelete ( $oSoapClient , $sUserSession , $ItsClass , $ItsID ) {
 			$aDeleteParams = array(
 				'UserSession' => $sUserSession,
 				'ItsClassName' => $ItsClass,
 				'IDRecord' => $ItsID
 			);

 			try {

 				$DeleteResult = $oSoapClient -> call( 'ItsDelete' , $aDeleteParams );
 				
 			} catch(Exception $e) {
				$response = array(
					'status' => 500,
					'message' => 'Error en el servidor: ' . $e->getMessage(),
					'error' => true
				);

				return $response;
			}


 			$DeleteResult = $DeleteResult['ItsDeleteResult'];

 			if( $DeleteResult == 0 ){
 				
 				$response = array(
 					'status' => 302,
 					'message' => 'Se eliminó el registro correctamente',
 					'error' => false
 				);
 				
 			} else {
 				$response = $this -> ItsGetLastError( $oSoapClient , $sUserSession );
 			}

 			return $response;
 		}
 		// End ItsDelete

 		// XML to JSON parser
 		function XML_to_JSON($XMLData, &$JSONData = ''){

 			$JSONData = simplexml_load_string($XMLData);

 			return $JSONData;

 		}
 		// End XML to JSON

 		// JSON to XML parser
 		function JSON_to_XML( $JSONData , &$XMLData = '' ) {

 			$json = $JSONData;

 			$xml = new DOMDocument();

			$xml_datapacket = $xml->createElement('DATAPACKET');
			$xml_metadata = $xml->createElement('METADATA');
			$xml_fields = $xml->createElement('FIELDS');

			$json_fields = json_decode(json_encode($json->METADATA->FIELDS[0]->FIELD), true);

			foreach($json_fields as $key => $value){

				$xml_field = $xml->createElement('FIELD');

				if(isset($json_fields[$key]['@attributes'])){

				foreach($json_fields[$key]['@attributes'] as $key_field => $key_value){
					if($key_field == 'fieldtype' && $key_value == 'nested') {

						$domAttribute = $xml->createAttribute($key_field);
						$domAttribute->value = $key_value;
						$xml_field->appendChild($domAttribute);

						$nested_fields = $xml->createElement('FIELDS');
						$json_nested_fields = $json_fields[$key];

						foreach($json_nested_fields['FIELDS']['FIELD'] as $nested_key => $nested_value){

							$nested_field_row = $xml->createElement('FIELD');

							$xml_row_nested_field = $json_nested_fields['FIELDS']['FIELD'][$nested_key]['@attributes'];
							foreach($xml_row_nested_field as $attributeName => $attributeValue){
								$domAttribute = $xml->createAttribute($attributeName);
								$domAttribute->value = $attributeValue;
								$nested_field_row->appendChild($domAttribute);
							}

							$nested_fields->appendChild($nested_field_row);
						}

						$xml_field->appendChild($nested_fields);

					} else {
						$domAttribute = $xml->createAttribute($key_field);
						$domAttribute->value = $key_value;
						$xml_field->appendChild($domAttribute);
					}
				}

				$xml_fields->appendChild($xml_field);
				} else {
					foreach($json_fields[$key] as $key_field => $key_value){
						$domAttribute = $xml->createAttribute($key_field);
						$domAttribute->value = $key_value;
						$xml_field->appendChild($domAttribute);
					}
				}

			}

			$xml_metadata->appendChild($xml_fields);

			$json_rowdata = json_decode(json_encode($json->ROWDATA->ROW), true);

			$xml_rowdata = $xml->createElement('ROWDATA');

			$xml_row = $xml->createElement('ROW');

			foreach($json_rowdata as $key => $value){
				if($key == '@attributes'){
					foreach($json_rowdata[$key] as $key_row => $value_row){
						$domAttribute = $xml->createAttribute($key_row);
						$domAttribute->value = $value_row;
						$xml_row->appendChild($domAttribute);
					}
				} else {
					$row_detail = $xml->createElement($key);
					
					foreach($json_rowdata[$key] as $key_row => $value_row){
						if(!isset($json_rowdata[$key][$key_row][1])){
							$nested_row_detail = $xml->createElement($key_row);
							foreach ($json_rowdata[$key][$key_row] as $index => $row) {
								
								foreach ($json_rowdata[$key][$key_row] as $key_nested_detail => $value_nested_detail){
									$domAttribute = $xml->createAttribute($key_nested_detail);
									$domAttribute->value = $value_nested_detail;
									$nested_row_detail->appendChild($domAttribute);
								}
								
							}

							$row_detail->appendChild($nested_row_detail);
						} else {
							foreach($json_rowdata[$key][$key_row] as $index => $row){
								$nested_row_detail = $xml->createElement($key_row);
								foreach($json_rowdata[$key][$key_row][$index] as $key_nested_detail => $value_nested_detail){
									$domAttribute = $xml->createAttribute($key_nested_detail);
									$domAttribute->value = $value_nested_detail;
									$nested_row_detail->appendChild($domAttribute);	
								}
								$row_detail->appendChild($nested_row_detail);
							}
						}
					}

					$xml_row->appendChild($row_detail);
				}
			}

			$xml_rowdata->appendChild($xml_row);

			$xml_datapacket->appendChild($xml_metadata);
			$xml_datapacket->appendChild($xml_rowdata);
			$xml->appendChild($xml_datapacket);

			return $xml->saveXML();
 		}
 		// End JSON to XML

	}