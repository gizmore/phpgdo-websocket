<?php
namespace GDO\Websocket\Method;

use GDO\UI\MethodPage;
use GDO\Websocket\Module_Websocket;

/**
 * @TODO: Implement websocket.exec client to run commands against the websocket server.
 *
 * @version 6.11.1
 * @since 6.11.0
 * @author gizmore
 */
final class Exec extends MethodPage
{

	public function getPermission(): ?string { return Module_Websocket::instance()->cfgClientPermission(); }

	public function getMethodTitle(): string
	{
		return 'Websocket Exec';
	}

}
