<?php
namespace GDO\Websocket\Websocket;

use GDO\Core\GDT_Response;
use GDO\Core\Module_Core;
use GDO\Form\GDT_Form;
use GDO\Register\Method\Form;
use GDO\Register\Module_Register;
use GDO\Session\GDO_Session;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_CommandForm;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

final class GWS_Register extends GWS_CommandForm
{

	public function getMethod()
	{
		return Form::make();
	}

	public function replySuccess(GWS_Message $msg, GDT_Form $form, GDT_Response $response)
	{
		if (!Module_Register::instance()->cfgEmailActivation())
		{
			$user = GDO_User::current();
			$user->tempSet('sess_id', GDO_Session::instance()->getID());
			$msg->conn()->setUser($user);
			GWS_Global::addUser($user, $msg->conn());
		}
		$msg->replyText($msg->cmd(), json_encode(Module_Core::instance()->gdoUserJSON()));
	}

}

GWS_Commands::register(0x0102, new GWS_Register());
