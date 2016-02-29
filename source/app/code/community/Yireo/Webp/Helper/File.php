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
 * Webp file helper
 */
class Yireo_Webp_Helper_File extends Mage_Core_Helper_Abstract
{
    /**
     * Method to check to see if a file exists or not
     *
     * @param string $file
     * @return bool
     */
    public function exists($file)
    {
        if (file_exists($file)) {
            return true;
        }

        $validator = new Zend_Validate_File_Exists();

        if ($validator->isValid($file) == true) {
            return true;
        }

        return false;
    }

    /**
     * Method to check to see if a file is writable or not
     *
     * @param $file
     *
     * @return bool
     */
    public function isWritable($file)
    {
        $fileIo = new Varien_Io_File;

        return $fileIo->isWriteable($file);
    }

    /**
     * Method to check to see if a file is writable or not
     *
     * @param $file
     *
     * @return bool
     */
    public function isWritableDir($file)
    {
        $fileIo = new Varien_Io_File;
        $fileHandler = new Varien_File_Object($file);

        return $fileIo->isWriteable($fileHandler->getDirName());
    }

    /**
     * Method to return the modification time of a file
     *
     * @param $file
     *
     * @return int
     */
    public function getModificationTime($file)
    {
        $fileHandler = new Varien_File_Object($file);

        return $fileHandler->getCTime();
    }

    /**
     * Method to check if a $file1 is newer than a $file2
     *
     * @param $file1
     * @param $file2
     *
     * @return bool
     */
    public function isNewerThan($file1, $file2)
    {
        $file1ModificationTime = $this->getModificationTime($file1);
        $file2ModificationTime = $this->getModificationTime($file2);

        if($file1ModificationTime > $file2ModificationTime) {
            return true;
        }

        return false;
    }
}
