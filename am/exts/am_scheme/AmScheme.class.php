<?php
/**
 * Amathista - PHP Framework
 *
 * @author Alex J. Rondón <arondn2@gmail.com>
 * 
 */

/**
 * Clase para representar una conexión a una base de datos.
 */
abstract class AmScheme extends AmObject{

  protected static

    /**
     * Instancias de los esquemas cargados.
     */
    $schemes = array();

  protected

    /**
     * String con el nombre clave de la conexión. Se asume es único.
     */
    $name = null,

    /**
     * String con el prefijo para las clases nacidas de esta fuente.
     */
    $prefix = null,

    /**
     * String con el driver utilizado en la fuente.
     */
    $driver = null,

    /**
     * String con el nombre de la base de datos a conectarse.
     */
    $database = null,

    /**
     * String con la dirección o nombre del servidor.
     */
    $server = null,

    /**
     * int/string con número del puerto para la conexión.
     */
    $port = null,

    /**
     * String con el nombre de usuario para la conexion.
     */
    $user = null,

    /**
     * String con el password para la conexion.
     */
    $pass = null,

    /**
     * String con el set de charset.
     */
    $charset = null,

    /**
     * String con el set de reglas para de caracteres.
     */
    $collation = null,

    /**
     * Hash con los las instancias por modelos de la conexión.
     */
    $tables = array(),

    /**
     * Equivalencias entre los tipos de datos del DBMS y PHP
     */
    $types = array(),

    /**
     * Hash de tamaños de subtipos
     */
    $lenSubTypes = array(),

    /**
     * Tipos por defecto de cada subtipo
     */
    $defaultsBytes = array();

  /**
   * Constructor. Se conecta en el contructor.
   */
  public function __construct($params = array()){
    parent::__construct($params);

    $this->connect(); // Conectar la fuente

  }

  /**
   * El destructor del objeto cierra la conexión
   */
  public function __destruct() {

    $this->close();

  }
    
  /**
   * Devuelve el nombre del esquema a la que referencia.
   * @return string Nombre del esquema.
   */
  public function getName(){
    
    return $this->name;

  }
    
  /**
   * Devuelve el prefijo para los modelos del esquema.
   * @return string Prefijo para clases.
   */
  
  public function getPrefix(){
    
    return $this->prefix;

  }
    
  /**
   * Devuelve el nombre driver de conexión.
   * @return string Nombre del driver.
   */
  public function getDriver(){
    
    return $this->driver;

  }
    
  /**
   * Devuelve el nombre de la base de datos.
   * @return string Nombre de la base de datos.
   */
  public function getDatabase(){
    
    return $this->database;

  }
    
  /**
   * Devuelve el nombre o dirección del servidor.
   * @return string Nombre o dirección del servidor.
   */
  public function getServer(){
    
    return $this->server;

  }
    
  /**
   * Devuelve el Número del puerto para la conexión.
   * @return int/string Número de puerto para la conexión.
   */
  public function getPort(){
    
    return $this->port;

  }
    
  /**
   * Devuelve el nombre de usuario para la conexión.
   * @return string Nombre de usuario para la conexión.
   */
  public function getUser(){
    
    return $this->user;

  }
    
  /**
   * Devuelve el password para la conexión.
   * @return string Password para la conexión.
   */
  public function getPass(){
    
    return $this->pass;

  }
    
  /**
   * Devuelve el charset.
   * @return string Charset.
   */
  public function getCharset(){
    
    return $this->charset;

  }
    
  /**
   * Devuelve el reglas de caracteres.
   * @return string Coleción de reglas para los caracteres.
   */
  public function getCollation(){
    
    return $this->collation;

  }

  /**
   * Obtiene un hash de los subtipos de un tipo de datos en el DBSM.
   * @param  $string $type  Nombre del tipo de datos.
   * @return hash           Hash con la colección de subtipos.
   */
  public function getLenSubTypes($type){

    return itemOr($type, $this->lenSubTypes);
    
  }

  /**
   * Devuelve un tipo de datos para el DBMS dependiendo de un tipo de datos
   * de lenguaje y la longuitud del mismo.
   * @param  string $type Tipo de datos en el lenguaje.
   * @param  int    $len  Longuitud del tipo de datos.
   * @return string       Tipo de datos en el DBMS.
   */
  protected function getTypeByLen($type, $len){
    $types = $this->getLenSubTypes($type);
    $index = array_search($len, $types);
    if($index){
      return $index;
    }
    return itemOr($type, $this->defaultsBytes);
  }
  
  /**
   * Devuelve un alias no existente en una colección. Si en la colección existe
   * algún key igual al alias se le irá agregando contador al final hasta
   * obtener uno que no exista.
   * @param  string $alias      Alias base.
   * @param  array  $collection Colección donde se buscará si el alias existe.
   * @return string             Alias generados
   */
  public function alias($alias, array $collection, $ignoreError = false){

    if(!isNameValid($alias)){
      if(!$ignoreError){
        throw Am::e('AMSCHEME_INVALID_ALIAS', $alias);
      }
      return null;
    }

    $i = 0;
    $finalAlias = $alias;
    while(isset($collection[$finalAlias])){
      $finalAlias = $alias . $i++;
    }

    return $finalAlias;

  }

  /**
   * Devuelve el nombre de un objeto de la BD pasado por realScapeString y por
   * nameWrapper.
   * @param  string $name Nombre que se desea escapar y colocar entre comillas.
   * @return string       Resultado de la operación.
   */
  public function nameWrapperAndRealScape($name){

    return $this->nameWrapper($this->realScapeString($name));

  }

  /**
   * Prepara el nombre complete de un objeto. Primero los separa por partes en
   * puntos luego los pasa por la función <code>nameWrapperAndRealScape</code>.
   * @param  string $name Nombre relativo del objeto.
   * @return string       Nombre procesado.
   */
  public function nameWrapperAndRealScapeComplete($name){

    // Dividir en puntos
    $nameArr = explode('.', (string)$name);

    // Preparar el nombre de cada parte del campo
    foreach ($nameArr as $key => $value) {
      if(!isNameValid($value)){
        throw Am::e('AMSCHEME_INVALID_NAME', $name);
      }
        
      $nameArr[$key] = $this->nameWrapperAndRealScape($value);
    }

    // Pegar campos
    return implode('.', $nameArr);

  }

  /**
   * Devuelve una cadena espacada y entre comillas.
   * @param  string $string Cadena que se desea escapar y colocar entre
   *                        comillas.
   * @return string         Resultado de la operación.
   */
  public function valueWrapperAndRealScape($string){

    return $this->valueWrapper($this->realScapeString($string));
    
  }

  /**
   * Agrega una tabla a la conexión.
   * @param  AmTable $table Tabla a agregar.
   * @return $this
   */
  public function addTable(AmTable $table){
    
    // Se agrega la tabla en la posición del modelo.
    $this->tables[$table->getModel()] = $table;
    return $this;

  }

  /**
   * Devuelve la instancia de una tabla correspondiente a un modelo. Si no
   * existe la instancia para dicho modelo devuelve null.
   * @param  string  $model Nombre del modelo.
   * @return AmTable        Devuelve la instancia de la tabla si existe.
   */
  public function getTableInstance($model = null){

    // Devuelve la instancia si existe
    return itemOr($model, $this->tables);

  }

  /**
   * Devuelve si la instancia para un modelo está cargada.
   * @param  string $model  Nombre del modelo a consultar.
   * @return bool           Indica si existe la instancia de la tabla para el
   *                        modelo.
   */
  public function hasTableInstance($model){

    // Inidica si existe la instancia
    return isset($this->tables[$model]);

  }

  /**
   * Devuelve los modelos del esquema generados en la aplicación
   * @return array Array de strings con los nombres de los modelos.
   */
  public function getGeneratedModels(){

    $ret = array();

    // Buscar los modelo dentro de la carpeta del esquema
    $ret = amGlob($this->getDir(), array(

      // Obtener solo los archivos de configuración
      'include' => '/.*[\/\\\\](.*)\.conf\.php/',

      // No buscar recursivamente
      'recursive' => false,

      // Indica que sección de la regez devolverá.
      'return' => 1

    ));

    return $ret;

  }

  /**
   * Devuelve el directorio de modelos del schema actual.
   * @return string Directorio de modelos de esquema.
   */
  public function getDir(){

    // Obtener el nombre del esquema
    $name = $this->getName();

    // Obtener el nombre del esquema por defecto su directorio raíz es el mismo
    // directorio de esquemas.
    return Am::getDir('schemes') . (!empty($name)? "/{$name}" : '');

  }

  /**
   * Devuelve la nombre del modelo para identificarlo dentro de todos los
   * esquemas de la aplicación. El formato es: :<schemeName>@<tableName>
   * @param  string $model Nombre del modelo que se desea consultar.
   * @return string        Nombre del modelo.
   */
  public function getSchemeModelName($model){

    return ':'.$this->name.'@'.underscore($model);

  }

  /**
   * Devuelve el nombre de la clase del modelo.
   * @param  string $model Nombre del modelo que se desea consultar.
   * @return string        Nombre de la clase del modelo.
   */
  public function getBaseModelClassName($model){

    return $this->getPrefix() . camelCase($model, true).'Base';

  }

  /**
   * Devuelve la dirección del archivo de configuración del modelo.
   * @param  string $model Nombre del modelo que se desea consultar.
   * @return string        Dirección del archivo de configuración.
   */
  public function getBaseModelConfFilename($model){

    return $this->getDir() .'/'. underscore($model) .'.conf.php';

  }

  /**
   * Devuelve la dirección de la clase base del model.
   * @param  string $model Nombre del modelo que se desea consultar.
   * @return string        Dirección de la clase base del modelo.
   */
  public function getBaseModelClassFilename($model){

    return $this->getDir() .'/'. $this->getBaseModelClassName($model) .'.class.php';

  }

  /**
   * Indica si existe la clase base de un modelo y su configuración.
   * @param  string $model Nombre del modelo que se desea consultar.
   * @return bool          Si existe o no el modelo.
   */
  public function existsBaseModel($model){

    // Preguntar si existen el archivo del modelo base y el del controlador.
    return is_file($this->getBaseModelConfFilename($model))

        && is_file($this->getBaseModelClassFilename($model));
  }

  /**
   * Devuelve la configuración de un modelo base leída desde su archivo de
   * copnfiguración.
   * @param  string $model Nombre del modelo que se desea consultar.
   * @return hash          Hash de propiedades del modelo.
   */
  public function getBaseModelConf($model){
  
    // Si el archivo existe retornar la configuración    
    if(is_file($confFilePath = $this->getBaseModelConfFilename($model))){
      return require $confFilePath;
    }

    // Si no existe retornar falso
    return false;

  }

  // /**
  //  * Crea el archivo con la clase del modelo base basando en una tabla.
  //  * @param  AmTable $table Tabla en el que se basará el modelo a generar.
  //  * @return bool           Si se generó o no el modelo.
  //  */
  // public function generateBaseModelFile(AmTable $table){

  //   // Obtener el nombre del archivo destino
  //   $path = $this->getBaseModelClassFilename($table->getTableName());
    
  //   // Crear directorio donde se ubicará el archivo si no existe
  //   Am::mkdir(dirname($path));

  //   // Generar el archivo
  //   return !!file_put_contents($path, "<?php\n\n" .
  //     AmGenerator::classBaseModel($this, $table));
    
  // }

  // /**
  //  * Genera la clase base del modelo y su archivo de configuración.
  //  * @param  AmTable $table Tabla en el que se basará el modelo a generar.
  //  * @return Hash           Resultado de la generación del archivo de
  //  *                        configuración y el modelo.
  //  */
  // public function generateBaseModel(AmTable $table){
    
  //   // Obtener la ruta del archivo
  //   $file = $this->getBaseModelConfFilename($table->getTableName());

  //   // Crear directorio donde se ubicará el archivo
  //   Am::mkdir(dirname($file));

  //   // Crear archivo de configuración
  //   $writed = file_put_contents($file, AmCoder::encode($table->toArray()));

  //   return array(

  //     // Si el archivo fue creado o no
  //     'conf' => $writed,

  //     // Crear clase
  //     'model' => $this->generateBaseModelFile($table)

  //   );
    
  // }

  // /**
  //  * Genera todos los modelos bases correspondientes a las tablas de un esquema.
  //  * @return hash Resultado de la generación de la configuración y el modelo.
  //  */
  // public function generateScheme(){

  //    // Para retorno
  //   $ret = array(
  //     'tables' => array(),
  //   );

  //   // Obtener listado de nombres de tablas
  //   $tables = $this->queryGetTables()->col('tableName');

  //   foreach ($tables as $tableName)

  //     // Obtener instancia de la tabla
  //     $ret['tables'][$tableName] = $this->generateBaseModel(
  //       $this->getTableFromScheme($tableName)
  //     );

  //   return $ret;

  // }

  /**
   * Devuelve el nombre de una tabla para ser reconocida en el DBSM
   * @param  string/AmTable/AmQuey $table Tabla de la que se desea obtener le
   *                                      nombre. Puede ser un string y una
   *                                      instancia de AmTable.
   * @return string                       Nombre de tabla obtenido.
   */
  public function getDatabaseObjectName($table){

    // Si es una instancia de AmTable se debe obtener el nombre
    if($table instanceof AmQuery){
      $table = $table->getTable();
    }

    // Si es una instancia de AmTable se debe obtener el nombre
    if($table instanceof AmTable){
      $table = $table->getTableName();
    }

    return $this->nameWrapperAndRealScapeComplete($table);

  }
  
  /**
   * Devuelve la cadena con la dirección del servidor y el puerto.
   * @return string Dirección del servidor con el puertos
   */
  public function getServerString(){

    // Obtener el puerto
    $port = $this->getPort();

    if(empty($port)){
      $port = $this->getDefaultPort();
    }

    return "{$this->getServer()}:{$port}";

  }

  /**
   * Realiza la conexión.
   * @return Resource Handle de conexión establecida o FALSE si falló.
   */
  public function connect(){

    return $this->start();

  }

  /**
   * Función para reconectar. Desconecta y vuelve a conectar la DB.
   * @return Resource Recurso generado por la nueva conexión.
   */
  public function reconnect(){

    $this->close();           // Desconectar
    return $this->connect();  // Volver a conectar

  }

  /**
   * Crea una instancia de un AmQuery para la actual BD.
   * @param  string/AmQuery $from  From principal de la consulta.
   * @param  string         $alias Alias del from recibido.
   * @return AmQuery               Instancia de l query creado.
   */
  public function q($from = null, $alias = null){

    // Crear instancia
    $q = new AmQuery(array('scheme' => $this));

    // Asignar el modelo
    if($from instanceof AmQuery || $from instanceof AmTable){
      $q->setModel($from->getModel());
    }elseif(is_string($from) && is_subclass_of($from, 'AmModel')){
      $q->setModel($from); 
    }
    
    // Asignar el from de la consulta
    if(!empty($from)){
      $q->fromAs($from, $alias);
    }

    return $q;

  }

  /**
   * Ejecutar una consulta SQL desde el ámbito de la BD actual
   * @param  string/AmQuery $q SQL a ejecutar o instancia de AmQuery a ejecutar.
   * @return bool/int          Devuelve el resultado de la ejecución.
   *                           Puede ser un valor booleano que indica si se
   *                           ejecuto la consulta satisfactoriamente, o un
   *                           int en el caso de haberse ejecutatado un insert.
   */
  public function execute($q){

    // Obtener SQL si es una instancia de AmQuery
    if(is_array($q)){
      $q = $this->_sqlQueryGroup($q);
    }else{
      $q = (string)$q;
    }

    // Selecionar la BD actual
    $this->select();

    // Ejecutar la consulta
    return $this->query($q);

  }

  /**
   * Setea el valor de una variable en el DBSM.
   * @param  string $varName Nombre de la variable.
   * @param  mixed  $value   Valor a asignar.
   * @param  bool   $scope   Si se agrega la cláusula GLOBAL o SESSION.
   * @return bool            Resultado de la operación
   */
  public function sqlSetServerVar($varName, $value, $scope = ''){
    
    $varName = $this->realScapeString($varName);
    $value = $this->valueWrapperAndRealScape($value);
    $scope = $this->_sqlScope($scope);

    return $this->_sqlSetServerVar($varName, $value, $scope);

  }

  public function setServerVar($varName, $value, $scope = ''){

    return !!$this->execute($this->sqlSetServerVar($varName, $value, $scope));

  }

  /**
   * Seleciona la BD.
   * @return bool Si se pudo selecionar la BD.
   */
  public function sqlSelectDatabase($database = null){

    if(!isset($database)){
      $database = $this->getDatabase();
    }

    $database = $this->nameWrapperAndRealScape($database);

    // Ejecuta el SQL de seleción de de BD.
    return $this->_sqlSelectDatabase($database);

  }

  public function select($database = null){

    return $this->query($this->sqlSelectDatabase($database));

  }

  /**
   * Indica si la BD existe.
   * @return bool Si la BD existe.
   */
  public function exists(){

    // Intenta selecionar. Si logra selecionar la BD existe.
    return !!$this->select();

  }

  /**
   * Crea la BD.
   * @param  bool $ifNotExists Indica si se agrega la clausula IF NOT EXISTS.
   * @return bool              Si se creó la BD. Si la BD ya existe y el
   *                           parámetro $ifNotExists == true, retornará true.
   */
  public function sqlCreate($database = null, $ifNotExists = true){

    if(is_bool($database)){
      $ifNotExists = $database;
      $database = null;
    }

    if(!isset($database)){
      $database = $this->getDatabase();
    }

    $database = $this->nameWrapperAndRealScape($database);

    $charset = $this->getCharset();
    if(!empty($charset)){
      $charset = $this->_sqlCharset($charset);
    }

    $collation = $this->getCollation();
    if(!empty($collation)){
      $collation = $this->_sqlCollation($collation);
    }

    $ifNotExists = $ifNotExists? $this->_sqlIfNotExists() : '';

    return $this->_sqlCreate($database, $charset, $collation, $ifNotExists);

  }

  public function create($database = null, $ifNotExists = true){

    return !!$this->execute($this->sqlCreate($database, $ifNotExists));

  }

  /**
   * Elimina la BD.
   * @param  bool $ifExists Si se agrega la clausula IF EXISTS.
   * @return bool           Si se eliminó la BD. Si la BD no existe y el
   *                        parémetro $ifExists==true entonces retorna
   *                        true.
   */
  public function sqlDrop($database = null, $ifExists = true){

    if(is_bool($database)){
      $ifExists = $database;
      $database = null;
    }

    if(!isset($database)){
      $database = $this->getDatabase();
    }

    $database = $this->nameWrapperAndRealScape($database);

    $ifExists = $ifExists? $this->_sqlIfExists() : '';

    return $this->_sqlDrop($database, $ifExists);

  }

  public function drop($database = null, $ifExists = true){

    return !!$this->execute($this->sqlDrop($database, $ifExists));

  }

  /**
   * Obtener la información de la BD.
   * @return hash Hash con las propiedades de laBD
   */
  public function getInfo(){

    return $this->queryGetInfo()->row();

  }
  
  /**
   * Obtener el SQL para un campo de una tabla al momento de crear la tabla.
   * @param  AmField $field Instancia del campo.
   * @return string         SQL correspondiente.
   */
  public function sqlField(AmField $field){

    // Preparar las propiedades
    $name = $this->nameWrapperAndRealScape($field->getName());
    $type = $field->getType();
    $len = $field->getLen();
    $extra = $field->getExtra();

    $attrs = array();

    // Get type
    // As int
    if(in_array($type, array('int', 'text'))){
      $type = self::getTypeByLen($type, $len);
      
    // as float precision
    }elseif($type == 'float'){

      $type = self::getTypeByLen($type, $len);

      $precision = $field->getPrecision();
      $scale = $field->getScale();

      if($precision && $precision)
        $type = "{$type}({$precision}, {$scale})";

    // with var len
    }elseif(in_array($type, array('bit', 'char', 'varchar'))){
      
      $type = "{$type}({$len})";

    }

    $unsigned = '';
    if($field->isUnsigned()){
      $unsigned = $this->_sqlUnsigned();
    }

    $zerofill = '';
    if($field->isZerofill()){
      $zerofill = $this->_sqlZerofill();
    }

    $charset = $field->getCharset();
    if(!empty($charset)){
      $charset = trim($this->_sqlCharset($charset));
    }else{
      $charset = '';
    }

    $collation = $field->getCollation();
    if(!empty($collation)){
      $charset = trim($this->_sqlCollation($collation));
    }else{
      $charset = '';
    }
    
    $notNull = '';
    if(!$field->allowNull()){
      $notNull = $this->_sqlNotNull();
    }

    $autoIncrement = '';
    if($field->isAutoIncrement()){
      $autoIncrement = $this->_sqlAutoIncrement();
    }

    $default = $field->getDefaultValue();
    if(isset($default)){

      $default = $field->parseValue($default);

      if(in_array($type, array('text', 'char', 'varchar', 'bit')) ||
        (in_array($type, array('date', 'datetime', 'timestamp', 'time')) &&
          $default != $this->_sqlCurrentTimestamp()
        )
      ){
        $default = $this->valueWrapperAndRealScape($default);
      }

      $default = $this->_sqlDefaultValue($default);

    }else{
      $default = '';
    }

    return trim($this->_sqlField(
      $name.(empty($name)?'':' '),
      $type.(empty($type)?'':' '),
      $unsigned.(empty($unsigned)?'':' '),
      $zerofill.(empty($zerofill)?'':' '),
      $charset.(empty($charset)?'':' '),
      $collation.(empty($collation)?'':' '),
      $notNull.(empty($notNull)?'':' '),
      $autoIncrement.(empty($autoIncrement)?'':' '),
      $default.(empty($default)?'':' '),
      $extra.(empty($extra)?'':' ')
    ));

  }

  /**
   * Obtener el SQL para crear una tabla.
   * @param  AmTable $table       Instancia de la tabla a acrear
   * @param  bool    $ifNotExists Se se debe agregar la cláusula IF NOT EXISTS.
   * @return string  SQL del query.
   */
  public function sqlCreateTable(AmTable $table, $ifNotExists = true){

    // Obtener nombre de la tabla
    $tableName = $this->nameWrapperAndRealScapeComplete($table->getTableName());

    // Lista de campos
    $fields = array();
    $realFields = $table->getFields();

    // Obtener el SQL para cada camppo
    foreach($realFields as $field){
      $fields[] = $this->sqlField($field);
    }

    // Obtener los nombres de los primary keys
    $pks = $table->getPks();
    foreach($pks as $offset => $pk){
      $pks[$offset] = $this->nameWrapperAndRealScape($table->getField($pk)->getName());
    }

    // Preparar otras propiedades
    $engine = $table->getEngine();
    if(!empty($engine)){
      $engine = $this->_sqlEngine($engine);
    }else{
      $engine = '';
    }
    
    $charset = $table->getCharset();
    if(!empty($charset)){
      $charset = $this->_sqlCharset($charset);
    }else{
      $charset = '';
    }
    
    $collation = $table->getCollation();
    if(!empty($collation)){
      $collation = $this->_sqlCollation($collation);
    }else{
      $collation = '';
    }

    if(!empty($pks)){
      // Agregar los primary key al final de los campos
      $fields[] = $this->_sqlPrimaryKey($this->_sqlPrimaryKeyGroup($pks));
    }else{
      $pks = '';
    }

    // Unir los campos
    $fields = $this->_sqlFieldsGroup($fields);

    $ifNotExists = $ifNotExists? $this->_sqlIfNotExists() : '';

    // Preparar el SQL final
    return $this->_sqlCreateTable($tableName, $fields, $engine, $charset, $collation, $ifNotExists);

  }

  /**
   * Crear tabla en la BD.
   * @param  AmTable $t           Tabla a crear
   * @param  bool    $ifNotExists Se agrega el parémtro IS NOT EXISTS.
   * @return bool                 Si se creó la tabla. Si la tabla existe y el
   *                              parámetro $ifNotExists == true, retornará
   *                              true.
   */
  public function createTable(AmTable $t, $ifNotExists = true){

    return !!$this->execute($this->sqlCreateTable($t, $ifNotExists));

  }

  /**
   * Obtener el SQL para eliminar una tabla.
   * @param  AmTable/string $table    Instancia o nombre de la tabla.
   * @param  bool           $ifExists Si se debe agregar la cláusula IF EXISTS.
   * @return string                   SQL correspondiente.
   */
  public function sqlDropTable($table, $ifExists = true){

    // Obtener nombre de la tabla
    if($table instanceof AmTable){
      $table = $table->getTableName();
    }

    $tableName = $this->nameWrapperAndRealScapeComplete($table);

    $ifExists = $ifExists? $this->_sqlIfExists() : '';

    return $this->_sqlDropTable($tableName, $ifExists);

  }

  /**
   * Elimina una tabla.
   * @param  string/AmTable $table    Nombre o instancia de la tabla a eliminar.
   * @param  bool           $ifExists Si se agrega la clausula IF EXISTS.
   * @return bool                     Si se eliminó la Tabla. Si la Tabla no
   *                                  existe y el parémetro $ifExists==true
   *                                  entonces retorna true.
   */
  public function dropTable($table, $ifExists = true){

    return !!$this->execute($this->sqlDropTable($table, $ifExists));

  }

  /**
   * Devuelve el SQL para truncar un tabla.
   * @param  AmTable/string $table    Instancia o nombre de la tabla.
   * @param  bool           $ignoreFk Si se debe ignorar las claves foráneas.
   * @return string         SQL de la acción.
   */
  public function sqlTruncate($table, $ignoreFk = true){

    // Obtener nombre de la tabla
    if($table instanceof AmTable){
      $table = $table->getTableName();
    }
    
    // Obtener nombre de la tabla
    $tableName = $this->nameWrapperAndRealScapeComplete($table);

    $sql = $this->_sqlTruncate($tableName);

    if($ignoreFk){
      return $this->_sqlQueryGroup(array(
        $this->_sqlSetServerVar('FOREIGN_KEY_CHECKS', 0, ''),
        $sql,
        $this->_sqlSetServerVar('FOREIGN_KEY_CHECKS', 1, '')
      ));
    }

    return $sql;

  }

  /**
   * Eliminar todos los registros de una tabla y reinicia los campos
   * autoincrementables.
   * @param  string/AmTable $table    Nombre o instancia de la tabla.
   * @param  bool           $ignoreFk Si se ingorará los Foreing Keys.
   * @return bool                     Si se vació la tabla satisfactoriamente.
   */
  public function truncate($table, $ignoreFk = true){

    return !!$this->execute($this->sqlTruncate($table, $ignoreFk));

  }

  /**
   * Indica si la tabla existe.
   * @param  string/AmTable $table Nombre o instancia de la tabla.
   * @return bool                  Si la tabla existe.
   */
  public function existsTable($table){

    // Intenta obtener la descripcion de la tabla para saber si existe.
    return !!$this->getTableDescription($table);

  }

  /**
   * Crea todas las tablas de la BD basandose en los modelos bases generados.
   * @param  bool $ifNotExists Si la se creanran las tablas si no existe
   * @return hash              Hash con un valor por cada tabla que indica si
   *                           se creó.
   */
  public function createTables($ifNotExists = true){

    $ret = array(); // Para el retorno

    // Obtener los nombres de la tabla en el archivo
    $tablesNames = $this->getGeneratedModels();

    // Recorrer cada tabla generar crear la tabla
    foreach ($tablesNames as $table){

      // Crear la tabla
      $ret[$table] = $this->createTable($table, $ifNotExists);

    }

    return $ret;

  }

  /**
   * Devuelve un array con el listado de tablas de la BD y su descripción.
   * @return array Array de hash con las descripción de las tablas.
   */
  public function getTables(){

    return $this->queryGetTables()->get();

  }

  /**
   * Obtiene la descripción de una tabla en el BD.
   * @param  string/AmTable $table Nombre o instancia de la tabla.
   * @return hash                  Hash con la descripcion de la tabla.
   */
  public function getTableDescription($table){

    // Obtener nombre de la tabla
    if($table instanceof AmTable){
      $table = $table->getTableName();
    }
    
    return $this->q($this->queryGetTables(), 'q')
                ->where('tableName', $table)
                ->row();

  }

  /**
   * Obtener un listado de las columnas de una tabla.
   * @param  string/AmTable $table Nombre o instancia de la tabla.
   * @return hash                  Array de hash con la descripcion de los
   *                               campos.
   */
  public function getTableColumns($table){

    // Obtener nombre de la tabla
    if($table instanceof AmTable){
      $table = $table->getTableName();
    }

    return $this->queryGetTableColumns($table)
                ->get(null, array($this, 'sanitize'));
                
  }

  /**
   * Obtener un listado de las claves foráneas de una tabla.
   * @param  string/AmTable $table Nombre o instancia de la tabla.
   * @return array                 Array de hash conla descripción de las
   *                               claves foráneas.
   */
  public function getTableForeignKeys($table){

    // Obtener nombre de la tabla
    if($table instanceof AmTable){
      $table = $table->getTableName();
    }

    $ret = array(); // Para el retorno

    // Obtener el nombre de la fuente
    $schemeName = $this->getName();

    // Obtener los ForeignKeys
    $fks = $this->queryGetTableForeignKeys($table)
                ->get();

    foreach($fks as $fk){

      // Dividir el nombre del FK
      $name = explode('.', $fk['name']);

      // Obtener el ultimo elemento
      $name = array_pop($name);

      // Si no existe el elmento en el array se crea
      if(!isset($ret[$name])){
        $ret[$name] = array(
          'scheme' => $schemeName,
          'table' => $fk['toTable'],
          'cols' => array()
        );
      }

      // Agregar la columna a la lista de columnas
      $ret[$name]['cols'][$fk['columnName']] = $fk['toColumn'];

    }

    return $ret;

  }

  /**
   * Obtener el listado de referencias a una tabla.
   * @param  string/AmTable $table Nombre o instancia de la tabla.
   * @return array                 Array de hash con la descripción de las
   *                               referencias.
   */
  public function getTableReferences($table){

    // Obtener nombre de la tabla
    if($table instanceof AmTable){
      $table = $table->getTableName();
    }

    $ret = array(); // Para el retorno

    // Obtener el nombre de la fuente
    $schemeName = $this->getName();

    // Obtener las referencias a una tabla
    $fks = $this->queryGetTableReferences($table)
                ->get();

    // Recorrer los FKs
    foreach($fks as $fk){

      // Dividir el nombre del FK
      $name = explode('.', $fk['name']);

      // Obtener el ultimo elemento
      $name = array_shift($name);

      // Si no existe el elmento en el array se crea
      if(!isset($ret[$name])){
        $ret[$name] = array(
          'scheme' => $schemeName,
          'table' => $fk['fromTable'],
          'cols' => array()
        );
      }

      // Agregar la columna a la lista de columnas
      $ret[$name]['cols'][$fk['toColumn']] = $fk['columnName'];

    }

    return $ret;

  }

  /**
   * Obtener un listado de las claves restricciones únicas de una tabla.
   * @param  string/AmTable $table Nombre o instancia de la tabla.
   * @return array                 Array de hash con la descripción de
   *                               claves únicas.
   */
  public function getTableUniques($table){

    // Obtener nombre de la tabla
    if($table instanceof AmTable){
      $table = $table->getTableName();
    }

    $uniques = $this->queryGetTableUniques($table)
                    ->get();

    // Group fields of unique indices for name.
    $realUniques = array();

    foreach ($uniques as $value) {
      $realUniques[$value['name']] = itemOr($value['name'], $realUniques, array());
      $realUniques[$value['name']][] = $value['columnName'];
    }

    return $realUniques;

  }

  /**
   * Devuelve la descripción completa de una tabla incluyendo los campos.
   * @param  string  $tableName Nombre de la tabla.
   * @return AmTable            Instancia de la tabla.
   */
  public function getTableFromScheme($tableName){

    // Obtener la descripcion basica
    $table = $this->getTableDescription($tableName);

    // Si no se encontró la tabla retornar falso
    if($table === false)
      return false;

    // Crear instancia anonima de la tabla
    $table = new AmTable(array_merge($table, array(

      // Asignar fuente
      'schemeName'    => $this->getName(),

      // Detalle de la tabla
      'fields'        => $this->getTableColumns($tableName),
      'referencesTo'  => $this->getTableForeignKeys($tableName),
      'referencesBy'  => $this->getTableReferences($tableName),
      'uniques'       => $this->getTableUniques($tableName),

    )));

    // Retornar tabla
    return $table;

  }

  /**
   * Crea una vista.
   * @param  AmQuery $q        Instancia de la consulta a crear.
   * @param  bool    $replace  Si se debe agregar la clausula OR REPLACE.
   * @return bool              Si se creó la vista.
   */
  public function sqlCreateView(AmQuery $q, $replace = true){

    $queryName = $this->nameWrapperAndRealScapeComplete($q->getName());

    $replace = $replace? $this->_sqlOrReplace() : '';

    return $this->_sqlCreateView($queryName, $q->sql(), $replace);

  }
  
  public function createView(AmQuery $q, $replace = true){

    return !!$this->execute($this->sqlCreateView($q, $replace));

  }

  /**
   * Eliminar una vista.
   * @param  string/AmQuery $q        Nombre o instancia de la consulta a
   *                                  eliminar.
   * @param  bool           $ifExists Si se debe agregar la clausula IF EXISTS.
   * @return bool                     Si se eliminó la vista
   */
  public function sqlDropView($q, $ifExists = true){
    
    if($q instanceof AmQuery){
      $q = $q->getName();
    }

    $queryName = $this->nameWrapperAndRealScapeComplete($q);
    
    $ifExists = $ifExists? $this->_sqlIfExists() : '';

    return $this->_sqlDropView($queryName, $ifExists);

  }
  
  public function dropView($q, $ifExists = true){

    return !!$this->execute($this->sqlDropView($q, $ifExists));

  }

  /**
   * SQL Para la cláusula SELECT.
   * @param  AmQuery $q Query.
   * @return string     SQL correspondiente.
   */
  public function sqlClauseSelect(AmQuery $q){

    $selects = trim($this->_sqlSelectGroup($q->getSelects()));

    if(empty($selects)){
      $selects = $this->_sqlSelectAll();
    }

    $distinct = '';
    if($q->getDistinct()){
      $distinct = $this->_sqlDistinct();
    }

    // Agregar SELECT
    return $this->_sqlSelect($selects, $distinct);

  }

  /**
   * Obtener el SQL para la clausula FROM.
   * @param  AmQuery $q Query.
   * @return string     SQL correspondiente.
   */
  public function sqlClauseFrom(AmQuery $q){

    // Unir argumentos procesados
    $froms = trim($this->_sqlFromGroup($q->getFroms()));

    if(!empty($froms)){
      return $this->_sqlFrom($froms);
    }

    return  '';

  }

  /**
   * Obtener el SQL para la clausula JOIN.
   * @param  AmQuery $q Query.
   * @return string     SQL correspondiente.
   */
  public function sqlClauseJoins(AmQuery $q){

    // Unir argumentos procesados
    $joins = $this->_sqlJoinGroup($q->getJoins());

    return empty($joins) ? '' : ' '.$joins;

  }

  /**
   * Obtener el SQL para la clausula WHERE.
   * @param  AmQuery $q Query.
   * @return string     SQL correspondiente.
   */
  public function sqlClauseWhere(AmQuery $q){

    $where = (string)$q->getWheres();

    if(!empty($where)){
      return $this->_sqlWhere($where);
    }

    return '';

  }

  /**
   * Obtener el SQL para la clausula ORDER BY.
   * @param  AmQuery $q Query.
   * @return string     SQL correspondiente.
   */
  public function sqlClauseOrders(AmQuery $q){

    // Unir resultado
    $orders = trim($this->_sqlOrderByGroup($q->getOrders()));

    if(!empty($orders)){
      return $this->_sqlOrderBy($orders);
    }

    return '';

  }

  /**
   * Obtener el SQL para la clausula GROUP BY.
   * @param  AmQuery $q Query.
   * @return string     SQL correspondiente.
   */
  public function sqlClauseGroups(AmQuery $q){

    // Unir resultado
    $groups = trim($this->_sqlGroupByGroup($q->getGroups()));

    if(!empty($groups)){
      return $this->_sqlGroupBy($groups);
    }

    return '';

  }

  /**
   * Obtener el SQL para la clausula LIMIT.
   * @param  AmQuery $q Query.
   * @return string     SQL correspondiente.
   */
  public function sqlClauseLimit(AmQuery $q){

    // Obtener limite
    $limit = $q->getLimit();

    if(isset($limit)){
      return $this->_sqlLimit($limit);
    }

    return '';

  }

  /**
   * Obtener el SQL para la clausula OFFSET.
   * @param  AmQuery $q Query.
   * @return string     SQL correspondiente.
   */
  public function sqlClauseOffset(AmQuery $q){

    // Obtener punto de partida
    $offset = $q->getOffset();
    $limit = $q->getLimit();

    if(isset($offset) && isset($limit)){
      return $this->_sqlOffset($offset);
    }

    return '';

  }

  /**
   * Obtener el SQL para la clausula SET de un query UPDATE.
   * @param  AmQuery $q Query.
   * @return string     SQL correspondiente.
   */
  public function sqlSets(AmQuery $q){

    // Obtener sets
    $sets = $q->getSets();

    // Recorrer los sets
    foreach($sets as $key => $set){

      $set['field'] = $this->nameWrapperAndRealScape($set['field']);

      // Acrear asignacion
      if($set['const'] === true){
        $sets[$key] = $this->_sqlSetItem($set['field'],
          $this->valueWrapperAndRealScape($set['value'])
        );

      }elseif($set['const'] === false){
        $sets[$key] = $this->_sqlSetItem($set['field'],
          $this->realScapeString($set['value'])
        );
      }

    }

    // Unir resultado
    $sets = $this->_sqlSetGroup($sets);

    // Agregar SET
    return $this->_sqlSet($sets);

  }

  /**
   * Devuelve el SQL de un query SELECT
   * @param  AmQuery $q Query.
   * @return string     SQL del query.
   */
  public function sqlQuerySelect(AmQuery $q){

    $select = $this->sqlClauseSelect($q);
    $select = empty($select)? '' : $select.' ';

    $from = $this->sqlClauseFrom($q);
    $from = empty($from)? '' : $from.' ';

    $joins = $this->sqlClauseJoins($q);
    $joins = empty($joins)? '' : $joins.' ';

    $where = $this->sqlClauseWhere($q);
    $where = empty($where)? '' : $where.' ';

    $groups = $this->sqlClauseGroups($q);
    $groups = empty($groups)? '' : $groups.' ';

    $orders = $this->sqlClauseOrders($q);
    $orders = empty($orders)? '' : $orders.' ';

    $limit = $this->sqlClauseLimit($q);
    $limit = empty($limit)? '' : $limit.' ';

    $offSet = $this->sqlClauseOffSet($q);
    $offSet = empty($offSet)? '' : $offSet.' ';

    return $this->_sqlQuerySelect($select, $from, $joins, $where, $groups, $orders, $limit, $offSet);

  }

  /**
   * Obtener el SQL para una consulta UPDATE.
   * @param  AmQuery $q Query.
   * @return string     SQL del query.
   */
  public function sqlQueryUpdate(AmQuery $q){

    $tableName = $this->nameWrapperAndRealScape($q->getTable()->getTableName());

    $joins = $this->sqlClauseJoins($q);
    $joins = empty($joins)? '' : $joins.' ';

    $sets = $this->sqlSets($q);
    $sets = empty($sets)? '' : $sets.' ';

    $where = $this->sqlClauseWhere($q);

    return $this->_sqlQueryUpdate($tableName, $joins, $sets, $where);

  }

  /**
   * Obtener el SQL para una consulta DELETE.
   * @param  AmQuery $q Query.
   * @return string     SQL del query.
   */
  public function sqlQueryDelete(AmQuery $q){

    $tableName = $this->nameWrapperAndRealScape($q->getTable()->getTableName());

    $where = $this->sqlClauseWhere($q);
    $where = empty($where)? '' : $where.' ';

    return $this->_sqlQueryDelete($tableName, $where);

  }

  /**
   * Obtiene el SQL de una Query dependiendo de su tipo.
   * @param  AmQuery $q Instancia de query.
   * @return string     SQL obtenido.
   */
  public function sqlOf(AmQuery $q){
    $type = $q->getType();

    // Consulta de seleción
    if($type == 'select'){
      return $this->sqlQuerySelect($q);
    }

    // Consulta de inserción
    if($type == 'insert'){
      return $this->sqlInsert($q, $q->getInsertTable(), $q->getInsertFields());
    }

    // Consulta de actualización
    if($type == 'update'){
      return $this->sqlQueryUpdate($q);
    }

    // Consulta de eliminación
    if($type == 'delete'){
      return $this->sqlQueryDelete($q);
    }

    throw Am::e('AMSCHEME_QUERY_TYPE_UNKNOW', var_export($q, true));

  }

  //////////////////////////////////////////////////////////////////////////////
  // Metodos para obtener los SQL a ejecutar.
  //////////////////////////////////////////////////////////////////////////////
  /**
   * Devuelve el SQL de un query INSERT.
   * @param  array/AmQuery  $values Array hash de valores, array
   *                                de instancias de AmModels, array de
   *                                AmObjects o AmQuery con consulta select
   *                                a insertar.
   * @param  string/AmTable $model  Nombre del modelo o instancia de la
   *                                tabla donde se insertará los valores.
   * @param  array          $fields Campos que recibirán con los valores que
   *                                se insertarán.
   * @return string                 SQL del query.
   */
  public function sqlInsert($values, $model, array $fields = array()){

    $table = $model;

    // Si los valores es una instancia de AmModel entonces convierte en un array
    // que contenga solo dicha instancia.
    if($values instanceof AmModel){
      $values = array($values);

    }

    // Obtener la instancia de la tabla
    if(!$table instanceof AmTable){
      $table = $this->getTableInstance($table);
    }

    if($table){

      // Agregar fechas de creacion y modificacion si existen en la tabla
      $table->setAutoCreatedAt($values);
      $table->setAutoUpdatedAt($values);
      $table = $table->getTableName();

    }else{

      $table = $model;

    }

    if($values instanceof AmQuery){

      // Si los campos recibidos estan vacíos se tomará
      // como campos los de la consulta
      if(count($fields) == 0){
        $fields = array_keys($values->getSelects());
      }

      // Los valores a insertar son el SQL de la consulta
      $values = $values->sql();

    // Si los valores es un array con al menos un registro
    }elseif(is_array($values) && count($values)>0){

      // Indica si
      $mergeWithFields = empty($fields);

      $rawValues = array();

      // Recorrer cada registro en $values par obtener los valores a insertar
      foreach($values as $i => $v){

        // Si el registro es AmModel obtener sus valores como array asociativo o simple
        if($v instanceof AmModel){
          $values[$i] = $v->getTable()->dataToArray($v, !$mergeWithFields);
          $rawValues[$i] = $v->getRawValues();

        // Si es una instancia de AmObjet se obtiene como array asociativo
        }elseif($v instanceof AmObject){
          $values[$i] = $v->toArray();
          
        }

        // Si no se recibieron campos, entonces se mezclaran con los indices obtenidos
        if($mergeWithFields){
          $fields = merge_unique($fields, array_keys($values[$i]));
        }

      }

      // Preparar registros para crear SQL
      $resultValues = array();
      foreach($values as $i => $v){

        // Asignar array vacío
        $resultValues[$i] = array();

        // Agregar un valor por cada campo de la consulta
        foreach($fields as $f){
          $val = $this->realScapeString(isset($v[$f])? $v[$f] : null);
          
          // Obtener el valor del registro actual en el campo actual
          if(isset($rawValues[$i][$f]) && $rawValues[$i][$f] === true){
            $resultValues[$i][] = $val;
          }else{
            $resultValues[$i][] = $this->valueWrapper($val);
          }

        }

      }

      // Asignar nuevos valores
      $values = $resultValues;

    }

    // Obtener el listado de campos
    foreach ($fields as $key => $field){
      $fields[$key] = $this->nameWrapperAndRealScape($field);
    }

    // Unir campos
    $fields = $this->_sqlInsertFields($this->_sqlInsertFieldsGroup($fields));

    if(is_array($values)){

      if(!empty($values)){

        // Preparar registros para crear SQL
        foreach($values as $i => $v){
          // Unir todos los valores con una c
          $values[$i] = $this->_sqlInsertValuesItem(
            $this->_sqlInsertValuesItemGroup($v)
          );
        }

        // Unir todos los registros
        $values = $this->_sqlInsertValuesGroup($values);

        // Obtener Str para los valores
        $values = $this->_sqlInsertValues($values);

      }else{
        $values = '';
        
      }

    }

    if(empty($values)){
      return '';
    }

    $table = $this->nameWrapperAndRealScapeComplete($table);

    // Generar SQL
    return $this->_sqlInsert($table, $fields, $values);

  }

  /**
   * Inserta registros en una tabla.
   * @param  array/AmQuery  $values Array hash de valores, array de instancias
   *                                de AmModels, array de AmObjects o AmQuery
   *                                con consulta select a insertar.
   * @param  string/AmTable $model  Nombre del modelo o instancia de la tabla
   *                                donde se insertará los valores.
   * @param  array          $fields Campos que recibirán con los valores que se
   *                                insertarán.
   * @return bool/int               Boolean se se logró insertar los registros,
   *                                o el id del registro insertado en el caso
   *                                de corresponda.
   */
  public function insertInto($values, $model, array $fields = array()){

    // Obtener el SQL para saber si es valido
    $sql = $this->sqlInsert($values, $model, $fields);

    // Si el SQL está vacío o si se genera un error en la inserción devuelve
    // falso
    if(trim($sql) == '' || $this->execute($sql) === false){
      return false;
    }
    
    // De lo contrario retornar verdadero.
    return true;

  }

  /**
   * Prepara una columna para ser creada en una tabla de la BD.
   * @param  array  $column Datos de una columna.
   * @return string
   */
  public function sanitize(array $column){
    // Si no se encuentra el tipo se retorna el tipo recibido

    $nativeType = $column['type'];
    $column['type'] = itemOr($column['type'], $this->types, $column['type']);

    // Parse bool values
    $column['pk'] = parseBool($column['pk']);
    $column['allowNull']  = parseBool($column['allowNull']);

    // Get len of field
    // if is a bit, char or varchar take len
    if(in_array($nativeType, array('char', 'varchar')))
      $column['len'] = itemOr('len', $column);

    elseif($nativeType == 'bit')
      $column['len'] = itemOr('precision', $column);

    // else look len into bytes used for native byte
    else
      $column['len']  = itemOr($nativeType, array_merge(
        $this->getLenSubTypes('int'),
        $this->getLenSubTypes('float'),
        $this->getLenSubTypes('text')
      ));

    if(in_array($column['type'], array('int', 'float'))){

      $column['unsigned'] = preg_match('/unsigned/',
        $column['columnType']) != 0;

      $column['zerofill'] = preg_match('/unsigned zerofill/',
        $column['columnType']) != 0;

      $column['autoIncrement'] = preg_match('/auto_increment/',
        $column['extra']) != 0;

    }

    // Unset scale is not is a float
    if($column['type'] != 'float')
      unset($column['precision'], $column['scale']);

    else
      $column['scale'] = itemOr('scale', $column, 0);

    // Unset columnType an prescicion
    unset($column['columnType']);

    // Drop auto_increment of extra param
    $column['extra'] = trim(str_replace('auto_increment', '', $column['extra']));

    // Eliminar campos vacios
    foreach(array(
      'defaultValue',
      'collation',
      'charset',
      'len',
      'extra'
    ) as $attr)
      if(!isset($column[$attr]) || trim($column[$attr])==='')
        unset($column[$attr]);

    return $column;
    
  }

  //////////////////////////////////////////////////////////////////////////////
  // Metodos estáticos
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Devuelve la configuración de un determinado esquema.
   * @param  string $name Nombre del esquema buscado.
   * @return hash         Configuración del esquema.
   */
  public static function getConf($name = ''){

    // Obtener configuraciones para las fuentes
    $schemes = Am::getProperty('schemes', array());

    // Si no existe una configuración para el nombre de fuente
    if(!isset($schemes[$name]))
      return null;

    // Asignar valores por defecto
    $schemes[$name] = array_merge(
      array(
        'database'  => $name,
        'driver'    => null,
      ),
      $schemes[$name]
    );

    $schemes[$name]['name'] = $name;

    return $schemes[$name];

  }

  /**
   * Devuelve una instancia de una fuente.
   * @param  string   $name Nombre del esquema buscado.
   * @return AmScheme       Instancia del esquema.
   */
  public static function get($name = ''){

    // Obtener la instancia si ya existe
    if(isset(self::$schemes[$name]))
      return self::$schemes[$name];

    // Obtener la configuración de la fuente
    $schemeConf = self::getConf($name);

    // Si no existe una configuración para el nombre de fuente solicitado se
    // retorna NULL
    if($schemeConf === null)
      throw Am::e('AMSCHEME_SCHEMECONF_NOT_FOUND', $name);

    // Obtener el driver de la fuente
    $driverClassName = self::driver($schemeConf['driver']);

    // Crear instancia de la fuente
    $schemes = new $driverClassName($schemeConf);

    return self::$schemes[$name] = $schemes;

  }

  /**
   * Incluye un driver de BD.
   * @param  string $driver Nombre del driver a incluir.
   * @return string         Nombre de la clase del driver a incluir.
   */
  public static function driver($driver){

    // Obtener el nombre de la clase
    $driverClassName = camelCase($driver, true).'Scheme';

    // Se retorna en nombre de la clase
    return $driverClassName;

  }

  /**
   * Incluye un modelo.
   * @param  string      $model Nombre del modelo a insertar. Puede ser un
   *                            modelo base :<modelName>@<schemeName> o el
   *                            nombre del modelo dado por el usuario.
   * @return string/bool        Si al final de la inclusión existe la clase
   *                            correspondiente devuelve el nombre de la clase,
   *                            de lo contrario devuelv falso.
   */
  public static function model($model){

    // Si es un modelo nativo
    if(preg_match('/^:(.*)@(.*)$/', $model, $m) ||
      preg_match('/^:(.*)$/', $model, $m)){

      // Si no se indica la fuente tomar la fuente por defecto
      if(empty($m[2]))
        $m[2] = '';
      
      $scheme = self::get($m[2]);

      $model = $scheme->getBaseModelClassName($m[1]);

    }

    // Retornar el nombre de la clase del modelo correspondiente
    return class_exists($model)? $model : false;

  }

  /**
   * Incluye un validador y devuelve el nombre de la clases correspondiente
   * @param  string $validator Nombre del validador a insertar.
   * @return string            Nombre de la clase del validador.
   */
  public static function validator($validator){

    // Obtener el nombre de la clase
    $validatorClassName = camelCase($validator, true).'Validator';

    // Se retorna en nombre de la clase
    return $validatorClassName;

  }

  //////////////////////////////////////////////////////////////////////////////
  // Metodos abstractos que deben ser definidos en las implementaciones
  //////////////////////////////////////////////////////////////////////////////

  /**
   * Metodo para obtener el puerto por defecto para una conexión.
   * @return string/int Devuelve el nro del purto por defecto.
   */
  abstract public function getDefaultPort();

  /**
   * Metodo para crear una conexion.
   * @return Resource Manejador para la conexión 
   */
  abstract protected function start();

  /**
   * Metodo para cerrar una conexión.
   * @return bool Resultado de la operación
   */
  abstract public function close();

  /**
   * Obtener el número del último error generado en la conexión.
   * @return int Nro de error.
   */
  abstract public function getErrNo();

  /**
   * Obtener la descripcion del último error generado en la conexión
   * @return string Descripción del error.
   */
  abstract public function getError();

  /**
   * Obtiene una cadena con un valor seguro para el manejador de DBSM.
   * @param  mixed  $value Valor que se desea procesar.
   * @return string        Valor procesado.
   */
  abstract public function realScapeString($value);

  /**
   * Realizar una consulta SQL.
   * @param  string   $sql SQL a ejecutar.
   * @return bool/int      Resultado de la operación.
   */
  abstract protected function query($sql);

  /**
   * Obtener el siguiente registro de un resultado
   * @param  Resourse $result Manejador del resultado.
   * @return hash             Hash de valores del registro.
   */
  abstract public function getFetchAssoc($result);

  /**
   * Obtener el ID del ultimo registro insertado. En el caso que el último
   * query ejecutado sea un insert de un solo elemento en una tabla con un solo
   * campo autonumérico.
   * @return null/int Null o valor autonumérico insertado.
   */
  abstract public function getLastInsertedId();

  /**
   * Ingresa el nombre de un objeto de la BD dentro de las comillas
   * correspondientes.
   * @param  string $name Nombre que se desea entre comillas.
   * @return string       Nombre entre comillas.
   */
  abstract public function nameWrapper($name);

  /**
   * Devuelve una cadena de caracteres entre comillas.
   * @param  string $string Cadena que se desea entre comillas.
   * @return string         Cadena entre comillas.
   */
  abstract public function valueWrapper($string);
  
  /**
   * Query para obtener la información de una BD.
   * @return string SQL para la operación.
   */
  abstract public function queryGetInfo();

  /**
   * Query para obtener la descripción de las tablas del esquema.
   * @return string SQL para la operación.
   */
  abstract public function queryGetTables();
  
  /**
   * SQL para obtener la descripción de las columnas de una tabla.
   * @param  string $table Nombre de la tabla.
   * @return string        SQL para la operación.
   */
  abstract public function queryGetTableColumns($table);
  
  /**
   * SQL para obtener la descripción de las claves unicas de una tabla.
   * @param  string $table Nombre de la tabla.
   * @return string        SQL para la operación.
   */
  abstract public function queryGetTableUniques($table);
  
  /**
   * SQL para obtener la descripción de las claves foráneas de una tabla.
   * @param  string $table Nombre de la tabla.
   * @return string        SQL para la operación.
   */
  abstract public function queryGetTableForeignKeys($table);
  
  /**
   * SQL para obtener la descripción de las referencias a una tabla.
   * @param  string $table Nombre de la tabla.
   * @return string        SQL para la operación.
   */
  abstract public function queryGetTableReferences($table);

}