<?php
namespace GDO\Websocket\Websocket;

use GDO\Core\GDO;
use GDO\Core\Logger;
use GDO\Core\ModuleLoader;
use GDO\DB\Cache;
use GDO\User\GDT_ACLRelation;
use GDO\User\GDO_User;
use GDO\User\Module_User;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Change a user setting.
 *
 * @author gizmore
 *
 */
final class GWS_Setting extends GWS_Command
{
	private const ACL_RANK = [
		GDT_ACLRelation::ALL => 0,
		GDT_ACLRelation::GUESTS => 1,
		GDT_ACLRelation::MEMBERS => 2,
		GDT_ACLRelation::FRIEND_FRIENDS => 3,
		GDT_ACLRelation::FRIENDS => 4,
		GDT_ACLRelation::NOONE => 5,
		GDT_ACLRelation::HIDDEN => 6,
	];

	public function execute(GWS_Message $msg)
	{
		$user = $msg->user();
		$moduleName = $msg->readString();
		$module = ModuleLoader::instance()->getModule($moduleName);
		$key = $msg->readString();
		$var = $msg->readString();
		$relation = $msg->hasMore() ? $msg->readString() : null;

		if (!$module || !$module->hasSetting($key))
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_unknown_setting', [html($key)]));
		}
		$setting = $module->userSetting($user, $key);
		if (!$setting->isSerializable() || $setting->isHidden() || !$setting->isWriteable())
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_unknown_setting', [html($key)]));
		}
		if (($var === $setting->var) && ($relation === null))
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_setting_unchanged'));
		}

// 		$setting->var($var);
		$value = $setting->toValue($var);
		if (!$setting->validate($value))
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_setting_validate', [$setting->error]));
		}
		if (($relation !== null) && !$this->isAllowedRelation($module, $user, $key, $relation))
		{
			return $msg->replyErrorMessage($msg->cmd(), 'The field visibility must not exceed profile visibility.');
		}

		Logger::logWebsocket("Writing Setting $key to $value");

		# XXX: Ugly fix.
		if ($value instanceof GDO)
		{
			$value = $value->getID();
		}

		$module->saveSetting($key, $value);

		# New clients may send a fourth, optional relation field. Older three-field
		# clients retain the existing behaviour unchanged.
		if ($relation !== null)
		{
			$module->saveUserSettingACLRelation($user, $key, $relation);
			Cache::remove("uset_acl_{$user->getID()}");
		}
		return $msg->replyBinary($msg->cmd());
	}

	private function isAllowedRelation($module, GDO_User $user, string $key, string $relation): bool
	{
		if (!Module_User::instance()->cfgACLRelations() || !$module->getSettingACL($key))
		{
			return false;
		}
		$global = Module_User::instance()->userSettingVar($user, 'profile_visibility');
		return isset(self::ACL_RANK[$global], self::ACL_RANK[$relation]) &&
			(self::ACL_RANK[$relation] >= self::ACL_RANK[$global]);
	}

}

GWS_Commands::register(0x0107, new GWS_Setting());
