<?php
namespace Froxlor\Frontend;

abstract class FeModule
{

	/**
	 * language array
	 *
	 * @var array
	 */
	public $lng = array();

	/**
	 * mailer object
	 *
	 * @var \Froxlor\System\Mailer
	 */
	public $mail = null;
}
