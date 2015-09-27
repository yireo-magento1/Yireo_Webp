<?php
/**
 * Yireo Webp for Magento
 *
 * @package     Yireo_Webp
 * @author      Yireo <info@yireo.com>
 * @copyright   2015 Yireo <https://www.yireo.com/>
 * @license     Open Source License (OSL v3)
 */

/**
 * Webp helper
 */
class Yireo_Webp_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Method to check whether this extension is enabled
     *
     * @return bool
     */
    public function enabled()
    {
        if ((bool)Mage::getStoreConfig('advanced/modules_disable_output/Yireo_WebP')) {
            return false;
        }

        $config_enabled = (bool)Mage::getStoreConfig('web/webp/enabled');
        if($config_enabled == false) {
            return false;
        }

        $webpCookie = (int)Mage::app()->getRequest()->getCookie('webp', 0);
        if($webpCookie == 1) {
            return true;
        }

        $browser = Mage::helper('core/http')->getHttpUserAgent();
        if(preg_match('/Chrome\/(9|10|11|12|13|14|15|16)/', $browser)) { 
            return true;
        }

        // Check for GD support
        if (Mage::getStoreConfig('web/webp/gd_enabled') == 1 && function_exists('imagewebp')) {
            return true;
        }

        // Check for potential cwebp execution
        $cwebp = $this->getCwebpBinary();
        if(Mage::getStoreConfig('web/webp/cwebp_enabled') == 1 &&  !empty($cwebp) && function_exists('exec')) {
            return true;
        }

        return false;
    }

    /**
     * Method to check whether WebP should actually be introduced
     *
     * @param string $image
     *
     * @return bool
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

        // The image already exists
        if(preg_match('/\.webp$/i', $image)) {
            return false;
        }

        if(!is_writable(dirname($image))) {
            return false;
        }

        return false;
    }

    /**
     * Method to convert an image to WebP
     *
     * @param string $imagePath
     * @return string
     */
    public function convertToWebp($imagePath)
    {
        if(empty($imagePath) || !file_exists($imagePath) || !is_readable($imagePath)) {
            return;
        }

        if($this->enabled() == false) {
            return;
        }

        // Detect alpha-transparency in PNG-images and skip it
        if(preg_match('/\.png$/', $imagePath)) {
            $imageContents = file_get_contents($image);
            $colorType = ord(file_get_contents($image, NULL, NULL, 25, 1));
            if($colorType == 6 || $colorType == 4) {
                return;
            } elseif(stripos($imageContents, 'PLTE') !== false && stripos($imageContents, 'tRNS') !== false) {
                return;
            }
        }

        // Construct the new WebP image-name
        $webpPath = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $imagePath);

        // Check for the current WebP image
        if(file_exists($webpPath) && filemtime($imagePath) < filemtime($webpPath)) {
            return $webpPath;
        }

        // GD function
        if (Mage::getStoreConfig('web/webp/gd_enabled') == 1 && function_exists('imagewebp')) {
            if(preg_match('/\.png$/', $imagePath) && function_exists('imagecreatefrompng')) {
                $image = imagecreatefrompng($imagePath);
            } elseif(preg_match('/\.gif$/', $imagePath) && function_exists('imagecreatefromgif')) {
                $image = imagecreatefromgif($imagePath);
            } elseif(preg_match('/\.(jpg|jpeg)$/', $imagePath) && function_exists('imagecreatefromjpeg')) {
                $image = imagecreatefromjpeg($imagePath);
            } else {
                return;
            }

            imagewebp($image, $webpPath);
            return $webpPath;
        }

        // Only do the following if the WebP image does not yet exist, or if the original PNG/JPEG seems to be updated
        if(Mage::getStoreConfig('web/webp/cwebp_enabled') == 1) {
            $cwebp = $this->getCwebpBinary();
            $cmd = $cwebp.'  -quiet '.$imagePath.' -o '.$webpPath;
            exec($cmd, $output, $return);
            return $webpPath;
        }

        return;
    }

    /**
     * Return all the system paths
     *
     * @return array
     */
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

    /**
     * Get the path to the "cwebp" binary
     *
     * @return string
     */
    public function getCwebpBinary()
    {
        $cwebp = Mage::getStoreConfig('web/webp/cwebp_path');
        if (empty($cwebp)) {
            return;
        }

        if (preg_match('/\/$/', $cwebp)) {
            return $cwebp.'cwebp';
        }

        return $cwebp;
    }
}
