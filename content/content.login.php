<?php

	require_once(LIB . '/class.administrationpage.php');

	Class contentExtensionUACLogin extends AdministrationPage{

		public $_context;
		private $invalid_credentials;

		function build($context=NULL){
			$this->invalid_credentials = false;

			$this->Headers->append('Content-Type', 'text/html; charset=UTF-8');

			$this->Html->setAttribute('lang', Symphony::lang());

			$meta = $this->createElement('meta');
			$this->insertNodeIntoHead($meta);
			$meta->setAttribute('http-equiv', 'Content-Type');
			$meta->setAttribute('content', 'text/html; charset=UTF-8');

			$this->insertNodeIntoHead($this->createStylesheetElement(ADMIN_URL . '/assets/css/peripheral.css'));

			parent::setTitle(__('%1$s &ndash; %2$s', array(__('Symphony'), __('Login'))));

			## Build the form
			$this->Form = $this->createElement('form');
			$this->Form->setAttribute('action', Administration::instance()->getCurrentPageURL());
			$this->Form->setAttribute('method', 'POST');
			$this->Form->setAttribute('class', 'panel');
			$this->Body->appendChild($this->Form);

			$this->Form->appendChild(
				$this->createElement('h1', __('Symphony'))
			);

			if($context) $this->_context = $context;
			if(isset($_REQUEST['action'])) $this->action();
			$this->view();
		}

		function view(){
			Widget::init($this);
			/*
			$emergency = false;
			if(isset($this->_context[0]) && in_array(strlen($this->_context[0]), array(6, 8))){
				$emergency = $this->__loginFromToken($this->_context[0]);
			}

			if(!$emergency && Administration::instance()->isLoggedIn()) redirect(ADMIN_URL . '/');
*/
			$fieldset = $this->createElement('fieldset');
			/*
			if($this->_context[0] == 'retrieve-password'):

				if(isset($this->_email_sent) && $this->_email_sent){
					$fieldset->appendChild($this->createElement('p', __('An email containing a customised login link has been sent. It will expire in 2 hours.')));
					$this->Form->appendChild($fieldset);
				}

				else{

					$fieldset->appendChild($this->createElement('p', __('Enter your email address to be sent a remote login link with further instructions for logging in.')));

					$label = Widget::Label(__('Email Address'));
					$label->appendChild(Widget::Input('email', $_POST['email']));

					$this->Body->setAttribute('onload', 'document.forms[0].elements.email.focus()');

					if(isset($this->_email_sent) && !$this->_email_sent){
						$div = $this->createElement('div', $label, array('class' => 'invalid'));
						$div->appendChild($this->createElement('p', __('There was a problem locating your account. Please check that you are using the correct email address.')));
						$fieldset->appendChild($div);
					}

					else $fieldset->appendChild($label);

					$this->Form->appendChild($fieldset);

					$div = $this->createElement('div', NULL, array('class' => 'actions'));
					$div->appendChild(Widget::Input('action[reset]', __('Send Email'), 'submit'));
					$this->Form->appendChild($div);

				}

			elseif($emergency):

				$fieldset->appendChild($this->createElement('legend', __('New Password')));

				$label = Widget::Label(__('New Password'));
				$label->appendChild(Widget::Input('password', NULL, 'password'));
				$fieldset->appendChild($label);

				$label = Widget::Label(__('Confirm New Password'));
				$label->appendChild(Widget::Input('password-confirmation', NULL, 'password'));
				$fieldset->appendChild($label);
				$this->Form->appendChild($fieldset);
				
				if($this->_mismatchedPassword){
					$div = $this->createElement('div', NULL, array('class' => 'invalid'));
					$div->appendChild($this->createElement('p', __('The supplied password was rejected. Make sure it is not empty and that password matches password confirmation.')));
					$this->Form->appendChild($div);
				}

				$div = $this->createElement('div', NULL, array('class' => 'actions'));
				$div->appendChild(Widget::Input('action[change]', __('Save Changes'), 'submit'));
				if(!preg_match('@\/symphony\/login\/@i', $_SERVER['REQUEST_URI'])) $div->appendChild(Widget::Input('redirect', $_SERVER['REQUEST_URI'], 'hidden'));
				$this->Form->appendChild($div);

			else:*/
				$label = Widget::Label(__('Username'));
				$label->appendChild(Widget::Input('username'));
				$fieldset->appendChild($label);

				$this->Body->setAttribute('onload', 'document.forms[0].elements.username.focus()');

				$label = Widget::Label(__('Password'));
				$label->appendChild(Widget::Input('password', NULL, 'password'));
				$fieldset->appendChild($label);
				$this->Form->appendChild($fieldset);
				
				$p = $this->createElement('p', NULL);
				$p->appendChild(
						Widget::Anchor(__('Forgot your password?'), ADMIN_URL . '/login/retrieve-password/')
					);
				$fieldset->appendChild($p);
				
				if($this->invalid_credentials){
					$div = $this->createElement('div', NULL, array('class' => 'invalid'));
					
					if($this->missing_username == true){
						$p = $this->createElement('p', __('Username is required and cannot be left blank. '));
					}
					
					elseif($this->missing_password == true){
						$p = $this->createElement('p', __('Password is required and cannot be left blank. '));
					}
					
					else{
						$p = $this->createElement('p', __('The username and password combination you provided is incorrect. '));
					}
					
					$p->appendChild(
						Widget::Anchor(__('Retrieve password?'), ADMIN_URL . '/login/retrieve-password/')
					);
					$div->appendChild($p);
					$this->Form->appendChild($div);
				}

				$div = $this->createElement('div', NULL, array('class' => 'actions'));
				$div->appendChild(Widget::Input('action[login]', __('Login'), 'submit'));
				if(!preg_match('@\/symphony\/login\/@i', $_SERVER['REQUEST_URI'])) $div->appendChild(Widget::Input('redirect', $_SERVER['REQUEST_URI'], 'hidden'));
				$this->Form->appendChild($div);

			//endif;

		}

		function __loginFromToken($token){
			##If token is invalid, return to login page
			if(!Administration::instance()->loginFromToken($token)) return false;

			##If token is valid and it is not "emergency" login (forgotten password case), redirect to administration pages
			if(strlen($token) != 6) redirect(ADMIN_URL . '/'); // Regular token-based login

			##Valid, emergency token - ask user to change password
			return true;
		}

		function action(){

			if(isset($_POST['action'])){

				$actionParts = array_keys($_POST['action']);
				$action = end($actionParts);

				##Login Attempted
				if($action == 'login'):

					if(!isset($_POST['username']) || strlen(trim($_POST['username'])) == 0){
						$this->invalid_credentials = true;
						$this->missing_username = true;
					}
					
					if(!isset($_POST['password']) || strlen(trim($_POST['password'])) == 0){
						$this->invalid_credentials = true;
						$this->missing_password = true;
					}

					elseif(!Administration::instance()->login($_POST['username'], $_POST['password'])) {

						## FIXME: Fix this delegate
						###
						# Delegate: LoginFailure
						# Description: Failed login attempt. Username is provided.
						//Extension::notify('LoginFailure', getCurrentPage(), array('username' => $_POST['username']));

						//$this->Body->appendChild(new XMLElement('p', 'Login invalid. <a href="'.ADMIN_URL . '/?forgot">Forgot your password?</a>'));
						//$this->_alert = 'Login invalid. <a href="'.ADMIN_URL . '/?forgot">Forgot your password?</a>';
						$this->invalid_credentials = true;
					}

					else{

						## FIXME: Fix this delegate
						###
						# Delegate: LoginSuccess
						# Description: Successful login attempt. Username is provided.
						//Extension::notify('LoginSuccess', getCurrentPage(), array('username' => $_POST['username']));

						if(isset($_POST['redirect'])) redirect(URL . str_replace(parse_url(URL, PHP_URL_PATH), NULL, $_POST['redirect']));

						redirect(ADMIN_URL . '/');
					}

				##Reset of password requested
				elseif($action == 'reset'):

					$user = Symphony::Database()->query("SELECT id, email, first_name FROM `tbl_users` WHERE `email` = '%s'", array($_POST['email']));

					if($user->valid()){
						$user = $user->current();

						Symphony::Database()->delete('tbl_forgotpass', array(DateTimeObj::getGMT('c')), " `expiry` < '%s'");

						$token = Symphony::Database()->query("
							SELECT
								token
							FROM
								`tbl_forgotpass`
							WHERE
								expiry > '%s'
							AND
								user_id = %d
							",
							DateTimeObj::getGMT('c'),
							$user->id
						);

						if($token->valid()){
							$token = substr(md5(time() . rand(0, 200)), 0, 6);
							Symphony::Database()->insert('tbl_forgotpass',
								array(
									'user_id' => $user->id,
									'token' => $token,
									'expiry' => DateTimeObj::getGMT('c', time() + (120 * 60))
								)
							);
						}

						$this->_email_sent = General::sendEmail($user['email'],
									'noreply@' . HTTP_HOST,
									__('Symphony Concierge'),
									__('New Symphony Account Password'),
									__('Hi %s,', array($user->first_name)) . PHP_EOL .
									__('A new password has been requested for your account. Login using the following link, and change your password via the Users area:') . PHP_EOL .
									PHP_EOL . '	' . ADMIN_URL . "/login/$token/" . PHP_EOL . PHP_EOL .
									__('It will expire in 2 hours. If you did not ask for a new password, please disregard this email.') . PHP_EOL . PHP_EOL .
									__('Best Regards,') . PHP_EOL .
									__('The Symphony Team'));


						## FIXME: Fix this delegate
						###
						# Delegate: PasswordResetSuccess
						# Description: A successful password reset has taken place. User ID is provided
						//Extension::notify('PasswordResetSuccess', getCurrentPage(), array('user_id' => $user['id']));

					}

					else{

						## FIXME: Fix this delegate
						###
						# Delegate: PasswordResetFailure
						# Description: A failed password reset has taken place. User ID is provided
						//Extension::notify('PasswordResetFailure', getCurrentPage(), array('user_id' => $user['id']));

						$this->_email_sent = false;
					}

				##Change of password requested
				elseif($action == 'change' && Administration::instance()->isLoggedIn()):

					if(empty($_POST['password']) || empty($_POST['password-confirmation']) || $_POST['password'] != $_POST['password-confirmation']){
						$this->_mismatchedPassword = true;
					}

					else{
						$user_id = Administration::instance()->User->id;

						$user = User::load($user_id);

						$user->set('password', md5(Symphony::Database()->escape($_POST['password'])));

						if(!User::save($user) || !Administration::instance()->login($user->username, $_POST['password'])){
							redirect(URL . "symphony/system/users/edit/{$user_id}/error/");
						}

						## FIXME: Fix this delegate
						###
						# Delegate: PasswordChanged
						# Description: After editing an User. ID of the User is provided.
						//Extension::notify('PasswordChanged', getCurrentPage(), array('user_id' => $user_id));

						redirect(ADMIN_URL . '/');
					}

				endif;
			}



			elseif($_REQUEST['action'] == 'resetpass' && isset($_REQUEST['token'])){

				$user = Symphony::Database()->query("
						SELECT
							u.id, u.email, u.first_name
						FROM
							`tbl_users` as u, `tbl_forgotpass` as t2
						WHERE
							t2.`token` = '%s'
						AND
							u.`id` = t2.`user_id`
						LIMIT 1
					",
					$_REQUEST['token']
				);

				if($user->valid()){
					$user = $user->current();

					$newpass = General::generatePassword();

					General::sendEmail($user->email,
								'noreply@' . HTTP_HOST,
								'Symphony Concierge',
								'RE: New Symphony Account Password',
								'Hi ' . $user['first_name']. ',' . PHP_EOL .
								"As requested, here is your new Symphony User Password for '". URL ."'".PHP_EOL ."	{$newpass}" . PHP_EOL . PHP_EOL .
								'Best Regards,' . PHP_EOL .
								'The Symphony Team');

					Symphony::Database()->update('tbl_users', array('password' => md5($newpass)), array($user->id), "`id` = '%d'");
					Symphony::Database()->delete('tbl_forgotpass', array($user->id), " `user_id` = '%d'");


					## FIXME: Fix this delegate
					###
					# Delegate: PasswordResetRequest
					# Description: User has requested a password reset. User ID is provided.
					//Extension::notify('PasswordResetRequest', getCurrentPage(), array('user_id' => $user['id']));

					$this->_alert = 'Password reset. Check your email';

				}
			}

		}

	}

