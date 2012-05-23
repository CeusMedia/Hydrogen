<?php
/**
 *	Setup for access control list using a Database.
 *
 *	Copyright (c) 2010 Christian Würker (ceus-media.de)
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
 *	@package		Hydrogen.Environment.Resource.Acl
 *	@author			Christian Würker <christian.wuerker@ceus-media.de>
 *	@copyright		2010-2012 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmframeworks/
 *	@since			0.3
 *	@version		$Id$
 */
/**
 *	Setup for access control list using a Database.
 *
 *	@category		cmFrameworks
 *	@package		Hydrogen.Environment.Resource.Acl
 *	@extends		CMF_Hydrogen_Environment_Resource_Acl_Abstract
 *	@author			Christian Würker <christian.wuerker@ceus-media.de>
 *	@copyright		2010-2012 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmframeworks/
 *	@since			0.3
 *	@version		$Id$
 */
class CMF_Hydrogen_Environment_Resource_Acl_Database extends CMF_Hydrogen_Environment_Resource_Acl_Abstract
{
	/**
	 *	Returns all rights of a role.
	 *	@access		protected
	 *	@param		integer		$roleId			Role ID
	 *	@return		array
	 */
	protected function getRights( $roleId )
	{
		if( $this->hasFullAccess( $roleId ) )
			return array();
		if( $this->hasNoAccess( $roleId ) )
			return array();
		if( !isset( $this->rights[$roleId] ) )
		{
			$model	= new Model_Role_Right( $this->env );
			$this->rights[$roleId]	= $model->getAllByIndex( 'roleId', $roleId );
		}
		return $this->rights[$roleId];
	}

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
	 *	Allowes access to a controller action for a role.
	 *	@access		public
	 *	@param		integer		$roleId			Role ID
	 *	@param		string		$controller		Name of Controller
	 *	@param		string		$action			Name of Action
	 *	@return		integer
	 */
	public function setRight( $roleId, $controller, $action )
	{
		if( $this->hasFullAccess( $roleId ) )
			return -1;
		if( $this->hasNoAccess( $roleId ) )
			return -2;
		$data	= array(
			'roleId'		=> $roleId,
			'controller'	=> $controller,
			'action'		=> $action,
			'timestamp'		=> time()
		);
		$model	= new Model_Role_Right( $this->env );
		return $model->add( $data );
	}
}
?>