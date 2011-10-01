<?
/***** include required files **************************************/
require_once("config.inc.php");
require_once("formula_parser.inc.php");
require_once("lib.inc.php");
require_once("modules_config.inc.php");
require_once("params.inc.php");

global $cfg, $module_cfg;

ini_set('display_errors', 0);

/***** INITIALIZE GLOBALS ******************************************/

// get cleaned POST, GET and SESSION parameters
$params = new Params();

/***** GET MODUS ***************************************************/
$modus = lib_checkModus();

/***** LOAD ACTIVE MODULES *****************************************/
$active_modules =& lib_getActiveModules();

/***** GET CONTENT *************************************************/
$modus_function = $modus . "_getContent";
$content = $modus_function();

/***** GET MENU *************************************************/
foreach ($active_modules AS $module){
  $menu_function = $module['modus'] . "_getMenu";
  if (function_exists("$menu_function"))
    $menu[] = $menu_function();
}

/***** GET SELECTORS ***********************************************/
foreach ($active_modules AS $module){
  $selector_function = $module['modus'] . "_getSelector";
  if (function_exists("$selector_function"))
    $selectors[] = array('modus' => $module['modus'], 'OPTION' => $selector_function());
}

/***** FILL TEMPLATE ***********************************************/
$template = @tmpl_open("templates/framework.ihtml");
tmpl_set($template, array('content'  => str_replace('%gfx%', $cfg['gfxpath'], $content),
                          'MENU'     => $menu,
                          'SELECTOR' => $selectors));

echo tmpl_parse($template);
?>
