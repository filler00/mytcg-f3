<?php

namespace Models\Core;

use ZipArchive as Zip;

use \Base;
use \Web;

class Plugins extends \DB\Jig\Mapper {
	
	protected $f3;
	
	public function __construct(\DB\Jig $jig) {
        parent::__construct($jig, 'plugins.json' );
        $this->f3 = Base::instance();
    }
	
	/****
	
	Basic CRUD operations
	
	****/
	
	public function add($plugin = '')
	{
		if ( $plugin !== '' ) {
			$this->copyfrom($plugin,function($val) {
				return array_intersect_key($val, array_flip(array('package','enabled','config')));
			});
		}
		else {
			$this->copyfrom('POST',function($val) {
				return array_intersect_key($val, array_flip(array('package','enabled','config')));
			});
		}
		return $this->insert();
	}
	public function read($filter,$options)
	{
		return $this->find($filter,$options);
	}
	public function edit($id)
	{
		$this->load(array('@_id = ?',$id));
		$this->copyfrom('POST',function($val) {
			return array_intersect_key($val, array_flip(array('package','enabled','config')));
		});
		return $this->update();
	}
	public function delete($id)
	{
		$this->load(array('@_id = ?',$id));
		return $this->erase();
	}
	
	/****
	
	Helper methods
	
	****/
	
	/* For plugins listed in the registry */
	
	public function registry()
	{
		$result = Web::instance()->request('https://raw.githubusercontent.com/filler00/mytcg-f3-plugins/master/registry.json');
		$json = json_decode($result['body'], true)['plugins'];
		ksort($json);
		return $json;
	}
	
	public function getRemoteConfig($package)
	{
		$result = Web::instance()->request('https://raw.githubusercontent.com/' . $package . '/master/mytcg.json');
		return json_decode($result['body'], true);
	}
	
	public function install($package, $plugins)
	{
		$errors = 0;
		$config = $this->getRemoteConfig($package);
				
		// download ZIP package to tmp storage
		$this->f3->write($this->getDir($package)['dl'], Web::instance()->request('https://github.com/' . $package . '/archive/master.zip')['body']);
		
		// extract package and install plugin
		$zip = new Zip;
		if ( $zip->open($this->getDir($package)['dl']) === true) {
			$zip->extractTo($this->getDir($package)['author']);
			$zip->close();
			
			// remove temporary zip file
			unlink($this->getDir($package)['dl']);
			
			// rename file path
			if ( rename($this->getDir($package)['init'], $this->getDir($package)['full']) ) {
				
				$plugin = [
					'package' => $package,
					'enabled' => false,
					'config' => $config
				];

				// insert plugin config into local registry
				if ( $_id = $this->add($plugin) ) {
					
					// initialize plugin files, if specified in mytcg.json
					if ( isset($config['initFiles']) ) {
						foreach ( $config['initFiles'] as $file ) {
							rename($this->getDir($package)['full'] . '/' . $file['source'], $file['destination']);
						}
					}
					
					// provision database, if specified in mytcg.json
					if ( isset($config['database']) ) {
						$trans = [];
						
						foreach ( $config['database'] as $table ) {
							$q = [];
							$q[] = "CREATE TABLE {$table['name']} (";
							foreach ( $table['fields'] as $field ) {
								$q[] = "{$field['name']} {$field['options']}";
							}
							$q[] = "PRIMARY KEY({$table['primary-key']})";
							if ( isset($table['indexes']) ) {
								foreach ( $table['indexes'] as $index ) {
									$q[] = "INDEX ($index)";
								}
							}
							if ( isset($table['foreign-keys']) ) {
								foreach ( $table['foreign-keys'] as $key ) {
									$q[] = "FOREIGN KEY ({$key['name']}) REFERENCES {$key['reference']} ON UPDATE CASCADE ON DELETE RESTRICT";
								}
							}
							$q[] = ") ENGINE=INNODB;";
							
							$trans[] = implode(', ', $q);
						}
						
						$db->exec($trans); // runs queries + reverts changes if something goes wrong
					}
					
					return true;
					
				} else { $errors++; }
				
			} else { $errors++; }
			
		} else { $errors++; }
		
		// if there were problems during the install process, revert everything
		if ( $errors ) {
			//remove from local registry
			$this->delete($_id);
			
			// remove plugin files
			$dirs = new RecursiveDirectoryIterator($this->getDir($package)['full'], RecursiveDirectoryIterator::SKIP_DOTS);
			$files = new RecursiveIteratorIterator($dirs, RecursiveIteratorIterator::CHILD_FIRST);
			foreach($files as $file) {
				if ($file->isDir()){
					rmdir($file->getRealPath());
				} else {
					unlink($file->getRealPath());
				}
			}
			rmdir($dir);
			
			return false;
		}
		
	}
	
	/* For installed plugins */
	
	public function all()
	{
		$this->load([]);
		return $this->query;
	}
	
	public function getAllEnabled() {
		return $this->find(['@enabled = ?', true]);
	}
	
	public function listPackages()
	{
		$this->load([]);
		foreach ( $this->query as $plugin ) {
			$packages[] = $plugin->package;		
		}
		return $packages;
	}
	
	public function getDir($package)
	{
		// extract author and plugin names
		if ( preg_match('/^([0-9a-z\-_]+)\/([0-9a-z\-_]+)$/i', $package, $matches) ) { 
			$author = $matches[1];
			$plugin = $matches[2];
			
			$dir = [
				'full' => 'app/plugins/' . preg_replace("/(-|_)/", "", strtolower($package)), // full path to plugin
				'author' => 'app/plugins/' . preg_replace("/(-|_)/", "", strtolower($author)), // path to author's directory
				'init' => 'app/plugins/' . preg_replace("/(-|_)/", "", strtolower($author)) . '/' . $plugin . '-master', // initial plugin install path
				'dl' => 'tmp/downloads/' . $plugin . '.zip' // temporary download path for zip file
			];
			return $dir;
		}
		
		else return false;
	}
	
	public function getId($id) {
		return $this->findone(['@id = ?', $id]);
	}
	
	public function getPackage($package) {
		return $this->findone(['@package = ?', $package]);
	}
	
	public function isEnabled($package) {
		return $this->findone(['@package = ? AND @enabled = ?', $package, true]);
	}
	
	public function updateStatus($id) {
		$this->load(array('@_id = ?',$id));
		$this->copyfrom('POST', function($val) {
			return array_intersect_key($val, array_flip(array('enabled')));
		});
		return $this->update();
	}
	
}