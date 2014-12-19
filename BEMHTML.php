<?php
require_once 'Templates.php';
/**
 *
 * @version 0.1.13
 */
class BEMHTML{
  
  protected $ctx = array(
      '_isBem'=> false,
      '_isBlock'=>false,
      '_isElem' =>false,
      '_parent'=>false,      
      '_position'=> 0,
      '_mode' => NULL,
      '_modName'=> false,
      '_modVal' => false,
      '_src' => NULL,
      '_buff'=>'', //Строковый буффер
      
      'block'=> false,
      'elem' => false,
      'tag'=>'',
      'mods'=>array(),// [modName]=>modVal
      'cls' =>array(),
      'mix' =>array(),
      'attrs'=>array(), //[attr]=> value
      'js'=>false,
      'content'=>''
  ); 
  /**
   * Список коротких тегов
   * @var array
   */
   static $shortTags = array(
      'input',
      'img',
      'link',
      'meta',
      'param',
      'source',
      'area',
      'base',
      'param',
      'command',
      'col',
      'embed',
      'br',
      'hr'
  );
   /**
    * Разделитель модификаторов
    * @var string
    */
  static $MD = '_';
  
  /**
   * Разделитель элементов
   * @var string
   */
  static $ED = '__';
  
  /**
   * Шаблон блока
   * @var array
   */
  private $_tpl = array();
  
  function __construct($ctx,$parent=false) {
    $this->ctx = @array_merge($ctx, $this->ctx);
    $this->ctx['_parent'] = $parent? $parent['block'] : false;
    $this->ctx['_isBlock'] = self::is_block($ctx);
    $this->ctx['_isElem']  = self::is_elem($ctx);
    $this->ctx['_isBem'] = (($this->ctx['_isBlock']) or ($this->ctx['_isElem']));
   
   //@deprecated
    $this->ctx['_src'] = $ctx;

    if($this->ctx['_isBem']){    
      $this->for_bem($ctx);
    }elseif(is_string($ctx)){
        $this->ctx['_buff'] = $ctx;
    }    
    
  }
  /**
   * Главный метод, преобразует БЭМ сущность в HTML строку
   * @param array $ctx БЭМ сущность
   * @param type $parent родительский блок
   * @return string готовый HTML
   */
  public static function apply($ctx,$parent=false){
    $bemhtml = new BEMHTML($ctx,$parent);
    return $bemhtml->_apply();
  }
  
  function _apply(){
    
    if(!$this->ctx['_isBem']){
      $str ='';
      $this->ctx['block'] = $this->ctx['_parent']; //проброс блока в массив
      if(is_array($this->ctx['_src'])){
        foreach ($this->ctx['_src'] as $item){
          $str .= self::apply($item,  $this->ctx);
        }
      }elseif(is_string($this->ctx['_src'])){
        return $this->ctx['_buff'];
      }
     return $str;
    }

    return $this->ctx['_buff'];
  }
  
  /*Стандартные моды*/
  
  /**
   * Стандатрная мода.
   * Управляет всеми остальными модами
   */
  function mod_def(){
    $this->ctx['_mode'] = 'def';
    $this->ctx['_buff']='';
    if(array_key_exists('def',$this->_tpl) && is_callable($this->_tpl['def'])){
     return call_user_func_array($this->_tpl['def'], array(&$this->ctx));
    }   
    /*Bem*/
    $this->ctx['attrs']['class'][] = $this->mod_bem();
    
    /*Mods*/
    $this->ctx['mods'] = $this->mod_mods();
    if(is_array($this->ctx['mods'])):
    foreach ($this->ctx['mods'] as $modName => $modVal){
      if(!$modVal)
        continue;
      
      $this->ctx['_modName'] = $modName;
      $this->ctx['_modVal'] = $modVal;
      $class = self::$MD.$modName;
      $class .= !is_bool($modVal)? self::$MD.$modVal : '';
      $class = $this->ctx['_isBlock']? $this->ctx['block'].$class : $this->ctx['block'].self::$ED.$this->ctx['elem'].$class;
      $this->ctx['attrs']['class'][] = $class;
      $this->get_template();//Перевыбор шаблона
    }
    endif;
    
    /*Tag*/
    $this->ctx['tag'] = $this->mod_tag($this->ctx);
    $this->ctx['_isShort'] = in_array($this->ctx['tag'], self::$shortTags);
    /*Mix*/
    $this->ctx['mix'] = $this->mod_mix();
    
    
    /*Cls*/
    $this->ctx['cls'] = $this->mod_cls();
    if(is_array($this->ctx['mix'])):
    foreach($this->ctx['mix'] as $item){
      switch ($item) {
        case self::is_block($item):
          $this->ctx['cls'][] = $item['block'];
          break;
        
        case self::is_elem($item):
          $this->ctx['cls'][] = Arr::get($item,'block',$this->ctx['block']).self::$ED.$item['elem'];
          break;
        
        default:
          break;
      }      
    }
    endif;
    /*JS*/
    $this->ctx['js'] = $this->mod_js();
     if($this->ctx['js']){
      foreach ($this->ctx['js'] as $blockName => $js){
        if (is_bool($js)){
          $this->ctx['js'][$blockName] = new ArrayObject();
        }
      } 
      $str = json_encode($this->ctx['js'], 256);
      $str = str_replace('"', '&quot;', $str);
      $this->ctx['attrs'][$this->mod_jsAttr()] = $str;
      $this->ctx['attrs']['class'][] = 'i-bem';
    }
    
    /*Content*/
    $this->ctx['content'] = $this->mod_content($this->ctx);
    
    /*Attrs*/
    $this->ctx['attrs']['class'] = array_merge($this->ctx['attrs']['class'], $this->ctx['cls']);    
    $this->ctx['attrs'] = $this->mod_attrs();
    $attrs = '';
    foreach($this->ctx['attrs'] as $attr => $values){
      $val = is_array($values)? implode(" ",$values):$values;
      $attrs .= ' '.$attr.'="'.$val.'"' ;
    }
    /*Запись строк в буфер*/
    $this->ctx['_buff'] .= "<{$this->ctx['tag']}".$attrs;
    $this->ctx['_buff'] .= !$this->ctx['_isShort']?'>':'';
    $this->ctx['_buff'] .= !is_array($this->ctx['content'])? $this->ctx['content'] :
      BEMHTML::apply($this->ctx['content'],$this->ctx);
    $this->ctx['_buff'] .= !$this->ctx['_isShort']? "</{$this->ctx['tag']}>" : "/>";
    return $this->ctx;
  }
  
  /**
   * Выбор тега для блока
   * @param array $ctx текущий контекст
   * @return string выбранный тег
   */
  function mod_tag($ctx){
    $ctx['_mode'] = 'tag';
    if(array_key_exists('tag',$ctx['_src'])){
      return $ctx['_src']['tag'];
    }
    
    if(is_callable(Arr::get($this->_tpl, 'tag'))){
      return call_user_func_array($this->_tpl['tag'], array($ctx));
    }else{
     return Arr::get($this->_tpl, 'tag','div');
    }
    
  }
  
  /**
   * Cобирает основные бэм классы.
   * Класс блока, если блок, или элемента, если элемент
   * @return string 
   */
  function mod_bem(){
    $this->ctx['_mode']= 'bem';
    if(is_callable(Arr::get($this->_tpl,'bem'))){
      return call_user_func_array($this->_tpl['bem'], array($this->ctx));
    }
    $this->ctx['bem'] = $this->ctx['_isBlock'] ? $this->ctx['block'] : Arr::get($this->ctx,'block', Arr::get($this->ctx['_parent'],'block')).self::$ED.$this->ctx['elem'];   
       
    return $this->ctx['bem'];
  }
  
  /**
   * Собирает модификаторы блока
   * @return array модификаторы
   */
  function mod_mods(){
    $this->ctx['_mode']= 'mods';
    if(is_callable(Arr::get($this->_tpl,'mods'))){
      return call_user_func_array($this->_tpl['mods'], array($this->ctx));
    }
    if(array_key_exists('mods',$this->_tpl)){
      return Arr::merge($this->ctx['mods'], $this->_tpl['mods']);
    }   
    
    return $this->ctx['mods'];    
      
  }
  
  /**
   * Собирает миксы блока
   * @return array
   */
  function mod_mix(){
    $this->ctx['_mode']= 'mix';
    if(is_callable(Arr::get($this->_tpl,'mix'))){
      return call_user_func_array($this->_tpl['mix'], array($this->ctx));
    }
    if(is_array(Arr::get($this->_tpl,'mix')))
    {
      array_push($this->ctx['mix'], $this->_tpl['mix']);
    }
    return $this->ctx['mix'];
    
  }
  
  /**
   * Устанавливает дополнительные классы
   * @return array
   */
  function mod_cls(){
    $this->ctx['_mode']= 'cls';
    if(is_callable(Arr::get($this->_tpl,'cls'))){
      return call_user_func_array($this->_tpl['cls'], array($this->ctx));
    }
    if(is_array(Arr::get($this->_tpl,'cls'))){
      return array_push($this->ctx['cls'], $this->_tpl['cls']);
    } 
   
    return $this->ctx['cls'];
  }
  
  /**
   * Собирает все js-параметры блоков на данной БЭМ сущности
   * @return array js-параметры
   */
  function mod_js(){
    $this->ctx['_mode']= 'js';
    if(is_callable(Arr::get($this->_tpl,'js'))){
      return call_user_func_array($this->_tpl['js'], array($this->ctx));
    }
    
    $a = array();
   
    if($this->ctx['js']){
      $a[$this->ctx['block']] = $this->ctx['js'];
    }
     if(is_array(Arr::get($this->_tpl,'js'))){
      $a[$this->ctx['block']] = array_merge($this->_tpl['js'], is_array($this->ctx['js'])? $this->ctx['js']: array() );
    } 
    if(!empty($this->ctx['mix'])){
      foreach ($this->ctx['mix'] as $block){
        if(is_array($block)){
          if (array_key_exists('js', $block)){
            $a[$block['block']] = $block['js'];
          }
        }
      }
    }
    
    return $a;
  }
  
  /**
   * Выбор атрибута в котором хранятся js-параметры
   * @return string Атрибут
   */
  function mod_jsAttr(){
    return 'data-bem';
  }
  
  /**
   * Все атрибуты блока
   * @return array атрибуты
   */
  function mod_attrs(){
    $this->ctx['_mode']= 'attrs';
    if(is_callable(Arr::get($this->_tpl,'attrs'))){
      return call_user_func_array($this->_tpl['attrs'], array($this->ctx));
    }
    if(is_array(Arr::get($this->_tpl,'attrs')))
    {
      array_push($this->ctx['attrs'], $this->_tpl['attrs']);
    }       
    return array_merge(Arr::get($this->ctx['_src'],'attrs',array()),$this->ctx['attrs']);
  }
  
  /**
   * Отвечает за содержимиое блока
   * @return array Содержимое блока
   */
  function mod_content(){
    $this->ctx['_mode'] = 'content';
    if(is_callable(Arr::get($this->_tpl,'content'))){
      return call_user_func_array($this->_tpl['content'], array($this->ctx));
    }else{
     return Arr::get($this->_tpl, 'content',$this->ctx['content']);
    }
  }  
  /*-- End Mods--*/

  /*Вспомогательные функции*/
  
  /**
   * Проверяет, является ли переданная сущность блоком
   * @param array $ctx сущность
   * @return bool блок
    */
  static function is_block($ctx){
    if(is_array($ctx))
    return (array_key_exists('block', $ctx)) and (!array_key_exists('elem', $ctx));

    return false;
  }
  /**
   * Проаеряет, является ли переданная сущность элементом
   * @param array $ctx сущность
   * @return bool элемент
   */
  static function is_elem($ctx){
    if(is_array($ctx))
    return array_key_exists('elem', $ctx);

    return false;
  }
  
  /**
   * Проверяет, является ли переданный массив БЭМ сущностью
   * @param type $ctx Сущность
   * @return type БЭМ
   */
  static function is_bem($ctx){
    return (self::is_block($ctx)) or (self::is_elem($ctx));
  }
  
  /**
   * Нормализация блока. 
   * Если не указан один из параметров, берем его из родительского блока
   */
  function normalize(){   
    $this->ctx['block'] = $this->ctx['_isBlock']? $this->ctx['_src']['block'] : $this->ctx['_parent'];
    if($this->ctx['_isElem']){      
      $this->ctx['elem'] = $this->ctx['_src']['elem'];
    }
  }
  
  /**
   * Порядок действий для блока
   * @param type $ctx
   */
   function for_bem($ctx){
     $this->ctx['content'] = Arr::get($ctx,'content');   
     $this->ctx['mix'] = Arr::get($ctx,'mix');
     $this->ctx['mods'] = Arr::get($ctx,'mods');
     $this->ctx['js'] = Arr::get($ctx,'js',false);
     $this->normalize();
      /*Применяем шаблон*/
     $this->get_template();
     
     //Вызываем станартную моду
     $this->mod_def();
   }
   
   /**
    * Выбор шаблоко для блока
    */
   function get_template()
   {
     $this->_tpl = array_merge($this->_tpl, Template::get()->apply($this->ctx));
   }
       
  
}

