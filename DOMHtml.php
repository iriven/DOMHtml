<?php
/**
* DOMHtml - PHP class to manipulate html files, using only the power of DOMDocument and DOMXpath.
* Copyright (C) 2014 Iriven France Software, Inc. 
*
* Licensed under The GPL V3 License
* Redistributions of files must retain the above copyright notice.
*
* @Copyright 		Copyright (C) 2014 Iriven France Software, Inc.
* @package 		DOMHtml
* @Since 		Version 1.0.0
* @link 		https://github.com/iriven/DOMHtml The DOMHtml GitHub project
* @author 		Alfred Tchondjo (original founder) <iriven@yahoo.fr>
* @license  		GPL V3 License(http://www.gnu.org/copyleft/gpl.html)
*
* ==================  NOTICE  =======================
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 3
* of the License, or any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
* or contact the author by mail at: <iriven@yahoo.fr>.
**/
class DOMHtml extends DOMDocument
{
	private $xpath;
	private $htmlAttributes = array('accept','accept-charset','accesskey','action','align','alt','async','autocomplete','autofocus','autoplay','bgcolor','border','buffered','challenge','charset','checked','cite','class','code','codebase','color','cols','colspan','content','contenteditable','contextmenu','controls','coords','data','data-*','datetime','default','defer','dir','dirname','disabled','download','draggable','dropzone','enctype','for','form','headers','height','hidden','high','href','hreflang','http-equiv','icon','id','ismap','itemprop','keytype','kind','label','lang','language','list','loop','low','manifest','max','maxlength','media','method','min','multiple','name','novalidate','open','optimum','pattern','ping','placeholder','poster','preload','pubdate','radiogroup','readonly','rel','required','reversed','rows','rowspan','sandbox','spellcheck','scope','scoped','seamless','selected','shape','size','sizes','span','src','srcdoc','srclang','start','step','style','summary','tabindex','target','title','type','usemap','value','width','wrap');
	/**
	 * Class constructor.
	 *
	 * @param  string  $domversion
	 * @param  string  $encoding
	 * @return object self Instance
	 */
	public function __construct($domversion = '1.0',$encoding='UTF-8')
	{	
		parent::__construct($domversion, $encoding);
		$this->preserveWhiteSpace = false;
		$this->formatOutput = true;
		$this->resolveExternals = false;
		libxml_use_internal_errors(true);
		return $this;
	}
	/**
	 * Load Html datas into the Domdocument.
	 *
	 * @param  string  $file
	 * @return string document content
	 */
	public function loadXHTML($datas)
	{
		if(!$datas) return false;
		switch($datas)
		{
		  case (filter_var($datas, FILTER_VALIDATE_URL) !== FALSE):
		  case (file_exists($datas)):
		  case (is_link($datas)):
		  if(!$datas = file_get_contents($datas))
		  die('Impossible de charger le document dans '.__CLASS__);
		  break;
		  default:
		  break;
		}
		$Charset = mb_detect_encoding($datas);
		if($this->encoding !== $Charset)
		$datas = mb_convert_encoding($datas, $Charset, $this->encoding);				
		if(!$this->loadHTML($datas,LIBXML_COMPACT)) return false;
		$this->normalizeDocument();
		$this->xpath = new DomXpath($this);
		$this->xpath->registerNamespace('html', 'http://www.w3.org/1999/xhtml');
		$this->xpath->registerNamespace('php', 'http://php.net/xpath');
		$this->xpath->registerPHPFunctions();
		return $this->saveHTML();
	
	}
	/**
	 * Get the attribute value of a given tag.
	 *
	 * @param  string  $attribute
	 * @param  mixed   $tagName
	 * @param  array   $options
	 * @return string
	 */		
	public function getAttributeValue($needle,$tagName,$options=array())
	{
		if(!$needle)return false;
		if(is_array($tagName))
		{
			$options=$tagName;
			if(isset($options['tag']) and $options['tag']) $tagName=$options['tag'];
		}
		if(!$tagName) $tagName='*';
		if(!is_array($options)) $options = array($options);
		$offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
		$attributes = ($options) ? call_user_func(function($tab){if(!is_array($tab)) return null;$out=array();foreach($tab as $k=>$v)
		if($k !== 'tag' and $k !=='offset') $out[$k]=$v;return $out;},$options) : array();
		$attributes[]=$needle;
		$params = array();
		$params = $this->xpathQueryBlocks($attributes);
		$query='//'.$tagName;
		if($params) $query.='['.implode(' and ',$params).']';
		$nodes = !is_null($offset)? $this->xpath->query($query)->item($offset):$this->xpath->query($query);
		switch($nodes):
			case ($nodes instanceof DomNodeList):
			$output=array();
			foreach ($nodes as $node)
			foreach($node->attributes as $name=>$objValue)
			{
				if($name !== $needle) continue;
				if(!isset($output[$node->nodeName]))
				{
				  if($tagName !== '*') $output[] = $objValue->value;
				 else $output[$node->nodeName] = $objValue->value;
				}
				else
				{
				  is_array($output[$node->nodeName]) or $output[$node->nodeName]=array($output[$node->nodeName]);
				  $output[$node->nodeName][] = $objValue->value;
				}
			}
				return $output;
			break;
			case ($nodes instanceof DomElement):
				foreach($nodes->attributes as $name=>$objValue)
				if($name !== $needle) continue;
				return $objValue->value;
			break;			
			default:
				return false;
			break;
		endswitch;
	}
  /**
   * del a single or multiple attributes value of a given tag.
   *
   * @param  mixed  $attribute
   * @param  string   $value
   * @param  array   $options
   * @return string
   */	
   public function delAttributes($needle,$tagName, $options=array())
  {
	  if(!$needle)return false;
	  if(is_array($tagName) and (func_num_args()==2))
	  {
		  $options=$tagName;
		  $tagName=(isset($options['tag']) and $options['tag'])?$options['tag'] : null;
	  }
	  if(!$tagName) $tagName='*';
	  if(!is_array($options)) $options = array($options);
	  if(is_array($needle)){ $needle = array_values($needle); foreach($needle as $item) $this->delAttributes($item,$tagName,$options);}
	  $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
	  $attributes = ($options) ? call_user_func(function($tab){if(!is_array($tab)) return null;$out=array();foreach($tab as $k=>$v)
	  if($k !== 'tag' and $k !=='offset') $out[$k]=$v;return $out;},$options) : array();
	  $attributes[]=$needle;
	  $params = array();
	  $params = $this->xpathQueryBlocks($attributes);
	  if(!is_array($tagName)) $tagName = array($tagName);
	  foreach($tagName as $tag)
	  {
		$query='//'.$tag;
		if($params) $query.='['.implode(' and ',$params).']';
		$nodes = !is_null($offset)? $this->xpath->query($query)->item($offset):$this->xpath->query($query);
		  switch($nodes):
			  case ($nodes instanceof DomNodeList):
			  $output=array();
			  foreach ($nodes as $node)
			  $node->removeAttribute($needle);
			  break;
			  case ($nodes instanceof DomElement):
			  $nodes->removeAttribute($needle);
			  break;			
			  default:
				  return false;
			  break;
		  endswitch;		    
	  }	  	  	  	  
	return $this->saveHTML();	  
  }	
  /**
   * set a single or multiple attributes value of a given tag.
   *
   * @param  mixed  $attribute
   * @param  string   $value
   * @param  array   $options
   * @return string
   */		
	public function addAttributes($attribute, $value='',$options=array())
	{ $numargs = func_num_args();
	  $args = func_get_args();
	  $tagName = null;
	  switch($numargs):
	  case '2':
	  $attribute = $args[0];
	  if(!is_array($attribute))
	  {
		  if(is_array($args[1]))return false;
		  $value = $args[1]; $options=array(); 
	  }
	  else
	  {
		  if(is_array($args[1])) $options=$args[1];
		  else{ $tagName = $args[1]; $options=array();}  
	  }
	  break;
	  case '1':
	  $options=$args[0];
	  if(!$options['attribute']) return false;
	  $attribute = $options['attribute'];
	  unset($options['attribute']);
	  $value = isset($options['value'])? $options['value'] : null;
	  if($value) 
	  {
		  unset($options['value']);
		  if(is_array($value)) $value = join(' ',$value);
	  }
	  if(!is_array($attribute))
	  {
		   if(!$value) return false;
		   $attribute = array($attribute=>$value);  
	  }
	  else
	  { $aTemp = array();
		  foreach($attribute as $key=>$val)
		  {
			  if(is_numeric($key) and empty($val)) continue;
			  if(!is_numeric($key)  and !empty($val)) $value = $val .' '.$value;
			  if(is_numeric($key) and !empty($val)) $key = $val;
			  $aTemp[$key] = $value;
		  }
		  $attribute = $aTemp;
	  }
	  break;	  
	  default:
	   if(is_array($value)) $value = join(' ',$value);
	  $attribute = array($attribute=>$value);
	  break;
	  endswitch;
	  if(!$attribute) return false;
	  if(!is_array($options)) $options = array($options);	  
	  if(!$tagName) $tagName =(isset($options['parent']) and $options['parent'])? $options['parent']:
		  			((isset($options['tag']) and $options['tag'])? $options['tag']:'*');

		$offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
		
		$attributes = ($options) ? call_user_func(function($tab){if(!is_array($tab)) return null;$out=array();foreach($tab as $k=>$v)
		if($k !== 'tag' and $k !=='parent' and $k !=='offset') $out[$k]=$v;return $out;},$options) : array();	
		$params = array();
		$params = $this->xpathQueryBlocks($attributes);
		$query='//'.$tagName;
		if($params) $query.='['.implode(' and ',$params).']';
		$nodes = !is_null($offset)? $this->xpath->query($query)->item($offset):$this->xpath->query($query);	
		switch($nodes):
			case ($nodes instanceof DomNodeList):	
				foreach ($nodes as $node)
				{	
					foreach($attribute as $key=>$value)
					{
						if(is_numeric($key)) continue;
						if($node->hasAttribute($key))
						{
							if($oldValue = $node->getAttribute($key))
							$value = $oldValue .' '.$value;
						}
						$node->removeAttribute($key);
						$node->setAttribute($key, $value);
					}
				}
			break;
			case ($nodes instanceof DomElement):
					foreach($attribute as $key=>$value)
					{
						if(is_numeric($key)) continue;
						if($nodes->hasAttribute($key))
						{
							if($oldValue = $nodes->getAttribute($key))
							$value = $oldValue .' '.$value;
						}
						$nodes->removeAttribute($key);
						$nodes->setAttribute($key, $value);
					}
			break;			
			default:
				return false;
			break;
		endswitch;									
	return $this->saveHTML();
	}	
	/**
	 * Returns an associative array of all atributes of a given tag.
	 *
	 * @param  string  $tagName
	 * @param  mixed   $options
	 * @return array
	 */	
	public function getAttributes($tagName,$options=array())
	{	if(!$tagName or trim($tagName)==='*') return false;
		$output = array();
		if(!is_array($options)) $options = array($options);
		$offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): array();
		$attribute = ($options) ? call_user_func(function($tab){if(!is_array($tab)) return null;$out=array();foreach($tab as $k=>$v)
		if($k !== 'tag' and $k !=='offset') $out[$k]=$v;return $out;},$options) : array();
		$params = array();
		$params = $this->xpathQueryBlocks($attribute);
		$query='//'.$tagName;
		if($params) $query.='['.implode(' and ',$params).']';
		$nodes = !is_null($offset)? $this->xpath->query($query)->item($offset):$this->xpath->query($query);
		switch($nodes):
			case ($nodes instanceof DomNodeList):	
				foreach ($nodes as $node)
				{	if(!$node->hasAttributes()) continue;
					 foreach($node->attributes as $name=>$objValue)
						if(!isset($output[$name]))
							$output[$name] = $objValue->value;
						else
						{
							is_array($output[$name]) or $output[$name]=array($output[$name]);
							$output[$name][] = $objValue->value;
						}	
				}
				return $output;
			break;
			case ($nodes instanceof DomElement):
				if($nodes->hasAttributes())
				foreach($nodes->attributes as $name=>$objValue)
				$output[$name] = $objValue->value;
				return $output;
				break;			
			default:
				return false;
			break;
		endswitch;		
	}
	/**
	 * Returns true if the specified tag has a given attribute, otherwise false.
	 *
	 * @param  string  $needle
	 * @param  mixed   $tagName
	 * @param  array   $options
	 * @return bool
	 */
	public function attributeExists($needle, $tagName,$options=array())
	{
		if(!$needle) return false;
		if(!$tagName) $tagName='*';
		if(is_array($tagName))
		{
			$options=$tagName;
			$tagName=(isset($options['tag']) and $options['tag'])? $options['tag']:'*';
		}
		if(!is_array($options)) $options = array($options);
		$offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): '0';	
		$attribute = ($options) ? call_user_func(function($tab){if(!is_array($tab)) return null;$out=array();foreach($tab as $k=>$v)
		if($k !== 'tag' and $k !=='offset') $out[$k]=$v;return $out;},$options) : array();
		$attribute[]=$needle;
		$params = array();
		$params = $this->xpathQueryBlocks($attribute);
		$query='//'.$tagName;
		if($params) $query.='['.implode(' and ',$params).']';
		$nodes = $this->xpath->query($query)->item($offset); //this catches all elements with itemprop attribute
		if (!$nodes instanceof DomElement) return false;
		return true;
	}
	/**
	 * appends a html content to the document or to a given tag .
	 *
	 * @param  string  $content
	 * @param  array   $options
	 * @return mixed
	 */
	public function appendHTML($content, $options=array())
	{	
		if(!$content or is_array($content)) return false;
		if($options and !is_array($options)){ $tagName = $options; $options=array();}
		 if(!$tagName) $tagName =(isset($options['parent']) and $options['parent'])? $options['parent']:
		  			((isset($options['tag']) and $options['tag'])? $options['tag']:null);
		$offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;		
		$tagAttributes = ($options) ? call_user_func(function($tab){if(!is_array($tab)) return null;$out=array();foreach($tab as $k=>$v)
		if($k !=='offset' and $k !=='parent' and $k !=='tag') $out[$k]=$v;return $out;},$options) : array();		
		if(!$tagName)
		{
			$root = $this->lastChild;
			if(strcasecmp($root->nodeName, 'html') == 0) $root = $root->lastChild;
			$tagName = $root->nodeName;
		}
		$params = array();
		if($tagAttributes) $params = $this->xpathQueryBlocks($tagAttributes);		
		$query='//'.$tagName;
		if($params) $query.='['.implode(' and ',$params).']';
		$nodes = !is_null($offset)? $this->xpath->query($query)->item($offset):$this->xpath->query($query);	
		switch($nodes):
		case($nodes instanceof DomNodeList):
		foreach($nodes as $node)
		{
			$domContent = $node->ownerDocument->createDocumentFragment();
			$domContent->appendXML($content);
			$node->appendChild($domContent);
		}
		break;
		case($nodes instanceof DomElement):
		$domContent = $nodes->ownerDocument->createDocumentFragment();
		$domContent->appendXML($content);
		$nodes->appendChild($domContent);
		break;		
		default:
			return false;
		break;
		endswitch;
		return $this->saveHTML();
	}	
	/**
	 * replace a given tag content(including the tag itself) by a new html content.
	 *
	 * @param  string   $tagName
	 * @param  string  $newcontent	 
	 * @param  array   $options
	 * @return mixed
	 */
	public function replaceHTML($tagName,$newcontent,$options=array())
	{	if(func_num_args()<2 or is_array($tagName)) return false;
		$newname = null;
		$newAtrributes=array();
		if(is_array($newcontent))
		{	
			if(isset($newcontent['newname'])) $newname = $newcontent['newname'];
			$newAtrributes = ($newname) ? call_user_func(function($tab){if(!is_array($tab)) return array();$out=array();foreach($tab as $k=>$v)
	 		if($k !== 'newname' and $k !=='newcontent' and $k !=='content') $out[$k]=$v;return $out;},$newcontent) : array();
			$newcontent = isset($newcontent['newcontent'])? $newcontent['newcontent'] :
						 (isset($newcontent['content'])? $newcontent['content'] : '');
		}
		if(!is_array($options)) $options = array($options);	
		$offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;		
		$oldAttributes = ($options) ? call_user_func(function($tab){if(!is_array($tab)) return null;$out=array();foreach($tab as $k=>$v)
		if($k !=='offset') $out[$k]=$v;return $out;},$options) : array();
		$params = array();
		if($oldAttributes) $params = $this->xpathQueryBlocks($oldAttributes);		
		$query='//'.$tagName;
		if($params) $query.='['.implode(' and ',$params).']';
		$nodes = !is_null($offset)? $this->xpath->query($query)->item($offset):$this->xpath->query($query);
			switch($nodes):
			case ($nodes instanceof DomNodeList):	
			foreach ($nodes as $node)
			{ 	
				$newnode = (!$newname)? $node->ownerDocument->createDocumentFragment(): $node->ownerDocument->createElement($newname);
				if($newname and $newAtrributes)
				foreach($newAtrributes as $name=>$value)  $newnode->setAttribute($name , $value );
				$domContent = $this->createDocumentFragment();
				$domContent->appendXML($newcontent);
				$newnode->appendChild($domContent);
				$node->parentNode->replaceChild($newnode, $node); 
			}
			break;
			case ($nodes instanceof DomElement):
			$newnode = (!$newname)? $this->createDocumentFragment(): $this->createElement($newname);
			if($newname and $newAtrributes)
			foreach($newAtrributes as $name=>$value)  $newnode->setAttribute($name , $value );		
			$domContent = $this->createDocumentFragment();
			$domContent->appendXML($newcontent);
			$newnode->appendChild($domContent);		
			$nodes->parentNode->replaceChild($newnode, $nodes); 
			break;			
			default:
				return false;
			break;
		endswitch;		
		return $this->saveHTML();
	}
	/**
	 * remove a tag content(including the tag itself) from the current document.
	 *
	 * @param  string   $tagName
	 * @param  array   $options
	 * @return mixed
	 */
	  public function removeHTML($tagName,$options=array())
	  {
		$options = call_user_func(function($tab){if(!is_array($tab)) return array();$out=array();foreach($tab as $k=>$v)
	 	if($k !== 'newname' and $k !=='newcontent') $out[$k]=$v;return $out;},$options);
		return $this->replaceHTML($tagName,'',$options);
	  }		  	
	/**
	 * change a tag name to a new one, preserving all it properties and content.
	 *
	 * @param  string   $tagName
	 * @param  string  $newname	 
	 * @param  array   $options
	 * @return mixed
	 */
  public function renameTag($tagName,$newname,$options=array())
  {	  if(!$tagName or !$newname) return false;
	  if(is_array($newname))
	  {
		  $options=$newname;
		  $newname=(isset($options['newname']) and $options['newname'])? $options['newname']:null;
		  if(!$newname) return false;	
	  } 
	  if(!is_array($options)) $options = array($options);	
	  $attribute = ($options) ? call_user_func(function($tab){if(!is_array($tab)) return null;$out=array();foreach($tab as $k=>$v)
	  if($k !== 'newname' and $k !=='offset') $out[$k]=$v;return $out;},$options) : array();
	  $params = array();
	  if($attribute) $params = $this->xpathQueryBlocks($attribute);
	  $query='//'.$tagName;
	  if($params) $query.='['.implode(' and ',$params).']';
	  $oldnodes = $this->xpath->query($query);
	  if (!$oldnodes instanceof DOMNodelist) return false;
	  foreach ($oldnodes as $node)
	  { 
		  $newnode = $node->ownerDocument->createElement($newname);
		  if($node->hasAttributes())
			foreach($node->attributes as $name=>$objValue)
			$newnode->setAttribute($name, $objValue->value);
		  if($node->hasChildNodes())
		  foreach ($node->childNodes as $child)
		  {
			  $temp = $node->ownerDocument->importNode($child, true);
			  $newnode->appendChild($temp);
		  }
		  $node->parentNode->replaceChild($newnode, $node);
	  }
	  return $this->saveHTML();	   
  }	
	/**
	 * get the content value of a given tag.
	 *
	 * @param  string   $tagName
	 * @param  array   $options
	 * @return mixed
	 */
	public function innerHTML($tagName,$options=array())
	{	
		$numargs = func_num_args();
   		$args = func_get_args();
   		if($numargs == 1)
		{
			if(is_array($args[0]))
			{ 
				$options = $args[0];
				$tagName =(isset($options['parent']) and $options['parent'])? $options['parent']:
		  			((isset($options['tag']) and $options['tag'])? $options['tag']:null);
			}
			else{ $tagName = $args[0]; $options = array();}	
		}
		if(!$tagName) return $this->saveHTML();
		if($options and !is_array($options)){$options=array($options);}
		$offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): '0';		
		$tagAttributes = ($options) ? call_user_func(function($tab){if(!is_array($tab)) return null;$out=array();foreach($tab as $k=>$v)
		if($k !=='offset' and $k !=='parent' and $k !=='tag') $out[$k]=$v;return $out;},$options) : array();		
		$params = array();
		if($tagAttributes) $params = $this->xpathQueryBlocks($tagAttributes);
		$query='//'.$tagName;
		if($params) $query.='['.implode(' and ',$params).']';
		$query.='/*';
		$nodes = $offset? $this->xpath->query($query)->item($offset) : $this->xpath->query($query);
		switch($nodes):
		case ($nodes instanceof DOMNodelist):
		$countent=array();
		foreach($nodes as $node)
		$countent[]= $this->saveHTML($node);
		return $countent;			
		break;
		case ($nodes instanceof DomElement):
		return $this->saveHTML( $nodes );
		/**/	
		break;		
		default:
		return false;
		break;
		endswitch;
	}	
	/**
	 * retrieve the whole document to check if a given tag exists.
	 *
	 * @param  string   $tagName
	 * @param  array   $options
	 * @return mixed
	 */
	public function tagExists($tagName=null,$options=array())
	{ 	
		if(!$tagName) return false;
		$params = array();
		if($options)	$params = $this->xpathQueryBlocks($options);
		$query='//'.$tagName;
		if($params) $query.='['.implode(' and ',$params).']';
		$result = $this->xpath->query($query)->item(0);
		if (!$result instanceof DomElement) return false;
		return true;		
	}
	/**
	 * generate xpath query blocks.
	 *
	 * @param  array   $attributes
	 * @return array
	 */
	private function xpathQueryBlocks($attribute=array())
	{
		$params = array();
		if($attribute)
		{
		  if(!is_array($attribute))
		  {   
			  if(!strpos($attribute,',')) $attribute = array($attribute);
			  else $attribute = explode(',',$attribute);
		   }
		  foreach($attribute as $k=>$v)
			  $params[] = !is_numeric($k)?'@'.strip_tags($k).'="'.$v.'"' : '@'.strip_tags($v);
		}
		return $params;
	}
/**
*fin de la classe
*
**/	
}
