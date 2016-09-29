<?php

	/* 
		Configuración de conexión a WebService.

		PARAMS
		---------------


		$ws 	--> Ruta de conexión a WebService
		$db 	--> Base de datos con la que se establecerá la conexión. Debe estar activa en la ruta {$ws}
		$user 	--> Usuario de conexión. Debe tener licencia activa en la base de datos {$db} donde se establecerá 				la conexión.
		$pass 	--> Contraseña de conexión de {$user}


		---------------
	 */
	
	$ws = 'http://itris.no-ip.com:85/ITSWS/ItsCliSvrWS.asmx?WSDL';
	$db = 'AGULARES2';
	$user = "AZARATE";
	$pass = "";