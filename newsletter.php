<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;

use Grav\Common\Utils;

use Grav\Common\Cache;

use Grav\Plugin\NewsletterPlugin\Subscribers;

require_once __DIR__ . '/classes/Subscribers.php';

/**
 * Class NewsletterPlugin
 * @package Grav\Plugin
 */
class NewsletterPlugin extends Plugin
{
    protected $route 				= 'newsletter';

    protected $args 				= [];

    protected $required_class_error = ['class_subscribers_not_initialized' => 'true'];

    protected $ajax_error 			= ['error' => 'true' ];

    protected $ajax_success 		= ['success' => 'true' ];

    protected $cache_id 			= '_plugin_newsletter_subs_count';

    protected $ajax_actions 		= [
    	'_plugin_newsletter_get_subs_count',
    	'_plugin_newsletter_update_list',
    	'_plugin_newsletter_email_subscribers',
    	'_plugin_newsletter_email_admin'
   	];

	/**
	* @return array
	*/
    public static function getSubscribedEvents()
    {
        return [
            'onPagesInitialized'    => ['onPagesInitialized', 0],
            'onPluginsInitialized'  => ['onPluginsInitialized', 0]
        ];
    }

	/**
	* Initialize the plugin
	*/
    public function onPluginsInitialized()
    {
        // Don't proceed if we are on the front-end
        if ( !$this->isAdmin() )

            return;
        
        $this->enable([
            'onAdminMenu'         => ['onAdminMenu', 0],
            'onTwigTemplatePaths' => ['onTwigAdminTemplatePaths', 0]
        ]);
    }

    /**
	* For front-end ajax $_POST's.
	*
	* Checks that the ajax action is set and in the list of allowed actions and sets up the class object
	*/
    public function onPagesInitialized()
    {
        if( isset( $_POST['ajax_action'] ) && in_array( $_POST['ajax_action'] , $this->ajax_actions ) )
        {
        	$this->initArgs();

            die( json_encode( $this->processAjaxAction() ) );
        }
    }

    /**
     * Add plugin templates path
     */
    public function onTwigAdminTemplatePaths()
    {
        $this->grav['twig']->twig_paths[] = __DIR__ . '/admin/templates';
    }

    /**
     * Add navigation item to the admin plugin
     */
    public function onAdminMenu()
    {
        $this->initArgs();

        $data = $this->getSubscriberCount();

        $menu_item = [
            'route' => $this->route,
            'icon' 	=> 'fa-envelope-open',
            'badge' => [
                'count' => $data['count'] ?: 0
            ]
        ];
        
        $this->grav['twig']->plugins_hooked_nav['PLUGIN_NEWSLETTER.NEWSLETTER'] = $menu_item;
    }

    /**
     * Initialize class arguments
     */
    protected function initArgs()
    {
    	$this->admin_name      = $this->config->get('plugins.email.to_name') ?: 'Admin';

        $this->email_from      = $this->config->get('plugins.newsletter.email_from') ?: $this->config->get('plugins.email.from');

        $this->email_from_name = $this->config->get('plugins.newsletter.email_from_name') ?: $this->config->get('plugins.email.from_name');

        $this->email_subject   = $_POST['email_subject'];

        $this->email_body      = Utils::processMarkdown( $_POST['email_body'] );

        $this->queue_enabled   = $this->config->get('plugins.email.queue.enabled');

        $this->flush_prev      = $this->config->get('plugins.newsletter.flush_email_queue_preview');

        $this->flush_send      = $this->config->get('plugins.newsletter.flush_email_queue_send');

        $log_enabled    	   = $this->config->get('plugins.newsletter.log_enabled');

        $log            	   = $this->config->get('plugins.newsletter.log') ?: '/logs/newsletter.log';

        $s_path         	   = $this->config->get('plugins.newsletter.sub_page_route') ?: '/user/data/newsletter';

        $u_path         	   = $this->config->get('plugins.newsletter.unsub_page_route') ?: '/user/data/newsletter-unsub';

        $this->args = [
            'log_enabled'   => $log_enabled,
            'log'           => $_SERVER['DOCUMENT_ROOT'] . $log,
            'data_paths'    => [
                's_path'    => $_SERVER['DOCUMENT_ROOT'] . $s_path,
                'u_path'    => $_SERVER['DOCUMENT_ROOT'] . $u_path,
                'a_path'    => $_SERVER['DOCUMENT_ROOT'] . '/user/accounts'
            ]
        ];
    }

    /**
     * Get the current count of subscribers from cache, if exists,
     * or retrive fresh count.
     */
    protected function getSubscriberCount()
    {
    	if ( $data = $this->grav['cache']->fetch( $this->cache_id ) )
	    {
	        $return = $data;

	        $return['from_cache'] = true;
	    }
	    else
	    {
	    	$Subscribers = new Subscribers( $this->args );

	    	if( $Subscribers->paths_exist )
	    	{
		    	$data = ['count' => sizeof( $Subscribers->get() ) ];
		        
		        $this->grav['cache']->save( $this->cache_id, $data );
		        
		        $return = $data;
		        
		        $return['from_cache'] = false;
	    	}
	    	else
	    	{
	    		$return = $this->required_class_error;
	    	}
	    }

	    return $return;
    }

	/**
     * Clear the cache and update the list of subscribers
     */
    protected function updateList()
    {
    	$Subscribers = new Subscribers( $this->args );

	    if( $Subscribers->paths_exist )
	    {
            $Subscribers->updateList();
            
            $this->grav['cache']->delete( $this->cache_id );
            
            return $this->ajax_success;
        }
    	else
    	{
    		return $this->required_class_error;
    	}
    }

    /**
    * Format twig string. Add user's name to twig vars.
    */
    protected function processTwigVars( $content, $name )
    {
        $twig = new \Twig_Environment( new \Twig_Loader_String() );

        $vars = array_merge( $this->grav['twig']->twig_vars, array( 'subscriber' => $name ) );

        return $twig->render( $content, $vars );
    }

    /**
     * Send an email preview to admin
     */
    protected function sendAdminEmail()
    {
        $message = $this->grav['Email']

            ->message( $this->processTwigVars( $this->email_subject, $this->admin_name ), $this->processTwigVars( $this->email_body, $this->admin_name ), 'text/html' )
            
            ->setFrom( [ $this->email_from => $this->email_from_name ] )
            
            ->setTo( [ $this->email_from => $this->email_from_name ] );

        return $this->grav['Email']->send( $message );
    }

    /**
     * Email the list of subscribers. Admin is included by default.
     */
    protected function emailSubscribers()
    {
        $Subscribers = new Subscribers( $this->args );

	    if( $Subscribers->paths_exist )
	    {
            $Subscribers->updateList();

            $this->grav['cache']->delete( $this->cache_id );

            $errors = 0;

            foreach ( $Subscribers->get() as $user )
            {
                $message = $this->grav['Email']

                	->message( $this->processTwigVars( $this->email_subject, $user['name'] ), $this->processTwigVars( $this->email_body, $user['name'] ), 'text/html' )

                	->setFrom( [ $this->email_from => $this->email_from_name ] )

                	->setTo( [ $user['email'] => $user['name'] ] );

                if( !$this->grav['Email']->send( $message ) )
                {
                    $errors += 1;

                    $Subscribers->log( sprintf(
                        "ERROR: [%s] Error sending email [%s]\n",
                        time(),
                        $user['email']
                    ) );
                }
            }

            if ( !$this->sendAdminEmail() )

            	$errors += 1;

            if ( $this->queue_enabled && $this->flush_send )
            
                $this->grav['Email']::flushQueue();
        
        	return $errors ? $this->ajax_error : $this->ajax_success;
    	}
        else
    	{
    		return $this->required_class_error;
    	}
    }

	/**
     * Take action based on provided ajax action.
     */
    protected function processAjaxAction()
    {
        $return = [];

        switch ( $_POST['ajax_action'] )
        {
            case $this->ajax_actions[0]:

			    $return = $this->getSubscriberCount();

                break;

            case $this->ajax_actions[1]:

            	$return = $this->updateList();

                break;

            case $this->ajax_actions[2]:

  				$return = $this->emailSubscribers();

                break;

            case $this->ajax_actions[3]:

                $sent = $this->sendAdminEmail();

                if ( $this->queue_enabled && $this->flush_prev )

                    $this->grav['Email']::flushQueue();

                $return = !$sent ? $this->ajax_error : $this->ajax_success;

                break;

            default:

            	return $this->ajax_error;

                break;
        }

        return $return;
    }
}
