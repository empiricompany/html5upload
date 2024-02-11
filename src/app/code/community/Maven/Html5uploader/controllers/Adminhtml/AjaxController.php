<?php
class Maven_Html5uploader_Adminhtml_AjaxController extends Mage_Adminhtml_Controller_Action
{
    protected function _isAllowed()
    {
        return true;
    }

    public function uploadAction()
    {
        try {
            $uploader = new Maven_Html5uploader_Model_Media_Uploader();
            $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
            $uploader->setAllowRenameFiles(true);
            $uploader->setAllowCreateFolders(true);
            $uploader->setFilesDispersion(true);
            $result = $uploader->save(
                Mage::getSingleton('catalog/product_media_config')->getBaseTmpMediaPath()
            );

        } catch (Exception $e) {
            $result = array(
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode());
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function cmsuploadAction()
    {
        try {
            $this->_initCmsAction();
            $targetPath = $this->getStorage()->getSession()->getCurrentPath();
            $uploader = new Maven_Html5uploader_Model_Media_Uploader();
            $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);
            $result = $uploader->save(
                $targetPath
            );

        } catch (Exception $e) {
            $result = array(
                'error' => $e->getMessage(),
                'errorcode' => $e->getCode());
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
    
    public function removeAction()
    {
        try {
            $path = $this->getRequest()->getParam('path');
            $file = $this->getRequest()->getParam('file');
            $uploader = new Maven_Html5uploader_Model_Media_Uploader();
            $result = array('success' => $uploader->remove($file, $path));

        } catch (Exception $e) {
            $result = array(
                'error'     => $e->getMessage(),
                'success'   => false,
                'errorcode' => $e->getCode());
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    /**
     * Register storage model and return it
     *
     * @return Mage_Cms_Model_Wysiwyg_Images_Storage
     */
    public function getStorage()
    {
        if (!Mage::registry('storage')) {
            $storage = Mage::getModel('cms/wysiwyg_images_storage');
            Mage::register('storage', $storage);
        }
        return Mage::registry('storage');
    }

    protected function _initCmsAction()
    {
        $this->getStorage();
        return $this;
    }
}