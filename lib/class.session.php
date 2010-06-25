<?php

	/**
	 * Based on: http://pl.php.net/manual/en/function.session-set-save-handler.php#81761 by klose at openriverbed dot de
	 *   which was based on: http://pl.php.net/manual/en/function.session-set-save-handler.php#79706 by maria at junkies dot jp
	 */

	require_once(LIB . '/class.cache.php');

	Class Session{

		private static $_initialized;
		private static $_registered;
		private static $_session_table;

		public static function start($lifetime = 0, $path = '/', $domain=NULL, $session_table='tbl_sessions'){

			if (!self::$_initialized){

				if(!is_object(Symphony::Database()) || !Symphony::Database()->connected()) return false;
				
				self::$_session_table = $session_table;
				
				$cache = Cache::instance()->read('_session_config');
				
				if(is_null($cache) || $cache === false){
					self::create();
					Cache::instance()->write('_session_config', true);
				}
				
				if (!session_id()) {
					ini_set('session.save_handler', 'user');
					ini_set('session.gc_maxlifetime', $lifetime);
					ini_set('session.gc_probability', '1');
					ini_set('session.gc_divisor', '3');
				}

				session_set_save_handler(
					array('Session', 'open'),
					array('Session', 'close'),
					array('Session', 'read'),
					array('Session', 'write'),
					array('Session', 'destroy'),
					array('Session', 'gc')
				);

				session_set_cookie_params($lifetime, $path, ($domain ? $domain : self::getDomain()), false, false);

				if(strlen(session_id()) == 0){
					if(headers_sent()){
						throw new Exception('Headers already sent. Cannot start session.');
					}
					session_start();
				}

				self::$_initialized = true;
			}

			return session_id();
		}

		public static function create(){
			Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `".self::$_session_table."` (
				  `session` varchar(255) NOT NULL,
				  `session_expires` int(10) unsigned NOT NULL default '0',
				  `session_data` text,
				  PRIMARY KEY  (`session`)
				);"
			);
		}

		public static function getDomain(){

			if(isset($_SERVER['HTTP_HOST'])){

				if(preg_match('/(localhost|127\.0\.0\.1)/', $_SERVER['HTTP_HOST']) || $_SERVER['SERVER_ADDR'] == '127.0.0.1'){
					return NULL; // prevent problems on local setups
				}

				$parsed = parse_url(
					preg_replace('/^www./i', NULL, $_SERVER['HTTP_HOST'])
				);

				if (!isset($parsed['host'])) return NULL;

				$domain = $parsed['host'];

				if(isset($parsed['port'])){
					$domain .= ':' . $parsed['port'];
				}

				return $domain;
			}

			return NULL;

		}

		public static function open(){
			if (!self::$_registered) {
				register_shutdown_function('session_write_close');
				self::$_registered = true;
			}

			return self::$_registered;
		}

		public static function close() {
			return true;
		}

		public static function read($id){
			$result = Symphony::Database()->query("
					SELECT
						`session_data`
					FROM
						`".self::$_session_table."`
					WHERE
						`session` = '%s'
					LIMIT
						1
				",
				array($id)
			);

			if ($result->valid()) {
				return $result->current()->session_data;
			}

			return null;
		}

		public static function write($id, $data){
			$fields = array(
				'session' => $id,
				'session_expires' => time(),
				'session_data' => $data
			);

			return Symphony::Database()->insert(self::$_session_table, $fields, Database::UPDATE_ON_DUPLICATE);
		}

		public static function destroy($id){
			return Symphony::Database()->delete(self::$_session_table, array($id), "`session` = '%s'");
		}

		public static function gc($max){
			return Symphony::Database()->delete(self::$_session_table, array(time() - $max), "`session_expires` <= '%s'");
		}
	}

