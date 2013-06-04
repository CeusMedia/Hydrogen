<?php
/**
 *	Router interface.
 *
 *	Copyright (c) 2011 Christian Würker (ceusmedia.com)
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
 *	@package		Hydrogen.Environment.Router
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2011 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmframeworks/
 *	@since			0.4
 *	@version		$Id$
 */
/**
 *	Router interface.
 *	@category		cmFrameworks
 *	@package		Hydrogen.Environment.Router
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2011 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmframeworks/
 *	@since			0.4
 *	@version		$Id$
 */
interface CMF_Hydrogen_Environment_Router_Interface
{
	public function __construct( CMF_Hydrogen_Environment_Abstract $env );

	public function getAbsoluteUri( $controller = NULL, $action = NULL, $arguments = NULL, $parameters = NULL, $fragmentId = NULL );

	public function getRelativeUri( $controller = NULL, $action = NULL, $arguments = NULL, $parameters = NULL, $fragmentId = NULL );

	public function parseFromRequest();

	public function realizeInResponse();
}
?>