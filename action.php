<?php
/**
 * Action adding DW Edit button to page tools (useful with fckedit)
 *
 * @author     Myron Turner <turnermm02@shaw.ca>
 * @author     Davor Turkalj <turki.bsc@gmail.com>
 */

if (!defined('DOKU_INC')) 
{    
    die();
}

class action_plugin_dwedit extends DokuWiki_Action_Plugin
{
    var $ckgedit_loaded = false;
    var $helper;
    function __construct() {
    /* is either ckgdoku or ckgedit enabled and if so get a reference to the helper */
       $list = plugin_list('helper');
       if(in_array('ckgedit',$list)) {
           $this->ckgedit_loaded=true;
           $this->helper = plugin_load('helper', 'ckgedit');
       }
       else if(in_array('ckgdoku',$list)) {
           $this->ckgedit_loaded=true;
           $this->helper = plugin_load('helper', 'ckgdoku');
       }
    }    

    function register(Doku_Event_Handler $controller)
    {
    $controller->register_hook('MENU_ITEMS_ASSEMBLY', 'AFTER', $this, 'addsvgbutton', array());
    /*  discontinued/deprecdated hooks */
        $controller->register_hook('TEMPLATE_PAGETOOLS_DISPLAY', 'BEFORE', $this, 'dwedit_action_link',array('page_tools'));
        $controller->register_hook('TEMPLATE_DWEDITLINK_DISPLAY', 'BEFORE', $this, 'dwedit_action_link', array('user'));
    
}
 

    public function addsvgbutton(Doku_Event $event) { 
        /* if this is not a page OR ckgedit/ckgedoku is not  active -> return */          
       if($event->data['view'] != 'page' || !$this->ckgedit_loaded) return;   
       if(method_exists($this->helper,'dw_edit_displayed') && $this->helper->dw_edit_displayed()) return;         
       $btn = $this->helper->getLang('btn_dw_edit');  // get the button's name from the currently enabled ckg_  plugin
       if(!$btn) $btn = 'DW Edit';           
       array_splice($event->data['items'], -1, 0, [new \dokuwiki\plugin\dwedit\MenuItem($btn)]);
    }
    
    function dwedit_action_link(&$event, $param)
    {
        global $ACT, $ID, $REV, $INFO, $INPUT, $USERINFO,$conf;

        /* do I need to insert button ?  */
        if (!$this->ckgedit_loaded || $event->data['view'] != 'main' || $ACT != 'show')
        {
            return;
        }
        
        if(!isset($USERINFO) && strpos($conf['disableactions'], 'source') !== false) return;
        $mode = $INPUT->str('mode', 'fckg');
        if($mode == 'dwiki') return;

        /* check excluded namespaces */
        $dwedit_ns = $this->helper->getConf('dwedit_ns');
        if($dwedit_ns) {
            $ns_choices = explode(',',$dwedit_ns);
            foreach($ns_choices as $ns) {
              $ns = trim($ns);
              if(preg_match("/$ns/",$_REQUEST['id'])) return;
            }
        }
        
        /* insert button at second position  */
        $params = array('do' => 'edit');
        if($REV) {
            $params['rev'] = $REV;
        }
        $params['mode'] = 'dwiki';
        $params['fck_preview_mode'] = 'nil';

        $name = $this->helper->getLang('btn_dw_edit');  

        if ($INFO['perm'] > AUTH_READ) {
            $title = $name;
            $name =$name;
            $edclass = 'dwedit';
        }
        else {
            $title = $name;
            $name = 'DokuWiki View';
            $edclass = 'dwview';
        }
        $link = '<a href="' . wl($ID, $params) . '" class="action ' . $edclass . '" rel="nofollow" title="' . $title . '"><span>' . $name . '</span></a>';

        if($param[0] == 'page_tools') {
            $link = '<li class = "dwedit">' . $link .'</li>';
        }
        else { 
            $link = '<span class = "dwedit">' . $link  .'</span>';          
        }

        $event->data['items'] = array_slice($event->data['items'], 0, 1, true) +
            array('dwedit' => $link) + array_slice($event->data['items'], 1, NULL, true);


    }
}
?>
