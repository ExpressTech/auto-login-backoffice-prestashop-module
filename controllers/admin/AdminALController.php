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

class AdminALController extends ModuleAdminController
{
	public function __construct() {
		
		parent::__construct();

		$token = Tools::getValue('l');
		$id_shop = Tools::getValue('id_shop', 1);

		if($token) {

			$context = Context::getContext();
			// $context->cookie->shopContext = 's-'.$id_shop;

			// $context = Context::
			$urls = unserialize(Configuration::get('AUTOLOGINBACKOFFICE_URL', null, null, $id_shop));

			if(!$urls)
				die('Bad URL');


			// var_dump($urls);exit;

			foreach ($urls as $autourl) {
				// echo $url['token'];
				if($autourl['token'] == $token) {
					//found the token
					
					//now login the employee
					$context->employee = new Employee();
					$is_employee_loaded = $context->employee->getByEmail($autourl['employee_email']);
					// var_dump($is_employee_loaded);exit;
		            $employee_associated_shop = $context->employee->getAssociatedShops();

		            //Validation
		            if (!$is_employee_loaded) {
		                $this->errors[] = Tools::displayError('The Employee does not exist, or the password provided is incorrect.');
		                $this->context->employee->logout();
		            } elseif (empty($employee_associated_shop) && !$this->context->employee->isSuperAdmin()) {
		                $this->errors[] = Tools::displayError('This employee does not manage the shop anymore (Either the shop has been deleted or permissions have been revoked).');
		                $this->context->employee->logout();
		            } else {

			            $context->employee->remote_addr = (int)ip2long(Tools::getRemoteAddr());
			            	
			            // Update cookie
			            
			            $cookie = Context::getContext()->cookie;
			            $cookie->id_employee = $context->employee->id;
			            $cookie->email = $context->employee->email;
			            $cookie->profile = $context->employee->id_profile;
			            $cookie->passwd = $context->employee->passwd;
			            $cookie->remote_addr = $context->employee->remote_addr;

			            if (!Tools::getValue('stay_logged_in')) {
			                $cookie->last_activity = time();
			            }

			            $cookie->write();

			            
			            $tab = new Tab((int)$context->employee->default_tab);
			            $url = $context->link->getAdminLink($tab->class_name);
			            $url .= '&setShopContext=s-'.$autourl['id_shop'];
			            Tools::redirectAdmin($url);
						break;
					}
				}
			}


		}
	}

}

