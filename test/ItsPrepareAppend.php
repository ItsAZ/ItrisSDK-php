<?php
	
	ini_set('max_execution_time', 0);
	require_once('../ItrisSDK.php');
	require_once('../ConfigItrisWS.php');

	// Instancia la clase Itris importada en {ItrisSDK}
	$Itris = new Itris;

	// Creación del cliente SOAP con $ws. Instancia $soapClient por referencia en ItrisSDK.
	$client = $Itris->ItsCreateClient( $ws , $soapClient );
	
	if($client['error']){
		echo $client['message'];
		break;
	};

	$do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );

	// Todos los métodos devuelven un objeto con el parámetro BOOL {'error'}.
	if(!$do_login['error']) {

		$UserSession = $do_login['UserSession'];

		$prepare_append = $Itris->ItsPrepareAppend( $soapClient , $UserSession , 'ERP_COM_VEN' );

		echo json_encode($prepare_append);

		if(!$prepare_append['error']) {
			echo $prepare_append['DataSession'];
		}

	}