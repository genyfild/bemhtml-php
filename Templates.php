<?php
/**
 * @version 0.2.1
 */
class Template{
  public $templates = array();
  static $MD = '_';
  static $ED = '__';

  
  private static $instance;
  
  protected function __construct() {
    $this->_template();
  }
  
  public static function get(){
    if (empty(self::$instance)){
      self::$instance = new Template();
    }
    return self::$instance;
  }
  
  /**
   * 
   * @param type $ctx
   * $ctx = array(
      '_isBem'=> false,
      '_isBlock'=>false,
      '_isElem' =>false,
      '_parent'=>false,      
      '_position'=> 0,
      '_mode' => NULL,
      '_modName'=> NULL,
      '_modVal' => NULL,
      '_src' => NULL,
      
      'block'=> false,
      'elem' => false,
      'tag'=>null,
      'mods'=>array(),// [modName]=>modVal
      'cls' =>array(),
      'attrs'=>array(), //[attr]=> value
      'js'=>false,
      'content'=>''
  ); 
   * @return type
   */
  function apply($ctx){
    return  $this->get_template($ctx['block'],$ctx['elem'],$ctx['_modName'], $ctx['_modVal']);
//    $this->ctx = $ctx;
//    $this->ctx = $this->mod_def($this->ctx);   
//    return $this->ctx;
//    //return BEM::tpl($this->template);
  }
  
 
  
  function get_template($b, $e, $mName, $mVal){
    $tplName = $b;
    if($e){      
      $tplName = $b.self::$ED. $e;
    }
    if($mName){
      $tplName .= self::$MD. $mName;
    }
    if(!is_bool($mVal)){
      $tplName .= self::$MD. $mVal;
    }
    
   
      if(!array_key_exists($tplName, $this->templates)) 
        {return array();}     
      return $this->templates[$tplName];
  }

  public function load_templates(array $tpls){
    $this->templates = array_merge_recursive($this->templates, $tpls);
  }
  
  /*template functions*/
  function _template(){
    $this->templates['page'] = array(
      'tag'=>'html', //мода tag
      'attrs'=>function($ctx){return false;},
      'content'=> function($ctx){       
        return array(
            array('elem'=>'head'),
            array('elem'=>'body',  
                'content'=>$ctx['content']
                  ),                   
            );
      }
    );
    $this->templates['page__head'] = array(
        'tag' => 'head',
        'attrs'=>function($ctx){return false;}
        );
    $this->templates['page__body'] = array(
        'tag'=>'body',
        'bem'=>'page',
        'content'=> function($ctx){
          return array(
              'block'=>'page',
              'elem'=>'wrapper',
              'content'=>$ctx['content']
          );
        } 
    );
        
    $this->templates['section'] =array(
        "tag"=>"section",
        "attrs"=>function($ctx){
          $i = ['id'=>'section_name_'.$ctx['mods']['name']];
          return is_array($ctx['attrs'])?array_merge($i,$ctx['attrs']):$i;
        },
        "content"=> function($ctx){
            return array(
                "block"=>"container",                
                "content"=>array(
                  'block'=>$ctx['mods']['name'],
                  'content'=> $ctx['content']
                )
            );
        }
    );

    $this->templates['link'] = array(
      'tag'=>'a',
      'attrs'=>function($ctx){
        return array_merge(array('href'=>$ctx['url']),$ctx['attrs']);
      },
      'content'=>function($ctx){
        return $ctx['text'];
      }
    );

    $this->templates['phone__num'] = array('tag'=>'span');
    $this->templates['phone__desc'] = array('tag'=>'span');

    
    
    $this->templates['input__box'] = array(
       'tag' => 'span'
    );
    

    $this->templates['input_type_separate'] = array(
       'tag' => 'span'
    );
    
    $this->templates['button'] = array(
        "tag"=>"button",
        "mix"=>array('block'=>'button','elem'=>'control'),
        "attrs"=> function($ctx){
          $c = Arr::merge($ctx['attrs'],array( 
           "role" => 'button',
           "type" => Arr::get($ctx['_src'],'type','button'),           
        ));
        return $c;
        },
        "content"=> function($ctx){
          return array(
              "tag"=>"span",
              "elem"=>"text",
              "content"=>$ctx['_src']['text']
          );
        }
    );
    
    $this->templates['list'] = array(
        "tag"=>"ul"
    );
    
    $this->templates['list_ordered'] = array(
        "tag"=>"ol"
    );
    
    $this->templates['list__item'] = array(
        "tag"=>"li"
    );

    $this->templates['heading'] = array(
        "tag"=>function($ctx){
          return 'h'.$ctx['_src']['lvl'];
        },
        "mods"=>function($ctx){
          $m = ['lvl'=>$ctx['_src']['lvl']];
          return is_array($ctx['mods'])? array_merge($m, $ctx['mods']): $m;
        }
    );
    
    $this->templates['image']= array(
      "tag" => "img",
      "attrs"=>function($ctx){
        $src = $ctx['_src']['url'];
        $c = Arr::merge($ctx['attrs'],array( 
           "src" => $src,
           "alt" => Arr::get($ctx['_src'],'alt'),
           "title"=>Arr::get($ctx['_src'],'title'),
        ));
        return $c;
      }
    );

    $this->templates['input'] = array(
      'tag'=>'span',
      'content'=>function($ctx){
      $i = uniqid();
       $c = array();
       if(array_key_exists('label', $ctx['_src'])){
        $c[] =  array(
               'elem'=>'label',
               'content'=>$ctx['_src']['label'],
               'attrs'=> array('for'=>$i)
           );
       }
        $c[1] = array( 
              'elem'=>'box',
              'content'=>array(
                'elem'=>'control',
                'attrs'=>array(
                    'type'=> $ctx['_src']['type'],
                    'name'=> $ctx['_src']['name'],                       
                    'id'=>$i,
                    'value'=> $ctx['_src']['value']
                  )        
              ),
            
         );
       if($ctx['_src']['required'])
         $c[1]['content']['attrs']['required'] = 'required';
       
       if(!empty($ctx['_src']['placeholder']))
        $c[1]['content']['attrs']['placeholder']= $ctx['_src']['placeholder'];
       
       return $c;
      }
    );
    
    $this->templates['input__control'] = array(
      'tag' => 'input',
    );

    $this->templates['form'] = array(
      'tag' => 'form',
    );
    
    $this->templates['input__label'] = array(
      'tag' => 'label',
    );

    $this->templates['input_type_hidden'] = array(
      'tag'=>'input',
      'attrs'=>function($ctx){
        return array(
          'type'=> 'hidden',
          'name'=> $ctx['name'],
          'value'=> $ctx['value']
        );
      },
      'content'=>null
    );

    $this->templates['input_type_textarea'] = array(
        'content'=>function($ctx){
          $i = uniqid();
           $c = array();
           if(array_key_exists('label', $ctx['_src'])){
            $c[] =  array(
                   'elem'=>'label',
                   'content'=>$ctx['_src']['label'],
                   'attrs'=> array('for'=>$i)
               );
           }
            $c[1] = array(
                  'elem'=>'box',
                  'content'=>array(
                    'elem'=>'control',
                    'tag'=>'textarea',
                    'attrs'=>array(
                        'type'=> $ctx['_src']['type'],
                        'name'=> $ctx['_src']['name'],
                        'id'=>$i,
                        'value'=> $ctx['_src']['value'],
                        'rows'=> array_key_exists('rows',$ctx)?$ctx['rows']:5,
                      )
                  ),

             );
           if($ctx['_src']['required'])
             $c[1]['content']['attrs']['required'] = 'required';

           if(!empty($ctx['_src']['placeholder']))
            $c[1]['content']['attrs']['placeholder']= $ctx['_src']['placeholder'];

           return $c;
        }
    );

    $this->templates['input_type_tel'] = array(
      'js'=>array('mask'=>'+7 (999) 999 99 99')
    );

    $this->templates['yametrica'] = array(
      'def'=>function(&$ctx){
        $id = $ctx['id'];
        $ctx['_buff'] = "<script async type=\"text/javascript\"> (function (d, w, c) { (w[c] = w[c] || []).push(function() { try { w.counter = new Ya.Metrika({id:$id, webvisor:true, clickmap:true, trackLinks:true, accurateTrackBounce:true}); } catch(e) { } }); var n = d.getElementsByTagName(\"script\")[0], s = d.createElement(\"script\"), f = function () { n.parentNode.insertBefore(s, n); }; s.type = \"text/javascript\"; s.async = true; s.src = (d.location.protocol == \"https:\" ? \"https:\" : \"http:\") + '//mc.yandex.ru/metrika/watch.js'; if (w.opera == \"[object Opera]\") { d.addEventListener(\"DOMContentLoaded\", f, false); } else { f(); } })(document, window, \"yandex_metrika_callbacks\"); </script> <noscript><div><img src=\"//mc.yandex.ru/watch/$id\" style=\"position:absolute; left:-9999px;\" alt=\"\" /></div></noscript><!-- /Yandex.Metrika counter -->";
        return ;
      }
    );
   
    
  }//template
}
