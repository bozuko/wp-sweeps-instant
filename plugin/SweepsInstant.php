<?php

class SweepsInstant extends Snap_Wordpress_Plugin
{
    protected $tab_name = 'instant';
    
    protected $last_entry = null;
    
    protected $display_form = true;
    
    protected $_api;
    
    protected function campaign()
    {
        return Snap::inst('Sweeps_Campaign');
    }
    /**
     * @wp.filter
     */
    public function mce_css($mce_css)
    {
        if ( ! empty( $mce_css ) )
            $mce_css .= ',';
    
        $mce_css .= SWEEPS_IW_URL.'/css/style.css';
    
        return $mce_css;
    }
    
    /**
     * @wp.filter
     */
    public function sweeps_campaign_tabs( $tabs )
    {
        $tabs[$this->tab_name] = 'Instant Win';
        return $tabs;
    }
    
    public function bozuko()
    {
        if( !isset($this->_api) ){
            $this->_api = Snap::inst('Bozuko_Api')
                ->setServer(
                    $this->campaign()
                        ->get_form()
                        ->field('bozuko_server')
                        ->getValue()
                )
                ->setApiKey(
                    $this->campaign()
                        ->get_form()
                        ->field('bozuko_api_key')
                        ->getValue()
                );
        }
        return $this->_api;
    }
    
    /**
     * @wp.action
     */
    public function sweeps_template_init()
    {
        Snap_Wordpress_Template::registerPath('sweeps', SWEEPS_IW_DIR.'/template' );
    }
    
    public function enabled()
    {
        return $this->campaign()->get_form()->field('iw_enabled')->getValue() == 'yes';
    }
    
    /**
     * @wp.action           sweep_enter
     * @wp.priority         10
     */
    public function after_enter()
    {
        if( !$this->enabled() ) return;
        // check for ajax header
        $ajax = @$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
        
        // not sure how we got here...
        if( !$this->campaign()->get_success() ) return;
        
        // add this dude to bozuko
        // $result = $this->bozuko()->call('/user', 'PUT', $user);
        if( ($user = $this->campaign()->getFacebookUser()) ){
            $user['service'] = 'facebook';
            $user['token'] = $this->campaign()->facebook()->getAccessToken();
            $result = $this->bozuko()->call('/user', 'PUT', array('data'=>$user) );
            // lets hope all went well!
            update_post_meta( $this->campaign()->get_entry_id(), 'bozuko_token', $result->token );
        }
        
        $result = $this->do_instant_win( $this->campaign()->get_entry_id() );
        $result['success'] = true;
        return $this->returnJSON( $result );
        // if not... we will need to display this elsewhere
        
    }
    
    /**
     * @wp.action
     */
    public function before_display_sweep()
    {
        if( !$this->enabled() ) return;
        wp_enqueue_script('sweep-instant-win', SWEEPS_IW_URL.'/js/iw.js', 'sweep-campaign');
        wp_enqueue_style('sweep-instant-win', SWEEPS_IW_URL.'/css/style.css');
    }
    
    public function get_last_entry()
    {
        static $entry;
        if( !isset( $entry ) ){ 
            $user = $this->campaign()->getFacebookUser();
            $user_id = $user['id'];
            if( $user_id ){
                // check to see if we have this dude in our db.
                $args = array(
                    'post_type'     => 'sweep_entry',
                    'meta_query'    => array(
                        array(
                            'key'       => 'campaign',
                            'value'     => get_the_ID()
                        ),
                        array(
                            'key'       => 'facebook_id',
                            'value'     => $user_id
                        ),
                        array(
                            'key'       => 'verified',
                            'value'     => '1'
                        )
                    )
                );
                $q = new WP_Query($args);
                wp_reset_query();
                if( $q->have_posts() ){
                    $entry = $q->posts[0];
                    
                }
                else {
                    $entry = false;
                }
            }
        }
        return $entry;
    }
    
    /**
     * @wp.action
     */
    public function sweep_before_form()
    {
        echo $this->instant_win_page();
    }
    
    /**
     * @wp.action               wp
     */
    public function after_like()
    {
        if( !@$_POST['after_like'] ) return;
        $html = $this->instant_win_page();
        $this->returnJSON(array(
            'newbie'        => !$html,
            'html'          => $html
        ));
    }
    
    public function instant_win_page()
    {
        if( !$this->enabled() ) return false;
        
        $last_entry = $this->get_last_entry();
        if( !$last_entry ) return false;
        
        $this->display_form = false;
        
        $GLOBALS['facebook_user'] = $this->campaign()->getFacebookUser();
        $GLOBALS['last_entry'] = $last_entry;
        $GLOBALS['sweeps_campaign'] = $this->campaign();
        $GLOBALS['config'] = $this->campaign()->get_form();
        
        ob_start();
        
        if( $this->can_enter() ){
            // we are good...
            Snap_Wordpress_Template::load('sweeps', 'includes/instant-enter');
        }
        else{
            Snap_Wordpress_Template::load('sweeps', 'includes/instant-wait');
        }
        
        
        $content = ob_get_clean();
        
        return apply_filters('sweep_instant_win_form', $content);
    }
    
    public function can_enter()
    {
        
        // force daily right now
        $last_entry = $this->get_last_entry();
        if( !$last_entry ) return true;
        
        $frequency = $this->campaign()->getValue('enter_frequency');
        
        if( $frequency == 'always') return true;
        
        $last_entry_parts = explode(' ',$last_entry->post_date);
        $date = new DateTime( $last_entry_parts[0] );
        $time = explode(':', $last_entry_parts[1] );
        $date->setTime($time[0], $time[1], $time[2]);
        $date->setTimezone( new DateTimeZone( $this->campaign()->getValue('timezone') ) );
        $now = new DateTime( null, new DateTimeZone( $this->campaign()->getValue('timezone') ) );
        
        // simple things
        
        $format ='';
        switch( $frequency ){
            case 'monthly':
                $format = 'Y-m';
                break;
            case 'daily':
                $format = 'Y-m-d';
                break;
            case 'hourly':
                $format = 'Y-m-d H';
                break;
            case 'minutely':
            default;
                $format = 'Y-m-d H:i';
                break;
        }
        $test = $date->format($format) != $now->format($format);
        return $test;
    }
    
    /**
     * @wp.action                       wp
     */
    public function reenter()
    {
        
        if( is_admin() || get_post_type() !== 'sweep_campaign' || !$this->enabled() ) return;
        if( !@$_REQUEST['sweep_reenter'] ) return;
        
        if( !$this->can_enter() ) return $this->returnJSON(array(
            'error'             => 'Error entering you into the contest'
        ));
        
        $last = $this->get_last_entry();
        
        // copy fields
        $copy = array('post_title', 'post_content', 'post_status', 'post_type');
        $post = array();
        foreach( $copy as $key ) $post[$key] = $last->$key;
        
        $id = wp_insert_post( $post );
        
        foreach( get_post_custom( $last->ID ) as $key => $value ){
            update_post_meta( $id, $key, $value[0] );
        }
        
        // now we need to do instant win stuff...
        $result = $this->do_instant_win( $id );
        return $this->returnJSON($result);
        
    }
    
    protected function do_instant_win( $entry_id )
    {
        $token = get_post_meta( $entry_id, 'bozuko_token', true );
        $this->bozuko()->setToken($token);
        
        $game_id = $this->campaign()->getValue('bozuko_game');
        $win=false;
        $prize;
        try{
            
            // do this to update the access token...
            $user = $this->campaign()->getFacebookUser();
            $user['service'] = 'facebook';
            $user['token'] = $this->campaign()->facebook()->getAccessToken();
            $user = $this->bozuko()->call('/user', 'PUT', array('data'=>$user) );
            
            
            $game = $this->bozuko()->call('/game/'.$game_id);
            if( !$game->game_state ) throw new Exception('No Game State');
            $state = $game->game_state;
            if( $state->button_enabled ){
                if( $state->button_action != 'play' ){
                    $enter = $this->bozuko()->call($state->links->game_entry, 'POST', array('ll'=>'0,0'));
                    $state = $enter[0];
                }
            }
            
            do{
                $result = $this->bozuko()->call( $state->links->game_result, 'POST' );
            }while( $result->free_play );
            
            $win = $result->win;
            if( $win ){
                // redeem it too
                $this->bozuko()->call( $result->prize->links->redeem, 'POST' );
                $GLOBALS['prize'] = array(
                    'name'     => $result->prize->name
                );
                update_post_meta( $entry_id, 'instant_prize', $result->prize->name);
                update_post_meta( $entry_id, 'instant_prize_id', $result->prize->id);
                update_post_meta( $entry_id, 'instant_prize_code', $result->prize->code);
            }
            
        }
        catch( Exception $e ){
            Sweeps::log( (string)$e );
            $win = false;
        }
        
        
        $GLOBALS['facebook_user'] = $this->campaign()->getFacebookUser();
        $GLOBALS['sweeps_campaign'] = $this->campaign();
        $GLOBALS['config'] = $this->campaign()->get_form();
        
        ob_start();
        Snap_Wordpress_Template::load('sweeps', 'includes/instant-'.($win?'win':'lose') );
        $html = ob_get_clean();
        
        return array(
            'win'       => $win,
            'html'      => preg_replace_callback('/\{\$(.+)?\}/', array( &$this, '_instant_win_replacer'), $html )
        );
    }
    
    
    /**
     * @wp.filter
     */
    public function sweep_instant_win_form( $content )
    {
        return preg_replace_callback('/\{\$(.+)?\}/', array( &$this, '_instant_win_replacer'), $content );
    }
    
    public function _instant_win_replacer($matches)
    {
        global $facebook_user, $last_entry, $prize;
        
        
        $replacements = array(
            'name'              => $facebook_user['name']
           ,'first_name'        => $facebook_user['first_name']
           ,'last_name'         => $facebook_user['last_name']
        );
        if( $last_entry ){
           $replacements['email'] = get_post_meta( $last_entry->ID, 'email', 1);
        }
        if( $prize ){
            $replacements['prize'] = $prize['name'];
        }
        
        return @$replacements[strtolower(@$matches[1])];
    }
    
    /**
     * @wp.filter
     */
    public function sweep_display_form( $display )
    {
        if( !$this->enabled() ) return $display;
        return $this->display_form;
    }
    
    
    /**
     * @wp.action
     */
    public function snap_form_init( &$form )
    {
        if( !($form instanceof Sweeps_Campaign_Form) ) return; 
        
        // lets add our fields!
        $fields = array(
            'iw_enabled'        => array(
                'label'             => 'Instant Win Enabled',
                'type'              => 'select',
                'options'           => array(
                    'no'                => 'No',
                    'yes'               => 'Yes'
                ),
                'group'             => 'iw_basic'
            ),
            'bozuko_server'     => array(
                'label'             => 'Bozuko Server',
                'type'              => 'text',
                'group'             => 'iw_basic'
            ),
            'bozuko_api_key'    => array(
                'label'             => 'Bozuko Api Key',
                'type'              => 'text',
                'group'             => 'iw_basic'
            ),
            'bozuko_game'       => array(
                'label'             => 'Bozuko Game ID',
                'type'              => 'text',
                'group'             => 'iw_basic'
            ),
            'enter_frequency'   => array(
                'label'             => 'Play Frequency',
                'type'              => 'select',
                'options'           => array(
                    'always'            => 'Always',
                    'bozuko'            => 'Bozuko Defined',
                    'minutely'          => 'Minutely',
                    'hourly'            => 'Hourly',
                    'daily'             => 'Daily',
                    'monthly'           => 'Monthly'
                ),
                'group'             => 'iw_basic'
            ),
            'enter_html'        => array(
                'label'             => 'Re-Enter Body',
                'hide_label'        => false,
                'type'              => 'wysiwyg',
                'group'             => 'iw_display'
            ),
            'wait_html'         => array(
                'label'             => 'Wait to Enter Body',
                'hide_label'        => false,
                'type'              => 'wysiwyg',
                'group'             => 'iw_display'
            ),
            'iw_win_html'       => array(
                'label'             => 'Instant Win Winning Body',
                'hide_label'        => false,
                'type'              => 'wysiwyg',
                'group'             => 'iw_display'
            ),
            'iw_lose_html'      => array(
                'label'             => 'Instant Win Losing Body',
                'hide_label'        => false,
                'type'              => 'wysiwyg',
                'group'             => 'iw_display'
            ),
            'facebook_share_win_title'      => array(
                'label'             => 'Facebook Share Win Title',
                'type'              => 'text',
                'group'             => 'iw_facebook'
            ),
            'facebook_share_win_caption'    => array(
                'label'             => 'Facebook Share Win Caption',
                'type'              => 'text',
                'group'             => 'iw_facebook'
            ),
            'facebook_share_win_body'       => array(
                'label'             => 'Facebook Share Win Body',
                'type'              => 'textarea',
                'group'             => 'iw_facebook'
            ),
            'facebook_share_win_image'      => array(
                'label'             => 'Facebook Share Win Image',
                'type'              => 'image',
                'use_id'            => true,
                'display_image'     => true,
                'group'             => 'iw_facebook'
            )
        );
        
        
        
        foreach( $fields as $name => $cfg ) $form->addField($name, $cfg);
    }
    
    /**
     * @wp.meta_box
     * @wp.post_type                    sweep_campaign
     * @wp.title                        Basic Information
     */
    public function meta_box_bozuko( $post )
    {
        $this->campaign()->get_form()->render(array(
            'group'             => 'iw_basic',
            'renderer'          => 'Snap_Wordpress_Form_Renderer_AdminTable'
        ));
    }
    
    /**
     * @wp.meta_box
     * @wp.post_type                    sweep_campaign
     * @wp.title                        Display
     */
    public function meta_box_images( $post )
    {
        $this->campaign()->get_form()->render(array(
            'group'             => 'iw_display',
            'renderer'          => 'Snap_Wordpress_Form_Renderer_AdminTable'
        ));
    }
    
    /**
     * @wp.meta_box
     * @wp.post_type                    sweep_campaign
     * @wp.title                        Display
     */
    public function meta_box_facebook( $post )
    {
        ?>
        <p>If these are left blank, the values from the "Facebook" tab will be used.</p>
        <?php
        $this->campaign()->get_form()->render(array(
            'group'             => 'iw_facebook',
            'renderer'          => 'Snap_Wordpress_Form_Renderer_AdminTable'
        ));
    }
    
    public function _wp_add_meta_box( $id, $title, $callback, $post_type, $context, $priority )
    {
        if( $this->campaign()->get_tab() != $this->tab_name) return;
        
        if( !$post_type ) $post_type = $this->name;
        add_meta_box( $id, $title, $callback, $post_type, $context, $priority );
    }
    
}