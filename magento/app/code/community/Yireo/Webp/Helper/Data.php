<?php
/**
 * Yireo Webp for Magento 
 *
 * @package     Yireo_Webp
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (c) 2012 Yireo (http://www.yireo.com/)
 * @license     Open Yireo License
 */

/**
 * Webp helper
 */
class Yireo_Webp_Helper_Data extends Mage_Core_Helper_Abstract
{
    /*
     * Method to check whether this extension is enabled
     */
    public function enabled()
    {
        static $enabled = null;
        if($enabled === null) {

            $config_enabled = (bool)Mage::getStoreConfig('web/webp/enabled');
            $browser = $_SERVER['HTTP_USER_AGENT'];
            $cwebp = Mage::getStoreConfig('web/webp/cwebp_path');

            $enabled = true;
            if($config_enabled == false) {
                $enabled = false;
            } elseif(isset($_COOKIE['webp']) && $_COOKIE['webp'] == 1) {
                $enabled = true;
            } elseif(preg_match('/Chrome\/(9|10|11|12|13|14|15|16)/', $browser)) { 
                $enabled = true;
            } elseif(empty($cwebp)) {
                $enabled = false;
            } elseif(function_exists('exec') == false) {
                $enabled = false;
            }
        }
        return $enabled;
    }

    /*
     * Method to check whether WebP should actually be introduced
     */
    public function allowWebp($image)
    {
        $enabled = $this->enabled();
        if($enabled == false) {
            return false;
        }

        if(empty($image)) {
            return false;
        }

        if(preg_match('/\.webp$/i', $image)) {
            return false;
        }

        if(!is_writable(dirname($image))) {
            return false;
        }

        return false;
    }

    /*
     * Method to convert an image to WebP
     */
    public function convertToWebp($image)
    {
        if(empty($image) || !file_exists($image) || !is_readable($image)) {
            return null;
        }

        // Detect alpha-transparency in PNG-images and skip it
        if(preg_match('/\.png$/', $image)) {
            $imageContents = @file_get_contents($image);
            $colorType = ord(@file_get_contents($image, NULL, NULL, 25, 1));
            if($colorType == 6 || $colorType == 4) {
                return null;
            } elseif(stripos($imageContents, 'PLTE') !== false && stripos($imageContents, 'tRNS') !== false) {
                return null;
            }
        }

        // Construct the new WebP image-name
        $webp = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $image);

        // Only do the following if the WebP image does not yet exist, or if the original PNG/JPEG seems to be updated
        if((!file_exists($webp)) || (file_exists($image) && filemtime($image) > filemtime($webp))) {
            $cwebp = Mage::getStoreConfig('web/webp/cwebp_path');
            $cmd = $cwebp.' -quiet '.$image.' -o '.$webp;
            exec($cmd, $output, $return);
        }

        return $webp;
    }

    public function getSystemPaths()
    {
        $systemPaths = array(
            'skin' => array(
                'url' => Mage::getBaseUrl('skin'),
                'path' => Mage::getBaseDir('skin'),
            ),
            'media' => array(
                'url' => Mage::getBaseUrl('media'),
                'path' => Mage::getBaseDir('media'),
            ),
            'base' => array(
                'url' => Mage::getBaseUrl(),
                'path' => Mage::getBaseDir('base'),
            ),
        );

        return $systemPaths;
    }
}
