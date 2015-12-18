<?php
/**
 * Amathista - PHP Framework
 *
 * @author Alex J. Rondón <arondn2@gmail.com>
 * 
 */

/**
 * -----------------------------------------------------------------------------
 * Clase para crear respuestas
 * -----------------------------------------------------------------------------
 */
class AmResponse{

  protected
    /**
     * -------------------------------------------------------------------------
     * Propiedades de la petición.
     * -------------------------------------------------------------------------
     */
    $__p = array(

      // Listado de headers a agregar para la petición
      'headers' => array(),

      // Indica si se resolvío o no la petición
      'resolved' => true,

    );

    /**
     * -------------------------------------------------------------------------
     * Constructor de la Clase.
     * -------------------------------------------------------------------------
     * Inicializa la propiedad __p con una instancia de AmObject. Las
     * propiedades son las indicadas en $this->__p mas la recibidas por
     * parámetro $data
     */
    public function __construct($data = null){
      $this->__p = new AmObject(array_merge(
        $this->__p,
        AmObject::parse($data)
      ));
    }

  /**
   * ---------------------------------------------------------------------------
   * Responde con un archivo indicado por parámetro.
   * ---------------------------------------------------------------------------
   * @param   string  $filename     Ruta del archivo con el que se responderá.
   * @param   bool    $attachment   Si la ruta se descarga o no.
   * @return  any                   Respuesta de manejador configurado.
   */
  public static function file($filename, $attachment = false){
    return (new AmFileResponse)
      ->filename($filename)
      ->attachment($attachment);
  }

  /**
   * ---------------------------------------------------------------------------
   * Busca una llamada como función, método estático de una clase o llamada
   * a controlador.
   * ---------------------------------------------------------------------------
   * @param   string $callback  String que identifica el controlador a buscar.
   * @param   array  $env      Variables de entorno.
   * @param   array  $params   Argumentos obtenidos de la ruta.
   * @return  any    Respuesta de manejador configurado.
   */
  public static function call($callback, array $env = array(),
                              array $params = array()){
    return (new AmCallResponse)
      ->callback($callback)
      ->env($env)
      ->params($params);
  }
  /**
   * ---------------------------------------------------------------------------
   * Busca un template y lo renderiza.
   * ---------------------------------------------------------------------------
   * @param   string  $tpl      Template a renderizar.
   * @param   array   $env      Variables de entorno
   * @return  any               Respuesta de manejador configurado.
   */
  public static function template($tpl, array $env = array()){
    return (new AmTemplateResponse)
      ->tpl($tpl)
      ->params($env);
  }

  /**
   * ---------------------------------------------------------------------------
   * Redirigir a una URL.
   * ---------------------------------------------------------------------------
   * @param   string $url   URL que se desea ir.
   */
  public static function go($url){

    return (new AmRedirectResponse)
      ->url($url);

  }

  /**
   * ---------------------------------------------------------------------------
   * Agrega las cabeceras para indicar un error 404 a la respuesta.
   * ---------------------------------------------------------------------------
   * @param   string  $msg  $mensaje para el error a 404.
   */
  public static function e404($msg = null){

    if(!$msg)
      $msg = Am::t('AM_NOT_FOUND');

    return (new AmResponse())
      ->resolved(false)
      ->addHeader("HTTP/1.0 404 {$msg}")
      ->addHeader("Status: 404 {$msg}");

  }

  /**
   * ---------------------------------------------------------------------------
   * Devuelve el valor de una propiedad.
   * ---------------------------------------------------------------------------
   */
  public function get($propertyName){
    return $this->__p->$propertyName;
  }

  public function set($propertyName, $value){
    $this->__p->$propertyName = $value;
    return $this;
  }

  /**
   * ---------------------------------------------------------------------------
   * Devuelve si la petición fue resuelta o no.
   * ---------------------------------------------------------------------------
   * @return boolean  Devuelve si la petición fué resuelta o no
   */
  public function isResolved(){
    return $this->__p->resolved;
  }

  /**
   * ---------------------------------------------------------------------------
   * Asignar Si la petición se resolvió o no.
   * ---------------------------------------------------------------------------
   * @param  bool   $resolved   
   * @return this
   */
  public function resolved($resolved = true){
    $this->__p->resolved = $resolved;
    return $this;
  }

  /**
   * -------------------------------------------------------------------------
   * Agrega un header al listado de headers de la respuesta
   * -------------------------------------------------------------------------
   * @param string $header Header a agregar
   */
  public function addHeader($header, $key = null){
    if(isset($key))
      $this->__p->headers[$key] = $header;
    else
      $this->__p->headers[] = $header;
    return $this;
  }

  /**
   * -------------------------------------------------------------------------
   * Método para ejecutar la respuesta
   * -------------------------------------------------------------------------
   */
  public function make(){

    foreach ($this->__p->headers as $header) {
      header($header);
    }
    
  }

}