# Módulo Itris SDK PHP - Integración con Itris WebService

El módulo provee métodos que agregan una capa de abstracción al consumo del WebService de Itris Software con PHP.

Las transacciones que originalmente intercambian datos en XML son parseadas en JSON / Arrays.

Roadmap: Migrar nusoap.php a extensión nativa SOAP PHP

* [Instalación](#install)
* [Configuración](#config)
* [Lógica de consumo](#consume-logic)
* [Métodos](#methods)
  * [ItsCreateClient](#ItsCreateClient)
  * [ItsLogin](#ItsLogin)
  * [ItsLogout](#ItsLogout)
  * [ItsGetLastError](#ItsGetLastError)
  * [ItsGetData](#ItsGetData)
  * [ItsPrepareAppend](#ItsPrepareAppend)
  * [ItsSetData](#ItsSetData)
  * [ItsPost](#ItsPost)
  * [ItsDelete](#ItsDelete)

<a name="install"></a>
## Instalación

### Línea de comandos
```git
$ mkdir ItrisSDK
$ cd ItrisSDK
$ git clone https://github.com/ItsAZ/ItrisSDK-php.git
```
### Descarga

1. Clone/descargue el repositorio
2. Copiar `ItrisSDK.php` y la carpeta `lib` en la carpeta de librerías de su proyecto.

<a name="config"></a>
## Configuración
### Init
Las primeras dos líneas del script que transaccione con el WebService deben ser
```php
    <?php
    // Recomendado para evitar alcanzar el 'max_execution_time' en caso de lenta respuesta del WebService
    ini_set('max_execution_time', 0); 
    require_once('./ItrisSDK.php');
```
Una vez incluído el archivo `ItrisSDK.php` deben incluirse las variables de conexión
### Variables de conexión (dos opciones)
##### Fichero `ConfigItrisWS.php`

**Método recomendado si el usuario que transacciona con el WebService es siempre el mismo.**

Crear archivo de configuración general de conexión al WebService. Deben definirse 4 variables globales:

```php
    /* ConfigItrisWS.php */
    <?php
    
    $ws     = 'http://hostwebservice?WSDL';
    $db     = '{Base de datos}';
    $user   = '{Nombre de usuario con licencia WS}';
    $pass   = '{Contraseña de usuario con licencia WS}';
```
Luego incluir el fichero de configuración debajo de `require_once('./ItrisSDK.php');`. Es decir,
```php
    <?php
    // Recomendado para evitar alcanzar el 'max_execution_time' en caso de lenta respuesta del WebService
    ini_set('max_execution_time', 0); 
    require_once('./ItrisSDK.php');
    require_once('./ConfigItrisWS.php');
```
#### Definir las variables en tiempo de ejecución
**Método recomendado si son múltiples usuarios que transaccionan con el WebService.**
Si el usuario que transacciona no es siempre el mismo, las variables podrían definirse en tiempo de ejecución (si la petición proviene de un método HTTP POST, por ejemplo):
```php
    <?php
    // Recomendado para evitar alcanzar el 'max_execution_time' en caso de lenta respuesta del WebService
    ini_set('max_execution_time', 0); 
    require_once('./ItrisSDK.php');
    require_once('./ConfigItrisWS.php');
    $ws     = $_POST['wsurl'];
    $db     = $_POST['database'];
    $user   = $_POST['username'];
    $pass   = $_POST['password'];
```

<a name="consume-logic"></a>
## Lógica de consumo
Para iniciar cualquier petición, debe crearse el **cliente SOAP** para establecer la conexión con el WebService. Una vez inicializado, debe enviarse con cada petición. Esto es así ya que podría instanciarse más de un cliente y establecer conexión con más de un WebService de Itris al mismo tiempo.

El método `ItsCreateClient` recibe dos parámetros obligatorios: **$ws**  *(URL de configuración)* y **$soapClient** *(Variable pasada como referencia interna al SDK)*. 
```php
    <?php
    ini_set('max_execution_time', 0);
    require_once('../ItrisSDK.php');
    require_once('../ConfigItrisWS.php');
    
    // Instancia la clase Itris importada en {ItrisSDK}
    $Itris = new Itris;
    $client = $Itris->ItsCreateClient( $ws , $soapClient );
    if($client['error']){
        echo $client['message'];
        break;
    };
```
Una vez establecida la conexión, debe loggearse al usuario que transaccione con el método `ItsLogin`  y obtener su token de sesión **UserSession**. Este token de sesión debe ser enviado junto con el **soapClient** en cada petición.

```php
    <?php
    ini_set('max_execution_time', 0);
    require_once('../ItrisSDK.php');
    require_once('../ConfigItrisWS.php');
    
    // Instancia la clase Itris importada en {ItrisSDK}
    $Itris = new Itris;
    $client = $Itris->ItsCreateClient( $ws , $soapClient );
    
    if($client['error']){
        break;
    };
    
    // Donde $db, $user y $pass son las variables de configuración.
    $do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );
    
    if($do_login['error']){
        // El login falló. El error queda guardado en $do_login['message']
    } else {
        $UserSession = $do_login['UserSession'];
        // Lógica del script
    }
```
<a name="methods"></a>
## Métodos

<a name="ItsCreateClient"></a>

### ItsCreateClient
El método que inicializa la conexión con el WebService y crea el **soapClient**.

#### Definición de la función

| Parámetros        | Descripción           | Obligatorio    | Valor por defecto |
| :-------------:   |:-------------:        | :------------: | :------------:    |
| $ws               | URL absoluta del WebService | Si       | null              |
| &soapClient       | Parámetro pasado por referencia para instanciar el cliente en la variable      | No | null |
##### Sintaxis

```php
    <?php
    ini_set('max_execution_time', 0);
    require_once('../ItrisSDK.php');
    require_once('../ConfigItrisWS.php');
    
    // Instancia la clase Itris importada en {ItrisSDK}
    $Itris = new Itris;
    $client = $Itris->ItsCreateClient( $ws , $soapClient );
    
    if($client['error']){
        break;
    };
```
#### Response
   * Éxito en la petición
     * **status**  [Integer]  = 200
     * **message** [String] = 'Conexión establecida'
     * **error** [Boolean] =  false
     * **SoapClient** [Objeto] =  Instancia del cliente creado
    * Error en la petición
      * **status**  [Integer]  = 400
      * **message** [String] = Mensaje de error cliente
      * **error** [Boolean] =  true
    * Excepción en la conexión
      * **status**  [Integer]  = 500
      * **message** [String] = 'Error en el servidor: ' . $exception->getMessage()
      * **error** [Boolean] =  true

<a name="ItsLogin"></a>

### ItsLogin
El método que inicia la sesión del usuario con el WebService y crea el **UserSession** a ser utilizado en la transacciones.

#### Definición de la función

| Parámetros        | Descripción           | Obligatorio    | Valor por defecto |
| :-------------:   |:-------------:        | :------------: | :------------:    |
| $oSoapClient               | Referencia al cliente soap creado en ItsCreateClient | Si       | null              |
| $db       | Base de datos con la que establecer conexión       | Si | null |
| $username       | Nombre de usuario con licencia para iniciar sesión      | Si | null |
| $password       | Contraseña del usuario      | Si | null |
| &sUserSession   | UserSession pasado por referencia al objeto **soapClient**      | Si | null |
##### Sintaxis

```php
    $do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );
    
    if(!$do_login['error']) {
        $UserSession = $do_login['UserSession'];
        echo $do_login['message'];
   } else {
        echo $do_login['message'];
   }
```
#### Response
   * Éxito en la petición
     * **status**  [Integer]  = 201
     * **message** [String] = 'Inicio de sesión exitoso'
     * **error** [Boolean] =  false
     * **UserSession** [String] =  Token de sesión del usuario (vence con 15 minutos de inactividad)
    * Error en la petición
      * **status**  [Integer]  = 403
      * **message** [String] = Resultado de [ItsGetLastError](#ItsGetLastError)
      * **error** [Boolean] =  true
      * **UserSession** [String] =  Token de sesión del usuario
    * Excepción en la conexión
      * **status**  [Integer]  = 500
      * **message** [String] = 'Error en el servidor: ' . $exception->getMessage()
      * **error** [Boolean] =  true

<a name="ItsLogout"></a>

### ItsLogout
El método que termina la sesión activa de un usuario y destruye el **UserSession**. Para mejorar la performance, la sesión debe ser eliminada una vez que terminan las transacciones con el WebService.

#### Definición de la función

| Parámetros        | Descripción           | Obligatorio    | Valor por defecto |
| :-------------:   |:-------------:        | :------------: | :------------:    |
| $oSoapClient      | Referencia al cliente soap creado en ItsCreateClient | Si       | null              |
| $UserSession    | Token de sesión a terminar  | Si | null |
##### Sintaxis

```php
    $do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );
    if(!$do_login['error']) {
        $UserSession = $do_login['UserSession'];
        $do_logout = $Itris->ItsLogout( $soapClient , $UserSession );
        if(!$do_logout['error']) {
            echo 'Sesión finalizada con éxito';
        } else {
            echo $do_logout['message'];
        }
    }
```
#### Response
   * Éxito en la petición
     * **status**  [Integer]  = 200
     * **message** [String] = 'Finalizó la sesión ' . $sUserSession . ' correctamente'
     * **error** [Boolean] =  false
    * Error en la petición
      * **status**  [Integer]  = 403
      * **message** [String] = Resultado de [ItsGetLastError](#ItsGetLastError)
      * **error** [Boolean] =  true
      * **UserSession** [String] =  Token de sesión del usuario
    * Excepción en la conexión
      * **status**  [Integer]  = 500
      * **message** [String] = 'Error en el servidor: ' . $exception->getMessage()
      * **error** [Boolean] =  true

<a name="ItsGetLastError"></a>

### ItsGetLastError
Devuelve el último error asociado a un **UserSession**. Si, por ejemplo, se hace un [ItsSetData](#ItsSetData) y falla la inserción de datos por una validación, este método muestra el mensaje de error asociado a esa validación.

Utilizando este SDK no es necesario ejecutar este método, ya que por definición todas las funciones lo ejecutan y devuelven su resultado en caso de haber habido un error. Sin embargo, es importante conocer su existencia.

Considere que la respuesta del método ItsGetLastError tiene un atributo `['error']`. Este atributo response a que el método se haya ejecutado con éxito o no. No hace referencia a un error en el cliente Itris.

NOTA: Este método **no devuelve excepciones del servidor ni fallos de conexión**, solo devuelve errores en el cliente Itris.

#### Definición de la función

| Parámetros        | Descripción           | Obligatorio    | Valor por defecto |
| :-------------:   |:-------------:        | :------------: | :------------:    |
| $oSoapClient      | Referencia al cliente soap creado en ItsCreateClient | Si       | null              |
| $UserSession    | Token de sesión de la que se quiere conocer el error  | Si | null |
##### Sintaxis

```php
    /*
     En este caso el método ItsPrepareAppend ya devuelve el resultado de ItsGetLastError.
     El siguiente código es solo a modo de ejemplo
    */
    $do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );
    if(!$do_login['error']) {
        $UserSession = $do_login['UserSession'];
        $do_prepare_append = $Itris->ItsPrepareAppend( $soapClient , $UserSession , 'ERP_COM_VEN_PED' );
        if($do_prepare_append['error']) {
            $do_get_last_error = $Itris->ItsGetLastError( $soapClient , $UserSession );
            if(!$do_get_last_error['error']){
                echo $do_get_last_error['message'];
            }
        }
    }
```
#### Response
   * Éxito en la petición
     * **status**  [Integer]  = 403
     * **message** [String] = Mensaje de error devuelto por el cliente Itris
     * **error** [Boolean] =  false
     * **UserSession** [String] = Token de sesión
    * Error en la petición
      * **status**  [Integer]  = 200
      * **message** [String] = 'No se encontró ningún error con el UserSession ' . $sUserSession
      * **error** [Boolean] =  true
      * **UserSession** [String] =  Token de sesión del usuario
    * Excepción en la conexión
      * **status**  [Integer]  = 500
      * **message** [String] = 'Error en el servidor: ' . $exception->getMessage()
      * **error** [Boolean] =  true

<a name="ItsGetData"></a>

### ItsGetData
El método que trae registros de una clase de Itris. Devuelve una estructura que tiene el patrón:
* DATAPACKET
  * METADATA (Información acerca del diccionario de la clase)
    * FIELDS 
      * FIELD 
  * ROWDATA
    * ROW (un registro) 
    * ROW (otro registro)

#### Definición de la función
| Parámetros        | Descripción           | Obligatorio    | Valor por defecto |
| :-------------:   |:-------------:        | :------------: | :------------:    |
| $oSoapClient      | Referencia al cliente soap creado en ItsCreateClient | Si       | null              |
| $UserSession    | Token de sesión  | Si | null |
| $ItsClass    | Clase de Itris  | Si | null |
| $RecordCount    | Cantidad de registros a buscar. Por defecto trae 10  | No | 10 |
| $SQLFilter    | Filtro SQL a aplicar en el formato ' ID = 1230 '  | No | " " |
| $SQLSort    | Ordenamiento. Debe escribirse en formato SQL. Ej: ' ID ASC '  | No | " " |
##### Sintaxis

```php
    $do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );
    if(!$do_login['error']) {
        $UserSession = $do_login['UserSession'];
        $do_get_data = $Itris->ItsGetData( $soapClient , $UserSession , 'ERP_COM_VEN_PED' );
        if(!$do_get_data['error']) {
            // foreach ROW in ROWDATA
            foreach($do_get_data['data']->ROWDATA->ROW as $key => $row){
                // Obtengo el ID de cada fila
                echo $row['ID'];
            }
        } else {
            echo $do_get_data['message'];
        }
    }
```
#### Response
   * Éxito en la petición
     * **status**  [Integer]  = 200
     * **message** [String] = 'Se encontraron ' . $RecordCount . ' registros solicitados'
     * **error** [Boolean] =  false
     * **data**  [Array] = Data que devuelve el WebService
    * Error en la petición
      * **status**  [Integer]  = 403
      * **message** [String] = Resultado de [ItsGetLastError](#ItsGetLastError)
      * **error** [Boolean] =  true
      * **UserSession** [String] =  Token de sesión del usuario
    * Excepción en la conexión
      * **status**  [Integer]  = 500
      * **message** [String] = 'Error en el servidor: ' . $exception->getMessage()
      * **error** [Boolean] =  true

<a name="ItsPrepareAppend"></a>

### ItsPrepareAppend
Prepara la inserción de un registro en una clase de Itris. Debe ejecutarse para obtener la estructura del registro a insertar.

Es el primer paso para insertar un registro en el sistema. La secuencia de pasos debe ser:
* [ItsPrepareAppend](#ItsPrepareAppend)
* Escribir en la estructura devuelta por el ItsPrepareAppend los datos correspondientes al registro
* [ItsSetData](#ItsSetData)
* [ItsPost](#ItsPrepareAppend)

El formato de la estructura devuelva es similar al del [ItsGetData](#ItsGetData):
* DATAPACKET
  * METADATA (Información acerca del diccionario de la clase)
    * FIELDS 
      * FIELD 
  * ROWDATA
    * ROW 
      * ATRIBUTOS DE LA CABECERA
      * UN ROW POR CADA DETALLE

#### Definición de la función

| Parámetros        | Descripción           | Obligatorio    | Valor por defecto |
| :-------------:   |:-------------:        | :------------: | :------------:    |
| $oSoapClient      | Referencia al cliente soap creado en ItsCreateClient | Si       | null              |
| $UserSession    | Token de sesión  | Si | null |
| $ItsClass    | Clase de Itris en la que se insertará el registro  | Si | null |
| &XMLData    | Referencia al objeto XML que devuelve el WebService  | No | null |
| &DataSession | Token de sesión de la inserción  | No | null |
##### Sintaxis

```php
    $do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );
    if(!$do_login['error']) {
        $UserSession = $do_login['UserSession'];
        $do_prepare_append = $Itris->ItsPrepareAppend( $oSoapClient , $UserSession , 'ERP_COM_VEN_PED' );
        if(!$do_prepare_append['error']) {
            $DataSession = $do_prepare_append['DataSession'];
            // Ejemplo: setear fecha y empresa del pedido
            $row_data = $do_prepare_append['data']->ROWDATA->ROW[0];
            $row_data['FECHA'] = '31/10/1993';
            $row_data['FK_ERP_EMPRESAS'] = '12345678';
        } else {
            echo $do_prepare_append['message'];
        }
    }
```
#### Response
   * Éxito en la petición
     * **status**  [Integer]  = 200
     * **message** [String] = 'Se inició correctamente la inserción del registro'
     * **DataSession** [String] = Token de sesión de inserción del registro. Debe ser enviado en todas las peticiones asociadas a la inserción del mismo.
     * **error** [Boolean] =  false
     * **data**  [Array] = Data de inicialización que devuelve el WebService
    * Error en la petición
      * **status**  [Integer]  = 403
      * **message** [String] = Resultado de [ItsGetLastError](#ItsGetLastError)
      * **error** [Boolean] =  true
      * **UserSession** [String] =  Token de sesión del usuario
    * Excepción en la conexión
      * **status**  [Integer]  = 500
      * **message** [String] = 'Error en el servidor: ' . $exception->getMessage()
      * **error** [Boolean] =  true 

<a name="ItsSetData"></a>

### ItsSetData
Escribe la información del registro y ejecuta los eventos en el cliente, validando la información. 

Este método debe ejecutarse cada vez que se realiza una modificación en el dataset a insertar.

Es el tercer paso para insertar un registro en el sistema. La secuencia de pasos debe ser:
* [ItsPrepareAppend](#ItsPrepareAppend)
* Escribir en la estructura devuelta por el ItsPrepareAppend los datos correspondientes al registro
* [ItsSetData](#ItsSetData)
* [ItsPost](#ItsPrepareAppend)

El formato de la estructura devuelva es similar al del [ItsGetData](#ItsGetData):
* DATAPACKET
  * METADATA (Información acerca del diccionario de la clase)
    * FIELDS 
      * FIELD 
  * ROWDATA
    * ROW 
      * ATRIBUTOS DE LA CABECERA
      * UN ROW POR CADA DETALLE

#### Definición de la función
$oSoapClient , $sUserSession , $sDataSession , $JSONData , &$oXMLData
| Parámetros        | Descripción           | Obligatorio    | Valor por defecto |
| :-------------:   |:-------------:        | :------------: | :------------:    |
| $oSoapClient      | Referencia al cliente soap creado en ItsCreateClient | Si       | null           |
| $UserSession    | Token de sesión de usuario  | Si | null |
| $DataSession    | Token de sesión de inserción del registro  | Si | null |
| $JSONData    | Data a insertar en formato JSON. Debe ser enviado el dataset que se recibió de [ItsPrepareAppend](#ItsPrepareAppend) con las modificaciones correspondientes | Si | null |
| &oXMLData | Referencia al XMLData que se transacciona  | No | null |
##### Sintaxis

```php
    $do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );
    if(!$do_login['error']) {
        $UserSession = $do_login['UserSession'];
        $do_prepare_append = $Itris->ItsPrepareAppend( $SoapClient , $UserSession , 'ERP_COM_VEN_PED' );
        if(!$do_prepare_append['error']) {
            // Ejemplo: setear fecha y empresa del pedido
            $DataSession = $do_prepare_append['DataSession'];
            $dataset = $do_prepare_append['data'];
            $row_data = $dataset->ROWDATA->ROW[0];
            $row_data['FECHA'] = '31/10/1993';
            $row_data['FK_ERP_EMPRESAS'] = '12345678';
            
            $do_set_data = $Itris->ItsSetData( $soapClient , $UserSession , $DataSession , $dataset );
            if(!$do_set_data['error']) {
                // Inserción exitosa
            } else {
                // Fallo en la inserción. El mensaje de error queda guardado en $do_set_data['message'];
                echo $do_set_data['message'];
            }
        } else {
            echo $do_prepare_append['message'];
        }
    }
```
#### Response
   * Éxito en la petición
     * **status**  [Integer]  = 302
     * **message** [String] = 'La inserción de datos se ha realizado exitosamente'
     * **UserSession** [String] = Token de sesión del usuario.
     * **DataSession** [String] = Token de sesión del dataset. Debe ser enviado en todas las peticiones asociadas a la inserción o modificación del mismo.
     * **error** [Boolean] =  false
     * **data**  [Array] = Data con validación de eventos al cambiar que devuelve el WebService
    * Error en la petición
      * **status**  [Integer]  = 403
      * **message** [String] = Resultado de [ItsGetLastError](#ItsGetLastError)
      * **error** [Boolean] =  true
      * **UserSession** [String] =  Token de sesión del usuario
    * Excepción en la conexión
      * **status**  [Integer]  = 500
      * **message** [String] = 'Error en el servidor: ' . $exception->getMessage()
      * **error** [Boolean] =  true

<a name="ItsPost"></a>

### ItsPost
Realiza la inserción definitiva del dataset. Deben haberse pasado todos los cambios al método [ItsSetData](#ItsSetData) y luego ejecutarse el método **ItsPost**.

Es el último paso para insertar un registro en el sistema. La secuencia de pasos debe ser:
* [ItsPrepareAppend](#ItsPrepareAppend)
* Escribir en la estructura devuelta por el ItsPrepareAppend los datos correspondientes al registro
* [ItsSetData](#ItsSetData)
* [ItsPost](#ItsPrepareAppend)

El formato de la estructura devuelva es similar al del [ItsGetData](#ItsGetData):
* DATAPACKET
  * METADATA (Información acerca del diccionario de la clase)
    * FIELDS 
      * FIELD 
  * ROWDATA
    * ROW 
      * ATRIBUTOS DE LA CABECERA
      * UN ROW POR CADA DETALLE

#### Definición de la función

| Parámetros        | Descripción           | Obligatorio    | Valor por defecto |
| :-------------:   |:-------------:        | :------------: | :------------:    |
| $oSoapClient      | Referencia al cliente soap creado en ItsCreateClient | Si       | null              |
| $UserSession    | Token de sesión del usuario  | Si | null |
| $DataSession    | Token de sesión del registro que se está insertando  | Si | null |
##### Sintaxis

```php
    $do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );
    if(!$do_login['error']) {
        $UserSession = $do_login['UserSession'];
        $do_prepare_append = $Itris->ItsPrepareAppend( $SoapClient , $UserSession , 'ERP_COM_VEN_PED' );
        if(!$do_prepare_append['error']) {
            // Ejemplo: setear fecha y empresa del pedido
            $DataSession = $do_prepare_append['DataSession'];
            $dataset = $do_prepare_append['data'];
            $row_data = $dataset->ROWDATA->ROW[0];
            $row_data['FECHA'] = '31/10/1993';
            $row_data['FK_ERP_EMPRESAS'] = '12345678';
            
            $do_set_data = $Itris->ItsSetData( $soapClient , $UserSession , $DataSession , $dataset );
            if(!$do_set_data['error']) {
                // Inserción exitosa
                $do_post = $Itris->ItsPost( $soapClient, $UserSession , $DataSession );
                if(!$do_post['error']) {
                    // Inserción realizada con éxito.
                } else {
                    // Fallo en la inserción. El mensaje de error queda guardado en $do_post['message'];
                }
            } else {
                // Fallo en el SetData. El mensaje de error queda guardado en $do_set_data['message'];
                echo $do_set_data['message'];
            }
        } else {
            echo $do_prepare_append['message'];
        }
    }
```
#### Response
   * Éxito en la petición
     * **status**  [Integer]  = 200
     * **message** [String] = 'Comprobante creado exitosamente'
     * **data** [Array] = Información definitiva con la que el registro queda guardado en el sistema
    * Error en la petición
      * **status**  [Integer]  = 403
      * **message** [String] = Resultado de [ItsGetLastError](#ItsGetLastError)
      * **error** [Boolean] =  true
      * **UserSession** [String] =  Token de sesión del usuario
    * Excepción en la conexión
      * **status**  [Integer]  = 500
      * **message** [String] = 'Error en el servidor: ' . $exception->getMessage()
      * **error** [Boolean] =  true

<a name="ItsDelete"></a>

### ItsDelete
Elimina el registro con el ID pasado por parámetro de una clase determinada.

 $oSoapClient , $sUserSession , $ItsClass , $ItsID 
#### Definición de la función

| Parámetros        | Descripción           | Obligatorio    | Valor por defecto |
| :-------------:   |:-------------:        | :------------: | :------------:    |
| $oSoapClient      | Referencia al cliente soap creado en ItsCreateClient | Si       | null              |
| $UserSession    | Token de sesión del usuario  | Si | null |
| $ItsClass    | Nombre de la clase de la que se quiere eliminar el registro  | Si | null |
| $ItsID    | ID del registro en la clase  | Si | null |
##### Sintaxis

```php
    $do_login = $Itris->ItsLogin( $soapClient , $db , $user , $pass , $UserSession );
    if(!$do_login['error']) {
       $id_a_eliminar = '423123';
       $UserSession = $do_login['UserSession'];
       $do_delete = $Itris->ItsDelete( $soapClient , $UserSession , 'ERP_COM_VEN_PED' , $id_a_eliminar);
       if(!$do_delete['error']){
            // Registro eliminado con éxito
       }
    }
```
#### Response
   * Éxito en la petición
     * **status**  [Integer]  = 302
     * **message** [String] = 'Se eliminó el registro correctamente'
     * **error** [Boolean] = false
    * Error en la petición
      * **status**  [Integer]  = 403
      * **message** [String] = Resultado de [ItsGetLastError](#ItsGetLastError)
      * **error** [Boolean] =  true
      * **UserSession** [String] =  Token de sesión del usuario
    * Excepción en la conexión
      * **status**  [Integer]  = 500
      * **message** [String] = 'Error en el servidor: ' . $exception->getMessage()
<<<<<<< HEAD
      * **error** [Boolean] =  true
=======
      * **error** [Boolean] =  true
>>>>>>> 1368d42904f693a14ecf02b64b108613e904369b
