<?php

class SmartyWrap {
  private static $theSmarty = null;
  private static $cssFiles = array();
  private static $jsFiles = array();

  static function init() {
    self::$theSmarty = new Smarty();
    self::$theSmarty->template_dir = util_getRootPath() . 'templates';
    self::$theSmarty->compile_dir = util_getRootPath() . 'templates_c';
    self::assign('wwwRoot', util_getWwwRoot());
    self::assign('imgRoot', util_getImgRoot());
    self::assign('staticServer', Config::get('static.url'));
    self::assign('sources', Model::factory('Source')->order_by_desc('isOfficial')->order_by_asc('displayOrder')->find_many());
    self::assign('sUser', session_getUser());
    self::assign('is_mirror', Config::get('global.mirror'));
    self::assign('nick', session_getUserNick());
    self::assign('contact_email', Config::get('global.contact'));
    self::assign('hostedBy', Config::get('global.hostedBy'));
    self::assign('currentYear', date("Y"));
    self::assign('bannerType', Config::get('global.bannerType'));
    self::assign('developmentMode', Config::get('global.developmentMode'));
    self::assign('isMobile', util_isMobile());
    self::assign('suggestNoBanner', util_suggestNoBanner());
    self::assign('GLOBALS', $GLOBALS);
    self::$theSmarty->registerPlugin('function', 'getDebugInfo', array('SmartyWrap', 'getDebugInfo'));
  }

  static function fetchSkin() {
    $skin = session_getSkin();
    self::addCss($skin);
    self::addJs('jquery', 'dex');

    // Set some skin variables based on the skin preferences in the config file.
    // Also assign some skin-specific variables so we don't compute them unless we need them
    $skinVariables = Config::getSection("skin-{$skin}");
    switch ($skin) {
    case 'zepu':
      $skinVariables['afterSearchBoxBanner'] = true;
      $skinVariables['minimalist'] = false;
      break;
    case 'polar':
      $wordCount = Definition::getWordCount();
      $wordCountRough = $wordCount - ($wordCount % 10000);
      $skinVariables['afterSearchBoxBanner'] = true;
      $skinVariables['minimalist'] = false;
      self::assign('words_total', util_formatNumber($wordCount, 0));
      self::assign('words_rough', util_formatNumber($wordCountRough, 0));
      self::assign('words_last_month', util_formatNumber(Definition::getWordCountLastMonth(), 0));
      break;
    case 'mobile':
      self::assign('words_total', util_formatNumber(Definition::getWordCount(), 0));
      self::assign('words_last_month', util_formatNumber(Definition::getWordCountLastMonth(), 0));
      $skinVariables['minimalist'] = true;
      $skinVariables['afterSearchBoxBanner'] = false;
      self::addJs('mobile');
      break;
    }
    self::assign('skinVariables', $skinVariables);

    self::registerOutputFilters();
    return self::fetch("pageLayout.ihtml");
  }

  /* Common case: render the $templateName inside pageLayout.ihtml and with the user-preferred skin */
  static function display($templateName) {
    print self::fetchCommonPageWithSkin($templateName);
  }

  static function fetchCommonPageWithSkin($templateName) {
    self::assign('contentTemplateName', "$templateName");  
    return self::fetchSkin();
  }

  static function displayPageWithSkin($templateName) {
    $skin = session_getSkin();
    self::assign('contentTemplateName', "$skin/$templateName");
    print self::fetchSkin();
  }

  static function displayWithoutSkin($templateName) {
    self::registerOutputFilters();
    print self::fetch($templateName);
  }

  static function displayAdminPage($templateName) {
    self::assign('templateName', $templateName);
  	self::addCss('flex');
    self::addJs('dex', 'flex', 'jquery');
    print self::fetch('admin/pageLayout.ihtml');
  }

  static function fetch($templateName) {
    ksort(self::$cssFiles);
    ksort(self::$jsFiles);
    self::assign('cssFiles', self::$cssFiles);
    self::assign('jsFiles', self::$jsFiles);
    return self::$theSmarty->fetch($templateName);
  }

  static function assign($variable, $value) {
    self::$theSmarty->assign($variable, $value);
  }

  static function registerOutputFilters() {
    if (session_user_prefers(Preferences::CEDILLA_BELOW)) {
      self::$theSmarty->registerFilter('output', array('StringUtil', 'replace_st'));
    }
    if (session_user_prefers(Preferences::OLD_ORTHOGRAPHY)) {
      self::$theSmarty->registerFilter('output', array('StringUtil', 'replace_ai'));
    }
  }

  static function getDebugInfo() {
    $data = DebugInfo::getDebugInfo();
    if (!$data['enabled']) {
      return '';
    }
    SmartyWrap::assign('debug_messages', $data['messages']);
    SmartyWrap::assign('debug_runningTimeMillis', $data['runningTimeMillis']);
    SmartyWrap::assign('debug_ormQueryLog', $data['ormQueryLog']);
    return SmartyWrap::fetch('bits/debugInfo.ihtml');
  }

  static function addCss(/* Variable-length argument list */) {
    // Note the priorities. This allows files to be added in any order, regardless of dependencies
    foreach (func_get_args() as $id) {
      switch($id) {
        case 'jqueryui':            self::$cssFiles[1] = 'lightness-1.10.3/jquery-ui-1.10.3.custom.min.css'; break;
        case 'jqueryui-smoothness': self::$cssFiles[2] = 'smoothness-1.10.4/jquery-ui-1.10.4.custom.min.css'; break;
        case 'jqgrid':              self::$cssFiles[3] = 'ui.jqgrid.css?v=3'; break;
        case 'tablesorter':         self::$cssFiles[4] = 'tablesorter/theme.blue.css'; break;
        case 'elfinder':            self::$cssFiles[5] = 'elfinder/css/elfinder.min.css?v=2'; break;
        case 'windowEngine':        self::$cssFiles[6] = 'jquery-wm/main.css'; break;
        case 'zepu':                self::$cssFiles[7] = 'zepu.css?v=63'; break;
        case 'polar':               self::$cssFiles[8] = 'polar.css?v=32'; break;
        case 'mobile':              self::$cssFiles[9] = 'mobile.css?v=15'; break;
        case 'flex':                self::$cssFiles[10] = 'flex.css?v=13'; break;
        case 'paradigm':            self::$cssFiles[11] = 'paradigm.css?v=2'; break;
        case 'hangman':             self::$cssFiles[12] = 'hangman.css?v=4'; break;
        case 'mill':                self::$cssFiles[13] = 'mill.css?v=3'; break;
        case 'lexemEdit':           self::$cssFiles[14] = 'lexemEdit.css?v=8'; break;
        case 'jcrop':               self::$cssFiles[15] = 'jcrop/jquery.Jcrop.min.css?v=3'; break;
        case 'select2':             self::$cssFiles[16] = 'select2/select2.css?v=3'; break;
        case 'gallery':            
          self::$cssFiles[17] = 'colorbox/colorbox.css?v=1'; 
          self::$cssFiles[18] = 'visualDict.css';
          break;
        case 'textComplete':        self::$cssFiles[19] = 'jquery.textcomplete.css'; break;
        default:
          FlashMessage::add("Cannot load CSS file {$id}");
          util_redirect(util_getWwwRoot());
      }
    }
  }

  static function addJs(/* Variable-length argument list */) {
    // Note the priorities. This allows files to be added in any order, regardless of dependencies
    foreach (func_get_args() as $id) {
      switch($id) {
        case 'jquery':           self::$jsFiles[1] = 'jquery-1.10.2.min.js'; break; 
        case 'jqueryui':         self::$jsFiles[2] = 'jquery-ui-1.10.3.custom.min.js'; break;
        case 'jqgrid':
          self::$jsFiles[3] = 'grid.locale-en.js?v=2';
          self::$jsFiles[4] = 'jquery.jqGrid.min.js?v=3';
          break;
        case 'jqnotice':         self::$jsFiles[5] = 'jquery.notice.js'; break;
        case 'jqTableDnd':       self::$jsFiles[6] = 'jquery.tablednd.0.7.min.js?v=1'; break;
        case 'tablesorter':
          self::$jsFiles[7] = 'jquery.tablesorter.min.js?v=4';
          self::$jsFiles[8] = 'tablesorter.dev.js?v=3';
          break;
        case 'pager':            self::$jsFiles[9] = 'jquery.tablesorter.pager.js'; break;
        case 'elfinder':         self::$jsFiles[10] = 'elfinder.min.js?v=1'; break; 
        case 'windowEngine':     self::$jsFiles[11] = 'jquery-wm.js'; break;
        case 'cookie':           self::$jsFiles[12] = 'jquery.cookie.js?v=1'; break;
        case 'dex':              self::$jsFiles[13] = 'dex.js?v=29'; break;
        case 'flex':             self::$jsFiles[14] = 'flex.js?v=2'; break;
        case 'mobile':           self::$jsFiles[15] = 'mobile.js?v=2'; break;
        case 'hangman':          self::$jsFiles[16] = 'hangman.js?v=5'; break;
        case 'mill':             self::$jsFiles[17] = 'mill.js?v=3'; break;
        case 'wotd':             self::$jsFiles[18] = 'wotd.js?v=1'; break;
        case 'lexemEdit':        self::$jsFiles[19] = 'lexemEdit.js?v=14'; break;
        case 'jcrop':            self::$jsFiles[20] = 'jquery.Jcrop.min.js?v=2'; break;
        case 'select2':          self::$jsFiles[21] = 'select2.min.js?v=3'; break;
        case 'select2Dev':       self::$jsFiles[22] = 'select2Dev.js?v=6'; break;
        case 'visualTag':        self::$jsFiles[23] = 'visualTag.js'; break;
        case 'gallery':          
          self::$jsFiles[24] = 'colorbox/jquery.colorbox-min.js';
          self::$jsFiles[25] = 'colorbox/jquery.colorbox-ro.js';
          self::$jsFiles[26] = 'dexGallery.js?v=2';
          self::$jsFiles[27] = 'jcanvas.min.js';
          break;
        case 'modelDropdown':    self::$jsFiles[28] = 'modelDropdown.js'; break;
        case 'textComplete':     self::$jsFiles[29] = 'jquery.textcomplete.min.js'; break;
        default:
          FlashMessage::add("Cannot load JS script {$id}");
          util_redirect(util_getWwwRoot());
      }
    }
  }
}

?>
