<?php

namespace Models\Core;

use \Base;
use \Web;
use \Cache;
use \DB\SQL\Schema;
use ZipArchive as Zip;

class Releases 
{
	
	protected $f3;
	protected $cache;
	protected $schema;
	public $version;
	
	public function __construct(\DB\SQL $db)
	{
        $this->f3 = Base::instance();
        $this->cache = Cache::instance();
        $this->schema = new Schema($db);
        $this->version = $this->version();
    }
    
    public function feed()
    {
    	if ($this->cache->exists('releasefeed', $feed))
			return $feed;
		else {
	    	$result = Web::instance()->request('https://api.github.com/repos/filler00/mytcg-f3/releases');
	    	$feed = json_decode($result['body'], true);
	    	$this->cache->set('releasefeed', $feed, 3600);
	    	return $feed;
		}
    }
    
    private function currentVersion()
	{
		$installed = $this->f3->read('.tag_name');
		
		$result = Web::instance()->request('https://api.github.com/repos/filler00/mytcg-f3/releases/tags/' . $installed);
		$current = json_decode($result['body'], true);
		if ( !isset($current['tag_name']) )
			return ['tag_name' => $installed];
		else 
			return $current;
	}
	
	private function latestVersion()
	{
		$result = Web::instance()->request('https://api.github.com/repos/filler00/mytcg-f3/releases/latest');
		$latest = json_decode($result['body'], true);
		if ( !isset($latest['tag_name']) )
			return false;
		else 
			return $latest;
	}
	
	private function version()
	{
		if ( $this->cache->exists('version', $version) )
			return $version;
		else {
			$version = [
				'latest' => $this->latestVersion(),
				'current' => $this->currentVersion(),
				'update' => false
			];
		
			if ( version_compare($version['current']['tag_name'], $version['latest']['tag_name'], '<') )
				$version['update'] = true;
			
			$this->cache->set('version', $version, 3600);
			return $version;
		}
	}
	
	public function install($tag)
	{
		$errors = 0;
				
		// download ZIP package to tmp storage
		$this->f3->write('tmp/downloads/' . $tag . '.zip', Web::instance()->request('https://github.com/filler00/mytcg-f3/archive/'.$tag.'.zip')['body']);
		
		// extract package and install plugin
		$zip = new Zip;
		if ( $zip->open('tmp/downloads/' . $tag . '.zip') === true) {
			$zip->extractTo('tmp/downloads/');
			$zip->close();
			
			// remove temporary zip file
			unlink('tmp/downloads/' . $tag . '.zip');
			
			/*
			 *	Update core files
			 */
			 
			// remove old core controller files
			$dirs = new \RecursiveDirectoryIterator('app/controllers/core', \RecursiveDirectoryIterator::SKIP_DOTS);
			$files = new \RecursiveIteratorIterator($dirs, \RecursiveIteratorIterator::CHILD_FIRST);
			foreach($files as $file) {
				if ($file->isDir())
					rmdir($file->getRealPath());
				else
					unlink($file->getRealPath());
			}
			
			// remove old core model files
			$dirs = new \RecursiveDirectoryIterator('app/models/core', \RecursiveDirectoryIterator::SKIP_DOTS);
			$files = new \RecursiveIteratorIterator($dirs, \RecursiveIteratorIterator::CHILD_FIRST);
			foreach($files as $file) {
				if ($file->isDir())
					rmdir($file->getRealPath());
				else
					unlink($file->getRealPath());
			}
			
			// remove old vendor files
			$dirs = new \RecursiveDirectoryIterator('vendor', \RecursiveDirectoryIterator::SKIP_DOTS);
			$files = new \RecursiveIteratorIterator($dirs, \RecursiveIteratorIterator::CHILD_FIRST);
			foreach($files as $file) {
				if ($file->isDir())
					rmdir($file->getRealPath());
				else
					unlink($file->getRealPath());
			}
			
			// install new core & vendor files
			if ( 	rename('tmp/downloads/mytcg-f3-' . preg_replace('/^[v]/', '', $tag) . '/app/controllers/core', 'app/controllers/core')
					&& rename('tmp/downloads/mytcg-f3-' . preg_replace('/^[v]/', '', $tag) . '/app/models/core', 'app/models/core')
					&& rename('tmp/downloads/mytcg-f3-' . preg_replace('/^[v]/', '', $tag) . '/vendor', 'vendor') ) {
				$this->f3->write('.tag_name', $tag); 
				return true;
			} else
				return false;
			
		} else { return false; }
		
	}
	
}