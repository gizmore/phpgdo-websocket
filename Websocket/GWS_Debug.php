<?php
namespace GDO\Websocket\Websocket;

use GDO\Perf\GDT_PerfBar;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

/**
 * A websocket command that returns performance statistics.
 *
 * @version 6.09
 * @since 6.08
 * @see GDT_PerfBar
 * @author gizmore
 */
final class GWS_Debug extends GWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$data = GDT_PerfBar::data();
		$data['gws_users'] = count(GWS_Global::$USERS);
		$data['gws_user_connections'] = count(GWS_Global::$CONNECTIONS);
		$msg->replyText($msg->cmd(), json_encode($data));
	}

}

GWS_Commands::register(0x0108, new GWS_Debug());
