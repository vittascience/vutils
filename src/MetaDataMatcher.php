<?php

namespace Utils;

require_once('../vendor/autoload.php');

abstract class MetaDataMatcher
{
    const PATTERN_ATTRIBUTE_NAME = "/@DEM_name=.*/";
    const PATTERN_ATTRIBUTE_MAPPED = "/@DEM_mapped/";
    const PATTERN_VAR = "/@var .*/";

    // Run unit tests for this
    // document
    // throw errors
    private static function matchProvider($pattern, $string, $sep = "=")
    {
        preg_match($pattern, $string, $matched);
        return @rtrim(substr($matched[0], strrpos($matched[0], $sep) + 1, strlen($matched[0])));
    }

    private static function getReflectionProperty($class, $attributeName)
    {
        return new \ReflectionProperty($class, $attributeName);
    }

    public static function matchAttributeType($class, $attributeName)
    {
        $reflectionProperty = self::getReflectionProperty($class, $attributeName);
        return self::matchProvider(self::PATTERN_VAR, $reflectionProperty->getDocComment(), " ");
    }
}


/*
        $matchAttributeMappedName = $this->matchProvider(self::PATTERN_ATTRIBUTE_NAME, $reflectionProperty->getDocComment());
        $matchAttributeIsMapped = $this->matchProvider(self::PATTERN_ATTRIBUTE_MAPPED, $reflectionProperty->getDocComment(), "");
*/
