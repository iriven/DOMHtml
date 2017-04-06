<?php
namespace Iriven;
/**
* DOMHtml - PHP class to manipulate html files, using only the power of DOMDocument and DOMXpath.
* Copyright (C) 2014 Iriven France Software, Inc. 
*
* Licensed under The GPL V3 License
* Redistributions of files must retain the above copyright notice.
*
* @Copyright 		Copyright (C) 2014 Iriven France Software, Inc.
* @package 		DOMHtml
* @Since 		Version 1.0.1
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
use \DOMDocument;
use \DOMXPath;
use \DomNodeList;
use \DOMNode;
use \DomElement;
class DOMHtml extends DOMDocument
{
    private $xpath;
    private $Reserved = ['tag', 'parent', 'offset', 'attribute', 'tagname', 'content', 'attributes', 'target','newname','value' ];

    /**
     * @param string $domversion
     * @param string $encoding
     */
    public function __construct($domversion = '1.0',$encoding='UTF-8')
    {
        parent::__construct($domversion, $encoding);
        $this->preserveWhiteSpace = false;
        $this->formatOutput = true;
        $this->recover = true;
        $this->resolveExternals = false;
        libxml_use_internal_errors(true);
        return $this;
    }

    /**
     * Append Caracter Data to a node and check for a javascript node
     *
     * @param $content
     * @param $nodeName
     * @param array $options
     * @return bool|string
     */
    public function appendCdata($content, $nodeName, $options=[])
    {
        if(!$content or !$nodeName) return false;
        if(!$options AND is_array($nodeName)){ $options = $nodeName; $nodeName=null;}
        if(!$nodeName) $nodeName =$this->checkTagName($options);
        $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
        $attributes = $this->filterOptions($options);
        if($nodeName === '*' )
        {
            $root = $this->lastChild;
            if(strcasecmp($root->nodeName, 'html') == 0) $root = $root->lastChild;
            $nodeName = $root->nodeName;
        }
        $query = $this->buildQuery($nodeName,$attributes);
        $nodes = !is_null($offset)? $this->loadXpath()->query($query)->item($offset):$this->loadXpath()->query($query);
        switch($nodes):
            case($nodes instanceof DomNodeList):
                foreach($nodes as $node)
                {
                    if($node instanceof DOMNode)
                    {
                        $ct = (strcasecmp($node->nodeName, 'script') == 0)?
                            $node->ownerDocument->createCDATASection("\n//<![CDATA[\n" . $content . "\n//]]>") : // Javascript hack
                            $node->ownerDocument->createCDATASection('<![CDATA[ '.$content.' ]]>'); // Normal CDATA node
                        $node->appendChild($ct);
                    }
                }
                break;
            case($nodes instanceof DOMNode):
                $ct = (strcasecmp($nodes->nodeName, 'script') == 0)?
                    $nodes->ownerDocument->createCDATASection("\n//<![CDATA[\n" . $content . "\n//]]>") : // Javascript hack
                    $nodes->ownerDocument->createCDATASection('<![CDATA[ '.$content.' ]]>'); // Normal CDATA node
                $nodes->appendChild($ct);
                break;
        endswitch;
        return $this->saveHTML();
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
        $tagName = null;
        if($options and !is_array($options)){ $tagName = $options; $options=array();}
        if(!$tagName) $tagName =$this->checkTagName($options);
        $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
        $attributes = $this->filterOptions($options);
        if($tagName === '*' )
        {
            $root = $this->lastChild;
            if(strcasecmp($root->nodeName, 'html') == 0) $root = $root->lastChild;
            $tagName = $root->nodeName;
        }
        $query = $this->buildQuery($tagName,$attributes);
        $nodes = !is_null($offset)? $this->loadXpath()->query($query)->item($offset):$this->loadXpath()->query($query);
        switch($nodes):
            case($nodes instanceof DomNodeList):
                foreach($nodes as $node)
                {
                    if($node instanceof DomElement)
                    {
                        $domContent = $node->ownerDocument->createDocumentFragment();
                        $domContent->appendXML($content);
                        $node->appendChild($domContent);
                    }
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
     * Get the attribute value of a given tag
     *
     * @param $attrName
     * @param $TagName
     * @param array $options
     * @return array|bool|null
     */
    public function getAttributeValue($attrName,$TagName,$options=array())
    {
        if(!$attrName)return false;
        if(is_array($TagName))
        {
            $options=$TagName;
            $TagName =null;
        }
        if(!is_array($options)) $options = array($options);
        if(!$TagName) $TagName=$this->checkTagName($options);
        $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
        $attributes = $this->filterOptions($options);
        $attributes[]=$attrName;
        $query = $this->buildQuery($TagName,$attributes);
        $nodes = !is_null($offset)? $this->loadXpath()->query($query)->item($offset):$this->loadXpath()->query($query);
        switch($nodes):
            case ($nodes instanceof DomNodeList):
                $output=array();
                foreach ($nodes as $node)
                    foreach($node->attributes as $name=>$objValue)
                    {
                        if($name !== $attrName) continue;
                        if(!isset($output[$node->nodeName]))
                        {
                            if($TagName !== '*') $output[] = $objValue->value;
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
            default:
                foreach($nodes->attributes as $name=>$objValue)
                {
                    if($name !== $attrName) continue;
                    return $objValue->value;
                }
                break;
        endswitch;
        return null;
    }
    /**
     * Returns an associative array of all attributes of a given tag.
     *
     * @param  string  $tagName
     * @param  mixed   $options
     * @return array
     */
    public function getElementAttributes($tagName,$options=array())
    {	if(!$tagName) return [];
        $output = array();
        if(!is_array($options)) $options = array($options);
        $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
        $attribute = $this->filterOptions($options);
        $query = $this->buildQuery($tagName,$attribute);
        $nodes = !is_null($offset)? $this->loadXpath()->query($query)->item($offset):$this->loadXpath()->query($query);
        switch($nodes):
            case ($nodes instanceof DomNodeList):
                foreach ($nodes as $node)
                {	if($node instanceof DomElement)
                {
                    if(!$node->hasAttributes()) continue;
                    $nodeName = $node->nodeName;
                    foreach($node->attributes as $attrName=>$objValue)
                        if(!isset($output[$nodeName][$attrName]))
                            $output[$nodeName][$attrName] = $objValue->value;
                        else
                        {
                            is_array($output[$nodeName][$attrName]) or $output[$nodeName][$attrName]=array($output[$nodeName][$attrName]);
                            $output[$nodeName][$attrName][] = $objValue->value;
                        }
                }
                }
                return $output;
                break;
            case ($nodes instanceof DomElement):
                if($nodes->hasAttributes())
                    foreach($nodes->attributes as $attrName=>$objValue)
                        $output[$attrName] = $objValue->value;
                return $output;
                break;
            default:
                return [];
                break;
        endswitch;
    }

    /**
     * retrieve the whole document to check if a given tag exists.
     * @param $tagName
     * @param array $options
     * @return bool
     */
    public function hasElement($tagName,$options=[])
    {
        if(!$tagName or !is_string($tagName)) return false;
        $attribute = $this->filterOptions($options);
        $query = $this->buildQuery($tagName,$attribute);
        $result = $this->loadXpath()->query($query)->item(0);
        if (!$result instanceof DomElement) return false;
        return true;
    }

    /**
     * get DOM object of a given tagName .
     *
     * @param $tagName
     * @param array $options
     * @return DOMElement|DOMNodeList|null
     */
    public function getElementsByName($tagName,$options=[]){
        if(func_num_args() == 1)
        {
            if(is_array($tagName))
            {
                $options = $tagName;
                $tagName = $this->checkTagName($options);
            }
        }
        if($this->hasElement($tagName,$options))
        {
            if($options and !is_array($options)){$options=array($options);}
            $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): '0';
            $attribute = $this->filterOptions($options);
            $query = $this->buildQuery($tagName,$attribute,true);
            $nodes = $offset? $this->loadXpath()->query($query)->item($offset) : $this->loadXpath()->query($query);
            if(($nodes instanceof DOMNodelist) OR
                ($nodes instanceof DomElement))
            return $nodes;
        }
            return null;
    }
    /**
     * Update tag content, preserving tag name and attributes
     *
     * @param string $newContent
     * @param $tagName
     * @param array $options
     * @return string
     */
    public function updateInnerHTML($newContent='',$tagName,$options=[])
    {
        if(func_num_args()>=2 AND is_string($newContent))
        {
            if(!$options AND is_array($tagName))
            {
                $options=$tagName;
                $tagName=$this->checkTagName($options);
            }
            if(!$tagName) $tagName='*';
            if(!is_array($options)) $options = array($options);
            $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
            $attributes = $this->filterOptions($options);
            $query = $this->buildQuery($tagName,$attributes);
            $nodes = !is_null($offset)? $this->loadXpath()->query($query)->item($offset):$this->loadXpath()->query($query);
            switch($nodes):
                case ($nodes instanceof DomNodeList):
                    foreach ($nodes as $node)
                    {
                        if($node instanceof DomElement)
                        {
                            if($node->hasChildNodes())
                            {
                                while ($node->childNodes->length)
                                    $node->removeChild($node->childNodes->firstChild);
                            }
                            $domContent = $node->ownerDocument->createDocumentFragment();
                            $domContent->appendXML($newContent);
                            $node->appendChild($domContent);
                        }
                    }
                    break;
                case ($nodes instanceof DomElement):
                    if($nodes->hasChildNodes())
                    {
                        while ($nodes->childNodes->length)
                            $nodes->removeChild($nodes->childNodes->firstChild);
                    }
                    $domContent = $nodes->ownerDocument->createDocumentFragment();
                    $domContent->appendXML($newContent);
                    $nodes->appendChild($domContent);
                    break;
            endswitch;
        }
        return $this->saveHTML();
    }
    /**
     * get the content value of a given tag.
     * @param $tagName
     * @param array $options
     * @return array|null|string
     */
    public function getInnerHTML($tagName,$options=array())
    {
        if(func_num_args() == 1)
        {
            if(is_array($tagName))
            {
                $options = $tagName;
                $tagName = $this->checkTagName($options);
            }
        }
        if(!$tagName) return $this->saveHTML();
        if($options and !is_array($options)){$options=array($options);}
        $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): '0';
        $attribute = $this->filterOptions($options);
        $query = $this->buildQuery($tagName,$attribute,true);
        $nodes = $offset? $this->loadXpath()->query($query)->item($offset) : $this->loadXpath()->query($query);
        switch($nodes):
            case ($nodes instanceof DOMNodelist):
                $countent=array();
                foreach($nodes as $node)
                    $countent[]= $this->saveHTML($node);
                return $countent;
                break;
            default:
                return ($nodes instanceof DomElement)? $this->saveHTML( $nodes ) : null;
                break;
        endswitch;
    }

    /**
     * Returns true if the specified tag has a given attribute, otherwise false.
     *
     * @param  string  $attrName
     * @param  mixed   $tagName
     * @param  array   $options
     * @return bool
     */
    public function elementHasAttribute($attrName, $tagName,$options=array())
    {
        if(!$attrName) return false;
        if(!$options AND is_array($tagName))
        {
            $options=$tagName;
            $tagName=$this->checkTagName($options);
        }
        if(!$tagName) $tagName='*';
        if(!is_array($options)) $options = array($options);
        $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): '0';
        $attributes = $this->filterOptions($options);
        $attributes[]=$attrName;
        $query = $this->buildQuery($tagName,$attributes);
        $nodes = $this->loadXpath()->query($query)->item($offset); //this catches all elements with itemprop attribute
        if (!$nodes instanceof DomElement) return false;
        return true;
    }

    /**
     * @param string $filename
     * @param null $options
     * @return $this
     */
    public function load($filename, $options = null)
    {
        $default = ['LIBXML_DTDLOAD','LIBXML_DTDATTR'];
        $options = implode('|',array_merge($default,$options));
        parent::load($filename, $options);
        $this->loadXpath(true);
        return $this;
    }
    /**
     * @param string $source
     * @param int $options
     * @return $this
     */
    public function loadHTML($source, $options = 0)
    {
        $Charset = mb_detect_encoding($source);
        if($this->encoding !== $Charset)
            $source = mb_convert_encoding($source, $Charset, $this->encoding);
        parent::loadHTML($source, $options);
        $this->loadXpath(true);
        return $this;
    }
    /**
     * @param string $filename
     * @param int $options
     * @return $this
     */
    public function loadHTMLFile($filename, $options = 0)
    {
        parent::loadHTMLFile($filename, $options);
        $this->loadXpath(true);
        return $this;
    }
    /**
     * @param $datas
     * @return $this
     */
    public function loadXHTML($datas)
    {
        if($datas)
        {
            switch($datas)
            {
                case (filter_var($datas, FILTER_VALIDATE_URL) !== FALSE):
                case (file_exists($datas)):
                case (is_link($datas)):
                    if(!$this->loadHTMLFile($datas,$flag=LIBXML_COMPACT))
                        die('Impossible de charger le fichier: '.$datas);
                    break;
                default:
                    if(!$this->loadHTML($datas,$flag=LIBXML_COMPACT))
                        die('Impossible de charger la source Html');
                    break;
            }
            $this->normalizeDocument();
            $this->saveHTML();
        }
        return $this;
    }
    /**
     * @param string $source
     * @param null $options
     * @return $this
     */
    public function loadXML($source, $options = null)
    {
        $Charset = mb_detect_encoding($source);
        if($this->encoding !== $Charset)
            $source = mb_convert_encoding($source, $Charset, $this->encoding);
        parent::loadXML($source, $options);
        $this->loadXpath(true);
        return $this;
    }
    /**
     * Remove Caracter Data to a node
     * @param $nodeName
     * @param array $options
     * @return bool|string
     */
    public function removeCdata($nodeName, $options = [])
    {
        if(!$nodeName) return false;
        if(!$options AND is_array($nodeName)){ $options = $nodeName; $nodeName=null;}
        if(!$nodeName) $nodeName =$this->checkTagName($options);
        $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
        $attributes = $this->filterOptions($options);
        $query = $this->buildQuery($nodeName,$attributes);
        $nodes = !is_null($offset)? $this->loadXpath()->query($query)->item($offset):$this->loadXpath()->query($query);
        switch($nodes):
            case($nodes instanceof DomNodeList):
                foreach($nodes as $node)
                {
                    if($node instanceof DOMNode)
                    {
                        foreach($node->childNodes as $child)
                        {
                            if ($child->nodeType == XML_CDATA_SECTION_NODE)
                                $node->removeChild($child);
                        }
                    }
                }
                break;
            case($nodes instanceof DOMNode):
                foreach($nodes->childNodes as $child)
                {
                    if ($child->nodeType == XML_CDATA_SECTION_NODE)
                        $nodes->removeChild($child);
                }
                break;
        endswitch;
        return $this->saveHTML();
    }

    /**
     * remove a tag content(including the tag itself) from the current document.
     *
     * @param $tagName
     * @param array $options
     * @return string
     */
    public function removeElement($tagName,$options=[])
    {
        if(!$tagName or !is_string($tagName)) return $this->saveHTML();
        return $this->replaceElement($tagName,'',$options);
    }
    /**
     * delete a single or multiple attributes of a given tag
     *
     * @param $attrName
     * @param $TagName
     * @param array $options
     * @return bool|string
     */
    public function removeAttribute($attrName,$TagName, $options=[])
    {
        if(!$attrName or !$TagName) return false;
        if(is_array($attrName))
        {
            $attrName = array_values($attrName);
            foreach($attrName as $item) call_user_func_array([$this,__METHOD__],[$item,$TagName,$options]);
        }
        if(!$options AND is_array($TagName)){ $options = $TagName; $TagName=null;}
        if(!is_array($options)) $options = array($options);
        if(!$TagName) $TagName =$this->checkTagName($options);
        $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
        $attributes = $this->filterOptions($options);
        $attributes[]=$attrName;
        if(!is_array($TagName)) $TagName = array($TagName);
        foreach($TagName as $tag)
        {
            $query = $this->buildQuery($tag,$attributes);
            $nodes = !is_null($offset)? $this->loadXpath()->query($query)->item($offset):$this->loadXpath()->query($query);
            switch($nodes):
                case ($nodes instanceof DomNodeList):
                    foreach ($nodes as $node)
                        if($node instanceof DomElement) $node->removeAttribute($attrName);
                    break;
                case ($nodes instanceof DomElement):
                default:
                    $nodes->removeAttribute($attrName);
                    break;
            endswitch;
        }
        return $this->saveHTML();
    }

    /**
     * replace a given tag content(including the tag itself) by a new html content.
     *
     * @param $oldTagName
     * @param array $newDatas
     * @param array $options
     * @return string
     */
    public function replaceElement($oldTagName,$newDatas=['tagname' =>null,'content' =>''],$options=[])
    {
        if(func_num_args()>=2 AND is_string($oldTagName))
        {
            if(!is_array($newDatas)) $newDatas=['content' =>$newDatas ];
            $newcontent = $newDatas['content']?: '';
            $NewTagName = trim($this->checkTagName($newDatas),'*');
            $newAtrributes = $this->filterOptions($newDatas);
            if(!is_array($options)) $options = array($options);
            $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
            $oldAttributes = $this->filterOptions($options);
            $query = $this->buildQuery($oldTagName,$oldAttributes);
            $nodes = !is_null($offset)? $this->loadXpath()->query($query)->item($offset):$this->loadXpath()->query($query);
            switch($nodes):
                case ($nodes instanceof DomNodeList):
                    foreach ($nodes as $node)
                    {
                        if($node instanceof DomElement)
                        {
                            $newnode = (!$NewTagName)? $node->ownerDocument->createDocumentFragment(): $node->ownerDocument->createElement($NewTagName);
                            if($NewTagName and $newAtrributes)
                                foreach($newAtrributes as $name=>$value)  $newnode->setAttribute($name , $value );
                            $domContent = $this->createDocumentFragment();
                            $domContent->appendXML($newcontent);
                            $newnode->appendChild($domContent);
                            $node->parentNode->replaceChild($newnode, $node);
                        }
                    }
                    break;
                case ($nodes instanceof DomElement):
                    $newnode = (!$NewTagName)? $this->createDocumentFragment(): $this->createElement($NewTagName);
                    if($NewTagName and $newAtrributes)
                        foreach($newAtrributes as $name=>$value)  $newnode->setAttribute($name , $value );
                    $domContent = $this->createDocumentFragment();
                    $domContent->appendXML($newcontent);
                    $newnode->appendChild($domContent);
                    $nodes->parentNode->replaceChild($newnode, $nodes);
                    break;
            endswitch;
        }
        return $this->saveHTML();
    }

    /**
     * change a tag name to a new one, preserving all it properties and content.
     *
     * @param  string   $oldName
     * @param  string  $newName
     * @param  array   $options
     * @return mixed
     */
    public function renameElement($oldName,$newName,$options=array())
    {
        if(!$oldName or !$newName) return false;
        if(is_array($newName))
        {
            $options = $newName;
            $newName = $this->checkTagName($options);
            if($newName === '*') return false;
        }
        if(!is_array($options)) $options = array($options);
        $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
        $attribute = $this->filterOptions($options);
        $query = $this->buildQuery($oldName,$attribute);
        $oldnodes = !is_null($offset)? $this->loadXpath()->query($query)->item($offset):$this->loadXpath()->query($query);
        switch($oldnodes):
            case($oldnodes instanceof DomNodeList):
                foreach ($oldnodes as $node)
                {
                    if($node instanceof DomElement)
                    {
                        $newnode = $node->ownerDocument->createElement($newName);
                        if($node->hasAttributes())
                            foreach($node->attributes as $name=>$objValue)
                                $newnode->setAttribute($name, $objValue->value);
                        if($node->hasChildNodes())
                        {
                            while ($node->hasChildNodes())
                            {
                                $child = $node->childNodes->item(0);
                                $child = $node->ownerDocument->importNode($child, true);
                                $newnode->appendChild($child);
                            }
                        }
                        $node->parentNode->replaceChild($newnode, $node);
                    }
                }
                break;
            case($oldnodes instanceof DomElement):
                $newnode = $oldnodes->ownerDocument->createElement($newName);
                if($oldnodes->hasAttributes())
                    foreach($oldnodes->attributes as $name=>$objValue)
                        $newnode->setAttribute($name, $objValue->value);
                if($oldnodes->hasChildNodes())
                {
                    while ($oldnodes->hasChildNodes())
                    {
                        $child = $oldnodes->childNodes->item(0);
                        $child = $oldnodes->ownerDocument->importNode($child, true);
                        $newnode->appendChild($child);
                    }
                }
                $oldnodes->parentNode->replaceChild($newnode, $oldnodes);
                break;
        endswitch;

        return $this->saveHTML();
    }


    /**
     * set a single or multiple attributes value of a given tag.
     *
     * @param  mixed  $attrName
     * @param  string   $value
     * @param  array   $options
     * @return string
     */
    public function setAttributes($attrName, $value='',$options=array())
    {
        if(!$attrName or !$value) return false;
        if(!$options AND is_array($value)){
            $options = $this->checkTagName($options)!=='*'?$value:[];
            !$options OR $value=null;
        }
        if(!is_array($options)) $options = [$options];
        if(!$value)
            $value = isset($options['value'])?$options['value']:'';
        $tagName = null;
        if(is_array($attrName))
        {
            if(is_array($value)){
                if(sizeof($value) !== sizeof($attrName)) return false;
                else {$attrName = array_combine(array_values($attrName), array_values($value));}
            }
            else { $tagName = $value; }
        }
        else
        {
            if(is_array($value)) $value = join(' ',$value);
            $attrName = array($attrName=>$value);
        }
        $aTemp = [];
        foreach($attrName as $key=>$val)
        {
            if(!$val) continue;
            if(is_numeric($key))  $key = $val;
            $aTemp[$key] = $val;
        }
        $attrName = $aTemp;
        if(!is_array($options)) $options = array($options);
        if(!$tagName) $tagName =$this->checkTagName($options);
        $offset=(isset($options['offset'])and is_numeric($options['offset']))?intval($options['offset']): null;
        $attributes = $this->filterOptions($options);
        $query = $this->buildQuery($tagName,$attributes);
        $nodes = !is_null($offset)? $this->loadXpath()->query($query)->item($offset):$this->loadXpath()->query($query);
        switch($nodes):
            case ($nodes instanceof DomNodeList):
                foreach ($nodes as $node)
                {
                    if($node instanceof DomElement){
                        foreach($attrName as $key=>$value)
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
                }
                break;
            case ($nodes instanceof DomElement):
            default:
                foreach($attrName as $key=>$value)
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
        endswitch;
        return $this->saveHTML();
    }
    /**
     * @return $this
     */
    public function stripComments(){
        $nodes = $this->loadXpath()->query('//comment()');
        switch($nodes):
            case($nodes instanceof DomNodeList):
                foreach($nodes as $node)
                {
                    if($node instanceof DOMNode)
                        $node->parentNode->removeChild($node);
                }
                break;
            case($nodes instanceof DOMNode):
            default:
                $nodes->parentNode->removeChild($nodes);
                break;
        endswitch;
        $this->saveHTML();
        return $this;
    }
    /**
     * @return $this
     */
    public function stripScripts(){
        $nodes = $this->loadXpath()->query('//*[self::script or self::noscript]');
        switch($nodes):
            case($nodes instanceof DomNodeList):
                foreach($nodes as $node)
                {
                    if($node instanceof DOMNode)
                        $node->parentNode->removeChild($node);
                }
                break;
            case($nodes instanceof DOMNode):
            default:
                $nodes->parentNode->removeChild($nodes);
                break;
        endswitch;
        $this->saveHTML();
        return $this;
    }
    /**
     * @param $tagName
     * @param array $attribute
     * @param bool $jocker
     * @return string
     */
    private function buildQuery($tagName,$attribute=[],$jocker=false)
    {
        $tagName = $tagName?:'*';
        $query='//'.$tagName;
        $params = [];
        if($attribute)
        {
            if(!is_array($attribute))
            {
                if(!strpos($attribute,',')) $attribute = [$attribute];
                else $attribute = explode(',',$attribute);
            }
            foreach($attribute as $k=>$v)
                $params[] = !is_numeric($k)?'@'.strip_tags($k).'="'.$v.'"' : '@'.strip_tags($v);
        }
        if($params) $query.='['.implode(' and ',$params).']';
        if($jocker) $query .= '/*';
        return $query;
    }

    /**
     * @param array $options
     * @return array
     */
    private function filterOptions($options=[])
    {
        is_array($options) or $options = [$options];
        $options= array_change_key_case($options,CASE_LOWER);
       $Reserved = $this->Reserved;
        if(!$options) return [];
        if(version_compare(phpversion(), '5.6.0', '<'))
        return array_diff_key($options,array_flip($this->Reserved));
        return array_filter(
            $options,
            function ($key) use($Reserved) {
                return !in_array($key, $Reserved);
            },
            ARRAY_FILTER_USE_KEY
        );
    }
    /**
     * @param array $options
     * @return string
     */
    private function checkTagName($options=[])
    {
        $tagName = '*';
        is_array($options) or $options = [$options];
        $options = array_change_key_case($options,CASE_LOWER);
        if(isset($options['parent'])) $tagName = $options['parent'];
        if(isset($options['tag'])) $tagName = $options['tag'];
        if(isset($options['target'])) $tagName = $options['target'];
        if(isset($options['tagname'])) $tagName = $options['tagname'];
        if(isset($options['attribute'])) $tagName = $options['attribute'];
        if(isset($options['newname'])) $tagName = $options['newname'];
        return $tagName;
    }
    /**
     * @param bool $reset
     * @return DOMXPath
     */
    private function loadXpath($reset = false)
    {
        !$reset OR  $this->xpath = null;
        if(!$this->xpath instanceof DOMXPath)
        {
            $this->xpath = new DOMXpath($this);
            $this->xpath->registerNamespace('html', 'http://www.w3.org/1999/xhtml');
            $this->xpath->registerNamespace('php', 'http://php.net/xpath');
            $this->xpath->registerPHPFunctions();
        }
        return $this->xpath;
    }
}
