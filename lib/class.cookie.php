<?php

	require_once('class.session.php');

	Class Cookie{
		
		private $_index;
		private $_session;

		private $_timeout;
		private $_path;
		private $_domain;
		
		public function __construct($index, $timeout = 0, $path = '/', $domain=NULL, $session_table='tbl_sessions') {
			$this->_index = $index;
			$this->_timeout = $timeout;
			$this->_path = $path;
			$this->_domain = $domain;
			$this->_session = false;
			$this->_session_table = $session_table;
			
			$this->__init();
		}
		
		public function set($name, $value) {
			if (!$this->_session) {
				$this->__init();
			}

			$_SESSION[$this->_index][$name] = $value;
		}
		
		public function get($name) {
			if (!$this->_session) {
				$this->__init();
			}
			
			if (!isset($_SESSION[$this->_index][$name])) {
				return null;
			}
			
			return $_SESSION[$this->_index][$name];
		}
		
		public function expire() {
			if (!$this->_session) {
				$this->__init();
			}

			if(!is_array($_SESSION[$this->_index]) || empty($_SESSION[$this->_index])) return;

			unset($_SESSION[$this->_index]);
		}

		private function __init() {
			if ($this->_session) return $this->_session;

			$this->_session = Session::start($this->_timeout, $this->_path, $this->_domain, $this->_session_table);
			if (!$this->_session) return false;

			if (!isset($_SESSION[$this->_index])) $_SESSION[$this->_index] = array();

			// Class FrontendPage uses $_COOKIE directly (inside it's __buildPage() function), so try to emulate it.
			$_COOKIE[$this->_index] = &$_SESSION[$this->_index];

			return $this->_session;
		}
	
	}

