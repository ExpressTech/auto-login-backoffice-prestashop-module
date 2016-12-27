{*
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
*}

<div class="panel">
	<h3><i class="icon icon-credit-card"></i> {l s='Auto Login to Back Office' mod='autologinbackoffice'}</h3>
	
	<br>
	{foreach from=$autologinurl item=item}
	<p>
		
		  <strong>Employee :</strong> {$item['employee_name']} <br> <strong>Shop : </strong>{$item['shopname']} <br> <strong>Login URL : </strong><a href="{$item['url']}">{$item['url']}</a> <br><a href="{$smarty.server.REQUEST_URI|escape:'html':'UTF-8'}&deleteUrl={$item['token']}"><i class="icon-trash"></i> {l s='Delete'}</a><br><hr>
		
	</p>
	{/foreach}
	
</div>

