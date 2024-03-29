<?php
namespace GDO\Websocket\Server;

use GDO\User\GDO_User;

/**
 * Example of a GWS_Commands implementation.
 *
 * @since 5.0
 * @author gizmore
 * @see GWS_Command
 */
final class GWS_NoCommands extends GWS_Commands
{

	public function init() {}

	public function timer() {}

	public function connect(GDO_User $user) {}

	public function disconnect(GDO_User $user) {}

}
