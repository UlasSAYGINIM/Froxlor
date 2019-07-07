<?php

/**
 * This file is part of the Froxlor project.
 * Copyright (c) 2010 the Froxlor Team (see authors).
 *
 * For the full copyright and license information, please view the COPYING
 * file that was distributed with this source code. You can also view the
 * COPYING file online at http://files.froxlor.org/misc/COPYING.txt
 *
 * @copyright  (c) the authors
 * @author     Froxlor team <team@froxlor.org> (2010-)
 * @license    GPLv2 http://files.froxlor.org/misc/COPYING.txt
 * @package    Formfields
 *
 */
return array(
	'domain_add' => array(
		'title' => \Froxlor\Frontend\UI::getLng('admin.domain_add'),
		'image' => 'icons/domain_add.png',
		'sections' => array(
			'section_a' => array(
				'title' => \Froxlor\Frontend\UI::getLng('domains.domainsettings'),
				'image' => 'icons/domain_add.png',
				'fields' => array(
					'domain' => array(
						'label' => 'Domain',
						'type' => 'text',
						'mandatory' => true
					),
					'customerid' => array(
						'label' => \Froxlor\Frontend\UI::getLng('admin.customer'),
						'type' => 'select',
						'select_var' => $customers,
						'mandatory' => true
					),
					'adminid' => array(
						'visible' => (\Froxlor\CurrentUser::getField('customers_see_all') == '1' ? true : false),
						'label' => \Froxlor\Frontend\UI::getLng('admin.admin'),
						'type' => 'select',
						'select_var' => $admins,
						'mandatory' => true
					),
					'alias' => array(
						'label' => \Froxlor\Frontend\UI::getLng('domains.aliasdomain'),
						'type' => 'select',
						'select_var' => $domains
					),
					'issubof' => array(
						'label' => \Froxlor\Frontend\UI::getLng('domains.issubof'),
						'desc' => \Froxlor\Frontend\UI::getLng('domains.issubofinfo'),
						'type' => 'select',
						'select_var' => $subtodomains
					),
					'caneditdomain' => array(
						'label' => \Froxlor\Frontend\UI::getLng('admin.domain_editable.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.domain_editable.desc'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array(
							'1'
						)
					),
					'add_date' => array(
						'label' => \Froxlor\Frontend\UI::getLng('domains.add_date'),
						'desc' => \Froxlor\Frontend\UI::getLng('panel.dateformat'),
						'type' => 'label',
						'value' => $add_date
					),
					'registration_date' => array(
						'label' => \Froxlor\Frontend\UI::getLng('domains.registration_date'),
						'desc' => \Froxlor\Frontend\UI::getLng('panel.dateformat'),
						'type' => 'text',
						'size' => 10
					),
					'termination_date' => array(
						'label' => \Froxlor\Frontend\UI::getLng('domains.termination_date'),
						'desc' => \Froxlor\Frontend\UI::getLng('panel.dateformat'),
						'type' => 'text',
						'size' => 10
					)
				)
			),
			'section_b' => array(
				'title' => \Froxlor\Frontend\UI::getLng('admin.webserversettings'),
				'image' => 'icons/domain_add.png',
				'fields' => array(
					'documentroot' => array(
						'visible' => (\Froxlor\CurrentUser::getField('change_serversettings') == '1' ? true : false),
						'label' => 'DocumentRoot',
						'desc' => \Froxlor\Frontend\UI::getLng('panel.emptyfordefault'),
						'type' => 'text'
					),
					'ipandport' => array(
						'label' => \Froxlor\Frontend\UI::getLng('domains.ipandport_multi.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('domains.ipandport_multi.description'),
						'type' => 'checkbox',
						'values' => $ipsandports,
						'value' => explode(',', \Froxlor\Settings::Get('system.defaultip')),
						'is_array' => 1,
						'mandatory' => true
					),
					'selectserveralias' => array(
						'label' => \Froxlor\Frontend\UI::getLng('admin.selectserveralias'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.selectserveralias_desc'),
						'type' => 'select',
						'select_var' => $serveraliasoptions
					),
					'speciallogfile' => array(
						'label' => \Froxlor\Frontend\UI::getLng('admin.speciallogfile.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.speciallogfile.description'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array()
					),
					'specialsettings' => array(
						'visible' => (\Froxlor\CurrentUser::getField('change_serversettings') == '1' ? true : false),
						'style' => 'align-top',
						'label' => \Froxlor\Frontend\UI::getLng('admin.ownvhostsettings'),
						'desc' => \Froxlor\Frontend\UI::getLng('serversettings.default_vhostconf.description'),
						'type' => 'textarea',
						'cols' => 60,
						'rows' => 12
					),
					'notryfiles' => array(
						'visible' => (\Froxlor\Settings::Get('system.webserver') == 'nginx' && \Froxlor\CurrentUser::getField('change_serversettings') == '1'),
						'label' => \Froxlor\Frontend\UI::getLng('admin.notryfiles.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.notryfiles.description'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array()
					),
					'writeaccesslog' => array(
						'label' => \Froxlor\Frontend\UI::getLng('admin.writeaccesslog.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.writeaccesslog.description'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array(
							'1'
						)
					),
					'writeerrorlog' => array(
						'label' => \Froxlor\Frontend\UI::getLng('admin.writeerrorlog.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.writeerrorlog.description'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array(
							'1'
						)
					)
				)
			),
			'section_bssl' => array(
				'title' => \Froxlor\Frontend\UI::getLng('admin.webserversettings_ssl'),
				'image' => 'icons/domain_add.png',
				'visible' => \Froxlor\Settings::Get('system.use_ssl') == '1' ? true : false,
				'fields' => array(
					'ssl_ipandport' => array(
						'label' => \Froxlor\Frontend\UI::getLng('domains.ipandport_ssl_multi.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('domains.ipandport_ssl_multi.description'),
						'type' => 'checkbox',
						'values' => $ssl_ipsandports,
						'value' => explode(',', \Froxlor\Settings::Get('system.defaultsslip')),
						'is_array' => 1
					),
					'ssl_redirect' => array(
						'visible' => ($ssl_ipsandports != '' ? true : false),
						'label' => \Froxlor\Frontend\UI::getLng('domains.ssl_redirect.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('domains.ssl_redirect.description'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array()
					),
					'letsencrypt' => array(
						'visible' => (\Froxlor\Settings::Get('system.leenabled') == '1' ? ($ssl_ipsandports != '' ? true : false) : false),
						'label' => \Froxlor\Frontend\UI::getLng('admin.letsencrypt.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.letsencrypt.description'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array()
					),
					'http2' => array(
						'visible' => ($ssl_ipsandports != '' ? true : false) && \Froxlor\Settings::Get('system.webserver') != 'lighttpd' && \Froxlor\Settings::Get('system.http2_support') == '1',
						'label' => \Froxlor\Frontend\UI::getLng('admin.domain_http2.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.domain_http2.description'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array()
					),
					'no_ssl_available_info' => array(
						'visible' => ($ssl_ipsandports == '' ? true : false),
						'label' => 'SSL',
						'type' => 'label',
						'value' => \Froxlor\Frontend\UI::getLng('panel.nosslipsavailable')
					),
					'hsts_maxage' => array(
						'visible' => ($ssl_ipsandports != '' ? true : false),
						'label' => \Froxlor\Frontend\UI::getLng('admin.domain_hsts_maxage.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.domain_hsts_maxage.description'),
						'type' => 'int',
						'min' => 0,
						'max' => 94608000, // 3-years
						'value' => 0
					),
					'hsts_sub' => array(
						'visible' => ($ssl_ipsandports != '' ? true : false),
						'label' => \Froxlor\Frontend\UI::getLng('admin.domain_hsts_incsub.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.domain_hsts_incsub.description'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array()
					),
					'hsts_preload' => array(
						'visible' => ($ssl_ipsandports != '' ? true : false),
						'label' => \Froxlor\Frontend\UI::getLng('admin.domain_hsts_preload.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.domain_hsts_preload.description'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array()
					),
					'ocsp_stapling' => array(
						'visible' => ($ssl_ipsandports != '' ? true : false) && \Froxlor\Settings::Get('system.webserver') != 'lighttpd',
						'label' => \Froxlor\Frontend\UI::getLng('admin.domain_ocsp_stapling.title'),
						'desc' => \Froxlor\Frontend\UI::getLng('admin.domain_ocsp_stapling.description') . (\Froxlor\Settings::Get('system.webserver') == 'nginx' ? \Froxlor\Frontend\UI::getLng('admin.domain_ocsp_stapling.nginx_version_warning') : ""),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array()
					)
				)
			),
			'section_c' => array(
				'title' => \Froxlor\Frontend\UI::getLng('admin.phpserversettings'),
				'image' => 'icons/domain_add.png',
				'visible' => ((\Froxlor\CurrentUser::getField('change_serversettings') == '1' || \Froxlor\CurrentUser::getField('caneditphpsettings') == '1') ? true : false),
				'fields' => array(
					'openbasedir' => array(
						'label' => 'OpenBasedir',
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array(
							'1'
						)
					),
					'phpenabled' => array(
						'label' => \Froxlor\Frontend\UI::getLng('admin.phpenabled'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array(
							'1'
						)
					),
					'phpsettingid' => array(
						'visible' => (((int) \Froxlor\Settings::Get('system.mod_fcgid') == 1 || (int) \Froxlor\Settings::Get('phpfpm.enabled') == 1) ? true : false),
						'label' => \Froxlor\Frontend\UI::getLng('admin.phpsettings.title'),
						'type' => 'select',
						'select_var' => $phpconfigs,
						'is_array' => 1
					),
					'mod_fcgid_starter' => array(
						'visible' => ((int) \Froxlor\Settings::Get('system.mod_fcgid') == 1 ? true : false),
						'label' => \Froxlor\Frontend\UI::getLng('admin.mod_fcgid_starter.title'),
						'type' => 'text'
					),
					'mod_fcgid_maxrequests' => array(
						'visible' => ((int) \Froxlor\Settings::Get('system.mod_fcgid') == 1 ? true : false),
						'label' => \Froxlor\Frontend\UI::getLng('admin.mod_fcgid_maxrequests.title'),
						'type' => 'text'
					)
				)
			),
			'section_d' => array(
				'title' => \Froxlor\Frontend\UI::getLng('admin.nameserversettings'),
				'image' => 'icons/domain_add.png',
				'visible' => (\Froxlor\Settings::Get('system.bind_enable') == '1' && \Froxlor\CurrentUser::getField('change_serversettings') == '1' ? true : false),
				'fields' => array(
					'isbinddomain' => array(
						'label' => 'Nameserver',
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array(
							'1'
						)
					),
					'zonefile' => array(
						'label' => 'Zonefile',
						'desc' => \Froxlor\Frontend\UI::getLng('admin.bindzonewarning'),
						'type' => 'text'
					)
				)
			),
			'section_e' => array(
				'title' => \Froxlor\Frontend\UI::getLng('admin.mailserversettings'),
				'image' => 'icons/domain_add.png',
				'fields' => array(
					'isemaildomain' => array(
						'label' => \Froxlor\Frontend\UI::getLng('admin.emaildomain'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array(
							'1'
						)
					),
					'email_only' => array(
						'label' => \Froxlor\Frontend\UI::getLng('admin.email_only'),
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array()
					),
					'subcanemaildomain' => array(
						'label' => \Froxlor\Frontend\UI::getLng('admin.subdomainforemail'),
						'type' => 'select',
						'select_var' => $subcanemaildomain
					),
					'dkim' => array(
						'visible' => (\Froxlor\Settings::Get('dkim.use_dkim') == '1' ? true : false),
						'label' => 'DomainKeys',
						'type' => 'checkbox',
						'values' => array(
							array(
								'label' => \Froxlor\Frontend\UI::getLng('panel.yes'),
								'value' => '1'
							)
						),
						'value' => array(
							'1'
						)
					)
				)
			)
		)
	)
);
