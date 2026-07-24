<?php
declare(strict_types=1);
namespace GDO\Websocket\Websocket;

use GDO\Core\GDO_Exception;
use GDO\Core\GDT;
use GDO\Core\ModuleLoader;
use GDO\Friends\GDO_Friendship;
use GDO\User\GDO_User;
use GDO\User\GDT_ACLRelation;
use GDO\User\Module_User;
use GDO\Util\WS;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;
use function GDO\LoC\tpl\page\printLoC;

/**
 * WS Profile view.
 * @author gizmore
 * @version 7.0.3
 */
final class GWS_Profile extends GWS_Command
{

	/**
	 * @throws GDO_Exception
	 */
	public function execute(GWS_Message $msg)
	{
		$me = $msg->user(); # own user
		$target = GDO_User::findById((string)$msg->read32u()); # target user

		$payload = $msg::wr32($target->getID());
		$payload .= $msg::wr8(GDO_Friendship::areRelated($me, $target) ? 1 : 0);

		/**
		 * @var GDT_ACLRelation $global
		 */
		$global = Module_User::instance()->userSetting($target, 'profile_visibility');
		$reason = '';
		if (!$global->hasAccess($me, $target, $reason))
		{
			$payload .= WS::wr8(0) . WS::wrString($reason);
			return $msg->replyBinary($msg->cmd(), $payload);
		}
        else
        {
            $payload .= WS::wr8(1);
        }

		$modules = ModuleLoader::instance()->getEnabledModules();
		foreach ($modules as $module)
		{
			$moduleSettings = $module->getSettingsCache();
			$settings[$module->getName()] = [];
			foreach ($moduleSettings as $gdt)
			{
                if ($gdt->isSerializable() && (!$gdt->isHidden()))
                {
                    printf("Writing {$gdt->getName()}\n");
                    $gdt = $target->setting($module->getName(), $gdt->getName());
                    $payload .= $this->gdtSetting($module, $target, $gdt);
				}
			}
		}
		return $msg->replyBinary($msg->cmd(), $payload);
	}

	private function gdtSetting(\GDO\Core\GDO_Module $module, GDO_User $target, GDT $gdt): string
	{
        echo "{$gdt->getName()}\n";
		$user = GDO_User::current();
		$name = $gdt->getName();
		$acl = $module->getSettingACL($name);
		$reason = '';
		if ($acl && !($acl->hasAccess($user, $target, $reason)))
		{
			return WS::wr8(0) . WS::wrString($reason);
		}
        GWS_Message::hexdump($gdt->renderBinary());
		return WS::wr8(1) . $gdt->renderBinary();
	}

}

GWS_Commands::register(0x0901, new GWS_Profile());
