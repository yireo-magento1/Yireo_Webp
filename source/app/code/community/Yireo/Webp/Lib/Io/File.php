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
class Yireo_Webp_Lib_Io_File extends Varien_Io_File
{
    public function setIwd($iwd)
    {
        $this->_iwd = $iwd;
    }

    public function setCwd($cwd)
    {
        $this->_cwd = $cwd;
    }
}