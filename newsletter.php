<?php
namespace Grav\Plugin;

use Composer\Autoload\ClassLoader;

use Grav\Common\Plugin;

use Grav\Common\Utils;

use Grav\Plugin\NewsletterPlugin\Subscribers;

/**
 * Class NewsletterPlugin
 * @package Grav\Plugin
 */
class NewsletterPlugin extends Plugin
{
    protected $route = 'newsletter';

    protected $args = [];

    protected $ajax_error = ['error' => 'true' ];

    protected $ajax_success = ['success' => 'true' ];

    protected $cache_id = '_plugin_newsletter_subs_count';

    protected $ajax_actions = [
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
            'onPluginsInitialized'  => ['onPluginsInitialized', 0],
            'onPagesInitialized'    => ['onPagesInitialized', 0],
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
            'onTwigTemplatePaths' => ['onTwigAdminTemplatePaths', 0],
            'onAdminMenu'         => ['onAdminMenu', 0]
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
        	$this->admin_name     = $this->config->get('plugins.email.to_name') ?: 'Admin';

            $this->email_from     = $this->config->get('plugins.newsletter.email_from') ?: $this->config->get('plugins.email.from');

            $this->log            = $this->config->get('plugins.newsletter.log') ?: '/logs/newsletter.log';

            $this->data_dir       = $this->config->get('plugins.newsletter.data_dir') ?: '/user/data';

            $this->s_path         = $this->config->get('plugins.newsletter.sub_page_route') ?: '/newsletter';

            $this->u_path         = $this->config->get('plugins.newsletter.unsub_page_route') ?: '/newsletter-unsub';

            $this->email_subject  = $_POST['email_subject'];

            $this->email_greeting = $_POST['email_greeting'];

            $this->email_body     = Utils::processMarkdown( $_POST['email_body'] );

            $this->queue_enabled  = $this->config->get('plugins.email.queue.enabled');

            $this->flush_prev     = $this->config->get('plugins.newsletter.flush_email_queue_preview');

            $this->flush_send     = $this->config->get('plugins.newsletter.flush_email_queue_send');

            $this->args = [
                'data_dir'      => $_SERVER['DOCUMENT_ROOT'] . $this->data_dir,
                'log'           => $_SERVER['DOCUMENT_ROOT'] . $this->log,
                'data_paths'    => [
                    's_path'    => $_SERVER['DOCUMENT_ROOT'] . $this->data_dir . $this->s_path,
                    'u_path'    => $_SERVER['DOCUMENT_ROOT'] . $this->data_dir . $this->u_path
                ]
            ];

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
        $this->grav['twig']->plugins_hooked_nav['PLUGIN_NEWSLETTER.NEWSLETTER'] = ['route' => $this->route, 'icon' => 'fa-envelope-open'];
    }

    /**
	* Format the email body to include the user's name. This should probably be done with some kind of Twig class
	* but I got impatient while browsing the API documentation and couldn't find a Twig solution faster than I could
	* write this one-liner.
	*/
    protected function getEmailBody( $name )
    {
    	$greeting = preg_replace( '/(\{\{\sname\s\}\})/', $name, $this->email_greeting );

    	return "<p>$greeting</p>$this->email_body";
    }

    protected function sendAdminEmail()
    {
    	$message = $this->grav['Email']

        	->message( $this->email_subject, $this->getEmailBody( $this->admin_name ), 'text/html' )
            
            ->setFrom( $this->email_from )
            
            ->setTo( $this->email_from );

        return $this->grav['Email']->send( $message );
    }

	/**
     * Take action based on provided ajax action.
     */
    public function processAjaxAction()
    {
        include __DIR__ . '/classes/Subscribers.php';

        $return = [];

        $cache = $this->grav['cache'];

        switch ( $_POST['ajax_action'] )
        {
            case $this->ajax_actions[0]:

			    if ( $data = $cache->fetch( $this->cache_id ) )
			    {
			        $return = $data;

			        $return['from_cache'] = true;
			    }
			    else
			    {
			    	$Subscribers = new Subscribers( $this->args );
			    	
			    	$data = ['count' => sizeof( $Subscribers->get() ) ];
			        
			        $cache->save( $this->cache_id, $data );
			        
			        $return = $data;
			        
			        $return['from_cache'] = false;
			    }

                break;

            case $this->ajax_actions[1]:

            	$Subscribers = new Subscribers( $this->args );
                
                $Subscribers->updateList();
                
                $cache->delete( $this->cache_id );
                
                $return = $this->ajax_success;

                break;

            case $this->ajax_actions[2]:

            	$Subscribers = new Subscribers( $this->args );
                
                $Subscribers->updateList();

                $cache->delete( $this->cache_id );

                $errors = 0;

                foreach ( $Subscribers->get() as $user )
                {
                    $message = $this->grav['Email']

                    	->message( $this->email_subject, $this->getEmailBody( $user['name'] ), 'text/html' )

                    	->setFrom( $this->email_from )

                    	->setTo( $user['email'] );

                    if( !$this->grav['Email']->send( $message ) )
                    {
                        $errors += 1;

                        file_put_contents(
                            $Subscribers->log,
                            sprintf(
                                "ERROR: [%s] Error sending email [%s]\n",
                                time(),
                                $user['email']
                            ),
                            FILE_APPEND
                        );
                    }
                }

                if ( !$this->sendAdminEmail() )

                	$errors += 1;

                if ( $this->queue_enabled && $this->flush_send )
                
                    $this->grav['Email']::flushQueue();

                $return = $errors ? $this->ajax_error : $this->ajax_success;

                break;

            case $this->ajax_actions[3]:

                $sent = $this->sendAdminEmail();

                if ( $this->queue_enabled && $this->flush_prev )

                    $this->grav['Email']::flushQueue();

                $return = !$sent ? $this->ajax_error : $this->ajax_success;

                break;

            default:

            	return false;

                break;
        }

        return $return;
    }
}
