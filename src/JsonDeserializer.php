<?php
namespace Utils;

interface JsonDeserializer
{
    /**
     * @param string|array $json
     * @return $this
     */
    public static function jsonDeserialize($json);    
}
