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

	// Todos los métodos devuelven un objeto con el parámetro BOOL {'error'}.
	if(!$do_login['error']) {

		$UserSession = $do_login['UserSession'];
		echo ($do_login['message'] . ': ' . $UserSession . '<br>');

		/*--------------------------------------------------

		Ejecuta el método ItsGetData instanciado en la clase Itris.
		Recibe como parámetros obligatorios:
		{
			$soapClient: Instanciado por referencia en ItrisSDK,
			$UserSession: Token de sesión del usuario,
			$ItsClass: Clase de Itris a ejecutar ItsGetData
		}

		Recibe como parámetros opcionales: 
		{
			$RecordCount: Cantidad de registros que devolverá el método. Debe indicarse '-1' si se desean obtener 				  todos los registros. Default: 10,
			$SQLFilter: Filtro SQL a aplicar a la consulta en formato ' ID = {ID} '. Default: '',
			$SQLSort: Equivalente al comando SQL ORDER BY en formato ' ID ASC '. Default: ''
		}

		----------------------------------------------------*/
		$get_data = $Itris->ItsGetData( $soapClient ,  $UserSession , 'ERP_COM_VEN' );

		if(!$get_data['error']) {
			echo $get_data['message'];

			foreach($get_data['data']->ROWDATA->ROW as $key => $row) {
				echo json_encode($row) . '<br><br>';
			}

			$do_logout = $Itris->ItsLogout( $soapClient , $UserSession );

			if(!$do_logout['error']){
				echo $do_logout['message'];
			} else {
				echo $do_logout['message'];
			}

		} else {
			echo $get_data['message'];
		}

	} else if ($do_login['error']) {
		echo ($do_login['message'] . '<br>');
	}