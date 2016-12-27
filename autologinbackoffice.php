<?php
/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Autologinbackoffice extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'autologinbackoffice';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Express Tech';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Auto Login to Back Office');
        $this->description = $this->l('This module allows anyone to automatically login to backoffice via a special crafted URL');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {

        Configuration::updateValue('AUTOLOGINBACKOFFICE_ADMINDIR', basename(_PS_ADMIN_DIR_));
        Configuration::updateValue('AUTOLOGINBACKOFFICE_URL', serialize(array()));

        // Prepare tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminAL';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang)
            $tab->name[$lang['id_lang']] = 'Auto Login Backoffice';
        $tab->id_parent = -1;
        $tab->module = $this->name;

        if (!$tab->add() ||
            !parent::install()) {
            return false;
        }


        return true;
    }

    public function uninstall()
    {

        $id_tab = (int)Tab::getIdFromClassName('AdminAL');

        if ($id_tab)
        {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        Configuration::deleteByName('AUTOLOGINBACKOFFICE_ADMINDIR');
        Configuration::deleteByName('AUTOLOGINBACKOFFICE_URL');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitAutologinbackofficeModule')) == true) {
            $this->postProcess();
        }

        $urls = unserialize(Configuration::get('AUTOLOGINBACKOFFICE_URL'));

        if(Tools::getIsset('deleteUrl')) {
            $token = Tools::getValue('deleteUrl');
            foreach ($urls as $key => $url) {
                if($url['token'] === $token) {
                    unset($urls[$key]);
                }
            }

            Configuration::updateValue('AUTOLOGINBACKOFFICE_URL', serialize($urls));
        }

    
        // var_dump($urls);
        $errors = array();
        $errors[] = $this->l('test');
// 
        // $newurls = array();

        foreach ($urls as $key=>$url) {
            $shop = new Shop($url['id_shop']);
            // var_dump($shop);exit;
            if($shop)
                $url['shopname'] = $shop->name;

            $emp = new Employee();

            $emp = $emp->getByEmail($url['employee_email']);

            if($emp && $emp->id)
                $url['employee_name'] = $emp->firstname . ' ' . $emp->lastname;


            $urls[$key] = $url;
            
        }

        $this->context->smarty->assign('errors', $errors);
        $this->context->smarty->assign('autologinurl',  $urls);

        if(count($urls))
            $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAutologinbackofficeModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $employees = Db::getInstance()->executeS('
            SELECT `email`, `firstname`, `lastname`
            FROM `'._DB_PREFIX_.'employee`
            '.($active_only ? ' WHERE `active` = 1' : '').'
            ORDER BY `lastname` ASC
        ');
        // var_dump($employees);

        $employees_opts = array();

        foreach ($employees as $emp) {
            // var_dump($emp);
            $employees_opts[] = array( 'email'=> $emp['email'],
                'name' => $emp['firstname'] . ' ' . $emp['lastname']
                );
        }

        // if(Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE')) {
            $shops = Shop::getShops();
            // var_dump($shops);

            $shops_opts = array();

            foreach ($shops as $shop) {
                $shops_opts[] = array( 'id'=> $shop['id_shop'],
                    'name' => $shop['name']
                    );
            }
        // }
        

        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    // array(
                    //     'type' => 'switch',
                    //     'label' => $this->l('Live mode'),
                    //     'name' => 'AUTOLOGINBACKOFFICE_LIVE_MODE',
                    //     'is_bool' => true,
                    //     'desc' => $this->l('Use this module in live mode'),
                    //     'values' => array(
                    //         array(
                    //             'id' => 'active_on',
                    //             'value' => true,
                    //             'label' => $this->l('Enabled')
                    //         ),
                    //         array(
                    //             'id' => 'active_off',
                    //             'value' => false,
                    //             'label' => $this->l('Disabled')
                    //         )
                    //     ),
                    // ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Login as Employee'),
                        'desc' => $this->l('Auto Login as this employee'),
                        'name' => 'AUTOLOGINBACKOFFICE_EMPLOYEE',
                        'required' => true,
                        'default_value' => $this->context->employee->id,
                        'options' => array(
                            'query' => $employees_opts,
                            'id' => 'email',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Login to Shop'),
                        'desc' => $this->l('Auto Login to this shop. Useful for Multi shop environment.'),
                        'name' => 'AUTOLOGINBACKOFFICE_SHOP',
                        'required' => true,
                        'default_value' => $this->context->shop->id,
                        'options' => array(
                            'query' => $shops_opts,
                            'id' => 'id',
                            'name' => 'name',
                        )
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'AUTOLOGINBACKOFFICE_EMPLOYEE' => Configuration::get('AUTOLOGINBACKOFFICE_EMPLOYEE', '1'),
            'AUTOLOGINBACKOFFICE_SHOP' => Configuration::get('AUTOLOGINBACKOFFICE_SHOP', '1')
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        $emp = new Employee();
        $emp = $emp->getByEmail(Configuration::get('AUTOLOGINBACKOFFICE_EMPLOYEE'));
        // var_dump($emp);exit;
        $emp_associated_shops = $emp->getAssociatedShops();
        // var_dump($emp_associated_shops);exit;
        if(!in_array(Configuration::get('AUTOLOGINBACKOFFICE_SHOP'), $emp_associated_shops)) {
            die('This employee cannot access selected shop');
        }

        $prev_urls = unserialize(Configuration::get('AUTOLOGINBACKOFFICE_URL', serialize(array())));

        $url_key = Configuration::get('AUTOLOGINBACKOFFICE_EMPLOYEE') . Configuration::get('AUTOLOGINBACKOFFICE_SHOP');

        $token = Tools::encrypt($url_key.'AUTOLOGINBKOFC'.Tools::getBytes());
        $id_shop = $this->context->shop->id;
        // var_dump($id_shop);exit;

        $url_value = Tools::getShopDomain(true, false).__PS_BASE_URI__.Configuration::get('AUTOLOGINBACKOFFICE_ADMINDIR').'/'.$this->context->link->getAdminLink('AdminAL', false).'&l='.$token.'&id_shop='.$id_shop;

        $prev_urls[$url_key] = array(
            'url'=>$url_value, 
            'token'=>$token, 
            'id_shop'=> Configuration::get('AUTOLOGINBACKOFFICE_SHOP'), 
            'employee_email' => Configuration::get('AUTOLOGINBACKOFFICE_EMPLOYEE')
            // 'employee_email' => Configuration::get('AUTOLOGINBACKOFFICE_EMPLOYEE')
        );

        Configuration::updateValue('AUTOLOGINBACKOFFICE_URL', serialize($prev_urls));

        
    }

    
}
