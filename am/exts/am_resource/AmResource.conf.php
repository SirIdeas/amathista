<?php
/**
 * Amathista - PHP Framework
 *
 * @author Alex J. Rondón <arondn2@gmail.com>
 * 
 */

return array(
  
  //////////////////////////////////////////////////////////////////////////////  
  // Directorio hijo donde se buscará las vistas
  'views' => 'views',

  //////////////////////////////////////////////////////////////////////////////
  // Prefijos
  'prefixs' => array(
    'actions'     => 'action_',
    'getActions'  => 'get_',
    'postActions' => 'post_',
    'filters'     => 'filter_',
  ),

  //////////////////////////////////////////////////////////////////////////////
  // Acciones permitidas
  'allows' => array(
    ''        => false,
    'index'   => true,
    'new'     => true,
    'edit'    => true,
    'cou'     => true,
    'delete'  => true,
    'detail'  => true,
    'data'    => true,
  ),

  //////////////////////////////////////////////////////////////////////////////
  // Filtros
  'filters' => array(
    'before' => array(
      'loadRecord' => array('detail', 'edit', 'delete')
    )
  ),

  //////////////////////////////////////////////////////////////////////////////
  // Entorno por defecto del recurso
  'env' => array(

    ////////////////////////////////////////////////////////////////////////////
    // Parámetros del recursos
    'model' => null,

    ////////////////////////////////////////////////////////////////////////////
    // Campos para los formularios y para los detalles
    'fields' => array(),

    ////////////////////////////////////////////////////////////////////////////
    // Nombre del formulario
    'form' => null,

  ),

);
