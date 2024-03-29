<?php
declare(strict_types=1);
namespace GDO\Websocket\Method;

use GDO\Core\GDO_ArgError;
use GDO\Core\GDT;
use GDO\Core\GDT_JSON;
use GDO\Core\GDT_UInt;
use GDO\Core\MethodAjax;
use GDO\Core\Module_Core;
use GDO\Session\GDO_Session;
use GDO\Websocket\Module_Websocket;

/**
 * Get cookie and user JSON for external apps.
 *
 * @version 7.0.3
 * @since 4.0.0
 * @author gizmore
 */
final class GetSecret extends MethodAjax
{

	public function getMethodTitle(): string
	{
		return t('btn_connect');
	}

	public function gdoParameters(): array
	{
		return [
			GDT_UInt::make('count')->min(0)->max(3)->notNull()->initial('0'),
		];
	}

	/**
	 * @throws GDO_ArgError
	 */
	public function execute(): GDT
	{
		$json = [
			'user' => Module_Core::instance()->gdoUserJSON(),
			'cookie' => GDO_Session::$COOKIE_NAME,
			'secret' => Module_Websocket::instance()->secret(),
			'count' => $this->gdoParameterValue('count'),
		];
		return GDT_JSON::make()->value($json);
	}

}
