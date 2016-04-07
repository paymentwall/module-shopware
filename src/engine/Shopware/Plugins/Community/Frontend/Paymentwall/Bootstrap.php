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
        return "1.0.0";
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
            'copyright' => 'Copyright (c) 2016, Paymentwall',
            'label' => 'Paymentwall',
            'description' => '<h2>Payment plugin for Shopware Community Edition Version 4.0.0 - 4.3.6</h2>'
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

            $sortedSnippets = parse_ini_file(dirname(__FILE__) . '/snippets/frontend/paymentwall/checkout/payments.ini', true);
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
                    'additionalDescription' => ''
                )
            );
            $this->createPayment(
                array(
                    'active' => 1,
                    'name' => 'pwlocal',
                    'action' => 'paymentwall',
                    'template' => 'pwlocal.tpl',
                    'description' => 'Paymentwall',
                    'additionalDescription' => ''
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
                    'value' => 'Yes',
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
            $parent = $this->Menu()->findOneBy('label', 'logfile');
            $this->createMenuItem(array('label' => 'Paymentwall', 'class' => 'paymentwall', 'active' => 1,
                'controller' => 'Paymentwall', 'action' => 'index', 'parent' => $parent));
        } catch (Exception $exception) {
            throw new Exception('Can not create menu entry.' . $exception->getMessage());
        }
    }
}
