<?php
namespace GDO\Websocket\Server;

use GDO\Core\Application;
use GDO\Core\Logger;
use GDO\Core\Debug;
use GDO\Core\ModuleLoader;
use GDO\DB\Database;
use GDO\Language\Trans;
use GDO\Session\GDO_Session;
use GDO\Websocket\Module_Websocket;
use GDO\Core\GDT;
use GDO\Core\Method\Stub;
use GDO\Form\GDT_Form;

# Load config
require_once 'GDO7.php';
require_once 'protected/config.php'; # <-- You might need to adjust this path.

# Autoloader for ratchet
require_once 'GDO/Websocket/gwf4-ratchet/autoload.php';

# Init some config like
$_SERVER['REQUEST_URI'] = 'ws.php';
$_REQUEST['_ajax'] = '1';
$_REQUEST['_fmt'] = 'json';
$_REQUEST['_mo'] = 'Websocket';
$_REQUEST['_me'] = 'Run';
global $me;
$me = Stub::make();

# Bootstrap
class GWS_ServerMain extends Application
{
	public function isCLI() : bool { return true; }
	public function isWebsocket() : bool { return true; }
}
$app = GWS_ServerMain::init();
$app->cli();
$app->verb(GDT_Form::POST);
$app->modeDetected(GDT::RENDER_BINARY);

Trans::$ISO = GDO_LANGUAGE;
Logger::init(null, Logger::_ALL&~Logger::BUFFERED); # 1st init as guest
Debug::init();
Debug::enableErrorHandler();
Debug::setDieOnError(false);
Debug::setMailOnError(GDO_ERROR_MAIL);
Database::init();
GDO_Session::init(GDO_SESS_NAME, GDO_SESS_DOMAIN, GDO_SESS_TIME, !GDO_SESS_JS, GDO_SESS_HTTPS);
$loader = ModuleLoader::instance();
$loader->loadModulesCache();
$loader->initModules();
Trans::inited();
define('GDO_CORE_STABLE', true); # all fine? @deprecated

# Create WS
$gws = Module_Websocket::instance();

$processorPath = $gws->cfgWebsocketProcessorPath();
require $processorPath;

$processor = $gws->processorClass();

$server = new GWS_Server();
if (GDO_IPC === 'ipc')
{
	$server->ipcTimer();
}
$server->initGWSServer(new $processor(), $gws);
$server->mainloop($gws->cfgTimer());
