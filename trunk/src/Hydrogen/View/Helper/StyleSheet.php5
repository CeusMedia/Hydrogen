<?php
/**
 *	Helper to collect and combine StyleSheets.
 *
 *	Copyright (c) 2010 Christian Würker (ceusmedia.com)
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
 *	@package		Hydrogen.View.Helper
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2010-2012 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmframeworks/
 *	@since			0.1
 *	@version		$Id$
 */
/**
 *	Component to collect and combine StyleSheet.
 *	@category		cmFrameworks
 *	@package		Hydrogen.View.Helper
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2010-2012 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			http://code.google.com/p/cmframeworks/
 *	@since			0.1
 *	@version		$Id$
 */
class CMF_Hydrogen_View_Helper_StyleSheet{

	protected $pathCache			= "";
	protected $prefix				= "";
	protected $suffix				= "";
	protected $revision;
	/**	@var	array				$styles		List of StyleSheet blocks */
	protected $styles				= array();
	protected $urls					= array();
	protected $useCompression		= FALSE;
	public $indent					= "\t\t";

	/**
	 *	Collect a StyleSheet block.
	 *	@access		public
	 *	@param		string		$style		StyleSheet block
	 *	@param		bool		$onTop		Put this block on top of all others
	 *	@return		void
	 */
	public function addStyle( $style, $onTop = FALSE ){
		$onTop ? array_unshift( $this->styles, $style ) : array_push( $this->styles, $style );
	}

	/**
	 *	Add a StyleSheet URL.
	 *	@access		public
	 *	@param		string		$url		StyleSheet URL
	 *	@param		bool		$onTop		Flag: add this URL on top of all others
	 *	@return		void
	 */
	public function addUrl( $url, $onTop = FALSE ){
		$onTop ? array_unshift( $this->urls, $url ) : array_push( $this->urls, $url );
	}

	/**
	 *	Removes all combined styles in file cache.
	 *	@access		public
	 *	@return		void
	 */
	public function clearCache(){
		$index	= new File_RegexFilter( $this->pathCache, '/pack\.\w+\.css$/' );
		foreach( $index as $file )
			unlink( $file->getPathname() );
	}

	/**
	 *	Returns hash calculated by added URLs and revision, if set.
	 *	@access		public
	 *	@return		string
	 */
	public function getPackageHash(){
		$copy	= $this->urls;
		sort( $copy );
		$key	= implode( '_', $copy );
		return md5( $this->revision.$key );
	}

	/**
	 *	Returns name of combined file in file cache.
	 *	@access		protected
	 *	@return		string
	 */
	protected function getPackageCacheFileName(){
		$hash	= $this->getPackageHash();
		return $this->pathCache.$this->prefix.$hash.$this->suffix.'.css';
	}

	/**
	 *	Returns name of combined StyleSheet file.
	 *	@access		protected
	 *	@param		bool		$forceFresh		Flag: force fresh creation instead of using cache
	 *	@return		string
	 */
	protected function getPackageFileName( $forceFresh = FALSE ){
		$combiner	= new File_CSS_Combiner();
		$compressor	= new File_CSS_Compressor();
		$fileCss	= $this->getPackageCacheFileName();
		if( !file_exists( $fileCss ) || $forceFresh ) {
			$contents	= array();
			if( $this->revision )
				$content	= "/* @revision ".$this->revision." */\n";

			foreach( $this->urls as $url ){
				if( preg_match( "/^http/", $url ) )
					$content	= Net_Reader::readUrl( $url );
				else
					$content	= @file_get_contents( $url );
				if( $content === FALSE )
					throw new RuntimeException( 'Style file "'.$url.'" not existing' );
				if( !preg_match( '@://@', $url ) ){
					$path		= dirname( $url ).'/';
					$symlinks	= array( '//themes/petrol' => CMF_PATH.'themes/Hydrogen/petrol' );		//   @todo kriss: find a dynamic solution
					$docRoot	= getEnv( "DOCUMENT_ROOT" );
					if( preg_match( "/\/[a-z]+/", $content ) ){
						$content	= File_CSS_Relocator::rewrite( $content, $path, $docRoot, $symlinks );
					}
					$content	= $combiner->combineString( $path, $content, TRUE );
				}
				$contents[]	= $content;
			}
			$content	= implode( "\n\n", $contents );
			$content	= $compressor->compressString( $content );
			File_Writer::save( $fileCss, $content );
		}
		return $fileCss;
	}

	/**
	 *	Returns a list of collected StyleSheet URLs.
	 *	@access		public
	 *	@return		array
	 */
	public function getUrlList(){
		return $this->urls;
	}

	/**
	 *	Renders an HTML scrtipt tag with all collected StyleSheet URLs and blocks.
	 *	@access		public
	 *	@param		bool		$indentEndTag	Flag: indent end tag by 2 tabs
	 *	@param		bool		$forceFresh		Flag: force fresh creation instead of using cache
	 *	@return		string
	 */
	public function render( $indentEndTag = FALSE, $forceFresh = FALSE ){
		$links		= '';
		$styles		= '';
		if( $this->urls ){
			if( $this->useCompression ){
				$fileCss	= $this->getPackageFileName( $forceFresh );
				$attributes	= array(
					'type'		=> 'text/css',
					'rel'		=> 'stylesheet',
					'media'		=> 'all',
					'href'		=> $fileCss
				);
				$links	= UI_HTML_Tag::create( 'link', NULL, $attributes );
			}
			else{
				$list	= array();
				foreach( $this->urls as $url )
				{
					if( $this->revision )
						$url	.= '?r'.$this->revision;
					$attributes	= array(
						'rel'		=> 'stylesheet',
						'type'		=> 'text/css',
						'media'		=> 'all',
						'href'		=> $url
					);
					$list[]	= UI_HTML_Tag::create( 'link', NULL, $attributes );
				}
				$links	= implode( "\n".$this->indent, $list  );
			}
		}

		if( $this->styles ){
			array_unshift( $this->styles, '' );
			array_push( $this->styles, $indentEndTag ? "\t\t" : '' );
			$content	= implode( "\n", $this->styles );
			$attributes	= array(
				'type'		=> 'text/css',
			);
			$styles	= "\n".$this->indent.UI_HTML_Tag::create( 'style', $content."\n".$this->indent, $attributes );
		}
		return $links.$styles;
	}

	/**
	 *	Set path to file cache.
	 *	@access		public
	 *	@param		string		$path		Path to file cache
	 *	@return		void
	 */
	public function setCachePath( $path ){
		$this->pathCache = $path;
	}

	public function setCompression( $compression ){
		$this->useCompression	= (bool) $compression;
	}

	public function setPrefix( $prefix ){
		$this->prefix	= $prefix;
	}

	public function setSuffix( $suffix ){
		$this->suffix	= $suffix;
	}

	/**
	 *	Sets revision for versioning cache.
	 *	@access		public
	 *	@param		mixed		$revision	Revision number or version string
	 *	@return		void
	 */
	public function setRevision( $revision ){
		$this->revision	= $revision;
	}
}
?>