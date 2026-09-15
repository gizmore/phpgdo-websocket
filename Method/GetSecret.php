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
		// Mail is a private user setting, so it must never become part of the
		// general GDO_User JSON used by profiles, searches or WebSocket users.
		// This response is tied to the holder's authenticated session and is the
		// one payload from which the app's own account page is initialised.
		$user = Module_Core::instance()->gdoUserJSON();
		if (GDO_Session::user()->isAuthenticated())
		{
			$user['user_email'] = GDO_Session::user()->getMail(false);
		}
		$json = [
			'user' => $user,
			'cookie' => GDO_Session::$COOKIE_NAME,
			'secret' => Module_Websocket::instance()->secret(),
			'count' => $this->gdoParameterValue('count'),
		];
		return GDT_JSON::make()->value($json);
	}

}
