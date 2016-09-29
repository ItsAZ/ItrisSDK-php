<?php
	
	// Sin máximo tiempo de ejecución del thread PHP
	ini_set('max_execution_time', 0);

	/*-----------------------------------------

		require_once ItrisSDK.
		Importa al fichero la clase {Itris}.

	-------------------------------------------*/
	require_once('../ItrisSDK.php');

	/*-----------------------------------------

		require_once ConfigItrisWS.php.
		Importa parámetros de configuración para la conexión al WebService
		{
			$ws,
			$db,
			$user,
			$pass
		}

	-------------------------------------------*/
	require_once('../ConfigItrisWS.php');

	// Instancia la clase Itris importada en {ItrisSDK}
	$Itris = new Itris;

	// Creación del cliente SOAP con $ws. Instancia $soapClient por referencia en ItrisSDK.
	$client = $Itris->ItsCreateClient( $ws , $soapClient );

	if($client['error']){
		echo $client['message'];
		break;
	};

	/*--------------------------------------------------

		Ejecuta el método ItsLogin instanciado en la clase Itris.
		Recibe como parámetros obligatorios:
		{
			$soapClient: Instanciado por referencia en ItrisSDK,
			$db,
			$user,
			$pass
		}

	----------------------------------------------------*/
	$do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );

	if(!$do_login['error']) {

		$UserSession = $do_login['UserSession'];
		echo ($do_login['message'] . ': ' . $UserSession . '<br>');

		$do_logout = $Itris->ItsLogout( $soapClient , $UserSession );

		if(!$do_logout['error']) {
			echo ($do_logout['message'] . '<br>');
		} else {
			echo ($do_logout['message'] . '<br>');
		}

	} else if ($do_login['error']) {
		echo $do_login['message'] . '<br>';
	}
	

