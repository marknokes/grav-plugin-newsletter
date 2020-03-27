<?php
namespace Grav\Plugin\NewsletterPlugin;

use RocketTheme\Toolbox\File\File;

use Symfony\Component\Yaml\Yaml;

class Subscribers
{
	public $log 			 = '';

	protected $data_dir 	 = '';

	protected $data_paths	 = [
		's_path' 	=> '',
		'u_path' 	=> ''
	];

	protected $unsubscribers = [];

	protected $subscribers   = [];

	protected $dont_email  	 = [];

	/**
     * Create a configured instance of the class. Note that this will return false if args are missing
     * or if any of the provided paths do not work. There could be better error handling, but this is
     * what you get right now :)
     */
	public function __construct( $args )
	{
		if( !$args )

			return false;

		foreach ($args as $key => $value)

			$this->$key = $value;

		if( false === $this->pathsExist() )

			return false;
	}

	/**
     * Public method to update the list of current subscribers minus unsubscribers.
     */
	public function updateList()
	{
		$this->setUnsubscribers();
		
		$this->setSubscribers();
		
		$this->processUnsubscribers();
	}

	/**
     * Public method to get a list of current subscribers. If you plan to email them, you should call
     * $this->updateList() first.
     */
	public function get()
	{
		$this->setSubscribers();

		return $this->subscribers;
	}

	/**
     * Check that provided necessary paths exist.
     */
	protected function pathsExist()
	{
		$return = true;

		$to_check = [
			$this->log,
			$this->data_dir
		];

		foreach ( array_merge( $to_check, $this->data_paths ) as $path )
		{
			if( !file_exists( $path ) )
			{
				$return = false;

				break;
			}
		}

		return $return;
	}

	/**
     * Parse file and return user array
     */
	protected function buildUserArray( $full_path )
	{	
		$file = File::instance( $full_path );

		$user = Yaml::parse( $file->content() );

		$user['file_path'] = $full_path;

		return $user;
	}

	/**
     * Scan the unsubscriber data directory and create a list of unsubsribers
     */
	protected function setUnsubscribers()
	{
		$unsubscribers = [];

		$dont_email = [];

		foreach ( scandir( $this->data_paths['u_path'] ) as $file )
		{
			if( '..' == $file || '.' == $file ) continue;

			$user = $this->buildUserArray( $this->data_paths['u_path'] . "/$file" );

			$dont_email[] = $user['email']; // For easier array searching later

			$unsubscribers[] = $user;
		}
		$this->dont_email = $dont_email;

		$this->unsubscribers = $unsubscribers;
	}

	/**
     * Scan the subscriber data directory and create a list of subsribers
     */
	protected function setSubscribers()
	{
		$subscribers = [];

		foreach ( scandir( $this->data_paths['s_path'] ) as $file )
		{
			if( '..' == $file || '.' == $file ) continue;

			$subscribers[] = $this->buildUserArray( $this->data_paths['s_path'] . "/$file"  );
		}

		$this->subscribers = $subscribers;
	}

	/**
     * Remove the unsubscribers from the subscribers list
     */
	protected function processUnsubscribers()
	{
		if ( $this->subscribers )
		{
			foreach ( $this->subscribers as $key => $arr )
			{
				if( in_array( $arr['email'], $this->dont_email ) )
				{
					if( unlink( $this->subscribers[ $key ]['file_path'] ) )

						$msg = "INFO: [%s] Removed [%s] [%s] from list [%s]\n";

					else

						$msg = "ERROR: [%s] Failed to remove [%s] [%s] from list [%s]\n";

					file_put_contents(
						$this->log,
						sprintf(
							$msg,
							time(),
							$this->subscribers[ $key ]['name'],
							$this->subscribers[ $key ]['email'],
							$this->subscribers[ $key ]['file_path']
						),
						FILE_APPEND
					);

					unset( $this->subscribers[ $key ] );
				}
			}
		}

		if( $this->unsubscribers )
		{
			foreach ( $this->unsubscribers as $key => $arr )
			{
				if ( unlink( $this->unsubscribers[ $key ]['file_path'] ) )

					$msg = "INFO: [%s] Removed [%s] from list [%s]\n";

				else
					
					$msg = "ERROR: [%s] Failed to remove [%s] from list [%s]\n";

				file_put_contents(
					$this->log,
					sprintf(
						$msg,
						time(),
						$this->unsubscribers[ $key ]['email'],
						$this->unsubscribers[ $key ]['file_path']
					),
					FILE_APPEND
				);
			}
		}
		else
		{
			file_put_contents(
				$this->log,
				sprintf(
					"INFO: [%s] There are no users to unsubscribe.\n",
					time()
				),
				FILE_APPEND
			);
		}
	}
}