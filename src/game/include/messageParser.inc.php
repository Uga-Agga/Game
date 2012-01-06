<?php
/*

##############################################################################
##############################################################################
##############################################################################
##########                         ######                         ############
##########    #################    ######    #################    ############
##########    #################    ######    #################    ############
##########    ####          ###    ######    ####          ###    ############
##########    ####          ###    ######    ####          ###    ############
##########    ####          ###    ######    ####          ###    ############
##########    ####          ###    ######    ####          ###    ############
##########    #################    ######    #################    ############
##########    #################    ######    #################    ############
##########    ####                 ######    ####                 ############
##########    ### ##               ######    ####                 ############
##########    ###   ##             ######    ####                 ############
##########    ###     ##           ######    ####                 ############
##########    ###       ##         ######    ####                 ############
##########    ###         ##       ######    ####                 ############
##########    ###           ##     ######    ####                 ############
##########                         ######                         ############
##############################################################################
##############################################################################
##############################################################################


||++++++++++ Application Name ++++++++++||
||-> Recruiting Parser

||++++++++ Application Version +++++++++||
||-> 1.0 Pre-Final #1 -dev

||+++++++++++ Creation Time ++++++++++++||
||-> Since August 2006

||+++++++++++ Initial Release ++++++++++||
||-> 10th July 2007

||+++++++++++ Latest Release +++++++++++||
|-> 21st January 2010

||+++++++++++++ Created By +++++++++++++||
||-> Nino Skopac

||++++++++++++ Base/Domain +++++++++++++||
||-> Recruiting Grounds - RecGr.com
||-> Recruiting Parser Base - BbcParser.recgr.com
||-> Development Grounds - Dev.recgr.com
||-> Discuss RePa and make it better - Forum-Grounds.com

||++++++++++++ Documentation +++++++++++||
||-> A big manual/documentation is available at
||   Recruiting Parser Base pages.
||-> http://bbcparser.recgr.com

||++++++++++++++ Licence ++++++++++++++||
||-> Recruiting Parser is a freeware, and even though you 
||   can edit its source code, you must (we will really like it)
||   post that change to Forum Grounds (more at Base).
||   Of course, you can use it in your applications, but keeping
||   all comments intact.

||++++++++++++++ Extras +++++++++++++++||
||-> Recruiting Parser has a few internal (built-in) extras.
||   External extras ran via file called 'parser_conf.ini' are
||   are available as of Recruiting Parser 1.0 Final.

|| -> -> ->    B R I E F   I N F O R M A T I O N    <- <- <- ||

 Recruiting Parser (RePa) is an Object Orientated Programming
 powered PHP application which transforms BBCode to (X)HTML.
 There is no any kind of storage required, altough there's a 
 file called 'parser_conf.ini' which is used for manipulating
 with Recruiting Parsers' extensions and add - onns.
 
 Requirements: the only thing you need is PHP 4.
 But, if you can, use PHP 5 instead, since it will work
 slighty faster. Recruiting Parser is not tested on PHP6.
 
 Implementation and usage: extremelly easy, you wouldn't believe.
 The function which powers this Application requires only one
 argument, although you can use them more (see below for more
 information).
 
 Major Features

    * over 25 case-insensitive different tags
    * incredibly easy set up
    * automatic correction for badly formated tags
    * protection against tags that would cause page crash
    * protection against spam-robots (mails get crypted)
    * 16 different smilies bundled
    * deparse function - parse and deparse how many times you want
    * built-in updates - check if a newer version is available, whenever you want
    * open code
    * reprogrammable & extensible

|| -> -> ->    S I M P L E S T         U S A G E    <- <- <- ||

 >> we assume there's some text that needs parsing
 stored in variable called $text <<

 require_once('parser.php'); // path to Recruiting Parsers' file
 $parser = new parser; //  start up Recruiting Parsers

 $parsed = $parser->p($text); // p() is function which parses

 >> parsed text is now stored in $parsed, if you want
 to output simply output $parsed, thusly: <<

 echo $parsed;

 >> Tags Supported <<
 (case - insensitive)

 > Bold Text
   [b]Some Text[/b]

 > Italic Text
   [i]Some Text[/i]

 > Underline Text

  [u]Some Text[/i]

 > Highlighted Text
   [cool]Some Text[/cool]

 > Indent Text
   [indent]Some Text[/indent]

 > Speech or Lyrics Text
   [lyrics]Some Text[/lyrics]

 > Small Caps Decoration
   [smallcaps]Some Text[/smallcaps]

 > Bigger Text
   [big]Some Text[/big]

 > Smaller Text
   [small]Some Text[/small]

 > Monospaced Text (Teletype Output)
   [tt]Some Text[/tt]

 > Subscript Text
   [sub]Some Text[/sub]

 > Superscript Text
   [sup]Some Text[/sup]

 > Hyperlinks
   [url]http://www.recgr.com[/url]
     - or -
   [url=http://www.recgr.com]Recruiting Grounds[/url]
     - note: links don't have to be set up that well,
       basic link format will be recognized and fixed
       by Recruiting Parser -

 > Images
   [img]http://get.recgr.com/i/rgwallpaper.jpg[/img]

 > Email Links
   [email]demo@recgr.com[/email]
    - or -
   [email=demo@recgr.com]Demo User[/email]
     - note: RePa can crypt them so these addresses
       become safe from spam robots. defaults to on,
       but you can turn it off by putting 0 to 6th
       argument. more in arguments chapter below -

 > Text Font
   [font=Verdana]Some Verdana Text[/font]

 > Text Color
   [color=red]Some Red Text[/color]
    - or -
   [color=#FF0000]Some Red Text[/color]

 > PHP Highlight
   [php]some php code[/php]
     - note: RePa will once again fix and parse
       not completely correct typed text -

 > Code Type
   [code]some html or similar code[/code]

 > List
   [list]Some List Property[/list]

 > List, with Order (Decimal numbers)
   [list=dec]Some Ordered Text[/list]

   --> XHTML Short Tags <--

   > Bull, &bull; 
     [bull /]
   
   > Copyright, &copy;
     [copyright /]

   > Registered, &reg;
     [registered /]

   > Trademark, &trade;
     [tm /]

  >> Smilies Supported <<
    (bundled with Repa)

   Shorter          Original
   --------------------------
     :)     =>     :confident:
     :D     =>     :happy:
     ;(     =>     :crying:
     ;)     =>     :friendly:
     >:)    =>     :evil:
     :O     =>     :panic:
     :|     =>     :indifferent:
     0:)    =>     :angel:
     :P     =>     :teasing:
     :(     =>     :angry:
     :3     =>     :polite:
     :X     =>     :mad:
     :@     =>     :tearing:
     :()    =>     :yelling:
     :/     =>     :sad:
     ;\     =>     :sceptical:	 

  *********** || YOU MUSTN'T EDIT THIS COMMENT, OTHERWISE PARSER WON'T EXECUTE || ***********

*/

/** ensure this file is being included by a parent file */
defined('_VALID_UA') or die('Direct Access to this location is not allowed.');

define('SECURITY_CODE', __LINE__);

function getRpPath() {
  // made in own function to prevent variables from overwritting...
  // we use a CONSTANT instead of variable so it is not neccessary to
  // transfer it through application.
  // this is a original path to RePa so we can link smilies absolutely
  $f = __FILE__;
  $f = str_replace('\\', '/', $f); // Windows
  $dr = preg_quote($_SERVER['DOCUMENT_ROOT']);

  $parser_filename = basename($f);
  $path = preg_replace("#^($dr)#", null, $f);
  $path = pathinfo($path, PATHINFO_DIRNAME) . '/' . $parser_filename;

  define('RP_FILENAME', $parser_filename);
  return define('RP_ORIGINAL_PATH', $path);
}
getRpPath();

class parser {
  protected $codeline = SECURITY_CODE;
  // Searching Repository
  private $bbc = array(
    1 => '[u]',
    2 => '[/u]',
    3 => '[i]',
    4 => '[/i]',
    5 => '[b]',
    6 => '[/b]',
    7 => '[cool]',
    8 => '[/cool]',
    9 => '[code]',
    10 => '[/code]',
    11 => '[indent]',
    12 => '[/indent]',
    13 => '[lyrics]',
    14 => '[/lyrics]',
    15 => '[smallcaps]',
    16 => '[/smallcaps]',
    17 => '[big]',
    18 => '[/big]',
    19 => '[small]',
    20 => '[/small]',
    21 => '[tt]',
    22 => '[/tt]',
    23 => '[sub]',
    24 => '[/sub]',
    25 => '[sup]',
    26 => '[/sup]'
  );


  // Replacement Repository
  private $rep = array(
    1 => '<span style="text-decoration: underline;">',
    2 => '</span>',
    3 => '<em>',
    4 => '</em>',
    5 => '<strong>',
    6 => '</strong>',
    7 => '<span style="font-family: Verdana, Arial, Helvetica, sans-serif; letter-spacing: 2px; word-spacing: 3px; font-size: 13px; font-weight: bold; font-style: italic; color: #333399; font-variant: small-caps; height: 12px; padding-left: 9pt; padding-right: 6pt; vertical-align: middle; display: block;">',
    8 => '</span>',
    9 => '<div style="width: 80%; overflow: auto; text-align: left; border: 1px solid #CCCCCC; display: block; padding-left: 20px;"><code style="white-space: pre;">',
    10 => "\n</code></div>",
    11 => '<blockquote>',
    12 => '</blockquote>',
    13 => '<span style="margin-left: 30px; font-style: italic; display: block;">',
    14 => '</span>',
    15 => '<span style="font-variant: small-caps;">',
    16 => '</span>',
    17 => '<span style="font-size: 22px;">',
    18 => '</span>',
    19 => '<span style="font-size: 10px;">',
    20 => '</span>',
    21 => '<tt>',
    22 => '</tt>',
    23 => '<sub>',
    24 => '</sub>',
    25 => '<sup>',
    26 => '</sup>'
  );

  // "Specials", XHTML-like BBC Repository
  private $xht = array(
    '[bull /]' => '<big>&bull;</big>',
    '[copyright /]' => '&copy;',
    '[registered /]' => '&reg;',
    '[tm /]' => '<big>&trade;</big>'
  );
  
  private $emoticons = array('confident', 'happy', 'crying', 'friendly', 'evil', 'panic', 'indifferent', 'angel', 'teasing', 'angry', 'polite', 'mad', 'tearing', 'yelling', 'sad', 'sceptical');
  private $altEmoticons = array(':)', ':D', ';(', ';)', '&gt;:)', ':O', ':|', 'O:)', ':P', ':(', ':3', ':X', ':@', ':()', ':/', ':\\');
  
  protected $rpVersion = '1.0.1';
  protected $rpAddr = 'www.bbcparser.recgr.com';

  function __construct() {
    if (!function_exists('caselessPcre')) {
      function caselessPcre() {
        $extensions = get_loaded_extensions();
        $extensions = array_flip($extensions);
        $extensions = array_change_key_case($extensions, CASE_LOWER);
        $extensions = array_flip($extensions);

        if (in_array('pcre', $extensions)) {
          return true;
        } else {
          return false;
        }
      }
    }

    if ( (!(extension_loaded('pcre') && caselessPcre())) && (!isset($_GET['silentmode'])) ) {
      echo "<h1>Fatal Error</h1> \n\n";
      echo "You don't have the necessary prerequisites! You must install the PCRE libray!";
      echo "<br /><br /> \n";
      echo "If you think this check is <strong>wrong</strong>, just append <code>?silentmode</code> to your URL.";
      die();
    }
  }

  public function protect($email) {
    // Protect Emails from robot-spamers
    // How does it work?
    // - it converts every character to its ordinal value
    // visitor won't see any difference

    $email = trim($email);
    $intEmail = "";
    $num = 0;

    while ($num < strlen($email)) {
      if (empty($intEmail)) {
        $intEmail = "&#".ord($email[$num]);
      } else {
        $intEmail .= "&#".ord($email[$num]);
      }

      $num++;
    }
    return $intEmail;
  }

  public function alterArray($array, $operation = '') {
    switch ($operation) {
      case 'lower':
        foreach ($array as $op1Num => $op1Data) {
          $array[$op1Num] = strtolower($op1Data);
        }
        break;
      case 'clean':
        foreach ($array as $op2Num => $op2Data) {
          if ($op2Data == null || strlen($op2Data) < 1 || empty($op2Data)) {
            unset($array[$op2Num]);
          }
        }
      break;

      default:
        return $array;
    }

    return $array;
  }

  // Enhanced str_ireplace
  function stir($seek, $replace, $subject) {

    if (function_exists('str_ireplace')) {
      return str_ireplace($seek, $replace, $subject);
    } else {
      $seek = preg_quote($seek, '#');
      return preg_replace("#".$seek."#i", $replace, $subject);
    }
  }

  public function smilies($whichOne) {
    // RP's Smilies (Emoticons) repository.
    // all in 8 bit format, otherwise it wouldn't survive

    $smiliesBundle = array(
    $this->emoticons[0] => 'R0lGODlhEwATAPesAIJzGIt8GYx+J4t9MIp8MoV7SYV8T4h+T4h/VpWHP5aHPJ2NNZ+ONZyMPJ2OPaCOHaCOM6yaLqeWNKKROKiWMKmXNKuZM7WhKreiKrulIrumIr2nI7mkLb2oI76oIomCX4qCXZWIQ5SITJGIX4+HYI6HZ4uFaI6Ha4+IaZGJY5GKbpKLbpGLcZKNdZWPd5OOeJSOeJSQf8auJMevJMCqKcGrK8KrKcOtKcqyJMqzJcy0Jc+3JcixKsu0Ks21Ks+3KM+3KdS7Jte+JtK5Kdm/KN3DJ9rAKdzBKd7DKN3EK97EKd/EKODFKeDGKOHGKOHGKeLHKeTJKOTKKeXKKObKKObLKefMKejNKenOKOnOKevPKuvPK+vQK+zQKu3SK+7RKu7RK+7SK+/SKu/TKu/TK/LVKfLVKvLVK/PWKvPWLPTXK/XXKvTYK/bZK/faK/bZLPfZLPfaLPjaKvnbK/jaLPnbLPrbLPrcK/vdK/ncLPrcLPvcLPvdLPzdK/zeK/zdLPzeLJaRgJiTg5mViZmWiZyYip2aj56bj6Ofl6Ogl6Whmaeknammn6+sqK+tqbm4tb69vb++v8C/v8HAwMHAwcbFx8vKy8rJzM/P0dDP0dPT1d/f4+Pj5enp6+zs7u3t8O7u8e/v8e/v8vT09vX19/b2+Pj4+vr6+/v7/Pz8/f39/f7+/v///6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAK0ALAAAAAATABMAAAj+AFkJZJVK0yADBAQMKBDj0qmBEEMpCsFDC5w8br4AaVCoE0RWnmBcWPPHTp2Tdf7IsVGC08BSgWjgQUnzJKAfKkIJfEThDk09KPeY/IOBEatPI7AArUOHjJiTaZJsAVpGxCZKFfqghOMBhx49YG5wAQqIg6NDPf7QfBMHJZ+vdfgcacFCyZ6TS2ueiUJHTxYEKKAAjWOlTU09WgAs4dPlwAsjfPgICcBGjx07X/lceTBmTxUQi2oAqnNGQ44sYbxocbIjQxE9fIYIwuRgTh09aoLM2NBBho4mau76sQDJlAsifE6+pUMn7t3bVEiAYpUpgZm8NU+6YSBJ4CpHENA6JM++x02ERKgGnmqkAAkeQHu+/sEzZQEiUh9VWVoxgQcTKU/4IEEKkzz0kUCjVGLICR+YQEgkonwUEAA7', 
    $this->emoticons[1] => 'R0lGODlhEwATAPfuAB8cIB8fICAcICAdICEdICEfICIeICIfICMfICYfIDccICUgBzItCSUhICgkIDEsITgxITs0IT02IUseIVoaIUc/InEdInYdIns3I0pBIk1EI05FI1FHI1JII1xRI1xSI2BOI2FVI2tfImpeJHxtFm9iJHNnJXRnJHZpLp0dIrQwI4R1GZB+GoFzJYV2JYZ3Jot7Jo5/JoV5PYZ6PIp7Nop9OrZKJYZ7R4V7ToR7Uod/V4R8WIR9Ws8fJMMhI8kmJOAcJOwZJPAcJJiGG5OCJpKEOZeIN52NNJ6POqGOHauXH6KPJ6OQJ6mWLamXLayYJ66aKKCQN6OSNa2aMLKdKLOfKLCcL7KeLLWfKLSgK7iiKLmkKrqlLL+pIr+pJ4eAXY+EVI2FXJOHQpKFR5CFSYqDZImDaIuEaIqFaoyGbY+IaZCJapKLcJCKdJCLd5GLdpGMd5OOfJOPfMSfKMenKcCqKcGqK8KsK8StKsSuK8unKcmqKcewJ8+3JcmxKc21KtGwKdG4Jte+J9K5KtO6KtW8Kte9Kde+Kdi/J9m/Kdi+KtzCJ9rAKd7EKN7EKd/FK+DFJ+DFKeLGK+PIKeTJKeXLKeXKK+bLKebMK+jMKOjNKenOKevPK+zPKuvQKevQK+zRK+3RK+7RKe7RK+7TKu/TK/LVKvPVK/PWK/PXKvPXK/PWLPTXLPXYKvXYK/bZKvfbK/fZLPfaLPjaK/nbLPrcK/vdK/ncLPrcLPvdLPveLPzeK/zeLP3eLP3fLP7fLP7fLf3gLP/iLZqWi5yZkKCclaGdlKGelaGelqKfl6ShmqajnKeknaikoKqooq2qpLCtqrSysba0tL28u8LBw8TDxMfHx8fGyMvKy8zLztPS1dnY3Nzc3+Li5OXl6Orq7Orq7ezs7u7u8PHx8/Pz9fT09fX19/b29/n5+vr6+/v7/Pz8/f39/v7+/////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAO8ALAAAAAATABMAAAj+AN0JdNfOW7Q4X3SUGUZt3MCH7spBA2PFUCVNk/5IUWNtHcRvcpyIMjVkEbA+SWA1KuIM3cBxcOrU4oVogRJhLBhs+uUFxTN2BJldoTKIVycSgYB1WaHqFxQRNbC52zZGVAkmvHLJosVVFq9QJqoIYmMOmZ1chD4QsZTLVy9ftAqVgKBoF5JsPCjlAqWBQAYXT7AsOREBgIRHv+4ok3EKF6cNAwYIQNDAAAADBCpg4pXoDQ1XuEZ1mIygtGkBHELlinRmBiparEIAME1bgIdVuRi12XEJF64Ws2mXBjAiFi88xYjl4cVLi4MA0AuchpHLVpRr2sS0wvUJBIULFiZsJDhg4IEfX5DWkGNnbIsuXXtUBBECJIWCAy9kvTJSTWA4NHzwcoseNvzQgw8YxFDKLE0ko85A3aSRhSnBgALIHHRIwksmRxxzDkTiLEMGF46Qkoonh0wRxjTpQCQQO9w0YwYON+TghjTgQBQQADs=', 
    $this->emoticons[2] => 'R0lGODlhEwATAPf4ADs0Cz01Cz84D0E5DEI6D0Q9DEU9DEg/DUQ9E0pBDUpCDUxDDk1FDk5FDk9GDlVLD1hOD1lPEFxSEV1TEVpWPmRYEmVZEmVaEmZbEmdbEmdcH2hcEmldEmleEWtfE2FXJW1gFHRmFXBmG3lrFXlrFnxuFnFpMHpvMHlvN3hvO3xwK3pwMnlwNVlXT21oRnpzS3p0Un95WH96XHZ0aX97YIFyF4FzF4R2F4d4GI5+GY5/GYd7NIZ7Nol9NYJ6SIF6UYF8XIR+WYR+W4F8YY+BGpSCGpWEGpiGG5uKGpqKHJyLHJ6NHY2AKZKEKZmJJZyLJ5mJLJ2NKZKEMKCPHaKRH6aTHqiVHqmWHqqXHqqYH62aH7CcH6aUIquZJrGeIbKeILWgILWhILeiILeiIbiiIbijIbmkIrqlJLumJL6oIb6oIr6oI4SAaIWAaIiEaYiEbo2KdpCNfsCqIsCrIsKrIsCqJMWvIcWuI8WvI8WuJMiwJMmyJMqxJMqzJMu0JM61JM+3JdC2JdC3JdC4JNC4JdG4JdG5JdG6JdK6JdS7Jde9Jde+JNa+Jti+Jdi/JtnAJtrAJdrCJt3DJt/FJt/FKODFJeDFJuLHKOPIJ+fNJuLIKOLJKOfMKenNJ+nNKerOKerPKOvPKuvQKuzQKOzQKu/SKe7SKu/TKvDTKvDTK/HUKvLVKvPXK/TXKfTXK/XXKvXYKvXYK/XZK/XYLPbZLPjaLPjbLPnbLPrbLPrdK/vdK/jcLPncLPrcLPvcLPvdLPveLfzdLPzeLPzeLf3eLP3fLP3fLf7fLf7gLf/hLf/iLYaFho+NhZGPgZKQgpGQhJaVh5eViJeVipeVi5iWi5uZkKemoqqpoaurpqyrpq6uq7Szsbq6ury8usHBwcrKzM3Ozs/Q0tDQz9LT1dXW197e4OTl5+np6+rq7Ozt7+7u8PHw8fDx8/Pz9PT09vb2+Pf4+fj4+fr6+/v7/Pz8/f39/v7+/v7+/////6usrQAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAPkALAAAAAATABMAAAj+APEJFPjuWzU2MYY045ZuoEN89sC5kYJGkSVIeaL82Bbv4TxsOxblomUqFClWvzI9iaNu4L1sTOwc8RDBAYMGDyrgSEMFjjuB4kx0KMDBSBY+gf6EUTIiAQQR1/Ctm0HggiNgdySIIibMRg5jnnQEoEBu2QcES5ApAwNgk69dGEIcS3ZIgIYWzMyIGUCCzqM9snrxYkSoUBEDNyK5eJNomKESCxREsABiw4QGBzKMiaWKhQxJvn79CiWIzBQiSb7o0VRrGK5XKto0+nXr1q9ZfrRgqaImVbDat1adoFZn2K1eqeQAMhXrVJ8rn3odx/TCGxRdvWrhuRRMOi5hlNRvuLo1bM0zdkEqndoCiDZw244Q9YLVIxy+bk1A1eAk/f0tW1bMUQY09eAjTzRcDIIKLv6RNwcSQpwzUDvOONFJMb8weIsvw7TiBRDlPASPNj500cgoq5QyyRk8SIPOQwOZYw0NKayAAgzTjEOPQwEBADs=', 
    $this->emoticons[3] => 'R0lGODlhEwATAPfGAHFkFH5xF4Z4GIZ8N4V7OoZ9OYd+O4V8P4t+Mop/NYF7UYN+W5GBGpuJHJuKHIyAL46BMI6EOo+GPpKGLZSHKJSGLJuMLpyMLZGEM5GFNJGGN6WTHqqXHq+eJ6maK6ycKbOgILOhJ7aiIbqlIbqmIbmlJrqlJryoIrypIr6sJY2EQYWAXYiCXoiDYomFZYmGbImGbouIcI2JcZCMcZGNd5CNeJGPf5WSfMGsI8KtI8OvI8CuJcWuJcWwI8WyI8awJcazJcmyJMu3I8i0Jcq1Jcu0JMu1JM22I8y1Jcy3JM62Jc65Jc+4JdG5I9G6JdG7JtO6JdO7JdK6JtK8JNa9Jta+Jti/JNbAJ9nBJ9rCJtzDJd7FJ+DIKOHIKOLKKOfMKObPKunPJ+nPKerPKOzQKu3SKO7SKu/TK+/VKe7UKu/VK/DUKPDVKfHWKfHWK/TWK/PZKfTaKvXaKvbZK/fZK/faKvbZLPbaLPfbLPfcK/jaK/jaLPnbLPrbLPrcK/vdK/jcLPncLPrcLPrdLPvcLPvdLPvdLfveLfzdLPzdLfzeLPzeLfzfLPzfLf3eLP3eLf3fLP3fLf3gLJORgpWSgZeUgZaUhJqXh52bjZ2bj5yakp+elaSinKmooamooqyrpayrprCwq7Gwq7GxrbKxrLa1sre2s7y8u76+vsTDwsfHx8rKy83Nz87Ozs/Pz9HR09PT09PT1NXV1tXV2NnZ2tnZ293d3+/v8fHx8/X19/f3+Pj4+vn5+vr6+/v7+/v7/Pv8/Pz8/f39/f7+/v7+//7//////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAMcALAAAAAATABMAAAj+AI0JFHjrlI0FClZMQoVroENjwVKxuPBjy5ctPCy0UCXsIS9OD7T8UURIECFFfqwg+ORrILFOFNoU4kOTjyCaiNZUEDWwVQI2N2sKMvOGpqAyBmQZ2zVjyiI+eG4WUiIgyB6ai4zc+PVKQx6oDYoUIgSlipurhQTJkVDLUwhIfA4FACC1UB8+dJykkfRhFCUiTwth2WCnJs1CDhws2oGpxhNDRgcZNnriSiMglywNWRR0sk0xPQBFSpEJVIdIZubcNSzoDg4vghh5KDUrQhwGKO4YGiSo0CI1JJJIhqPCVi8aQqggybGkCxguOjgwCcRnkY9KwYzBKoDm0RkpI0BNiAhCBnKhMAdoCSxGakIZR2ltJrqpaAwEUw6BhRrQRI+iQgAqUscRBJTSkUPDuPICBiZEkQUUJWQgQyzEPDRQLqtoAoMLMWzCii4PBQQAOw==', 
    $this->emoticons[4] => 'R0lGODlhEwATAPfAAFNKIlRKI3xuJYJzJYV1Jop6Jot7Jo1/O4Z7R4Z8S4h8Q4p9QIh8RIZ+WZCAJpOCJpWEJpiHJ5uJJ56MJ5uMM52NNZ2NO5yNPaGOJ6KQJ6OQJ6WTJ6qYL62ZKK6bLKOSMaOSNqeWNLGdKLKdKLKfL7SfKLWgKLahKLejK7mkKbmmKbqlKrumKrqmLL+pK4+ETY2EVY2FXpGEQJKFQJOGRZSHQ4iBYYiBYouEZo+HZYyFaIyGao2GaoyGbY2HbY+Jc5GLc5SOdpOOeMGrKcGrLMOsKcOsK8StKcStK8WuKcevKcewKcixK8y0Ks21Ks61KNa9Kte+KNi/KNm/KdrBKdvBKdzCKd7EKd/FK9/GKuDGKeHHKeHHK+HIKeLIKOLIKePIK+bLKObLKebLK+fLK+jMK+rPKerPK+vPK+zQK+3RK+7RK+/TKe7SKu7SK+/TLPHVKfHUKvHVKvLVKvPWK/LVLPTXKvTXLPXYKvXYK/XYLPXZLPfaLPjaKvnbK/jaLPjbLPnbLPrbLPncK/rcK/rdK/vdK/rcLPrdLPvcLPvdLPzeK/zdLPzeLP3eLP3fLP7gLP/hLJeThJqViJuYjZyYi5yZj5+ckqCck6CdlKKfl6OflqKfmKWim6aknq6sqK+tqrCuq7a1s7i3tr69vsG/wMTDxMzLzc7N0M7O0NDP0dLS1dbW2d7e4eLi5OXl6Orq7evr7ezs7+/v8fDw8vPz9PT19vX19/X2+Pj4+fr6+/v7/P39/v3+//7+/v7+/////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAMEALAAAAAATABMAAAj+AIEJBLZrlaUGCw4wuOGp1a+BEGFlqoFEDJ08cba0ePHJFkRgrniwwNPoUKCThxqx4SBp1sBYO540CvRH0clAhxQdKoRiUi5gvTatmBloyZE/OMss+SOI0IdRwFjRsIOySAARihydCJDm0KEwMWRxcuHokKA6EQSIOOTIBICugRaFIGXjSyA1gfScQWTyEJkjfE42alIJwZwSGPrexGnzZKIrPhTMGTBBkaDFmHF2wZGgDRQCEMY8MonZpCIqP3pUOQRGggona0jfdPOnkRFNoEg0cqTEwYMNiG4yytLhzyALp17BMHMI0J47DoY0ms7FgZNHUoDgAibqgx/TWApUZEihwUASRXJmpBKYi5KHPokCKXqjZEQRNJDgUADla2AtTBV4YUgjijwCSSSERCFDKLp8pEspOYDAhBVaTEHEBUGowstHA9FiyiVA6CBEJ6jc8lFAADs=', 
    $this->emoticons[5] => 'R0lGODlhEwATAPfoABwYIBwZIB4bIB4cICAcICAdICEeICIeICIfICMeICMfICYfICkfIC0cIDEeICknISolIC0oIC0oITImITMtITQuITYwITcxITo1IUkdIVAaIVAeIXEbInceIk9FDk9GDlhOEE1HI1FIIlhOI1tRI1xSI2FWJGldJGpeJH1SI3hqFn1uFm1hJG9jJHJnJXdqJHdvOnZtPHlvMXluMnlvMn5yLXdwP3ZvRHdxS3lyR3lzTXlzTnp0Snt2Vn55XYIaIpQdIrEbI7YcI7wcI6czI4x8God5JYh5JYd9OYl+Noh9OoN6QoF4RIF5S4B5T4F6TYJ9XoJ9YIJ+ZcYZJModI98cJOcbJPQcJJOFJ5SFJ5eFJpeHJpqEJpqJJ5uKJZyNIp+PIZ6NJK6aH6CPIaKRJqSTJ6CQKKiVIqiWIaqXIqiYIK6bIa2aJa6cJbKdILCdJLWhJrikJLmlJLmkKbynKLyoI4eDaYaCboeEb4iEbIiFc4uJd42KecKtJsevI8KrKcOuKcavKcy0JMy2Js62JM+3Jcq0KtK6JtS7Jta+JdO7KtW8Kte+Kti/Jd7FJtnAKt7EKN/FKOLIJ+PJJuTKJuXLJ+bLJubLJ+jNJ+vPJ+jNKOjNK+rOK+vQKOvQKezQK+3RKu3RK+7RKu7SKe/TKvDUK/HUKvHWK/PVK/PWK/LWLPTXKvTXK/XYK/fZLPfaLPjaKvnbK/jaLPnbLPrbLPrcK/rdK/rcLPrdLPvcLPvdLPzdLPzeLPzeLf3eLP3fLP7gLZORhJKRhpSRhJaVjJiWipyaj5qZkZ6dk6SjnKSjnaSkn6moo66tq66uqq6uq7Oyr7KxsLSzsdHR09XV19bW2N7e4d/f4eDg4ubm6Ofo6ujo6enq7Orr7Ozs7u3t7+7v8fLy8/Ly9PX19vj4+fv7/P39/f7+/////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAOkALAAAAAATABMAAAj+ANEJFMgtmp4dN3TgaZZtoEN05KQ5MSNI0iVJhco0cTbuobhhWCb9cgVK161dvjB54fNtILlgZ2Ll6uUHxCFds2bdqrVmT0d00LLAIiVLV6QPRXDmnGUrDDN02p5YYrVCzK1bkBDdWqozExNsz8jwkqXCg6hbupRy9cVG2R1Cu25FcpOKq11djXzwqHR1FrCtXNG+wnXLUwwbnXj9OdGFk69btG752qQFRSBdpmjk0FSqRAAFJOiEQvVpzogDAEyoGgVDSqJFFQ4oICBBBIkQDwgoMHDhkaMey9oAgoBAgYIEDTRw2LBAwYEIhuIcs7akDwXZChgAGSKkSofdFgZlIamGLtmXFgJkI8gwxcoVKg4GvEhj7By6cHnAsIAgoACGFEQE8cMELqgRhTcDbWPHGG8YsQUjpyjCxRFwoAHFNQ+Bg4wSckyySiurUFJHEsV085BA5lBDzA4z1CADDsJMU45DAQEAOw==', 
    $this->emoticons[6] => 'R0lGODlhEwATAPerADw1C01FDmJWEXJkFHRnFXlrFn5wFnduOnduO3ZuPnluNHhvN3xxM3tyO3pyQ3tzRX94S3p0Un14WX15XIx8GYt/LoJ5PYZ8OoN6Q4F5R4R7QoB8Y4J+Y42ALZaHJpOFKJuMJJGDMJSGMKmWHqGQI6STJKeUI6eVI6aUJquYIquYJa2bJa6bJrKeJLWgILeiIbejJLmjIbqkIbulIbilJLylIbynIYaBYoWAZoaBZIeCZ4WBaIeDa4aCbIiFb4qGcoqHc8CpIsewI8y0JNG4JdK5Jde+I9S8JdS8JtW8Jte9Jta+Jde+Jdi/Jdm/JtnAJNnAJtvDJ9zCJt/FJ9/GJt/GKObKJuTKKObLKefLKejNJunOKO3RJ+3RKezQKu7RKu7SKO7SKe/TKO/TKe/TKvHVKfHUK/LVKfLVKvLVK/PXKfPWKvXZKvbYK/bZKvfaK/bZLPnbK/jaLPnbLPrbLPncK/rcLPvcLPvdLPveLPveLfzdLfzeLPzeLf3eLP3eLf3fLP3fLZOShZeWjJiWjZuZj56ck52clJ2clZ+elqKhmaSjm6SjnqWknqemoKemoamopLCwrLW1ssLCwsXFxcjJysrKy9bW2Nra3N3e4N/g4uPj5ebm6Ojp6u/v8PHx8/Pz9PP09fT09vX19vX19/f4+fn6+vz8/Pz8/fz9/f39/f///6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAKwALAAAAAATABMAAAj+AFcJFEjKUiIgOn4cqiRqoMNVqCjlCAGDiJMiNETcmHTq4ahDFYzE+YOn5J86TzoUCjXQ1CAQavDQwTOnpp2SbEgIKiVQ0gc3duaYUQJnjp0rU+zYeeMh0ipOEKzcmdMnCIAod/QYECDHqJYMmyCh4FOzj5AAVbJSGNB1DiAVjDYcoVmzDZaudsh4oVMTT5MJCbYorWmU8GCjdrocUIAmTZY+fCJLlhwIy5A1DBCEgUIghozPoEHXKDDjzAIJUr64QJKktWvXSEZkoRIBUYtAe0rq3l3STiAYhDBZEBOUsHHDZS5cUqXIRJ7j0PmkMJRq1aceL/IUP26Hj40dngYvdvJRgosfPEFvAgJzggenh6AcYWCxZMuYLUxWaFj06eFATY/g8EADDnDQSCYPBQQAOw==', 
    $this->emoticons[7] => 'R0lGODlhEwAZAPf2AHtvKHluLXdtN3duO3dvP3tyOnx0P0pVW0tWXFBeZVFfZnZvQ3pzRnpzSn12TXx4W354WGpvcWtvcmdwdGpxdWpyd2tydnF5fWx4gW97gW97gneBh3eBiH+Eh3+KkH+LkX+MkX+MkoFyF4p6GY9/Got+LI1/K4d6MIh9NYh+P4p/PIB5SIF8W4N+XoR9WIN/Z4+CL5WFJ5GDLZSFLZiIJ5mJJp+PI5qLL52NKZqLMKaTHa+bH6GQJKORK6qYJbSfILWgILeiI7ijIbmkI7qlIbumIrymIb2nIbynIrynI4WBZ4eCZ4mEZoqFa4iEbomFcomFc4mFdYuHdcKrIsavI8myIsmyJMuzJMu0JMy0JM63JM+3JdG4I9K5I9O6I9O7I9G4JdO6JtO7JtS8Jta9Jte+Jti/JNi+Jti/Jtm/JtrAJdrBJNrAJ9vCJ9zDJN3DJN3DJd3DJuHHKOLHKOTKJ+fMJuPJKOXLKebLKefMKOvPKOvPKuvQKuvRKu3RKezQKu3RKu7RKe7SKvDUKvLVKfPXKfPWKvPWK/TWKfTXK/XXKvXYKfXYKvXZKvbZK/faKvbZLPfaLPnbK/jbLPnbLPrbLPncK/rdK/vdK/rcLPrdLPvcLPvdLPvdLfzeK/zdLPzdLfzeLPzeLf3eLP3eLf3fLP3fLYCFiIOLj4SMkIiOkYyPkZOQgZSShpiWjZiXjpeZnJabnpacnp6ck5ianJqeoZ2hoqCel6GgmKWkn6alnqGmqKWqrairrauusLGwrbOyr7a1tLm5t8C/v8DAwcHAwMDBwsHCw8PDwsbGx8fHyMnJycnJysvKzM7O0M/P0NDQ0tjY2dnZ2tzd4N/g4uLj5OPj5eTk5OXl5ebl5ebm5uvr6uvr6+zs7O3s7O7u8PDv7/Hx8fDw8vLx8fPy8vPz9fT09fb29fb29/b3+Pf3+fj4+vj5+vn5+vr6+/v6+vv7+/z8/f39/f7+/v7+///+/v///6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAPcALAAAAAATABkAAAj+AO0JFFjPWzhy4Made1dvoEN73YzB6nChwoQKF07ROubtYTYPCCSsstXLVy9bqyIc+KDN4TUQCiyokrWL165YqigkCIHt4TZjtVBt0IAhA4dUtYxxezgQXbNbUl48mZWsHFOB8ZYtgRGEixovSWa0GObuYTtdJ75YKvWJE6dRl9aYeGUVKy4ahDhR2suXEqdGNlqtEzisBKJMfRNnehTjl71vLuDoTay4zgprwXB8osw5lI9cULKE4ptJFF9OnU6jedCADmJKmbYAScPp0BQigBBXCiRggJ9KnTJlsjOFT6ZJYa44gk1JEQACeiiR+SOc1GtOoCg5EpTJUAAIcQakiQBjmnKmPiQA5VngaggnPELupE7MScuIOVaiQEPB6JMcHWdowolwnEzCBhWJSCIDMfGwUsQom+Cxww9j4GGHGFaUAYkpVTiRjj3VOPDFKJlE0sYRRiCBxR6lvWFANAM5w0AVbnEiSiacfPKJJ10UgMxD0ihRgxmFYDIKJou4wQMLz1xljjBNpJBDDzeowAQw4lxljzzwsEMNM8UoM4069MzjUEAAOw==', 
    $this->emoticons[8] => 'R0lGODlhEwATAPfmAEtDI0xEImdcJHNGI25iJG9jJHFmJH9rJY83I4g6I68vI7InI7UpJL8hI4dOJIVaJZZDJI5+Jo9/Jo9/J5l9J6BdJapZJapnJqtvJod+UtAaI9wXJOIYJOQZJOgYJOsZJO4YJO0cJO8cJPIbJPEcJJKDJpqKJ5eJPZqLOJuMOKCNJ6SRJ6STLamYKKuYKK2aKKOTMaOTM6aVMaGRO6CSP6mXNa6cNLGeKLKeKLWhKLShLbGgM7GiMbKgMrekMYeAW4yCUI2EUYiAXo+GWZGFQZKGQZGGR5aLSZaKTZuPSJCIXouEYI+HaI+IbpCIZJKMcpOMcpGMepaVfsWnKcGrKcGsKcOtKcapKcexKcezK8myKcmzLMm1K8y3KM22Kc60Ks24Ks65Ks+4Ks+6KtC4KtG5KtK7K9O7KtK8KdO9KtW8KtW9KtW/K9a9Kti/Ktm/KtnAKNnAKtrAKtvCKNvEKtzCKt/FKuDHKODGK+HHK+HJK+HKK+PIKOLIKuPIKuXKKuXKK+bLKubMKOfNKOfMKunOKevPKujQK+/VKe7UKu/UK+/ULO/VLPDVKfLWK/HVLPLWLPPWLPTXKvTXLPPYKvHaLPXYKvfbKvTYLPXZLPbZLPfaLPXdLPfcLPnbK/nbLPrbLPvdK/jcLPncLPjfLPrcLPvdLPveLPzfK/zdLPzeLPzfLP3fLP7gLP7iLJSQgpqVg5iUhZqXiZqWipuXjJuZhJ2ZiZ6ajZ+cip+cj52akaGekqWjnaimoquoobGwrbKwr7Kxr7OysbWzsbW0s7e1tLi3t7m4t7u6vMC/wMHBwcLBwsfGyMrJy8zMzs3N0NbV2NjY29nZ3Nzc393d4d7e4Ofn6unp7O3t8PDw8vPz9fP09vb2+Pf3+Pf4+fn5+/r6+/v7/Pz8/f39/f7+/v7//////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAOcALAAAAAATABMAAAj+AM0JFHjNWCwmS5roYqZtoENz3o4psWHGDyE7W2Y8cTbuYTZaMQr1iXOqlCYxjuAUARZuILdZOjyxikCgkyk5AMC0apRiWDmBxGCgMmVKQgFRpuIECKNqFSIj0MxZG3IHRx1VedyU+jTJC6RSavSggfVNWA8qAgCVKmXqk9tUn0ytMdAlSbQobRS1WATKrV+3pRiVoOMj2I9AqnKQgfvXraozOFxxyZXBkCk8Jh5t/VsKkwk2rcbYEvKHbQ4Vj1StLaUq0goXo1Rl2fWqTNtNNyZU0XNojxUJLzKVWrWjGLIaoT6xDTMAAYQEB9KM2koJCTVsTvi0NfVGAYgPGzBhkHKM5ZY4c8pQWGJ9pQGJEB4sVIorKEg1geF4yZDU6suCESJwcAEnqgxCxDIOddPLCXMkUoEGHTAwxSVaAJEMOQ+J0wwUNLDwgAMU8HCELNM85NA2z/hSixS4/CINOA8FBAA7', 
    $this->emoticons[9] => 'R0lGODlhEwATAPe0AE5FDnJlFYN0F4V2GIV7TYZ8TIh8RYZ9UYd/WpuLN56ONpqLOaKPHamVH6qXH6CPNKeVLqyaLq+cLKCQNKSSMaaUMKeWNqKROaSTOKWUOKuZMqyaMbSfILOgLrSgK7ShLrulIb2nI4eAWo6EVY6FXY+GX5CGVI+HaYyHbo2HbpCIZ5KKZZGKaJKLbZOMa5GKcJOMcZONdJWPdpOOfZSPfsKsI8OsI8avI8SuJMWvK8awJMewK8y0JM62JcixKsuzKtC4JdO7JtO6KdO7Kdi/J9/EJ9vBKdzCKd3CKd3DKN3EKN/EKeHHJ+DGKOHHKOLIKOPIKOPIKeTJKeXJKeXKKOXKKebLKefLKOfLKebKKubLKuvPKOrOKurPKuvQKuzQKezQKu7SKu/TK/DTK/DUKvDUK/HVK/PWK/XYKvbZKvbZK/faKvfaK/XYLPXZLPbZLPfZLPjbK/jaLPjbLPnbLPrbLPncK/vdK/ncLPrcLPrdLPvcLPvdLPveLPzeK/3fK/zdLPzdLfzeLPzeLf3eLP3fLP7gLZuXiZyYjZ2ajp6ajp+ckZ+dkqGelKOgl6KgmKShmKWinK2qqK6tqrWzsLe1s7e2trm3t7y7ur+9vb++vsLBwcPCw8TDxMXExsfGx8fGyMnIy+Tk5+Xl6Orr7evs7u/w8vHx9PLy9PLy9fPz9fP09vX19/b29/f3+Pn5+vr6+/v7/Pz8/f7+/////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAALUALAAAAAATABMAAAj+AGkJFHgKVKMYJ2Q4CrVqoENasDaxyLADSZQjOS60+CTroSpGCp74McQnD59Cf4osiNRqoCtEEtYIctIjD508UHjwQQPhESxasypRiJNnkA0AVvbMCSCgT541CjLRGjVii006YxhU2dOmQRI+N6mUKDWpgyA6aOsMysM20FU6gjRcQmEE7M08ZrBM6aLGLh0+QmgQAGOTTxggOHToCMEByBubebIgMECGzpkgNarA0cMHj5caNtzsycPlQAEwg24MKOP3ph4lDprw0SIixRFAYoi8RYtWDggphIbMkPTh7B7eyNGWFLTBkigTX3YnR5vnCglSsyhVsFNnOu88bB5AYBLY6pAHot75pInQ6NVAVIomULkjaDQfQXeYJIDE6uErTStY8MMSUizhAwYudBLLQwOZwkkiL6gAwyKepPJQQAA7', 
    $this->emoticons[10] => 'R0lGODlhEwATAPfAAFpQEGRZEnFkFHZtNXdtNHZuOndvPXhuM3twLXhxPXlyRHlyRnp0SHt1Unt2VX54VH55VX14WH97YIN6PYZ7OoV7PYN7R4R7Q4F6ToJ7TYN8U4F9ZIR/ZJSDGpiGG5iHG5eHJpOEKJiIJpyMJZ2NKaaUHquYH62ZH6+bH6OSIqSTI6STJ6eVIqGQKKqXIqqYJayZIq2aJbOfILOfJLaiILSgJLeiJLijILijIbmkIb2nIb6oIr+pIoSAZYaCaoWCbIeDbIqHdo2Keo6Le5COf8GqIcCqIsCrIsSuI8ewI8WwJMawJMmzI8iwJMmzJMqzJMu0JNG5JdO6JtW8JdW9Jta9Jda8Jta9Jte/Jtm/J9nAJtrBJ9vCJtzCJ93DJt3EJ97FJuHHJuHHJ+DFKODHKOPJJeXKJubLJ+XMJ+bMJuXKKOjNJ+rPJ+vPKOvPKuvQKOzRKe7SKO7TKe/TKO/TKe7SKvDUKPDUKvLVKfLWK/TXKvTXK/XYK/bZK/faK/bZLPfaLPjbK/nbK/jaLPjbLPnbLPrbLPncK/ncLPrcLPrdLPvcLPvdLPvdLfveLPveLfzdLPzeLPzeLfzfLf3eLf3fLP3fLf/gLZKPgpSRhZiXjpyakZybk52blJ6clKGgl6SjnqWknaWkoK2tqq6tqLKxrrOzr7Ozsba2tbe2tNbW2NnZ29na3ODg4uLj5ePj5efn6efn6unp7Ozs7u7u8PDw8fHx8vLy8/X19vf3+Pf4+Pn5+v39/v7+/v7+//7//////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAMEALAAAAAATABMAAAj+AIEJBPbLlSgJBg4UiNBpFa+BEHGRsvCiSps7cLjMqOCJFkRgtYaMYFOJUaJChhhRsuOix6uBuYjAOHSykM2bhiIV4eARWCkRggzdHHozEoxNvmRhMHNSUc1ENW0awkNB1akWkQologGFEaMsJwgNpWRDU5Ankgr1AVDCkiUPAYguAtPgQZiTfDpsYSRJBgpHROkQYIBG0kk/iGwOujlocSE9CCCIadKFEVGbidzgAGRIzgAhSj7gaHS5ECMyAsZI8uIAFYlATLBYHpqIzxEqeyzV4DRLQ5k+O5z8kcQI0qM6SLSYnDOBFbBUIQLt0WFiiRUpRnB8OemIxSeBuzJHpQjESE2SGzyu5FmUiFEOH7YG3sIEIk2lS5MkSYJUKY4KILB8pIspGawQxRlvrDFFDBeEEt9HAsUyyg8LJKDABqC00gtEAQEAOw==', 
    $this->emoticons[11] => 'R0lGODlhEwAUAPfpAEA4DU5GD2VfPnFlFHJkFHZvPHhvMH90KXpxMH92NmdhRmhjSG5oS3BqRnFsVXRvVXVvVnRvXH52RHtzSn12THhzV3x2Wn54XXRwZXh0ZHp2Z3x3YHp2bXx4aoN1GIZ4IYN4K41/K4d9NoR6OYh8Noh/PoN7TJeGG4yAJY+EN46FOo2EP5KEJ5GGKZKEKZaJKpWILpSKLZmLJ5mLLJuNLJ+PK5CEN5mNMp2TM6aZH6CTJ6GSKqKTKaOTLKOXKaebLKibKquaKK+eKK2hIqygK7OgJrKiJ7mnJrqoJLqrJL2pIr6qIr6rJomAQ4mAR4uBRYqCT42FTYiDZoiEbI+KaoSCeYqGdYiFf4uIfo+Nf5WRe8CrJsGsJsKwJsSwJcaxJ8S1JMi2Jcq3Jcu6IdK7JtO9JdO8Jta/JtjBJtnDJdrCJNzGJtnEKN3FKN7FKODHJuPJJeLIKOPLKObNKenPKefQKevTJurQKezQKO/TKO7TKu3WKe7UKe/UKu7WKvDUKPDUK/LYKvPbKfPbK/HcKPXbKvbZK/baKffaLPXfKfbcKvjbKvnbLPrbLPnfK/rdK/reK/ndLfvdLPvfLPzeLfvgLPzhK/3hLP7hLf/jLYmHhY2Lio+OkZCOi5KQjpiWjpWTk5aVlJmYlJqYlpmYmpqYmZ+eoaCenaKhoaOioqmopKmoq6qoqaupq6uqqqqqra+urq6us7GwsrSztLW1trW1uba1ube2uLi3ubi3uri4ubm5u7y8wb+/w7+/xMC/wMC/w8HBw8PCw8LCxcLCxsXFycfGyMjHy8jIzMrKzczLzMzLz87OzszM0M/P0c7P09HR0dDQ09LS09PT19bW29jY29vb29rZ3Nvb3t/f4uLi4uHh5OTk5ufo6ujo6u7u7+7u8fDw8fHw8vLy8/X19vj4+fv7+/r7/P39/v///6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAOoALAAAAAATABQAAAj+ANMJFNjtFYcFAhRgMLUN3cCH5mw5CMHFjZw2XlgwWEXuYbpypRCUgYRojiRJlCSlOaBJ3EB0qUDsSXkmwBJJjBhRCoRiUzmBzgrUoXNHkp4BHiLlZCTJj4Fd6c5hQZLpBIE+kvrEWZqTUpgM4bBJ4ENJCQAyKXFyZTQoQbFbMCYxMmQGkFqmJxsxuqSj1KkgKR8dWpSW0h81avKgPHJllBBMeHo8sbEFzpsiTrRMifLlEpMqrHZYqhHqGbFPVKSoGjYuGrUKbIx0QkbCjolYv4TpSlZLGi1os5Rx+hFDFrgLY6DwYgZtWC5QwIJpE2aN1IsJ2dK1auHDUzNfWVRjAFlhxVivXhQ+iHIYrsMQIk1SdCl0SZCYGyVw5HjAbeA1CEkQ4ggleulUSSJgNHCMR9Vo4AIahUwioSJryBDBMh4J9I0rG4wwAw80iGABKt5k+JA40+wCCy7RgGMiRA69OFBAADs=', 
    $this->emoticons[12] => 'R0lGODlhEwASAPfDAFFII1NKI1hOI2JWJHFlJX5xJn9yJYFzJYN0Jol5JpGCJ5KDJpODJpaFJ5qJJ5+NJ52QPZ+RPaCOJ6KQJ6SSJ6mWJ6eXNaKVOq6cM6maPrOeKLGfNbOiL7ejKLikKLmlKLqlKb6oKbGgMbSjNbelM72qMJWLS5SJTpWLTpOJU5+SQ5yQSpyRT5+STZ2RUJuRXZWNYpWNa5WPbpaOb5mQZZmSZ5aQcZmScpmTepmUeqOVQcGrKcGtKcKtKcCsMMSwKsawKcWwLcaxLcizLMm0Ksm0K8i0LM+3Ks64Kc64Ks+6KdG5KNC6KdG7KNO7K9a9KtjAKtnBKtrDKNrCKtzCKN/FKuHHK+HJKOTJKeTKK+TLK+bLKebMKefNK+jNK+nPKOnOK+vPK+3RKe7TKe/TK+3UKe/UK/DVKfHUKfDVK/PXK/PWLPbZKvfZKvbaKvTYLPXYLPXZLPbZLPfZLPbaLPfaLPfbLPjbKvjbK/nbLPrbLPjcK/ncK/rdKvveK/ncLPrcLPrdLPvcLPvdLPzeK/zfK/zdLPzeLPzfLP3eLP3fLP3gLP7gLP/hLJ+ag5+biKCcjKCcjaWhj6WhkqShlKekmammm6yon6ypn62qorCtpra0sLu5tb27ur69ur++vMC+vcHAwMLBwcTDwsjHx83MzdDQ0tPS1NPT1dbW2NfW2Nra3d7d4d7e4N/f4eLi5ebl6Obm6ujo7Orq7O7u8e/v8vHx9PLy9vTz9vT09vT09/n5+vr6+/r6/Pv7/Pv8/f39/v7+/v7+/////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAMQALAAAAAATABIAAAj+AIcJFEgrFKQZMXBkSuVroMNhu0DB2OAEy5YqQVQ8cvVwWC5KFr4QMgQI0KBEd5ikKOWQVyUOfATBgTMoz5w1gw5xOaFqIKkLe/IA6kLByqA3FZIAOiTFRq5hum5cqZmHiAEBO6IsCPCgDiEMo4ah0tEnTx46Awh8gJIAgAcgcgZRcfRrUwlFZv80ANGIkQYHiw6ZRfOilqUigs3KqWN2jhyzZtu0iHVpSGLImDGzcSGrEwlEmE0CgqzHrBgat1itcANZUJgnZA4NOuIFkKElk4L1etRE8KAsBQ4gCKFFQY9DdyKcErjKRJlBaRZ0sDNlAgMeahD9iNRwmDBPEMY9mJEAxmSgOIMKIZExyyGwTyiUnMGT6FAiP19E5IDVcVgrSSyMIIQRPmRQAye49CcQMK+Iogkmm5hiS0cBAQA7', 
    $this->emoticons[13] => 'R0lGODlhEwATAPfLAAAAAAIBAAUEAQYFAQcGAQ4MAxYDAx4FBRwYBR4bBSMeBywnCFIHDEQ8DV4tEElADV9OEWdbE3JlFHJmFocWFbETG7EcHLp/IZKCG5WEG5aJP5iLPKKPHa2ZIKOSMqCSNqGSOqOTOaiZOaqaOLmHIrCcIbOeIL2nI72qLr2qL76pLr6qLLKgNLajMbqnMY+ERo6EVYyDWY+GWpCFS5OIQ5WJQJWKS5uORJyOQJWKUZaNV5WMWo+HYI6HaJWOcJSOc5WPdZaQd5mTfpmTf8agJMajJMKsKsKtK8exJcOwKsewKcixK8iyK8m1K8y3Ks+3K9G4JdW8JtK6KtG8K9W+KNi/KtnAJ9zDKN3EKN3FKN7FKN/FKN7EKt/EKt/HKOHHKOLJKOPJKOPJKePKKenPKurPKuvQKuzRKu7SKu/UKfDVKfHVKfDUK/HUK/HWKvHWK/LWKvLWK/LXK/PXKvTXKfTXK/TYKvTYK/XYK/bZK/fbKvfaLPfbLPjbKvjaLPjbLPnbLPrbLPrdK/vdK/veK/rcLPrdLPvcLPvdLPvdLfvfLfzeK/zdLPzeLPzeLf3eLP3eLf3fLP3fLfrgLZyXh5+ajaGciqCckKKfkaKflaOglqOgl6Wilqekm6uona2qoqyqpKyqpa2rp6+tqLCuqrCuq7KxrrOwrrSysby6usbFxsfGyMfHyMfHycrJy8nJzMvLzM3Mzs7Oz8/O0M/P0tLS1NbV2Nzc3+Hh5OHh5ePj5ubm6Ofn6+jo6+np7PPz9fT09vb3+fj4+vn5+/v7+/z8/fz9/v39/v3+//7+/v7+/////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAMwALAAAAAATABMAAAj+AJcJFPirVSYgPYBsegVsoMNlxWD5CLGkixguTED8mHXs4TBQG7YM+tMGEZ48gq7UKEVs4DFRHuwgKnQCiSQtJe4gcvPBVDKBsjTMKQQIkJk6jfaU8QOo0BoatZYFC2IFUVFAieRgiWO1aCMqQoTRwtHnaiEyERRMYEO0qJ4btkKpaHTVEQYBAAqYgHS1kYtTlKR0LYSmAYDDEgz1dYJpSJWujaAgOAzgQZu2iKZYqvSELiBJHQhQXiDmkNcmnFC18CwpQwDKCaJ0XcQi1S0bdIpK4jDAwAEGDryYDgQnRy5jl5TQLTQGAoUKFi7wKfrIiCZky3DBCGMV0RkiJIpJvCHK6EsMXQNZvQDTKFAhRZMUuW+UZUYsh8pcyTiihlCkRpEQksYKPMzykEC7eKLDCCgkkYIIO3zSy4Eu8aLKKJ2QsoovPzkUEAA7', 
    $this->emoticons[14] => 'R0lGODlhEwATAPexAHttF3tuF5aHG5+QHpaJPZ+PNZuMOJ6QPqCQHaOTHqaTH6WUH6uZIKGRNaWUMqaWMqWWOKiXMKiYN6+eMbGeIbCeLLGfMbahIrekIrilIbmkIbikK7qmL7yoL7WiMY+ERIuBSomAUomAU4qDW5GGQ5KGQZmLQ5mMQpGHVZCHWJKJW4yFYoyGaY2HaJKKZZSNbZCKcpKMc5KMdpWPeJSOepaQe5eRfpmUf8CsI8OuJMOuLMmyJM+3Jcu1K8+5JtG5K9K5KNK6KtO8Kda9KNa/KNi/J9i/KtrBKdvBKt7FKd7GKODGKOHIKeLIKeLKKeLLKePKKeTJKeTKKOTLKefLKebNKevPKunQKurQKerRKuvQKu3RKezRK+3TKu7RKu7SKe/UKfHVKfDUKvHVK/LXK/PXKvTXK/XYK/faKvXYLPfcLPjbKvjaLPnbLPrbLPncKvncK/ndK/rdK/veK/jcLPncLPrcLPrdLPvcLPvdLPvdLfveLPveLfzdLPzeLPzeLfzfLPzfLf3fLJiTgpiUhZmUh5uWhpuXiJ2ZjZ2Zjp+bkJ+bkqajnKyqp62rp6+tqrCurLGvrbWzsbW0srW0s7i2tLi3tcC/v8PCxMTDxMvKys/P0tDQ0tHR1dPS1NTU1tTU2OLi5eXl6Ojo6+7u8O/v8vHx8/X19vb2+Pb3+Pf3+fn5+vn6+/z8/f39/v7+/v7//////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAALIALAAAAAATABMAAAj+AGMJFJhqEyMaLGIowlRqoMNYrjzNOKADCZMkPSS4uLTqYStJJIi88ZPHDh4/c6QUWGRqIKxJBsDkaUOzZhs8ayogUiXw04cveGrWoWnHTRs3cBxQirXKBhA/RKMoSLMnA4+ZdrCkEAXKBJqaeYoEIBNIgIY/NAFZiPSIA9SaZ6i0saNFjB2aeYzIOPSjj825R0vWtHNFxA0jM2m6QctmDJc2eoK66QLCUBC/bdh42UEhQYIBDDD4uJM1RKMOUPMsAYAAxxMoWZzkWEAnzxEYnU58tWPlgho+eOwIz2OGjSAPj1TVGALVTeK/wregCBWLU4kwd//alBMBksBXjxo6lHlu006cDYRODWTliICSOX6E28kDCMuDQqQ8anoBoUeTKlMIMYEKlaDy0ECmZJJICyOsMIglozwUEAA7', 
    $this->emoticons[15] => 'R0lGODlhFQATAPfJAIJ6OYd+OYh/PoN7QYJ6R4N9ToF6U4V/UoJ9Wo2BMIyDMJKHLZ2OJpmMKZOGM5WIMZ6SKKmaH62aH6STJ6WTJ6OTK6CTLaGVK6SWLaaaKa6fJKyaKLCdILGeILalJrekJ7emJLikJ7inJr2pI7+rI76qJr+uJIeBToqCRImCSYWAVY+KXoaAYoqGaYuHa4+KZo+NepCLaJGOdpKPfJSQfMOuI8CvJMOtKcSwJMeyJsmyJMm0Jcu2Jsy1Kcy6Jc+6Js+7KdC5JtG7JNC7JtC9JdO8J9W/J9a/JdG5KdC6KtK6KdS7KdW9KdbAJ9rCJ9zGJd/GJ9rBKdrDKN/IJt/LJuDKJuDMJ+TLJ+LJKeXKKOXLKeTMKubRJ+fQKOfRKOrQKurRKuvUKOzQK+3TK+7SKu7SK+3UK+7VKe/VK+/ULPLWKfPXK/HVLPPXLPHYKfPZKfTaKvTZLPXZLPXaLPbZLPbaLPfcLPjbLPnbLPrbLPjcK/ndK/jeK/nfK/ncLPrcLPrcLfrdLfvcLPvdLPvdLfveLPvfLPzdLPzdLfzeLPzeLfzfLPzfLf3eLP3fLfzgLPzgLZSSgZWSgpeVhZmWgpiVhpiWiZuZh52bjZ2bkZ+dkaCelaOimqSjmqSjm66tqLCvqrOyrrSzr7Ozsbi3tbu6try7uby7u76+vb+/vMTEw8XExcfGxsfHx8rKy8zLy8/Pz9ra3Nvb3N/f4eHh4+Tk5uXl5+jo6uzt7u7v8PP09vX19fT09vX29/b29/b39/f4+vn5+/r6+vr6+/v7/Pz8/f39/v7+/v7+/////6usrQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAUAAMoALAAAAAAVABMAAAj+AJMJFEgs1idJMmhwcqVroEOHsyYJ2JBjCI8PDlq0IvZwoCoCJNQsUoQIUSM9RxJs8tWRFYApjvIM+oMHz6BBid4wyDTMYa0CTxT9KXNDDM0lSPz8gbPAlENNIBzhydNmApOZSSjYsVlFBS6BtlKcyTM1TYg2NnsAKVTT0IVUAlFZMFQzDxsld/D8ibJFEKE/inxcQpbMkwepNfH4SezHT50aZAZRiRFMGCYTimomGiSI7J9BdKRE0HKIywperzqJkLpGhxMsaOSAMYKjiZ0/f6y8ALbrFIZHf75IKBJkBIcOP8YEqqmICCXCtFC4wXMHEKFCcczM+ZsYUoZSAo1cVbLBKHFNmuYFeTlxa6CsAV0GmZ+fpw8EUA9JKQiTeb5NPhrM0MtDxYgSgBB7ODLTIIosckUDkeTSUTLHwOLCAyU4kQUUO1RwQCi/TDgQL6tYwoIBCMAwSnsdBQQAOw==');

    if ( (array_key_exists($whichOne, $smiliesBundle)) !== true) {
      return; // hacking attempt
    } else {
      header("Content-type: image/gif");
      echo base64_decode($smiliesBundle[$whichOne]);
      flush();
      exit();
    }
    
  }

  public function p($string, $toBr = 1, $justParse = 1, $useSmilies = 0, $simpleSmilies = 0, $protectMails = 0, $showFormattedTwice = 0, $onShutdown = "") {
  // ARGUMENTS :
    # $string = the text you want to format

    # $toBr = convert newlines (\n) to <br /> (OPTIONAL) disabled by default

    # $justParse = disable it when you indent to print text directly on document. (OPTIONAL) enabled by default
        # in the current mode, it will save your parsed text in some variable (or other storage mode, up to you)

    # $useSmilies = use smilies or not? Smilies are bundled with Parser (OPTIONAL) enabled by default
        # for the list of smilies and have to use them see above

    # $simpleSmilies = use same smilies but with more friendly codes (OPTIONAL) enabled by default
        # be sure to have previous argument enabled for use of this one

    # $protectMails = protect mails via protect() function (OPTIONAL) enabled by default
        # this function will significantly increase size of your text, but it will block mail-bots

    # $showFormattedTwice = shows formatted text once more, but in special look (OPTIONAL)
        # then you can also use a constant RP_BUFFER through documents to access that text

    # $onShutdown = put here some PHP code (with or without newlines) and it will be executed when script finishes with its work (OPTIONAL)

    // The Core
    $s = (string) $string;

    if (empty($s)) {
      return;
    }
    if (PHP_VERSION <= 4) {
      self::__construct();
    }

    // remove the garbage
    // 30th Jan 2010 added Unicode support
    $s = htmlentities($s, ENT_QUOTES, 'UTF-8');
    //$s = htmlentities($s, ENT_QUOTES, 'ISO-8859-15');

    // S m i l i e s
    // firstly transform "simple Smilies" to their "full names"
    if ($simpleSmilies) {
      foreach ($this->altEmoticons as $altEmNum => $altEmEnt) {
        $altEmEntParse = preg_quote($altEmEnt);
        $s = preg_replace("#([\n\r\t\040]+)$altEmEntParse([\n\r\t\040]+)#", "$1".':'.$this->emoticons[$altEmNum].':'."$2", $s);
      }
    }

    if ($useSmilies) {
      $smiliesRepository = $this->emoticons;

      // prepare for matching
      foreach ($smiliesRepository as $srKey => $srEntries) {
        $smiliesRepository[$srKey] = ':'.$srEntries.':';
      }
      // pattern for replacing
      $smReplace = '<img src="'.constant('RP_ORIGINAL_PATH').'?smiley=';
      $smReplace2 = '" border="0" />';
      // search and replace
      foreach ($smiliesRepository as $smilieKey => $smilieName) {
        $smilieNameParse = preg_quote($smilieName, '#');
        $s = preg_replace("#([\n\r\t\040]+)$smilieNameParse([\n\r\t\040]+)#", "$1".$smReplace.$this->emoticons[$smilieKey].$smReplace2."$2", $s);
      }
    }

    // B a s i c P a r s e	(M a i n)
    for ($b = 1; $b < count($this->bbc); $b++) {
      $bbcn = '#' . preg_quote($this->bbc[$b], '#') . "(.*)" . preg_quote($this->bbc[$b+1], '#') . '#Uis'; // needle
      $bbcr = $this->rep[$b] . "$1" . $this->rep[++$b]; // replacement
      $s = preg_replace($bbcn, $bbcr, $s);
    }

    foreach ($this->xht as $xhtBbc => $xhtHtml) {
      $s = $this->stir($xhtBbc, $xhtHtml, $s);
    }

    // fix invalid link format
//  $s = preg_replace("#\[url\](www\..+)\[\/url\]#i", "[url=http://$1]$1[/url]", $s);
//  $s = preg_replace("#\[url\=(www\..+)\](.*)\[\/url\]#i", "[url=http://$1]$2[/url]", $s);

    // it can't be [php].+ it must be [php]\n.+
    $s = preg_replace("#\[php\]([^\r\n])#i", "[php]\r\n$1", $s);
    // same but for [/php]
    $s = preg_replace("#([^\r\n])\[\/php\]#i", "$1\r\n[/php]", $s);

    // remove prepended <?php || <? || php closing tag
    $s = preg_replace("#\[php\](\r\n|(\r\n)+|)((\&lt\;\?php)|(\&lt\;\?))#i", "[php]", $s);
    $s = preg_replace("#(\?\&gt\;)(\r\n|(\r\n)+|)\[\/php\]#i", "[/php]", $s);

    // prepend <?php and php closing tag
    $s = preg_replace("#\[php\]#i", "[php]\n<?php", $s);
    $s = preg_replace("#\[\/php\]#i", "?>\n[/php]", $s);

    // P a r s e
//  $s = preg_replace("#\[url\=(.*)\](.*)\[\/url\]#Ui", "<a href=\"$1\" target=\"_blank\">$2</a>", $s);
//  $s = preg_replace("#\[url\](.*)\[\/url\]#Ui", "<a href=\"$1\" target=\"_blank\">$1</a>", $s);
//  $s = preg_replace("#\[img\](.*)\[\/img\]#Ui", "<img src=\"$1\" border=\"0\" />", $s);
//  $s = preg_replace("#\[email\=(.*)\](.*)\[\/email\]#Ui", "<a href=\"mailto: $1\">$2</a>", $s);
//  $s = preg_replace("#\[email\](.*)\[\/email\]#Ui", "<a href=\"mailto: $1\">$1</a>", $s);
//  $s = preg_replace("#\[font\=(.*)\](.*)\[\/font\]#Ui", "<span style=\"font-family: $1;\">$2</span>", $s);
    $s = preg_replace("#\[color\=(\#[0-9A-F]{0,6}|[A-z]+)\](.*)\[\/color\]#Ui", "<span style=\"color: $1;\">$2</span>", $s);

    // [php]...[/php] parse
    $s = preg_replace("#\[php\][\n\r|\n](.*)[\n\r|\n]\[\/php\]#Uise", "'<div style=\"width: 80%; overflow: auto; text-align: left; border: 1px solid #CCCCCC; display: block; padding-left: 20px;\">'.highlight_string(html_entity_decode('\\1', ENT_QUOTES), 1).'</div>'", $s);
    // <span> for PHP5, <font> for PHP4
    $s = preg_replace("#\<\/(span|font)\>\[\/php\]#i", "</$1>\n</$1>\n</code><div style=\"display: block;\">", $s);

    // [youtube] code added 30th Jan 2010
//  $s = preg_replace("#\[youtube\](.*)\[\/youtube\]#Ui", "<object width=\"425\" height=\"350\"><embed src=\"http://www.youtube.com/v/$1\" type=\"application/x-shockwave-flash\" width=\"425\" height=\"350\"></embed></object>", $s);

    if (PHP_VERSION >= 5) {
      $highlight_string_type = 'span';
    } else {
      $highlight_string_type = 'font';
    }

    $s = preg_replace("#\[php\]\<#i", "</div><code><$highlight_string_type style=\"color: #000000;\">\n<", $s);

    // [list]...[/list] parse
    if (preg_match("#\[list\](.*?)\[\/list\]#is", $s)) {
      preg_match_all("#\[list\](.*?)\[\/list\]#is", $s, $list);
      $list = $list[1];
      $backupList = $list;

      // now seperate lines
      foreach ($list as $listNum => $lt) {
        $lt = explode("\n", $lt);
        unset($lt[0], $lt[count($lt)]); // get rid of first/last arrays

        foreach ($lt as $ltaNum => $lta) {
          $lta = str_replace("\n", '', $lta);
          $lt[$ltaNum] = '<li>'.$lta.'</li>';
        }

        $lt = implode("", $lt);
        $list[$listNum] = '<ul style="list-style-type: square; padding-left: 20px;">' . "\n" . $lt . "\n" . '</ul>';

        // replace...
        foreach ($backupList as $backupListNum => $bla) {
          $s = str_replace($bla, $list[$backupListNum], $s);
        }
      }
      $s = $this->stir('[list]<ul', '<ul', $s);
      $s = $this->stir('</ul>[/list]', '</ul>', $s);
    }

    // [list=xxx]...[/list] parse
    if (preg_match("#\[list\=([A-z]{1,})\](.*?)\[\/list\]#is", $s)) {
      preg_match_all("#\[list\=([A-z]{1,})\](.*)\[\/list\]#Uis", $s, $listd);
      $listd = $listd[2];
      $backupListd = $listd;

      foreach ($listd as $listdNum => $ltd) {
        $ltd = explode("\n", $ltd);
        unset($ltd[0], $ltd[count($ltd)]);

        foreach ($ltd as $ltdNum => $ltda) {
          $ltda = str_replace("\n", '', $ltda);
          $ltd[$ltdNum] = '<li>'.$ltda.'</li>';
        }

        $ltd = implode("", $ltd);

        foreach ($backupListd as $backupListdNum => $blda) {
          $s = str_replace($blda, $listd[$backupListdNum], $s);
        }
      }
      $s = preg_replace("#\[list\=([A-z]{1,})\]<ol#i", '<ol', $s);
      $s = $this->stir('</ol>[/list]', '</ol>', $s);
    }

    // clean empty LISTs
    $s = preg_replace("#\<li\>([\r\n])\<\/li\>#", '', $s);

    // fix line formats (neccessary for deparse process)
    $s = $this->stir('</li><li>', "</li>\n<li>", $s);

    // E x t r a s
    if ($protectMails != 0) {
      $mails = preg_match_all("#\<a href\=\"mailto\: (.*)\"\>#Ui", $s, $mailsFound);
      $correctMails = $mailsFound[1];

      foreach ($correctMails as $mailNum => $mailContent) {
        $protected = $this->protect($correctMails[$mailNum]);
        $currMail = $correctMails[$mailNum];
        $currMail = str_replace('#', '\#', $currMail);

        $s = preg_replace("#\<a href\=\"mailto\: $currMail\"\>#i", "<a href=\"mailto: $protected\">", $s);
      }

      $simplemPattern = "#\<a href\=\"mailto\: (.*)\"\>(.*)\@(.*)\<\/a\>#Ui"; // * simplem = simple Mail
      $simplemProtect = preg_match_all($simplemPattern, $s, $simplemFound);
      $simplemImportant = $simplemFound[1];
      $smCount = count($simplemImportant);

      for ($csm = 0; $csm < $smCount; $csm++) {
        $this_simplem = $simplemImportant[$csm];

        $smExp = explode('&', $this_simplem);

        // clean up that array
        foreach ($smExp as $smArNum => $smEntries) {
          $remove = array('#', ';');
          $smExp[$smArNum] = str_replace($remove, NULL, $smExp[$smArNum]);

          if (empty($smExp[$smArNum])) {
            unset($smExp[$smArNum]);
          }
        }

        foreach ($smExp as $numsNum => $asciiStuff) {
          $smExp[$numsNum] = sprintf('%c', $asciiStuff);
        }

        foreach ($smExp as $nonalphanumericNum => $nonAlphanumeric) {
          // quotemeta() nor preg_quote() is not sufficient here
          if ($smExp[$nonalphanumericNum] == '#') {
            $smExp[$nonalphanumericNum] = '\#';
          }
          elseif (!preg_match("#[A-z0-9]#", $smExp[$nonalphanumericNum])) {
            $smExp[$nonalphanumericNum] = '\\'.$smExp[$nonalphanumericNum];
          }
        }

        $smExp = implode("", $smExp);

        $this_simplem = str_replace('#', '\#', $this_simplem);
        $this_simplem = str_replace('&', '\&', $this_simplem);

        $this_replace = str_replace(array('\#', '\&'), array('#', '&'), $this_simplem);

        $s = preg_replace("#\<a href\=\"mailto\: $this_simplem\"\>$smExp\<\/a\>#i", "<a href=\"mailto: $this_replace\">$this_replace</a>", $s);
      }
    }

    if ($toBr != 0) {
      // this following line cleans up rubbish made by previous
      // search & replace actions
      $s = str_replace('<br />', NULL, $s);
      $s = nl2br($s);

      // now remove <br /> within [code]...[/code] and [list]...[/list] and [list=*]...[/list]
      // and after that around them too
      // this will REALLY enhance parsed text, especially when viewed through Opera
      $lineBreaks = array(
        0 => array('<code style="white-space: pre;">', '</code>'),
        1 => array('<ul style="list-style-type: square; padding-left: 20px;">', '</ul>'),
        2 => array('<ol style="list-style-type: decimal;">', '</ol>')
      );

      foreach ($lineBreaks as $lbArray) {
        $lb1 = $lbArray[0];
        $lb2 = $lbArray[1];
        $lb1Quoted = preg_quote($lb1, '#');
        $lb2Quoted = preg_quote($lb2, '#');
        $lbNeedle = "#" . $lb1Quoted . "(.+?)" . $lb2Quoted . "#sie";

        $s = preg_replace($lbNeedle, "'" . $lb1 . "'.str_replace('<br />', '', str_replace('\\\"', '\"', '$1')).'".$lb2."'", $s);

        $s = preg_replace("#\<br \/\>(\r\n)" . $lb1Quoted . "#i", "\n" . $lb1, $s);
        $s = preg_replace("#" . $lb2Quoted . "\<br \/\>#i", $lb2, $s);
        $s = preg_replace("#" . $lb2Quoted . "(\r\n)\<br \/\>#i", $lb2, $s);
      }

      // some other tags, works in most cases but it's faster
      $s = str_replace('</blockquote><br />', '</blockquote>' . "\n", $s);
      $s = str_replace('<br />' . "\r\n" . '<blockquote>', "\n" . '<blockquote>', $s);
    } else {
      // or simply clean!
      $s = str_replace('<br />', NULL, $s);
    }

    if ($showFormattedTwice != 0) {
      define('RP_INTERNAL_BUFFER_FOR_TWICE', $s);
      function showFormattedTwice($just_return = 0) {

        $layout = array(
        1 => '<div style="border: 3px dashed #003399; display: block; background-color: #F7F7F7; padding-top: 15px; padding-bottom: 15px; padding-left: 25px; padding-right: 25px;">',
        2 => '</div>'
        );

        $show = "\n\n" . $layout[1] . "\n\n" . RP_INTERNAL_BUFFER_FOR_TWICE . "\n\n" . $layout[2] . "\n\n";

        if ($just_return == 0) {
          echo $show;
        } else {
          return define('RP_BUFFER', $show);
        }

      }
      showFormattedTwice(1);
      register_shutdown_function('showFormattedTwice');
    }

    // Output
    if ($justParse == 0) {
      echo $s;
    } else {
      return $s;
    }

    @eval($onShutdown);
  } // p()...

  public function deparse($string, $removeBr = 1, $return = 1) {
    // DEPARSE CORE
    // This one returns PARSED text to UNPARSED, ORIGINAL text
    // However, it probably won't be ORIGINAL, but it will have the SAME MEANING
    // Example:
    // Before parsing: [email]my@email.com[/email]
    // After parsing/deparsing: [email=my@email.com]my@email.com[/email]
    $s = (string) $string;

    // D e p a r s e
    // #1 Basic BBcode
    $bbc = $this->bbc;
    $rep = $this->rep;

    for ($i = 1; $i <= count($bbc); $i++) {
      $s = preg_replace("#" . preg_quote($rep[$i], '#') . "(.*)" . preg_quote($rep[$i+1], '#') . "#Uis", 
      $bbc[$i] . "$1" . $bbc[$i+1], $s);
      $i += 1;
    }

    foreach ($this->xht as $xhtNum => $xhtCode) {
      $s = $this->stir($xhtCode, $xhtNum, $s);
    }

    // #2 Smilies
//  $s = preg_replace("#\<img src\=\"(.*)\?smiley\=(.*)\" border\=\".\" \/\>#Ui", ":$2:", $s);

    // #3 Advanced BBCode
    $adv = array(
//  "[email=$1]$2[/email]" => "\<a href\=\"mailto\: (.*)\"\>(.*)\<\/a\>",
//  "[url=$1]$2[/url]" => "\<a href\=\"(.*)\" target\=\"_blank\"\>(.*)\<\/a\>",
//  "[img]$1[/img]" => "\<img src\=\"(.*)\" border\=\"0\" \/\>",
    "[font=$1]$2[/font]" => "\<span style\=\"font\-family\: (.*)\;\"\>(.*)\<\/span\>",
    "[color=$1]$2[/color]" => "\<span style\=\"color\: (.*)\;\"\>(.*)\<\/span\>",
//  "[youtube]$1[/youtube]" => "\<object width\=\"425\" height\=\"350\"\>\<embed src\=\"http://www.youtube.com/v/(.*)\" type\=\"application/x-shockwave-flash\" width\=\"425\" height\=\"350\"\>\<\/embed\>\<\/object\>",
    );

    // go go go
    foreach ($adv as $adv1 => $adv2) {
      $adv2 = "#".$adv2."#Ui";
      $s = preg_replace($adv2, $adv1, $s);
    }

    // now check if eMails were crypted, if so we need to recover them
/*  if (preg_match("#\[email\=\&\#[0-9]+#i", $s)) {
      preg_match_all("#\[email\=(.*)\]#Ui", $s, $mails);
      $mails = $mails[1];
      $mails = array_unique($mails); // crypted
      $recoveredMails = array(); // un-crypted, recovered

      foreach ($mails as $cry) {
        $cry = preg_split("#[^0-9]#", $cry);
        $cry = $this->alterArray($cry, 'clean');
        $recovered = '';

        foreach ($cry as $nums) {
          $nums = chr($nums);
          $recovered .= $nums;
        }
        $recoveredMails[] = $recovered;
      }
      $mails = array_values($mails);

      for ($i = 0; $i < count($mails); $i++) {
        $mails[$i] = preg_quote($mails[$i], '#');
        $recMaQuote = preg_quote($recoveredMails[$i], '#');

        $s = preg_replace("#\[email\=$mails[$i]\]#i", "[email=$recoveredMails[$i]]", $s);
        $s = preg_replace("#\[email\=$recMaQuote\]$mails[$i]\[\/email\]#", "[email=$recoveredMails[$i]]$recoveredMails[$i][/email]", $s);
      }
    }*/

    $s = preg_replace(
    "#\<div style\=\"width\: 80\%\; overflow\: auto\; text\-align\: left\; border\: 1px solid \#CCCCCC\; display\: block\; padding\-left\: 20px\;\"\>(.*)\<\/div\>#Uis",
    "[php]$1[/php]", $s);

    if (preg_match("#\[php\](.*?)\[\/php\]#is", $s)) {
      // 1) Collect all [php]...[/php] entries
      // 2) Clean them, keep PHP only
      // 3) Return them clean

      preg_match_all("#\[php\](.*)\[\/php\]#Uis", $s, $php);
      $php = $php[1];
      $backupPhp = $php;

      foreach ($php as $phpArrayNum => $pc) {
        // *pc stands for PHP Code

        $pc = strip_tags($pc);
        $php[$phpArrayNum] = $pc;
      }

      for ($i = 0; $i < count($php); $i++) {
        $s = $this->stir($backupPhp[$i], $php[$i], $s);
      }

      // and finally remove php tags to prevent multiple start/ends tags
      $st = preg_quote('&lt;?php');
      $et = preg_quote('?&gt;');
      $s = preg_replace("#(\[php\])[\r\n]$st#i", "$1", $s);
      $s = preg_replace("#{$et}[\r\n]+(\[\/php\])#i", "$1", $s);
    }

    // [list]...[/list] && [list={1,}]...[/list]
    $l = array('\<ul style\="list-style-type\: square;"\>', '\</ul\>', '\<ol style\="list-style-type\: decimal;"\>', '\</ol\>');
    $lo = array('<ul style="list-style-type: square;">', '</ul>', '<ol style="list-style-type: decimal;">', '</ol>');
    $o = array('[list]', '[/list]', '[list=dec]');

    $s = preg_replace("#" . $l[0] . "(.+?)" . $l[1] . "#sie", "'" . $lo[0] . "'.str_replace(array('<li>', '</li>'), '', '$1').'".$lo[1]."'", $s);
    $s = preg_replace("#" . $l[0] . "(.+?)" . $l[1] . "#si", $o[0] . "$1" . $o[1], $s);

    $s = preg_replace("#" . $l[2] . "(.+?)" . $l[3] . "#sie", "'" . $lo[2] . "'.str_replace(array('<li>', '</li>'), '', '$1').'".$lo[3]."'", $s);
    $s = preg_replace("#" . $l[2] . "(.+?)" . $l[3] . "#si", $o[2] . "$1" . $o[1], $s);

    // get back the garbage 
    $s = html_entity_decode($s, ENT_QUOTES, 'UTF-8');

    if ($removeBr > 0) {
      $s = str_replace('<br />', '', $s);
    }
    if ($return > 0) {
      return $s;
    } else {
      echo $s;
    }
  }

  public function security($string) {
    // Recruiting Parser CHECKING TOOL
    // A part of RP Core, no need to include any of RP modules.

    // Each function has its own variables, so this (and every other) variable won't interfere
    // with RP's main function - p() .
    $s = (string) $string;
    set_time_limit(300);

    if ( (!strpos($s, '[')) || (!strpos($s, ']')) ) {
      // passes
      return true;
    }

    // Phase #1 - load all present [*] in array
    preg_match_all("#\[(/{0,1})[A-z]+\]#U", $s, $bbc);
    unset($bbc[1]);
    $bbc = $bbc[0];

    // Phase #2 - lowercase all found tags (for caseless searching)
    $bbc = $this->alterArray($bbc, 'lower');

    // Phase #3 - filter these which isn't a BBCode
    foreach ($bbc as $num => $ent) {
      if (!in_array($ent, $this->bbc)) {
        unset($bbc[$num]);
      }
    }

    // Phase #4 - determine if tag is opneing or closing
    $o = 0; // opening tag
    $c = 0; // closing tag

    foreach ($bbc as $tag) {
      if (strpos($tag, '/') === false) {
        // opening tag
        $o++;
      } else {
        // closing tag
        $c++;
      }
    }

    // Phase #5 - final phase, determine if there's same amount
    // of [/*] and [[^/]*] (and return so)
    if ($o == $c) {
      // passes
      return true;
    } else {
      // fails
      return false;
    }
  }
} // parser...

  /*
  
  >>>>>>>>>>>>>>>>>>>>>><<<<<<<<<<<<<<<<<<<<<<
  >>> END OF RECRUITING PARSER APPLICATION <<<
  >>> Thanks for using. For any questions  <<<
  >>> merely visit:                        <<<
  >>>      http://bbcparser.recgr.com      <<<
  >>>>>>>>>>>>>>>>>>>>>><<<<<<<<<<<<<<<<<<<<<<
  
  */

?>