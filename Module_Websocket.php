<?php
namespace GDO\Websocket;

use GDO\Core\GDO_Module;
use GDO\Date\GDT_Duration;
use GDO\Net\GDT_Url;
use GDO\Session\GDO_Session;
use GDO\Util\Strings;
use GDO\UI\GDT_CodeParagraph;
use GDO\UI\GDT_Page;
use GDO\Core\GDT_Array;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_Int;
use GDO\Core\GDT_Path;
use GDO\Core\GDT_String;
use GDO\Core\Javascript;
use GDO\UI\GDT_Link;
use GDO\UI\GDT_Container;
use GDO\User\GDO_User;

/**
 * Websocket server module.
 * 
 * Uses a slightly modified version of ratchet, which can pass the IP to the server.
 * 
 * It is advised to use gdo6-session-db for sites with a websocket server.
 * The cookie is exchanged via js and ws and www, and it can be quite large when storing sessions.
 * 
 * @author gizmore
 * 
 * @version 7.0.1
 * @since 6.5.0
 */
final class Module_Websocket extends GDO_Module
{
	##############
	### Module ###
	##############
	public int $priority = 45;
	public function onLoadLanguage() : void { $this->loadLanguage('lang/websocket'); }
	public function thirdPartyFolders() : array { return ['/gwf4-ratchet/']; }
	
	public function getDependencies() : array
	{
		return [
			'Session',
		];
	}
	
	##############
	### Config ###
	##############
	public function getConfig() : array
	{
		return [
			GDT_Checkbox::make('ws_autoconnect')->initial('0'),
			GDT_Checkbox::make('ws_guests')->initial('1'),
			GDT_String::make('ws_exec_permission')->initial('staff'),
			GDT_Int::make('ws_port')->bytes(2)->unsigned()->initial('61221'),
			GDT_Duration::make('ws_timer')->initial('0s'),
			GDT_Path::make('ws_processor')->initial($this->defaultProcessorPath())->existingFile(),
			GDT_Url::make('ws_url')->initial('ws://'.GDT_Url::host().':61221')->schemes('wss', 'ws')->allowAll(false),
		    GDT_Checkbox::make('ws_left_bar')->initial('1'),
		];
	}
	public function cfgAutoConnect() { return $this->getConfigValue('ws_autoconnect'); }
	public function cfgUrl() { return $this->getConfigVar('ws_url'); }
	public function cfgPort() { return $this->getConfigValue('ws_port'); }
	public function cfgTimer() { return $this->getConfigValue('ws_timer'); }
	public function cfgWebsocketProcessorPath() { return $this->getConfigValue('ws_processor'); }
	public function cfgAllowGuests() { return $this->getConfigValue('ws_guests'); }
	public function cfgLeftBar() { return $this->getConfigValue('ws_left_bar'); }
	public function cfgClientPermission() { return $this->getConfigVar('ws_exec_permission'); }
	
	public function defaultProcessorPath() { return sprintf('%sGDO/Websocket/Server/GWS_NoCommands.php', GDO_PATH); }
	public function processorClass()
	{
	    $path = $this->cfgWebsocketProcessorPath();
	    $path = str_replace('\\', '/', $path);
		$path = Strings::substrFrom($path, GDO_PATH);
		$path = str_replace('/', '\\', $path);
		return Strings::substrTo($path, '.'); 
	}

	##########
	### JS ###
	##########
	public function onIncludeScripts() : void
	{
		$this->addJS('js/gws-message.js');
		Javascript::addJSPreInline($this->configJS());
	}
	
	private function configJS()
	{
		return sprintf('window.GDO_CONFIG.ws_url = "%s";
window.GDO_CONFIG.ws_secret = "%s";
window.GDO_CONFIG.ws_autoconnect = %s;',
				$this->cfgUrl(), $this->secret(), $this->cfgAutoConnect()?'1':'0');
	}
	
	public function secret()
	{
		$sess = GDO_Session::instance();
		return $sess ? $sess->cookieContent() : GDO_Session::DUMMY_COOKIE_CONTENT;
	}
	
	##############
	### Navbar ###
	##############
	public function onInitSidebar() : void
	{
	    if ($this->cfgLeftBar())
	    {
	    	$navbar = GDT_Page::instance()->leftBar();
	    	
	    	if (GDO_User::current()->hasPermission($this->cfgClientPermission()))
	    	{
	    		$navbar->addField(GDT_Link::make()->href($this->href('Exec'))->label('link_ws_exec'));
	    	}
		}
	}
	
	#############
	### Hooks ###
	#############
	public function hookIgnoreDocsFiles(GDT_Array $ignore)
	{
	    $ignore->data[] = 'GDO/Websocket/gwf4-ratchet/**/*';
	}
	
	public function hookInstallCronjob(GDT_Container $container)
	{
		$cron = $this->filePath('bin/cron_start_websocket_server.sh');
		$websocket_cronjob_code = "* * * * * {$cron} > /dev/null";
		$container->addField(GDT_CodeParagraph::make()->textRaw($websocket_cronjob_code));
	}
	
}
