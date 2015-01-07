<?php
/**
 * Webp plugin for Magento 
 *
 * @package     Yireo_Webp
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

class Yireo_Webp_Model_Observer
{
    /*
     * Listen to the event core_block_abstract_to_html_after
     * 
     * @access public
     * @parameter Varien_Event_Observer $observer
     * @return $this
     */
    public function coreBlockAbstractToHtmlAfter($observer)
    {
        if(Mage::helper('webp')->enabled() == false) {
            return $this;
        }

        $transport = $observer->getEvent()->getTransport();
        $block = $observer->getEvent()->getBlock();
        $systemPaths = Mage::helper('webp')->getSystemPaths();

        $allowedBlocks = array('root');
        //$allowedBlocks = array('root', 'content');
        if(in_array($block->getNameInLayout(), $allowedBlocks)) {
            $layout = Mage::app()->getLayout();
            $html = $transport->getHtml();

            $newHtml = array();
            if(preg_match_all('/\ src=\"([^\"]+)\.(png|jpg|jpeg)/i', $html, $matches)) {

                $imageList = array();
                foreach($matches[0] as $index => $match) {

                    // Convert the URL to a valid path
                    $imagePath = null;
                    $imageUrl = $matches[1][$index].'.'.$matches[2][$index];
                    if(preg_match('/^http/', $imageUrl)) {
                        foreach($systemPaths as $systemPath) {
                            if(strstr($imageUrl, $systemPath['url'])) {
                                $imagePath = str_replace($systemPath['url'], $systemPath['path'].DS, $imageUrl);
                                break;
                            }
                        }
                    }

                    // If this failed, don't continue
                    if(!file_exists($imagePath)) {
                        continue;
                    }
    
                    // Construct the new WebP image-name
                    $webpPath = Mage::helper('webp')->convertToWebp($imagePath);

                    // If this failed, don't continue
                    if(empty($webpPath) || file_exists($webpPath) == false) {
                        continue;
                    }

                    // Convert the path back to a valid URL
                    $webpUrl = null;
                    foreach($systemPaths as $systemPath) {
                        if(strstr($webpPath, $systemPath['path'])) {
                            $webpUrl = str_replace($systemPath['path'], $systemPath['url'].DS, $webpPath);
                            break;
                        }
                    }

                    // Replace the img tag in the HTML
                    $htmlTag = $matches[0][$index];
                    $newHtmlTag = str_replace('src="'.$imageUrl, 'data-img="'.md5($imageUrl), $htmlTag);
                    $html = str_replace($htmlTag, $newHtmlTag, $html);
    
                    // Add the images to the return-array
                    $imageList[md5($imageUrl)] = array('orig' => $imageUrl, 'webp' => $webpUrl);
                }

                // Add a JavaScript-list to the HTML-document
                if(!empty($imageList)) {
                    $newHtml[] = '<script>';
                    $newHtml[] = 'var SKIN_URL = \''.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN).'\';';
                    $webpCookie = (isset($_COOKIE['webp'])) ? (int)$_COOKIE['webp'] : 0 ;
                    $newHtml[] = 'var WEBP_COOKIE = '.$webpCookie.';';
                    $newHtml[] = 'if(webpReplacements == null) { var webpReplacements = new Object(); }';
                    foreach($imageList as $name => $value) {
                        $newHtml[] = 'webpReplacements[\''.$name.'\'] = '.json_encode($value);
                    }
                    $newHtml[] = '</script>';
                }
            }

            if($block->getNameInLayout() == 'root') {
                $newHtml[] = '<script type="text/javascript" src="'.Mage::getBaseUrl('js').'webp/jquery.detect.js"></script>';
            }

            $newHtml = implode("\n", $newHtml)."\n";
            if(strstr($html, '</body>')) {
                $html = str_replace('</body>', $newHtml.'</body>', $html);
            } else {
                $html = $html.$newHtml;
            }

            $transport->setHtml($html);
        }

        return $this;
    }
}
