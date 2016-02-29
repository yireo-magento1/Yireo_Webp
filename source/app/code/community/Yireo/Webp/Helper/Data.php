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
     * @var Yireo_Webp_Helper_File
     */
    protected $fileHelper;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->fileHelper = Mage::helper('webp/file');
    }

    /**
     * Method to check whether this extension is enabled
     *
     * @return bool
     */
    public function enabled()
    {
        if ($this->isModuleEnabled() == false) {
            return false;
        }

        if ($this->hasWebpCookieEnabled()) {
            return true;
        }

        /** @var Mage_Core_Helper_Http $httpHelper */
        if ($this->isChromeBrowser()) {
            return true;
        }

        // Check for GD support
        if ($this->hasGdSupport()) {
            return true;
        }

        // Check for potential cwebp execution
        if ($this->hasBinarySupport()) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function hasWebpCookieEnabled()
    {
        $webpCookie = (int)Mage::app()
            ->getRequest()
            ->getCookie('webp', 0);

        if ($webpCookie == 1) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function hasBinarySupport()
    {
        if (Mage::getStoreConfig('web/webp/cwebp_enabled') == 0) {
            return false;
        }

        $cwebp = $this->getCwebpBinary();
        if (empty($cwebp)) {
            return false;
        }

        if (function_exists('exec') == false) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function hasGdSupport()
    {
        if (Mage::getStoreConfig('web/webp/gd_enabled') == 0) {
            return false;
        }

        if (!function_exists('imagewebp')) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function isChromeBrowser()
    {
        /** @var Mage_Core_Helper_Http $httpHelper */
        $httpHelper = Mage::helper('core/http');
        $browser = $httpHelper->getHttpUserAgent();
        if (preg_match('/Chrome\/(9|10|11|12|13|14|15|16)/', $browser)) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isModuleEnabled()
    {
        if ((bool)Mage::getStoreConfig('advanced/modules_disable_output/Yireo_WebP')) {
            return false;
        }

        $config_enabled = (bool)Mage::getStoreConfig('web/webp/enabled');
        if ($config_enabled == false) {
            return false;
        }

        return true;
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
        if ($enabled == false) {
            return false;
        }

        if (empty($image)) {
            return false;
        }

        // The image already exists
        if (preg_match('/\.webp$/i', $image)) {
            return false;
        }

        if (!$this->fileHelper->isWritableDir($image)) {
            return false;
        }

        return false;
    }

    /**
     * Method to convert an image to WebP
     *
     * @param string $imagePath
     *
     * @return string
     */
    public function convertToWebp($imagePath)
    {
        if (empty($imagePath)) {
            return false;
        }

        if (!$this->fileHelper->exists($imagePath)) {
            return false;
        }

        if ($this->enabled() == false) {
            return false;
        }

        // Detect alpha-transparency in PNG-images and skip it
        if ($this->hasAlphaTransparency($imagePath)) {
            return false;
        }

        // Construct the new WebP image-name
        $webpPath = $this->getWebpNameFromImage($imagePath);

        // Check for the current WebP image
        if ($this->fileHelper->exists($webpPath) && $this->fileHelper->isNewerThan($webpPath, $imagePath)) {
            return $webpPath;
        }

        // GD function
        $webpPath = $this->convertToWebpViaGd($imagePath, $webpPath);
        if ($this->fileHelper->exists($webpPath)) {
            return $webpPath;
        }

        // Only do the following if the WebP image does not yet exist, or if the original PNG/JPEG seems to be updated
        return $this->convertToWebpViaBinary($imagePath, $webpPath);
    }

    /**
     * Method to convert an image to WebP using the GD method
     *
     * @param $imagePath
     * @param $webpPath
     *
     * @return bool
     */
    public function convertToWebpViaGd($imagePath, $webpPath)
    {
        if ($this->hasGdSupport() == false) {
            return false;
        }

        if (preg_match('/\.png$/', $imagePath) && function_exists('imagecreatefrompng')) {
            $image = imagecreatefrompng($imagePath);
        } elseif (preg_match('/\.gif$/', $imagePath) && function_exists('imagecreatefromgif')) {
            $image = imagecreatefromgif($imagePath);
        } elseif (preg_match('/\.(jpg|jpeg)$/', $imagePath) && function_exists('imagecreatefromjpeg')) {
            $image = imagecreatefromjpeg($imagePath);
        } else {
            return false;
        }

        imagewebp($image, $webpPath);

        return $webpPath;
    }

    /**
     * Method to convert an image to WebP using the binary method
     *
     * @param $imagePath
     * @param $webpPath
     *
     * @return bool
     */
    public function convertToWebpViaBinary($imagePath, $webpPath)
    {
        if ($this->hasBinarySupport() == false) {
            return false;
        }

        $cwebp = $this->getCwebpBinary();
        $cmd = $cwebp . '  -quiet ' . $imagePath . ' -o ' . $webpPath;
        exec($cmd, $output, $return);

        return $webpPath;
    }

    /**
     * Detect whether an image has PNG alpha transparency
     *
     * @param $image
     *
     * @return bool
     */
    public function hasAlphaTransparency($image)
    {
        if (empty($image)) {
            return false;
        }

        if ($this->fileHelper->exists($image) == false) {
            return false;
        }

        if (preg_match('/\.png$/', $image)) {
            return false;
        }

        $fileIo = new Yireo_Webp_Lib_Io_File();
        $fileIo->setCwd(dirname($image));
        $fileIo->setIwd(dirname($image));

        $imageContents = $fileIo->read($image);
        $colorType = ord(substr($imageContents, 25, 1));

        if ($colorType == 6 || $colorType == 4) {
            return true;
        } elseif (stripos($imageContents, 'PLTE') !== false && stripos($imageContents, 'tRNS') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Get the WebP path equivalent of an image path
     *
     * @param $image
     *
     * @return mixed
     */
    public function getWebpNameFromImage($image)
    {
        return preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $image);
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
                'path' => Mage::getBaseDir('skin').DS),
            'media' => array(
                'url' => Mage::getBaseUrl('media'),
                'path' => Mage::getBaseDir('media').DS),
            'base' => array(
                'url' => Mage::getBaseUrl(),
                'path' => Mage::getBaseDir('base').DS));

        return $systemPaths;
    }

    /**
     * Get the path to the "cwebp" binary
     *
     * @return string
     */
    public function getCwebpBinary()
    {
        $cwebp = $this->getCwebpPath();
        if (empty($cwebp)) {
            return null;
        }

        if (preg_match('/\/$/', $cwebp)) {
            return $cwebp . 'cwebp';
        }

        return $cwebp;
    }

    /**
     * @return mixed
     */
    protected function getCwebpPath()
    {
        return Mage::getStoreConfig('web/webp/cwebp_path');
    }
}
