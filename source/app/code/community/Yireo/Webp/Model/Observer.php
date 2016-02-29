<?php
/**
 * Webp plugin for Magento
 *
 * @package     Yireo_Webp
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

/**
 * Class Yireo_Webp_Model_Observer
 */
class Yireo_Webp_Model_Observer
{
    /**
     * @var Yireo_Webp_Helper_Data
     */
    protected $helper;

    /**
     * @var Yireo_Webp_Helper_File
     */
    protected $fileHelper;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->helper = Mage::helper('webp');
        $this->fileHelper = Mage::helper('webp/file');
    }

    /**
     * @param Mage_Core_Block_Abstract $block
     *
     * @return bool
     */
    protected function isAllowedBlock($block)
    {
        $allowedBlocks = array('root');

        if (in_array($block->getNameInLayout(), $allowedBlocks)) {
            return true;
        }

        return false;
    }

    /**
     * Listen to the event core_block_abstract_to_html_after
     *
     * @parameter Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function coreBlockAbstractToHtmlAfter($observer)
    {
        if ($this->helper->enabled() == false) {
            return $this;
        }

        $transport = $observer->getEvent()->getTransport();
        $block = $observer->getEvent()->getBlock();

        if ($this->isAllowedBlock($block) == false) {
            return $this;
        }

        $html = $transport->getHtml();

        if (preg_match_all('/\ src=\"([^\"]+)\.(png|jpg|jpeg)/i', $html, $matches) == false) {
            return $this;
        }

        $imageList = array();
        foreach ($matches[0] as $index => $match) {

            // Convert the URL to a valid path
            $imageUrl = $matches[1][$index] . '.' . $matches[2][$index];
            $webpUrl = $this->convertImageUrlToWebp($imageUrl);

            if (empty($webpUrl)) {
                return false;
            }

            // Replace the img tag in the HTML
            $htmlTag = $matches[0][$index];
            $newHtmlTag = str_replace('src="' . $imageUrl, 'data-img="' . md5($imageUrl), $htmlTag);
            $html = str_replace($htmlTag, $newHtmlTag, $html);

            // Add the images to the return-array
            $imageList[md5($imageUrl)] = array('orig' => $imageUrl, 'webp' => $webpUrl);
        }

        // Add a JavaScript-list to the HTML-document
        if (empty($imageList)) {
            return $this;
        }

        $newHtml = $this->getScriptHtmlLines($imageList);

        if ($block->getNameInLayout() == 'root') {
            $newHtml[] = '<script type="text/javascript" src="' . Mage::getBaseUrl('js') . 'webp/jquery.detect.js"></script>';
        }

        $html = $this->addScriptToBody($html, $newHtml);
        $transport->setHtml($html);

        return $this;
    }

    /**
     * @param $imageUrl
     *
     * @return bool|mixed
     */
    protected function convertImageUrlToWebp($imageUrl)
    {
        $imagePath = $this->getImagePathFromUrl($imageUrl);

        if (empty($imagePath)) {
            return false;
        }

        if ($this->fileHelper->exists($imagePath) == false) {
            return false;
        }

        // Construct the new WebP image-name
        $webpPath = $this->helper->convertToWebp($imagePath);

        if (empty($webpPath)) {
            return false;
        }

        if ($this->fileHelper->exists($webpPath) == false) {
            return false;
        }

        // Convert the path back to a valid URL
        $webpUrl = $this->getImageUrlFromPath($webpPath);

        if (empty($webpUrl)) {
            return false;
        }

        return $webpUrl;
    }

    /**
     * @param $imageList
     *
     * @return array
     */
    protected function getScriptHtmlLines($imageList)
    {
        $newHtml = array();

        $newHtml[] = '<script>';
        $newHtml[] = 'var SKIN_URL = \'' . Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN) . '\';';
        $webpCookie = (int)Mage::app()->getRequest()->getCookie('webp', 0);

        $newHtml[] = 'var WEBP_COOKIE = ' . $webpCookie . ';';
        $newHtml[] = 'if(webpReplacements == null) { var webpReplacements = new Object(); }';
        foreach ($imageList as $name => $value) {
            $newHtml[] = 'webpReplacements[\'' . $name . '\'] = ' . json_encode($value);
        }
        $newHtml[] = '</script>';

        return $newHtml;
    }

    /**
     * @param string $imagePath
     *
     * @return mixed
     */
    protected function getImageUrlFromPath($imagePath)
    {
        $systemPaths = $this->helper->getSystemPaths();

        foreach ($systemPaths as $systemPath) {
            if (strstr($imagePath, $systemPath['path'])) {
                return str_replace($systemPath['path'], $systemPath['url'], $imagePath);
            }
        }
    }

    /**
     * @param string $imageUrl
     *
     * @return mixed
     */
    protected function getImagePathFromUrl($imageUrl)
    {
        $systemPaths = $this->helper->getSystemPaths();

        if (preg_match('/^http/', $imageUrl)) {
            foreach ($systemPaths as $systemPath) {
                if (strstr($imageUrl, $systemPath['url'])) {
                    return str_replace($systemPath['url'], $systemPath['path'], $imageUrl);
                }
            }
        }
    }

    /**
     * @param $html
     * @param $scriptLines
     *
     * @return mixed|string
     */
    protected function addScriptToBody($html, $scriptLines)
    {
        $script = implode("\n", $scriptLines) . "\n";
        if (strstr($html, '</body>')) {
            $html = str_replace('</body>', $script . '</body>', $html);
        } else {
            $html = $html . $script;
        }

        return $html;
    }
}
