<?php
/**
 *	Application class for a MVC web site.
 *
 *	Copyright (c) 2007-2012 Christian Würker (ceus-media.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		cmFrameworks
 *	@package		Hydrogen.Application.Web
 *	@author			Christian Würker <christian.wuerker@ceus-media.de>
 *	@copyright		2007-2012 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmframeworks/
 *	@since			0.1
 *	@version		$Id$
 */
/**
 *	Application class for a MVC web site.
 *	@category		cmFrameworks
 *	@package		Hydrogen.Application.Web
 *	@author			Christian Würker <christian.wuerker@ceus-media.de>
 *	@copyright		2007-2012 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmframeworks/
 *	@since			0.1
 *	@version		$Id$
 *	@todo			Code Documentation
 */
class CMF_Hydrogen_Application_Web_Site extends CMF_Hydrogen_Application_Web_Abstract
{
	public static $checkClassActionArguments			= TRUE;

	/**	@var		string								$content				Collected Content to respond */
	protected $content									= '';
	protected $_dev;

	/**
	 *	Executes called Controller and stores generated View.
	 *	@access		protected
	 *	@param		string		$defaultController	Controller if none is set and not 'index'
	 *	@param		string		$defaultAction		Action if none is set and not 'index'
	 *	@return		string		Content generated by view triggered by controller
	 *	@throws		Exception	if a exception is caught and neither error view not messenger is available
	 */
	protected function control( $defaultController = NULL, $defaultAction = NULL )
	{
		$request		= $this->env->getRequest();
		try
		{
			$dispatcher	= new CMF_Hydrogen_Dispatcher_General( $this->env );
			$dispatcher->checkClassActionArguments	= self::$checkClassActionArguments;
			if( $defaultController )
				$dispatcher->defaultController	= $defaultController;
			if( $defaultAction )
				$dispatcher->defaultAction		= $defaultAction;
			$output	= $dispatcher->dispatch();														//  get requested content
			$this->setViewComponents( array(														//  note for main template
				'controller'	=> $request->get( 'controller' ),									//  called controller
				'action'		=> $request->get( 'action' )										//  called action
			) );
			return $output;																			//  return generated output
		}
		catch( Exception $e )
		{
			if( class_exists( 'Controller_Error' ) )
			{
				$controller	= new Controller_Error( $this->env );
				$controller->handleException( $e );
			}
			if( class_exists( 'View_Error' ) )
			{
				$view	= new View_Error( $this->env );
				$result	= $view->handleException( $e );
				if( $result )
					return $result;
			}
			else if( !$this->env->getMessenger() )
				throw $e;
			else
				$this->env->getMessenger()->noteFailure( $e->getMessage() );
		}
	}

	/**
	 *	Main Method of Framework calling Controller (and View) and Master View.
	 *	@access		protected
	 *	@return		void
	 */
	protected function main()
	{
		ob_start();
		$content	= $this->control();											// dispatch and run request
		$dev		= ob_get_clean();
		
		if( $this->env->getRequest()->isAjax() )								// this is an AJAX request
			return $content;													// deliver content only

		$config		= $this->env->getConfig();									// shortcut to configation object

		$data		 = array(
			'page'			=> $this->env->getPage(),
			'config'		=> $config,											// configuration object
			'request'		=> $this->env->getRequest(),						// request object
			'content'		=> $content,										// rendered response page view content
			'clock'			=> $this->clock,									// system clock for performance messure
			'dev'			=> $dev,											// warnings, notices or development messages
		);

		if( $this->env->has( 'messenger' ) )
			$data['messenger']	= $this->env->getMessenger();					// UI messages for user
		if( $this->env->has( 'language' ) )
		{
			$language			= $this->env->getLanguage();					// shortcut to language object
			$data['language']	= $language->getLanguage();						// document language
			$data['words']		= $language->getWords( 'main', FALSE, FALSE );	// main UI word pairs
		}
		if( $this->env->has( 'database' ) )
		{
			$database	= $this->env->getDatabase();							// shortcut to database connection object
			$data['dbQueries']		= (int) $database->numberExecutes;			// number of SQL queries executed
			$data['dbStatements']	= (int) $database->numberStatements;		// number of SQL statements sent
		}
		$this->setViewComponents( $data );										// set up information sources for main template
		return $this->view();													// render and return main template to constructor
	}

	/**
	 *	Simple implementation of content response. Can be overridden for special moves.
	 *	@access		public
	 *	@param		string		$body		Response content body
	 *	@return		int			Number of sent bytes
	 */
	protected function respond( $body, $headers = array() )
	{
		$response	= $this->env->getResponse();

		$body		= ob_get_clean().$body;
		if( $body )
			$response->setBody( $body );

		foreach( $headers as $key => $value )
			if( $value instanceof Net_HTTP_Header_Field )
				$response->addHeader( $header );
			else
				$response->addHeaderPair( $key, $value );

		$type		= NULL;
		$encodings	= $this->env->getRequest()->headers->getField( 'Accept-Encoding' );
		$isAjax		= $this->env->request->isAjax();
		if( 0 && $encodings && !$isAjax )
		{
			$typesSupported	= array( 'gzip', 'deflate' );
			$typesRequested	= array_keys( $encodings[0]->getValue( TRUE ) );
			foreach( $typesRequested as $code ){
				if( in_array( $code, $typesSupported ) ){
					$type	= $code;
					break;
				}
			}
		}
		return Net_HTTP_Response_Sender::sendResponse( $response, $type, TRUE );
	}

	/**
	 *	Main method.
	 *	@access		public
	 *	@return		void
	 */
	public function run()
	{
		error_reporting( E_ALL );
		try
		{
			/*	@todo		Hack for moved clock, please remove later */
			$this->clock	= $this->env->getClock();

			$this->respond( $this->main() );
			$this->logOnComplete();
			$this->env->close();
			exit( 0 );
		}
		catch( Exception $e )
		{
			$page	= new UI_HTML_Exception_Page();
			die( $page->render( $e ) );
		#	new UI_HTML_Exception_TraceViewer( $e );
		}
	}
}
?>