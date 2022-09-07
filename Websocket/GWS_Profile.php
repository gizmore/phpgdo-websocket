<?php
namespace GDO\Websocket\Websocket;

use GDO\User\GDO_Profile;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;
use GDO\User\GDT_ACLRelation;
use GDO\User\Method\Profile;
use GDO\Friends\GDO_Friendship;

/**
 * Ping and ws system hooks.
 *
 * 1. hook cache invalidation
 * 2. hook module vars changed
 * 3. hook user settings changed
 *
 * @author gizmore
 *
 */
final class GWS_Profile extends GWS_Command
{
	public function execute(GWS_Message $msg)
	{
		$me = $msg->user(); # own user
		$user = GDO_User::findById($msg->read32u()); # target user
		
		/** @var $globalACL GDT_ACLRelation **/
		$globalACL = $user->setting('User', 'profile_visibility');
		$reason = '';
		$globalACL->hasAccess($me, $user, $reason);
		
		$method = Profile::make()->inputs(['for' => $user->renderUserName()]);
		$card = $method->getCard();
		$profile = GDO_Profile::blank(); # The profile GDO/DTO
// 		$profile->setVar($globalACL->name, $globalACL->getVar());
		
		foreach ($card->getAllFields() as $gdt)
		{
// 			$settings = $module->getSettingsCache();
// 			foreach ($settings as $gdt)
// 			{
// 				$gdt = $module->userSetting($user, $gdt->name);
// 				$profile->setVar($gdt->name, null);
// 				$aclName = $gdt->name . '_visible';
// 				if ($module->hasSetting($aclName))
// 				{
// 					/** @var $fieldACL GDT_ACL **/
// 					$fieldACL = $module->userSetting($user, $aclName);
// 					if ($fieldACL->hasAccess($me, $user, $reason, false))
// 					{
// 						$profile->setVar($gdt->name, $module->userSettingVar($user, $gdt->name));
// 					}
// 				}
// 				else
// 				{
// 					$profile->setVar($gdt->name, $module->userSettingVar($user, $gdt->name));
// 				}
// 			}
		}
		
		$payload = $msg->wr32($user->getID());
		$payload .= $msg->wr8(GDO_Friendship::areRelated($me, $user)?1:0);
		$payload .= $this->gdoToBinary($profile);
		return $msg->replyBinary($msg->cmd(), $payload);
	}
	
}

GWS_Commands::register(0x0901, new GWS_Profile());
