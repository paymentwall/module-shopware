<?php

class Shopware_Plugins_Frontend_Paymentwall_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{

    /**
     * Indicates the caches to be cleared after install/enable/disable the plugin
     * @var type
     */
    private $clearCache = array(
        'config', 'backend', 'theme'
    );

    /**
     * Returns the version
     *
     * @return string
     */
    public function getVersion()
    {
        return "2.0.0";
    }

    /**
     * Get Info for the Pluginmanager
     *
     * @return array
     */
    public function getInfo()
    {
        return array(
            'version' => $this->getVersion(),
            'author' => 'The Paymentwall Team',
            'source' => $this->getSource(),
            'supplier' => 'Paymentwall',
            'support' => 'support@paymentwall.com',
            'link' => 'https://www.paymentwall.com',
            'copyright' => 'Copyright (c) 2017, Paymentwall',
            'label' => 'Paymentwall',
            'description' => '<h2>Payment plugin for Shopware Community Edition Version 5.3</h2>'
        );
    }

    /**
     * Fixes a known issue.
     *
     * @throws Exception
     */
    private function solveKnownIssue()
    {
        try {
            //Deleting translation for mainshop which causes in not be able to change it via backend
            Shopware()->Db()->delete('s_core_translations', Shopware()->Db()->quoteInto('objecttype = ?', 'config_payment')
                . ' AND ' . Shopware()->Db()->quoteInto('objectkey = ?', 1)
                . ' AND ' . Shopware()->Db()->quoteInto('objectlanguage = ?', '1')
            );
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Performs the necessary installation steps
     *
     * @throws Exception
     * @return boolean
     */
    public function install()
    {
        try {
            $this->createPaymentMeans();
            $this->_createForm();
            $this->_registerController();
            $this->_applyBackendViewModifications();
            $this->_translatePaymentNames();
            $this->solveKnownIssue();
            $this->Plugin()->setActive(true);
        } catch (Exception $exception) {
            $this->uninstall();
            throw new Exception($exception->getMessage());
        }

        return array('success' => parent::install(), 'invalidateCache' => $this->clearCache);
    }

    /**
     * Performs the necessary uninstall steps
     *
     * @return boolean
     */
    public function uninstall()
    {
        $this->removeSnippets();
        return parent::uninstall();
    }

    /**
     * Updates the Plugin and its components
     *
     * @param string $oldVersion
     *
     * @throws Exception
     * @return boolean
     */
    public function update($oldVersion)
    {
        try {
            switch ($oldVersion) {
                case "1.0.0":
                    $sql = "DELETE FROM s_core_config_element_translations
                        WHERE element_id IN (SELECT s_core_config_elements.id FROM s_core_config_elements
                        WHERE s_core_config_elements.form_id = (SELECT s_core_config_forms.id FROM s_core_config_forms
                        WHERE s_core_config_forms.plugin_id = ?));
                        DELETE FROM s_core_config_elements
                        WHERE form_id = (SELECT id FROM s_core_config_forms WHERE plugin_id = ?);";
                    Shopware()->Db()->query($sql, array($this->getId(), $this->getId()));
            }
            return true;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * Translates the payment names
     *
     * @throws Exception
     * @return void
     */
    private function _translatePaymentNames()
    {
        try {
            $paymentwall = $this->Payments()->findOneBy(array('name' => 'pwlocal'));
            $brick = $this->Payments()->findOneBy(array('name' => 'brick'));

            $sortedSnippets = parse_ini_file(dirname(__FILE__) . '/Snippets/frontend/paymentwall/checkout/payments.ini', true);
            $shops = Shopware()->Db()->select()
                ->from('s_core_shops', array('id', 'default'))
                ->joinInner('s_core_locales', '`s_core_shops`.`locale_id`=`s_core_locales`.`id`', 'locale')
                ->query()
                ->fetchAll();

            foreach ($shops as $shop) {
                $shopId = $shop['id'];
                $locale = $shop['locale'];
                $this->updatePaymentTranslation($shopId, $brick->getID(), $sortedSnippets[$locale]['creditcard'], $shop['default']);
                $this->updatePaymentTranslation($shopId, $paymentwall->getID(), $sortedSnippets[$locale]['directdebit'], $shop['default']);
            }

        } catch (Exception $exception) {
            throw new Exception('Can not create translation for payment names. ' . $exception->getMessage());
        }
    }

    /**
     * Update the translation of a payment
     *
     * @param integer $shopId
     * @param integer $paymentId
     * @param string $description
     * @param integer $default
     */
    private function updatePaymentTranslation($shopId, $paymentId, $description, $default)
    {
        if ($default) {
            Shopware()->Db()->update('s_core_paymentmeans', array(
                'description' => $description
            ), 'id=' . $paymentId
            );
        } else {
            $translationObject = new Shopware_Components_Translation();
            $translationObject->write(
                $shopId, 'config_payment', $paymentId, array('description' => $description), true
            );
        }
    }

    /**
     * Disables the plugin
     *
     * @throws Exception
     * @return boolean
     */
    public function disable()
    {
        try {
            $payment = array('pwlocal', 'brick');

            foreach ($payment as $key) {
                $currentPayment = $this->Payments()->findOneBy(array('name' => $key));
                if ($currentPayment) {
                    $currentPayment->setActive(false);
                }
            }
        } catch (Exception $exception) {
            throw new Exception('Cannot disable payment: ' . $exception->getMessage());
        }

        return array('success' => true, 'invalidateCache' => $this->clearCache);
    }

    public function enable()
    {
        return array('success' => true, 'invalidateCache' => $this->clearCache);
    }

    /**
     * Creates the payment method
     *
     * @throws Exception
     * @return void
     */
    protected function createPaymentMeans()
    {
        try {
            $this->createPayment(
                array(
                    'active' => 1,
                    'name' => 'brick',
                    'action' => 'brick',
                    'template' => 'brick.tpl',
                    'description' => 'Createdit Card',
                    'additionalDescription' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJEAAAAvCAYAAADjGCgmAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NTc3MiwgMjAxNC8wMS8xMy0xOTo0NDowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MUQ0NkQ1RUY0RUVGMTFFNTk1QzU5NDFBOTkyQzA2MjAiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MUQ0NkQ1RjA0RUVGMTFFNTk1QzU5NDFBOTkyQzA2MjAiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDoxRDQ2RDVFRDRFRUYxMUU1OTVDNTk0MUE5OTJDMDYyMCIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDoxRDQ2RDVFRTRFRUYxMUU1OTVDNTk0MUE5OTJDMDYyMCIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PliHr8sAAAxqSURBVHja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">'
                )
            );
            $this->createPayment(
                array(
                    'active' => 1,
                    'name' => 'pwlocal',
                    'action' => 'paymentwall',
                    'template' => 'pwlocal.tpl',
                    'description' => 'Paymentwall',
                    'additionalDescription' => '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAJEAAAAvCAYAAADjGCgmAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA0xpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDIxIDc5LjE1NTc3MiwgMjAxNC8wMS8xMy0xOTo0NDowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MUQ0NkQ1RjQ0RUVGMTFFNTk1QzU5NDFBOTkyQzA2MjAiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MUQ0NkQ1RjM0RUVGMTFFNTk1QzU5NDFBOTkyQzA2MjAiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTQgKE1hY2ludG9zaCkiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0iYWRvYmU6ZG9jaWQ6cGhvdG9zaG9wOjk0ZDZhM2NhLTk3NWItMTE3OC05MjVlLWI0YWMyZDU2YmE3ZiIgc3RSZWY6ZG9jdW1lbnRJRD0iYWRvYmU6ZG9jaWQ6cGhvdG9zaG9wOjk0ZDZhM2NhLTk3NWItMTE3OC05MjVlLWI0YWMyZDU2YmE3ZiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PipHtNUAAAUKSURBVHja7FxrbFRFGN2tYmsQrYhKkBpJ8MEPQ3xhJJpA5AdseSgVDGhEqK/E+AhRggbFBNEIfwgBNAq0IhFMaG2Jj4BGty0xhoCRBLWiVRT0B4/YBmlTHl3OR841k8nd292tS9jtOcnJ3O+buTN3Zs79vrlLQzyVSsUEoS8o0RIIEpEgEQkSkSBIRIJEJEhEgkQkCBKRIBEJEpEgEQmCRCRIRIJEJEhEgiARCf8XLsxHp/F4PNTfk7xiHIqvPff4knFHk/1x8RPTpl9mywV2ftZYfyIP/Z/9s1X0HQ+uP22oixeEiCLwSBpfMptF8dABvg++mI+NyDP+AE1Ic8FaRaLMcH8a37w+9Gmb8Cx4EnzBEVwcokoF12xn6IK/m/5SFBfzbW0v8Kj233yLUkRIY0HYHgF2I3110W8bWIqyHGUK/o4Mu1wNbqEwloE3mBCxkEtRLgYfAK+Bbf1tB58Gd7Dde+AT7Ocd8FFwH9p20VfDyDAa3A3OBF8CHzYBgvOxWbXcuCoUb7DfP8FFqPuAdd+zv3fZ323gIV5/40Qhwxq0r8a996A8DHuIzQF2Hez1vKcGts3Rnucj8Ah4I/hq2HzR9nDRHKwhkMko7C3/hzwI3wCjXTv+drbNBL9ikZJgI66/oO9y8EnwOVtQ8AQ3aQa4DlzLdjMsAoEm4OnORo8mV7CMceO/Ax8DyzjGmxTJJAp5JPg5eBrcAP9U3hv0Z4K/nS/R1bTdyBhjNBzE6yaWd7Kc4JVjnHaPR8y3qL7OHvTsweC95OBe2qbDbGxWLbjFiSo/g6vAJeCV3JhVrJvIc9Mp0KKeCWAKeCnT4AanbxNEgpEixntmUWiGoRh3ICPA2WgGMScolH/BBd6zfsnxG2mPYLvx4HH63qJQY875cCzGsQhXQbuC9l1Ou9UR8y0qEU0J8d1HZtI2DHeAc0BLJxbResCF4CiwkqllL3gJ2w/ARlsq2Ur7IaYnQ4MX+pfBNiG10P4E9maUm5w2FzkRwTa7AeV6RqMx3rMuxf3bUL4dOGCfskhKgRpaYe/yRHQrxW7Yx3IS/UG7tPMtqjMRzjnlEdVP5ditLVibnaPAv8CN2IRvsZm22MPB5Zyb3/9apjAT6wVOKgvD6Yjx3QNsOdOTYX/EPd0Zzu1HpndLnc/T9xr4Ie0y1lu71l7mW7giwtnGNqrOcbVCTKPgH4brA04EtAhSgbq/UfcLzxcBquCvTzPEOohmhf91guJ6O3SibjF9JoRXnGbbOH6QIn4Hv8pxmrsZEXcyKtrbPwFjf5xFH0EkqsSztuHeFrAH183wTQOv4wHaDtIraRuaKeTe5lvQ6WymZ98EkdzM9FPijV+Fuls8AYX1EQl+3v4ATsZivgwu9N9M2yCmHVeMPTnO8XXnOTv48VCPce/Ooo82lvZ1tcbxu7+bNfEZm9z6TOZb6CJKhPimMn/7qEzTPpHDuHbWsYhmn/rVoH1u7yHtTb2KqSBIVzW5ThCbuJUC+olnpC5GjP1ZdDOfX3+HGCHDRJSM8EXO91whno//0CHVNGSQc+YIcJzpszTkrGBhfaB/JkE6O+alrHL/B8MsfoyzsTudQ+dm9DEr1s+Qj/3Oy5nI33wHJ/nGhqE9g7e/L78qX8vI28nP+GdiwvkrovP0v6v5LXbu/5mnX0B/CiJIRIJEJEhEgiARCRKRIBEJEpEgSESCRCRIRIJEJAgSkSARCRKRUDQ4I8AAMeGueo5K3IUAAAAASUVORK5CYII=">'
                )
            );
        } catch (Exception $exception) {
            throw new Exception('There was an error creating the payment means. ' . $exception->getMessage());
        }
    }

    /**
     * Creates the configuration fields
     *
     * @throws Exception
     * @return void
     */
    private function _createForm()
    {
        try {
            $form = $this->Form();
            $form->setElement('text', 'merchantName', array('label' => 'Merchant name', 'required' => true, 'position' => 0, 'description' => 'Name of merchant'));
            $form->setElement('text', 'projectKey', array('label' => 'Project key', 'required' => true, 'position' => 5, 'description' => 'Project key for payment method: Paymentwall'));
            $form->setElement('text', 'secretKey', array('label' => 'Secret key', 'required' => true, 'position' => 10, 'description' => 'Secret key for payment method: Paymentwall'));
            $form->setElement('text', 'publicKey', array('label' => 'Public key', 'required' => true, 'position' => 20, 'description' => 'Public key for payment method: Brick'));
            $form->setElement('text', 'privateKey', array('label' => 'Private key', 'required' => true, 'position' => 30, 'description' => 'Private key for payment method: Brick'));
            $form->setElement('text', 'widgetCode', array('label' => 'Widget code', 'required' => true, 'position' => 40, 'description' => 'Widget for payment method: Paymentwall'));
            $form->setElement('select', 'testMode', array('label' => 'Test mode', 'store' => array(
                    array(1, 'Yes'),
                    array(0, 'No'),
                ),
                    'value' => 0,
                    'position' => 50,
                    'description' => 'Enable Test method for payment method: Paymentwall'
                )
            );
        } catch (Exception $exception) {
            throw new Exception('There was an error creating the plugin configuration. ' . $exception->getMessage());
        }
    }

    /**
     * Registers all Controllers
     */
    private function _registerController()
    {
        $this->registerController('Frontend', 'Paymentwall');
        $this->registerController('Frontend', 'Brick');
    }


    /**
     * Modifies the Backend menu by adding a Paymentwall Label as a child element of the shopware logging
     *
     * @throws Exception
     * @return void
     */
    private function _applyBackendViewModifications()
    {
        try {
            $parent = $this->Menu()->findOneBy(['label' => 'Logfile']);
            $this->createMenuItem(
                array(
                    'label' => 'Paymentwall', 
                    'class' => 'paymentwall', 
                    'active' => 1,
                    'controller' => 'Paymentwall', 
                    'action' => 'index', 
                    'parent' => $parent
                )
            );
        } catch (Exception $exception) {
            throw new Exception('Can not create menu entry.' . $exception->getMessage());
        }
    }

}
