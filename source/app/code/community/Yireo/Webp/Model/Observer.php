<?php
/**
 * Webp plugin for Magento 
 *
 * @package     Yireo_Webp
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (C) 2014 Yireo (http://www.yireo.com/)
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

        $allowedBlocks = array('root', 'content');
        if(in_array($block->getNameInLayout(), $allowedBlocks)) {
            $layout = Mage::app()->getLayout();
            $html = $transport->getHtml();

            $newHtml = array();
            if(preg_match_all('/([^\'\"]+)\.(png|jpg|jpeg)/i', $html, $matches)) {

                $imageList = array();
                foreach($matches[0] as $oldImageUrl) {

                    // Convert the URL to a valid path
                    $oldImagePath = null;
                    if(preg_match('/^http/', $oldImageUrl)) {
                        foreach($systemPaths as $systemPath) {
                            if(strstr($oldImageUrl, $systemPath['url'])) {
                                $oldImagePath = str_replace($systemPath['url'], $systemPath['path'].DS, $oldImageUrl);
                                break;
                            }
                        }
                    }

                    // If this failed, don't continue
                    if(!file_exists($oldImagePath)) continue;
    
                    // Construct the new WebP image-name
                    $newImagePath = Mage::helper('webp')->convertToWebp($oldImagePath);

                    // If this failed, don't continue
                    if(empty($newImagePath) || file_exists($newImagePath) == false) continue;

                    // Convert the path back to a valid URL
                    $newImageUrl = null;
                    foreach($systemPaths as $systemPath) {
                        if(strstr($newImagePath, $systemPath['path'])) {
                            $newImageUrl = str_replace($systemPath['path'], $systemPath['url'].DS, $newImagePath);
                            break;
                        }
                    }
    
                    // Add the images to the return-array
                    $imageList[$oldImageUrl] = $newImageUrl;
                }

                // Add a JavaScript-list to the HTML-document
                if(!empty($imageList)) {
                    $newHtml[] = '<script type="text/javascript">';
                    $newHtml[] = 'if(webpReplacements == null) { var webpReplacements = new Object(); }';
                    foreach($imageList as $name => $value) {
                        $newHtml[] = 'webpReplacements["'.$name.'"] = "'.$value.'";';
                    }
                    $newHtml[] = '</script>';
                }
            }

            if($block->getNameInLayout() == 'root') {
                $newHtml[] = '<script type="text/javascript" src="'.Mage::getBaseUrl('js').'webp/detect.js"></script>';
            }

            $newHtml = implode("\n", $newHtml)."\n";
            if(strstr($html, '</body>')) {
                $html = str_replace('</body>', $newHtml.'</body>', $html);
            } else {
                $html = $html.$newHtml;
            }

            $transport->setHtml($html);
        }

        if((bool)Mage::getStoreConfig('web/webp/enabled') == true) {
            if($block->getNameInLayout() == 'head') {
                $newHtml = '<link rel="stylesheet" type="text/css" href="'.Mage::getDesign()->getSkinUrl('css/webp.css').'"/>';
                $html = $transport->getHtml().$newHtml;
                $transport->setHtml($html);
            }
        }

        return $this;
    }
}
