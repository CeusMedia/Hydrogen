<?php
/**
 *	Abstract access control list resource.
 *
 *	Copyright (c) 2011-2016 Christian Würker (ceusmedia.de)
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
 *	@category		Library
 *	@package		CeusMedia.HydrogenFramework.Environment.Resource.Acl
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2011-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */
/**
 *	Abstract access control list resource.
 *
 *	@category		Library
 *	@package		CeusMedia.HydrogenFramework.Environment.Resource.Acl
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2011-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/HydrogenFramework
 */
abstract class CMF_Hydrogen_Environment_Resource_Acl_Abstract
{
	public $roleAccessNone	= 0;
	public $roleAccessFull	= 1;
	public $roleAccessAcl	= 2;

	protected $controllerActions	= array();
	protected $rights				= array();
	protected $roles				= array();
	/*	@var		$publicLinks				Map of links with public access */
	protected $linksPublic			= array();
	protected $linksPublicInside	= array();
	protected $linksPublicOutside	= array();

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		CMF_Hydrogen_Environment_Abstract	$env	Environment Object
	 *	@return		void
	 */
	public function __construct( CMF_Hydrogen_Environment_Abstract $env )
	{
		$this->env	= $env;
	}

	abstract protected function getRights( $roleId );

	/**
	 *	Returns Role.
	 *	@access		protected
	 *	@param		integer		$roleId			Role ID
	 *	@return		array
	 */
	protected function getRole( $roleId )
	{
		if( !$roleId )
			return array();
		if( !$this->roles )
		{
			$model	= new Model_Role( $this->env );
			foreach( $model->getAll() as $role )
				$this->roles[$role->roleId]	= $role;
		}
		return $this->roles[$roleId];
	}

	/**
	 *	Indicates whether access to a controller action is allowed for role of current user.
	 *	Needs session resource. Works only if user is logged and assigned role is existing.
	 *	@access		public
	 *	@param		integer		$roleId			Role ID
	 *	@param		string		$controller		Name of controller
	 *	@param		string		$action			Name of action
	 *	@return		integer		Right state: -1: no access at all | 0: no access | 1: access | 2: access at all
	 */
	public function has( $controller = 'index', $action = 'index' ){
		if( !$this->env->has( 'session' ) )
			return 0;
		$roleId	= $this->env->getSession()->get( 'roleId' );
		$right	= $this->hasRight( $roleId, $controller, $action );
#		remark( 'Controller: '.$controller.' | Action: '.$action.' | Right: '.$right );
		return $right > 0;
	}

	/**
	 *	Return list controller actions or matrix of controllers and actions of role.
	 *	@abstract
	 *	@public
	 *	@param		string		$controller		Controller to list actions for, otherwise return matrix
	 *	@param		integer		$roleId			Specified role, otherwise current role
	 *	@return		array						List of actions or matrix of controllers and actions
	 */
	abstract public function index( $controller = NULL, $roleId = NULL );

	/**
	 *	Indicates wheter a role is system operator and has access to all controller actions.
	 *	@access		public
	 *	@param		integer		$roleId			Role ID
	 *	@return		boolean
	 */
	public function hasFullAccess( $roleId )
	{
		if( !$roleId )
			return FALSE;
		$role	= $this->getRole( $roleId );
		if( !$role )
			throw new InvalidArgumentException( 'Role with ID '.$roleId.' is not existing' );
		return isset( $role->access ) && $role->access == $this->roleAccessFull;
	}

	/**
	 *	Indicates whether a role has no access as all.
	 *	@access		public
	 *	@param		integer		$roleId			Role ID
	 *	@return		boolean
	 */
	public function hasNoAccess( $roleId )
	{
		if( !$roleId )
			return FALSE;
		$role	= $this->getRole( $roleId );
		if( !$role )
			throw new InvalidArgumentException( 'Role with ID '.$roleId.' is not existing' );
		return !isset( $role->access ) || $role->access == $this->roleAccessNone;
	}

	/**
	 *	Indicates whether access to a controller action is allowed for a given role.
	 *	@access		public
	 *	@param		integer		$roleId			Role ID
	 *	@param		string		$controller		Name of controller
	 *	@param		string		$action			Name of action
	 *	@return		integer		Right state
	 *
	 *	Return statuses:
	 *	-2: outside but logged in
	 *	-1: no access at all
	 *	 0: no access
	 *	 1: access by right
	 *	 2: access at all
	 *	 3: public access
	 *	 4: public access if outside
	 *	 5: public access if inside
	 */
	public function hasRight( $roleId, $controller = 'index', $action = 'index' )
	{
		$controller	= strtolower( str_replace( '/', '_', $controller ) );
		$linkPath	= $controller && $action ? $controller.'_'.$action : '';

		if( in_array( $linkPath, $this->linksPublic ) )
			return 3;
		if( $roleId ){
			if( in_array( $linkPath, $this->linksPublicInside ) )
				return 5;
			if( in_array( $linkPath, $this->linksPublicOutside ) )
				return -2;
			if( $this->hasFullAccess( $roleId ) )
				return 2;
			if( $this->hasNoAccess( $roleId ) )
				return -1;
			$rights	= $this->getRights( $roleId );
			if( isset( $rights[$controller] ) && in_array( $action, $rights[$controller] ) )
				return 1;
		}
		else{
			if( in_array( $linkPath, $this->linksPublicOutside ) )
				return 4;
		}
		return 0;
	}

	/**
	 *	Sets a list of links with public access.
	 *	@access		public
	 *	@param		array		$links			Map of links, eg. auth_login
	 *	@param		string		$mode			Mode: set (default) or append
	 *	@return		void
	 */
	public function setPublicLinks( $links, $mode = 'set' ){
		if( is_array( $links ) && count( $links ) ){
			if( $mode === 'append' )
				foreach( $links as $link )
					$this->linksPublic[]	= $link;
			else
				$this->linksPublic	= $links;
		}
		return $this->linksPublic;
	}

	/**
	 *	Sets a list of links with public access.
	 *	@access		public
	 *	@param		array		$links			Map of links, eg. auth_login
	 *	@param		string		$mode			Mode: set (default) or append
	 *	@return		void
	 */
	public function setPublicInsideLinks( $links, $mode = 'set' ){
		if( is_array( $links ) && count( $links ) ){
			if( $mode === 'append' )
				foreach( $links as $link )
					$this->linksPublicInside[]	= $link;
			else
				$this->linksPublicInside	= $links;
		}
		return $this->linksPublicInside;
	}

	/**
	 *	Sets a list of links with public access.
	 *	@access		public
	 *	@param		array		$links			Map of links, eg. auth_login
	 *	@param		string		$mode			Mode: set (default) or append
	 *	@return		void
	 */
	public function setPublicOutsideLinks( $links, $mode = 'set' ){
		if( is_array( $links ) && count( $links ) ){
			if( $mode === 'append' )
				foreach( $links as $link )
					$this->linksPublicOutside[]	= $link;
			else
				$this->linksPublicOutside	= $links;
		}
		return $this->linksPublicOutside;
	}

	/**
	 *	Allowes access to a controller action for a role.
	 *	@access		public
	 *	@abstract
	 *	@param		integer		$roleId			Role ID
	 *	@param		string		$controller		Name of Controller
	 *	@param		string		$action			Name of Action
	 *	@return		integer
	 */
	abstract public function setRight( $roleId, $controller, $action );
}
?>
