<?php

/**
 * This file is part of the PHP Oxygen package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright   Copyright (c) 2011 Sébastien HEYD <sheyd@php-oxygen.com>
 */

/**
 * {$className} class 
 * 
 * This file was auto-generated on {$smarty.now|date_format:"%A, %B%e, %Y at %H:%M:%S"}
 */

class {$className} extends Document
{
{foreach from=$vars key=var item=value}
    protected ${$var}{if $value.Default !== null} = '{$value.Default}'{/if};
{/foreach}

    protected static $tableName = '{$tableName}';
    {if $primary != ''}protected static $primaryKeys = array('{$primary}');{/if}
    

    /**
     * @return string
     */
    public function __toString()
    {
        $txt = '{$className}';
{if $primary != ''}
        foreach(self::$primaryKeys as $k) $txt .= ' '.$this->$k;
{/if}
        return $txt;
    }
    
    /**
     * @return string
     */
    public function getTableName()
    {
        return self::$tableName;
    }

{if $primary != ''}
    /**
     * @return array
     */
    public function getPrimary()
    {
        return self::$primaryKeys;
    }
{/if}    
{foreach from=$fields item=field}
{if $field.Key != ''}
    /**
     * @param ${$field.Field} {$field.Type}
{if $field.LoadMethod=='fetchAllObject'}
     * @return array
{else}
     * @return {$className}
{/if}
     */
    public static function {$field.LoadByMethodName}(${$field.Field})
    {
        return DB::select()->from(self::$tableName)->where('{$field.Field}', ${$field.Field})->{$field.LoadMethod}(get_called_class());
    }

{/if}
{/foreach}
{foreach from=$fields item=field}
{if isset($field.ReferencedBy)}
{foreach from=$field.ReferencedBy item=ref}
    /**
     * @return f_db_Select
     */
    public function {$ref.searchMethod}()
    {
        return DB::select()->from('{$ref.table}')->where('{$ref.column}', $this->{$field.getMethodName}());
    }
    
    /**
     * @return array
     */    
    public function {$ref.method}()
    {
        return $this->{$ref.searchMethod}()->fetchAllObject('{$ref.class}');
    }

{/foreach}
{/if}
{/foreach}
{foreach from=$fields item=field}
   /**
{if isset($field.Get)}
    * @return {$field.Get.class}
{else}
{if $field.Type == 'datetime' || $field.Type == 'date'}
    * return Date
{else}
    * @return {$field.Type}
{/if}
{/if}
    */
    public function {$field.getMethodName}()
    {
{if isset($field.Get)}
        return DB::select()->from('{$field.Get.table}')->where('{$field.Get.primary}', $this->{$field.Field})->fetchObject('{$field.Get.class}');
{else}
{if $field.Type == 'datetime' || $field.Type == 'date'}
        try
        {
            return Date::fromMySql($this->{$field.Field});
        }
        catch(Exception $e)
        {
            return null;
        }
{else}
        return $this->{$field.Field};
{/if}
{/if}
    }
    
{if $field.Type == 'datetime' || $field.Type == 'date'}
    /**
     * @return string
     */
    public function {$field.getMethodName}Smart($format = 'fulltext-date-time')
    {
        try
        {
            $dateObj = $this->{$field.getMethodName}();
            if($dateObj === null) return '';
            return $this->{$field.getMethodName}()->toSmartFormat($format);
        }
        catch(Exception $e)
        {
            return '';
        }        
    }
    
    /**
     * @return string
     */
    public function {$field.getMethodName}Diff()
    {
        try
        {
            $dateObj = $this->{$field.getMethodName}();
            if($dateObj === null) return '';
            return $this->{$field.getMethodName}()->toDiff();
        }
        catch(Exception $e)
        {
            return '';
        }        
    }
{/if}

{if $field.Extra != 'auto_increment'}
   /**
    * @return {$className}
    */
    public function {$field.setMethodName}($value)
    {
        $this->{$field.Field} = $value;
        return $this;
    }
{/if}
    
{/foreach}

    ################## GENERAL METHODS ####################

    /**
     * @return {$className}
     */
    public static function create()
    {
        return new self();
    }    
    
    /**
     * @return {$className}
     */
    public static function load($args)
    {
        return parent::_load($args, '{$className}');
    }
        
    /**
     * @return {$className}[]
     */
    public static function loadAll($order = null, $orderDirection = 'ASC')
    {
        return parent::_loadAll('{$className}', $order, $orderDirection);
    }     
}